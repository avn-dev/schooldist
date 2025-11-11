<?php

namespace Core\Handler;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Diese Klasse ersetzt die normale Verwendung von Cookies über setcookie und $_COOKIE
 * 
 * Als Singleton implementiert
 */
class CookieHandler
{
	/**
	 * @var CookieHandler 
	 */
	private static $_oInstance;

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if(self::$_oInstance === null) {
			self::$_oInstance = new self();
		}
		
		return self::$_oInstance;
	}
	
	public function getValue($sName)
	{
		$mValue = filter_input(INPUT_COOKIE, $sName, FILTER_SANITIZE_STRING);

		//try {
		//	$decrypted = Crypt::decryptString($mValue);
		//} catch (DecryptException $e) {
			$decrypted = $mValue;
		//}

		return $decrypted;
	}
	
	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @param int $iExpire
	 * @param bool $bSecure
	 * @param bool $bHttpOnly
	 * @return mixed
	 */
	public function setValue($sName, $mValue, $iExpire=0, $bSecure=true, $bHttpOnly=false)
	{
		//$encrypted = Crypt::encryptString($mValue);
		$encrypted = $mValue;

		$_COOKIE[$sName] = $encrypted;
		setcookie($sName, $encrypted, $iExpire, "/", "", $bSecure, $bHttpOnly);

		return $encrypted;
	}
	
	public function removeValue($sName)
	{
		unset($_COOKIE[$sName]);
		setcookie($sName, '', time() - 3600, "/");
	}
	
	public function __isset($sName)
	{
		return isset($_COOKIE[$sName]);
	}
	
	public static function get($sName)
	{
		$oHandler = self::getInstance();
		$mValue = $oHandler->getValue($sName);
		return $mValue;
	}
	
	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @param int $iExpire
	 * @param bool $bSecure
	 * @param bool $bHttpOnly
	 */
	public static function set($sName, $mValue, $iExpire=0, $bSecure=true, $bHttpOnly=false)
	{
		$oHandler = self::getInstance();
		$oHandler->setValue($sName, $mValue, $iExpire, $bSecure, $bHttpOnly);
	}

	public static function remove($sName)
	{
		$oHandler = self::getInstance();
		$oHandler->removeValue($sName);
	}
	
	public static function is($sName)
	{
		$oHandler = self::getInstance();
		$bIsset = isset($oHandler->$sName);
		
		return $bIsset;
	}
}