<?php

/**
 * @todo auf \Core\Facade\Cache umstellen
 */
class WDCache {

	const ERROR = 0;
	const ADDED = 1;
	const REPLACED = 2;

	/**
	 * The instance
	 * 
	 * @var WDCache
	 */
	private static $_oInstance;

	/**
	 * Memcache object
	 * 
	 * @var Memcached
	 */
	private $oMemcached = null;

	/**
	 * Static cache array (for DB-Caching)
	 * 
	 * @var array
	 */
	private static $_aStaticCache = array();

	private $sLicense;

	/**
	 * Protect the __clone()-Method
	 */
	private function __clone() {}

	/**
	 * The constructor
	 */
	private function __construct($sLicence) {

		$this->sLicense = $sLicence;

		$sHost = System::d('memcache_host');
		$sPort = System::d('memcache_port');

		if(
			!empty($sHost) &&
			!empty($sPort)
		) {
			if(extension_loaded('memcached')) {
				$this->oMemcached = new Memcached();
				$this->oMemcached->addServer($sHost, $sPort);
			}
		}

	}

	/**
	 * Get instance
	 *
	 * @return WDCache
	 */
	private static function _getInstance() {

		if(is_null(self::$_oInstance)) {
			self::$_oInstance = new self(System::d('license'));
		}

		return self::$_oInstance;
	}

	/* ==================================================================================================== */

	/**
	 * Delete a cached data
	 *
	 * @param string $sKey
	 * @param bool $bPersistent
	 */
	public static function delete($sKey, $bPersistent = false) {
		$oCache = self::_getInstance();
		$oCache->_delete($sKey, $bPersistent);
	}

	/**
	 * Flush the cache
	 */
	public static function flush() {
		$oCache = self::_getInstance();
		$oCache->_flush();
	}

	/**
	 * Get the data by given key
	 * 
	 * @param string $sKey
	 * @param bool $bPersistent
	 * @return mixed
	 */
	public static function get($sKey, $bPersistent = false) {
		$oCache = self::_getInstance();
		$mData = $oCache->_get($sKey, $bPersistent);
		return $mData;
	}

	/**
	 * Get the stats of memcache
	 * 
	 * @return mixed
	 */
	public static function getStats() {
		$oCache = self::_getInstance();

		if($oCache->oMemcached) {
			$aStats = $oCache->oMemcached->getStats();
		} else {
			$sSql = '
				SELECT
					COUNT(*)
				FROM
					`system_cache`
					';
			$iCount = DB::getQueryOne($sSql);
			$aStats = array('curr_items' => $iCount);
		}

		return $aStats;
	}

	/**
	 * Set the data into the cache
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @param bool $bPersistent
	 * @return int
	 */
	public static function set($sKey, $iExpiration, $mData, $bPersistent = false, $sGroup = null) {
		$oCache = self::_getInstance();
		return $oCache->_set($sKey, $iExpiration, $mData, $bPersistent, $sGroup);
	}

	/* ==================================================================================================== */

	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param \Closure $oClosure
	 * @param bool $bPersistent
	 * @param string $sGroup
	 * @return mixed
	 */
	public static function remember($sKey, $iExpiration, \Closure $oClosure, $bPersistent = false, $sGroup = null) {
		$oCache = self::_getInstance();
		return $oCache->_remember($sKey, $iExpiration, $oClosure, $bPersistent, $sGroup);
	}

	/**
	 * Get an item from the cache, or execute the given Closure and store the result.
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param \Closure $oCallback
	 * @param bool $bPersistent
	 * @param string $sGroup
	 * @return mixed
	 */
	private function _remember($sKey, $iExpiration, \Closure $oCallback, $bPersistent = false, $sGroup = null) {

		$mExistingData = $this->_get($sKey, $bPersistent);

		if(!is_null($mExistingData)) {			
            return $mExistingData;
        }

		$this->_set($sKey, $iExpiration, $mCacheData = $oCallback(), $bPersistent, $sGroup);
		
		return $mCacheData;
	}
	
