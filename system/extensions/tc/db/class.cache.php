<?php
/**
 * Ahnlich wie WDCache nur OHNE Memcache!
 * Ja das ist redudant aber wegen php 5.2 und late static binding haben wir ein problem!
 * WICHTIG! KEIN CACHEN!
 */
class Ext_TC_DB_Cache
{ 
	/**
	 * The instance
	 *
	 * @var WDCache object
	 */
	private static $_oInstance;


	/* ==================================================================================================== */

	/**
	 * Protect the __clone()-Method
	 */
	private function __clone() {}


	/**
	 * Get instance
	 * @return WDCache
	 */
	private static function getInstance()
	{
		if(is_null(self::$_oInstance))
		{
			self::$_oInstance = new self();
		}

		return self::$_oInstance;
	}

	/* ==================================================================================================== */

	/**
	 * Delete a cached data
	 *
	 * @param string $sKey
	 */
	public static function delete($sKey)
	{
		$oCache = self::getInstance();

		$oCache->_delete($sKey);
	}


	/**
	 * Flush the cache
	 */
	public static function flush()
	{
		$oCache = self::getInstance();

		$oCache->_flush();
	}


	/**
	 * Get the data by given key
	 *
	 * @param string $sKey
	 * @return mixed
	 */
	public static function get($sKey)
	{
		$oCache = self::getInstance();

		$mData = $oCache->_get($sKey);

		return $mData;
	}


	/**
	 * Set the data into the cache
	 *
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 */
	public static function set($sKey, $iExpiration, $mData)
	{
		$oCache = self::getInstance();

		$oCache->_set($sKey, $iExpiration, $mData);
	}

	/* ==================================================================================================== */

	/**
	 * Build the caching key
	 *
	 * @param string $sKey
	 * @return string
	 */
	protected function _buildKey($sKey)
	{
		$sNewKey = md5($_SERVER['HTTP_HOST'] . $sKey);

		return $sNewKey;
	}


	/**
	 * Delete a cached data
	 *
	 * @param string $sKey
	 */
	protected function _delete($sKey)
	{
		$sKey = $this->_buildKey($sKey);

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

	}


	/**
	 * Flush the cache
	 */
	public function _flush()
	{

		$sSQL = "
			TRUNCATE TABLE
				`system_cache`
		";
		DB::executeQuery($sSQL);

	}


	/**
	 * Get the data by given key
	 *
	 * @param string $sKey
	 * @return mixed
	 */
	protected function _get($sKey)
	{
		$sKey = $this->_buildKey($sKey);

		$sSQL = "
			SELECT
				`data`
			FROM
				`system_cache`
			WHERE
				`expiration` >= NOW() AND
				`key` = :sKey
			LIMIT
				1
		";
		$aSQL = array(
			'sKey' => $sKey
		);
		$mCheck = DB::getQueryOne($sSQL, $aSQL);

		if($mCheck !== null)
		{
			return $mCheck;
		}

		return null;
	}


	/**
	 * Set the data into the cache
	 *
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 */
	protected function _set($sKey, $iExpiration, $mData)
	{
		$sKey = $this->_buildKey($sKey);

		$mSerializedData = serialize($mData);

		$aSQL = array(
			'sKey'			=> $sKey,
			'sExpiration'	=> date('Y-m-d H:i:s', time() + $iExpiration),
			'mData'			=> $mSerializedData
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Clear old entries

		$sSQL = "
			DELETE FROM
				`system_cache`
			WHERE
				`expiration` < NOW()
		";
		DB::executePreparedQuery($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Replace data

		$sSQL = "
			REPLACE INTO
				`system_cache`
			SET
				`key`			= :sKey,
				`expiration`	= :sExpiration,
				`data`			= :mData
		";
		DB::executePreparedQuery($sSQL, $aSQL);

	}
}

?>