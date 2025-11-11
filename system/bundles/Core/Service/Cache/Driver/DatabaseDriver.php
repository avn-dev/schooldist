<?php

namespace Core\Service\Cache\Driver;

use Core\Service\Cache;
use DB;

class DatabaseDriver extends AbstractDriver {
	
	/**
	 * Statischer Cache
	 * 
	 * @var array
	 */
	private static $aStaticCache = array();
	/**
	 * Connection object
	 * 
	 * @var \DB 
	 */
	private $oDB;
	
	/**
	 * Database Driver
	 * 
	 * @param \DB $oDB
	 */
	public function __construct(\DB $oDB) {
		$this->oDB = $oDB;
	}
	
	/**
	 * Löscht einen Eintrag aus dem Cache
	 * 
	 * @param string $sKey
	 * @return bool
	 */
	public function forget($sKey) {
		
		$sSQL = "
			DELETE FROM
				`system_cache`
			WHERE
				`key` = :key
		";

		$this->oDB->executePreparedQuery($sSQL, ['key' => $sKey]);

		unset(self::$aStaticCache[$sKey]);
		
		return true;
	}

	/**
	 * Liefert einen Eintrag aus dem Cache
	 * 
	 * @param string $sKey
	 * @return mixed|null
	 */
	public function get($sKey) {
		
		if(isset(self::$aStaticCache[$sKey])) {
			return self::$aStaticCache[$sKey];
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
		
		$mCheck = $this->oDB->getQueryOne($sSQL, ['key' => $sKey]);

		if($mCheck !== null) {
			self::$aStaticCache[$sKey] = unserialize($mCheck);
			return self::$aStaticCache[$sKey];
		}
		
		return null;
	}

	/**
	 * Fügt einen Eintrag zum Cache hinzu
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @return int
	 */
	public function add($sKey, $iExpiration, $mData) {
		
		$mSerializedData = serialize($mData);

		// Memcache-Konventionen
		if($iExpiration === 0) {
			$iExpiration = strtotime('01-01-2038');
		}

		$aSql = array(
			'key' => $sKey,
			//'expiration' => date('Y-m-d H:i:s', $iExpiration),
			'expiration' => $iExpiration,
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
		
		$this->oDB->executePreparedQuery($sSql, $aSql);

		$iStatus = Cache::STATUS_ERROR;
		
		// Wenn INSERT IGNORE nicht erfolgreich, replace() von Memcache mit UPDATE-Query abbilden
		if($this->oDB->getAffectedRows() == 0) {

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
			
			$this->oDB->executePreparedQuery($sSql, $aSql);

			// Hier kann nicht mit Affected Rows geprüft werden,
			// denn wenn der Wert mit exakt gleichen Werten vorhanden ist,
			// wird Affected Rows immer 0 zurückliefern
			$iStatus = Cache::STATUS_REPLACED;

		} else {
			// Solange der Eintrag nicht manuell durch den DELETE QUERY gelöscht wurde,
			// wird dieser Wert nicht zurückgeliefert werden, sondern REPLACED
			$iStatus = Cache::STATUS_ADDED;
		}

		self::$aStaticCache[$sKey] = $mData;
	
		return $iStatus;
	}

	/**
	 * @return mixed
	 */
	public function getStats() {
		$sSql = '
			SELECT
				COUNT(*)
			FROM
				`system_cache`
				';
		$iCount = DB::getQueryOne($sSql);
		$aStats = array('curr_items' => $iCount);
		
		return $aStats;
	}
	
	/**
	 * Löscht alle abgelaufenen Einträge aus dem Cache
	 */
	protected function garbageCollector() {
		$sSql = "
			DELETE FROM
				`system_cache`
			WHERE
				`expiration` < NOW()
		";
		$this->oDB->executeQuery($sSql);
	}

	/**
	 * Liefert alle existierenden Keys
	 * 
	 * @return array
	 */
	public function getExistingKeys($sPrefix) {
		
		$sSQL = "
			SELECT DISTINCT
				`key`
			FROM
				`system_cache`
		";
		
		$aRows = $this->oDB->getPreparedQueryData($sSQL, []);

		$aExistingKeys = [];
		foreach($aRows as $aRow) {
			$aExistingKeys[] = $aRow['key'];
		}
		
		return $aExistingKeys;
	}

	public function increment($key, $value, $initialValue = 0, $expiry = 0) {
		 throw new \BadMethodCallException('increment method not implemented for database cache driver');
	}

}