	/**
	 * Build the caching key
	 *
	 * @param string $sKey
	 * @param bool $bPersistent
	 * @return string
	 */
	private function _buildKey($sKey, $bPersistent) {
		$sNewKey = $this->_buildLicenseKeyPart().'_'.md5($sKey);
		if($bPersistent) {
			$sNewKey .= '_1'; // persistent
		} else {
			$sNewKey .= '_0'; // nicht persistent
		}
		return $sNewKey;
	}

	/**
	 * @return string
	 */
	private function _buildLicenseKeyPart() {
		// x + Licence da die Lizenz nicht rein aus Zahlen bestehen darf
		$sKeyPart = md5('x'.$this->sLicense);
		return $sKeyPart;
	}

	/**
	 * Delete a cached data
	 * 
	 * @param string $sKey
	 * @param bool $bPersistent
	 * @param bool $bAlreadyEncoded
	 */
	private function _delete($sKey, $bPersistent, $bAlreadyEncoded = false) {

		if(!$bAlreadyEncoded) {
			$sKey = $this->_buildKey($sKey, $bPersistent);
		}

		if($this->oMemcached) {
			$this->oMemcached->delete($sKey, 0);
		} else {
			$sSQL = "
				DELETE FROM
					`system_cache`
				WHERE
					`key` = :sKey
			";
			$aSQL = array(
				'sKey' => $sKey
			);
			DB::executePreparedQuery($sSQL, $aSQL);

			unset(self::$_aStaticCache[$sKey]);
		}
	}

	/**
	 * Flush the cache
	 * ACHTUNG: Gruppeneinträge mit Persistenz bleiben bei flush erhalten, verlieren aber Ihre Gruppenzugehörigkeit!
	 */
	private function _flush($bAll = false) {

		$aExistingKeys = $this->getExistingKeys($bAll);

		foreach($aExistingKeys as $sKey) {
			$this->_delete($sKey, null, true);
		}

		$oLogger = Log::getLogger();
		$aLogInfo = array(
			'backtrace' => Util::getBacktrace(),
			'deleted_keys' => count($aExistingKeys),
		);
		$oLogger->addInfo('WDCache::flush', $aLogInfo);

	}

	/**
	 * Get the data by given key
	 * 
	 * @param string $sKey
	 * @param bool $bPersistent
	 * @return mixed
	 */
	private function _get($sKey, $bPersistent, $bAlreadyEncoded=false) {

		if($bAlreadyEncoded === false) {
			$sEncodedKey = $this->_buildKey($sKey, $bPersistent);
		} else {
			$sEncodedKey = $sKey;
		}

		if($this->oMemcached) {

			$mCheck = $this->oMemcached->get($sEncodedKey);
			if($mCheck !== false) {
				return $mCheck;
			}

		} else {

			if(isset(self::$_aStaticCache[$sEncodedKey])) {
				return self::$_aStaticCache[$sEncodedKey];
			}

			$sSQL = "
				SELECT
					`data`
				FROM
					`system_cache`
				WHERE
					`expiration` >= NOW() AND
					`key` = :key
				LIMIT
					1
			";
			$aSQL = array(
				'key' => $sEncodedKey,
			);
			$mCheck = DB::getQueryOne($sSQL, $aSQL);

			if($mCheck !== null) {
				self::$_aStaticCache[$sEncodedKey] = unserialize($mCheck);
				return self::$_aStaticCache[$sEncodedKey];
			}

		}

		return null;
	}

	/**
	 * Gruppenkey erzeugen mit Prefix, damit er möglichst nicht mit normalen Keys verwechselt werden kann
	 *
	 * @param string $sGroup
	 * @return string
	 */
	private function getGroupKey($sGroup) {
		$sGroupKey = 'WDCache:Group_'.$sGroup;
		return $sGroupKey;
	}

	/**
	 * Neuen Key in Gruppe merken
	 *
	 * @param string $sGroup
	 * @param string $sKey
	 */
	private function addGroupKey($sGroup, $sKey) {

		$sGroupKey = $this->getGroupKey($sGroup);

		$aGroupKeys = $this->_get($sGroupKey, false);

		if(
			$aGroupKeys === null ||
			!is_array($aGroupKeys)
		) {
			$aGroupKeys = array();
		}

		$aGroupKeys[$sKey] = 1;

		$this->_set($sGroupKey, 2419200, $aGroupKeys);

	}

