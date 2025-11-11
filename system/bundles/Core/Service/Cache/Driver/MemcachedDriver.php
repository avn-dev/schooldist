<?php

namespace Core\Service\Cache\Driver;

use Core\Service\Cache;

class MemcachedDriver extends AbstractDriver {
	/**
	 * @var \Memcached 
	 */
	private $oMemcached;
	
	/**
	 * Memcached
	 * 
	 * @param string $sHost
	 * @param string $iPort
	 */
	public function __construct(private string $sHost, private int $iPort) {
		$this->oMemcached = new \Memcached();
		$this->oMemcached->addServer($sHost, $iPort);
	}

	/**
	 * Löscht einen Eintrag aus dem Cache
	 * 
	 * @param string $sKey
	 * @return bool
	 */
	public function forget($sKey) {
		return $this->oMemcached->delete($sKey, 0);
	}

	/**
	 * Liefert einen Eintrag aus dem Cache
	 * 
	 * @param string $sKey
	 * @return mixed|null
	 */
	public function get($sKey) {

		$mCheck = $this->oMemcached->get($sKey);

		if($mCheck !== false) {
			return $mCheck;
		}
		
		return null;
	}

	/**
	 * Fügt einen Eintrag dem Cache hinzu
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @return int
	 */
	public function add($sKey, $iExpiration, $mData) {

		// Wert direkt hinzufügen und im Fehlerfall ersetzen
		// Vorher wurde hier zuerst get() aufgerufen, aber das ermöglichte Race Conditions!
		$bAdded = $this->oMemcached->add($sKey, $mData, $iExpiration);

		$iStatus = Cache::STATUS_ERROR;
		
		if(!$bAdded) {

			$bReplaced = $this->oMemcached->replace($sKey, $mData, $iExpiration);

			if($bReplaced) {
				$iStatus = Cache::STATUS_REPLACED;
			}

		} else {
			$iStatus = Cache::STATUS_ADDED;
		}
		
		return $iStatus;
	}

	/**
	 * @return mixed
	 */
	public function getStats() {
		return $this->oMemcached->getStats();
	}

	/**
	 * Liefert alle existierenden Keys aus dem Cache
	 * 
	 * @return array
	 */
	public function getExistingKeys($sPrefix) {

		$aMemcachedKeys = $this->oMemcached->getAllKeys();
		if(!is_array($aMemcachedKeys)) {
			$aMemcachedKeys = array();
		}

		$aCliKeys = $this->getAllKeysFromCli();

		$aScriptKeys = $this->getAllKeysFromScript();

		$aExistingKeys = array_merge($aMemcachedKeys, $aCliKeys, $aScriptKeys);
		$aExistingKeys = array_unique($aExistingKeys);

		// Nur die Keys der aktuellen Installation nehmen
		$aExistingKeys = array_filter($aExistingKeys, fn ($sKey) => str_starts_with($sKey, $sPrefix));

		return $aExistingKeys;
	}

	/**
	 * Memcached->getAllKeys() geht in Version 1.4.25 nicht mehr, daher der Fallback hier
	 * 
	 * @return array
	 */
	private function getAllKeysFromCli() {

		$sCmd = "echo -e \"stats items\nquit\"  | nc localhost 11211 | grep -oe ':[0-9]*:'  |  grep -oe '[0-9]*' | sort | uniq | xargs -L1 -I{} bash -c 'echo -e \"stats cachedump {} 1000\nquit\" | nc localhost 11211'";

		$sOutput = \Update::executeShellCommand($sCmd);

		$aItems = explode("\n", $sOutput);

		$aKeys = [];
		foreach($aItems as $sItem) {
			
			$aMatch = [];
			preg_match('/(.{32}_.{32}_(0|1))/', $sItem, $aMatch);

			if(!empty($aMatch)) {
				$aKeys[] = $aMatch[1];
			}

		}

		return $aKeys;
	}

	private function getAllKeysFromScript() {

		$aKeysFromScript = [];
		$errno = $errstr = null;
		$sock = fsockopen($this->sHost, $this->iPort, $errno, $errstr);
		if ($sock === false) {
			throw new \Exception("Error connection to server {$this->sHost} on port {$this->iPort}: ({$errno}) {$errstr}");
		}

		if (fwrite($sock, "stats items\n") === false) {
			throw new \Exception("Error writing to socket");
		}

		$slabCounts = [];
		while (($line = fgets($sock)) !== false) {
			$line = trim($line);
			if (
				$line === 'END' ||
				$line === 'ERROR'
			) {
				break;
			}

			// STAT items:8:number 3
			if (preg_match('!^STAT items:(\d+):number (\d+)$!', $line, $matches)) {
				$slabCounts[$matches[1]] = (int)$matches[2];
			}
		}

		foreach ($slabCounts as $slabNr => $slabCount) {
			if (fwrite($sock, "lru_crawler metadump {$slabNr}\n") === false) {
				throw new \Exception('Error writing to socket');
			}

			$count = 0;
			while (($line = fgets($sock)) !== false) {
				$line = trim($line);
				if (
					$line === 'END' ||
					$line === 'ERROR'
				) {
					break;
				}

				// key=foobar exp=1596440293 la=1596439293 cas=8492 fetch=no cls=24 size=14908
				if (preg_match('!^key=(\S+)!', $line, $matches)) {
					$aKeysFromScript[] = $matches[1];
					$count++;
				}
			}

		}

		if (fclose($sock) === false) {
			throw new \Exception('Error closing socket');
		}

		return $aKeysFromScript;
	}


	public function increment(string $key, int $value, int $initialValue = 0, int $expiry = 0) {
		// Damit initalValue und expiry bei increment() funktioniert, müsste man:
		// $this->oMemcached->setOption($this->oMemcached::OPT_BINARY_PROTOCOL, true);
		$count = $this->oMemcached->increment($key, $value);
		if ($count === false) {
			$this->oMemcached->add($key, $value, $expiry);
			return $value;
		}

		return $count;
	}
}

