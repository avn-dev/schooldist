<?php

namespace Core\Facade;

use Core\Service\Cache\Driver\MemcachedDriver;
use Core\Service\Cache\Driver\FileDriver;
use Core\Service\Cache\Driver\DatabaseDriver;

/**
 * @method static put($sKey, $iExpiration, $mData, $sCacheGroup = null)
 * @method static forever($sKey, $mData)
 * @method static remember($sKey, $iExpiration, \Closure $oCallback, $sCacheGroup = null)
 * @method static rememberForever($sKey, \Closure $oCallback)
 * @method static get(string $sKey, bool $bForever = false)
 * @method static exists(string $sKey, $bForever = false)
 * @method static forget(string $sKey, $bForever = false)
 * @method static forgetGroup(string $sCacheGroup)
 * @method static flush($bAllEntries = false)
 * @method static getExistingKeys($bAllEntries = false)
 * @method static getStats()
 * @method static increment(string $key, int $value, int $initalValue = 0, int $expiry = 0, bool $forever = false)
 *
 * @see \Core\Service\Cache
 */
class Cache {
	
	const MEMCACHED_DRIVER = 'memcached';
	const FILE_DRIVER = 'file';
	const DATABASE_DRIVER = 'db';
	
	/**
	 * Generated instances to ensure singleton pattern
	 * 
	 * @var array
	 */
	private static $aInstances;
	
	/**
	 * Directly call store instance by key
	 * 
	 * @param string $sDriver
	 * @return \Core\Service\Cache
	 */
	public static function store(string $sDriver) {
		return self::getInstance($sDriver);
	}
	
	/**
	 * Build an instance of cache service
	 * 
	 * @param string $sDriver
	 * @return \Core\Service\Cache
	 */
	private static function getInstance(string $sDriver) {
		
		if(isset(self::$aInstances[$sDriver])) {			
			return self::$aInstances[$sDriver];
		}

		switch($sDriver) {
			case self::MEMCACHED_DRIVER:
				$oDriver = new MemcachedDriver(\System::d('memcache_host'), \System::d('memcache_port'));
				break;
			case self::FILE_DRIVER:
				throw new \RuntimeException('Please implement file cache driver!');
				$oDriver = new FileDriver(\Util::getDocumentRoot() . 'storage/app/cache/');
				break;
			case self::DATABASE_DRIVER:
				$oDriver = new DatabaseDriver(\DB::getDefaultConnection());
				break;
			default:
				throw new \InvalidArgumentException('Unknown cache driver!');
		}
		
		self::$aInstances[$sDriver] = new \Core\Service\Cache($oDriver, \System::d('license'));
		
		return self::$aInstances[$sDriver];
	}
	
	/**
	 * Generate default cache service instance
	 * 
	 * @return \Core\Service\Cache
	 */
	public static function getDefault() {
		
		$sHost = \System::d('memcache_host');
		$sPort = \System::d('memcache_port');

		// generate cache instance by given conditions
		
		$sDriver = (!empty($sHost) && !empty($sPort))
				? self::MEMCACHED_DRIVER 
				: self::DATABASE_DRIVER;

		return self::getInstance($sDriver);
	}
		
	/**
	 * Generate default cache service instance and call funtion on it
	 * 
	 * @param string $sFunction
	 * @param array $aArguments
	 * @return mixed
	 */
	public static function __callStatic($sFunction, $aArguments) {
		return call_user_func_array([self::getDefault(), $sFunction], $aArguments);
	}
	
}