	/**
	 * Alle Einträge der Gruppe werden gelöscht
	 * @param string $sGroup
	 */
	private function _deleteGroup($sGroup, $bAlreadyEncoded = false) {
		
		if($bAlreadyEncoded === false) {
			$sGroupKey = $this->getGroupKey($sGroup);
		} else {
			$sGroupKey = $sGroup;
		}

		$aGroupKeys = $this->_get($sGroupKey, false, $bAlreadyEncoded);
		
		if(is_array($aGroupKeys)) {
			foreach(array_keys($aGroupKeys) as $sGroupItemKey) {
				$this->_delete($sGroupItemKey, false);
			}
		}
		
		$this->_delete($sGroupKey, false, $bAlreadyEncoded);		
	}
	
	/**
	 * @see self::_deleteGroup()
	 * @param string $sGroup
	 */
	public static function deleteGroup($sGroup) {
		$oCache = self::_getInstance();

		$oCache->_deleteGroup($sGroup);
	}

	/**
	 * Set the data into the cache
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @param bool $bPersistent
	 * @return int
	 */
	private function _set($sKey, $iExpiration, $mData, $bPersistent = false, $sGroup = null) {

		if(
			$bPersistent !== false &&
			$sGroup !== null
		) {
			throw new BadMethodCallException('Group keys must not be persistent.');
		}

		$iReturnValue = self::ERROR;

		if($sGroup !== null) {
			// Key merken in Gruppe
			$this->addGroupKey($sGroup, $sKey);
		}

		$sEncodedKey = $this->_buildKey($sKey, $bPersistent);

		if($this->oMemcached) {

			// Wert direkt hinzufügen und im Fehlerfall ersetzen
			// Vorher wurde hier zuerst get() aufgerufen, aber das ermöglichte Race Conditions!
			$bAdded = $this->oMemcached->add($sEncodedKey, $mData, $iExpiration);

			if(!$bAdded) {

				$bReplaced = $this->oMemcached->replace($sEncodedKey, $mData, $iExpiration);

				if($bReplaced) {
					$iReturnValue = self::REPLACED;
				}

			} else {
				$iReturnValue = self::ADDED;
			}

		} else {

			$oDb = DB::getDefaultConnection();
			$mSerializedData = serialize($mData);

			// Memcache-Konventionen
			if($iExpiration === 0) {
				$iExpiration = strtotime('01-01-2038');
			}
//			elseif($iExpiration < 2592000) {
//				$iExpiration = time() + $iExpiration;
//			}
		
			$aSql = array(
				'key' => $sEncodedKey,
				//'expiration' => date('Y-m-d H:i:s', $iExpiration),
				'expiration' => (int)$iExpiration,
				'data' => $mSerializedData
			);

			// Zuerst ein INSERT IGNORE ausführen, damit add() von Memache abgebildet werden kann
			$sSql = "
				INSERT IGNORE INTO
					`system_cache`
				SET
					`key` = :key,
					`expiration` = IF(
						:expiration < 2592000,
						NOW() + INTERVAL :expiration SECOND,
						FROM_UNIXTIME(:expiration)
					),
					`data` = :data
			";
			DB::executePreparedQuery($sSql, $aSql);

			// Wenn INSERT IGNORE nicht erfolgreich, replace() von Memcache mit UPDATE-Query abbilden
			if($oDb->getAffectedRows() == 0) {

				$sSql = "
					UPDATE
						`system_cache`
					SET
						`expiration` = IF(
							:expiration < 2592000,
							NOW() + INTERVAL :expiration SECOND,
							FROM_UNIXTIME(:expiration)
						),
						`data` = :data
					WHERE
						`key` = :key
				";
				DB::executePreparedQuery($sSql, $aSql);

				// Hier kann nicht mit Affected Rows geprüft werden,
				//	denn wenn der Wert mit exakt gleichen Werten vorhanden ist,
				//	wird Affected Rows immer 0 zurückliefern
				$iReturnValue = self::REPLACED;

			} else {
				// Solange der Eintrag nicht manuell durch den DELETE QUERY gelöscht wurde,
				//	wird dieser Wert nicht zurückgeliefert werden, sondern REPLACED
				$iReturnValue = self::ADDED;
			}

			// Manuelles Bereinigen von abgelaufenen Einträgen
			$sSql = "
				DELETE FROM
					`system_cache`
				WHERE
					`expiration` < NOW()
			";
			DB::executePreparedQuery($sSql, $aSql);

			self::$_aStaticCache[$sEncodedKey] = $mData;
		}

		return $iReturnValue;
	}

	/**
	 * check if connection is established
	 *
	 * @return bool 
	 */
	public static function isMemcacheConnected() {

		$oSelf = self::_getInstance();

		if($oSelf->oMemcached) {
			return true;
		}

		return false;
	}

	public static function getCachingEngine() {

		$oCache = self::_getInstance();

		if(!empty($oCache->oMemcached)) {
			return get_class($oCache->oMemcached);			
		} else {
			return 'MySQL';
		}

	}

	/**
	 * Memcached->getAllKeys() ist nicht zuverlässig, daher werden hier verschiedene Methoden kombiniert.
	 * @return array
	 */
	private function getAllMemcachedKeys() {

		$sHost = System::d('memcache_host');
		$sPort = System::d('memcache_port');

		// Methode 1
		$aKeysFromMemcached = $this->oMemcached->getAllKeys();
		
		// Methode 2
		$aKeysFromCommandline = [];

		$sCmd = "echo -e \"stats items\nquit\"  | nc localhost 11211 | grep -oe ':[0-9]*:'  |  grep -oe '[0-9]*' | sort | uniq | xargs -L1 -I{} bash -c 'echo -e \"stats cachedump {} 1000\nquit\" | nc localhost 11211'";

		$sOutput = Update::executeShellCommand($sCmd);

		$aItems = explode("\n", $sOutput);

		foreach($aItems as $sItem) {
			
			$aMatch = [];
			preg_match('/(.{32}_.{32}_(0|1))/', $sItem, $aMatch);

			if(!empty($aMatch)) {
				$aKeysFromCommandline[] = $aMatch[1];
			}

		}
		
		// Methode 3
		$aKeysFromScript = [];
		$errno = $errstr = null;
		$sock = fsockopen($sHost, $sPort, $errno, $errstr);
		if ($sock === false) {
			throw new Exception("Error connection to server {$host} on port {$port}: ({$errno}) {$errstr}");
		}

		if (fwrite($sock, "stats items\n") === false) {
			throw new Exception("Error writing to socket");
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
				throw new Exception('Error writing to socket');
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
			throw new Exception('Error closing socket');
		}			

		$aKeys = array_merge($aKeysFromMemcached, $aKeysFromCommandline, $aKeysFromScript);
		$aKeys = array_unique($aKeys);

		return $aKeys;
	}
	
	/**
	 * @param bool $bWithPersistent
	 * @return string[]
	 */
	private function getExistingKeys($bWithPersistent = false) {

		$sCachingengine = self::getCachingEngine();
		$aExistingKeys = array();
		$bUseFilter = true;

		switch($sCachingengine) {

			case 'Memcached':
				
				$aExistingKeys = $this->getAllMemcachedKeys();
				
				if(!is_array($aExistingKeys)) {
					$aExistingKeys = array();
				}
				break;

			case 'MySQL':
				$bUseFilter = false;
				$sSQL = "
					SELECT DISTINCT
						`key`
					FROM
						`system_cache`
				";
				$aSQL = array();
				$aRows = DB::getPreparedQueryData($sSQL, $aSQL);
				foreach($aRows as $aRow) {
					$aExistingKeys[] = $aRow['key'];
				}
				break;

		}

		if($bUseFilter) {
			// alle Keys rausfiltern die nicht mit dem Lizenzkey der Installation starten
			$sLicenseKeyPart = $this->_buildLicenseKeyPart();
			$aExistingKeys = array_filter(
				$aExistingKeys,
				function($sKey) use ($sLicenseKeyPart) {
					return (strpos($sKey, $sLicenseKeyPart) === 0);
				}
			);
		}

		if(!$bWithPersistent) {
			// alle Keys rausfiltern die mit "_1" enden
			$aExistingKeys = array_filter(
				$aExistingKeys,
				function($sKey) {
					$sLastChar = substr($sKey, -1);
					return ($sLastChar !== '1');
				}
			);
		}

		return $aExistingKeys;
	}

}
