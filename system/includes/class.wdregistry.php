<?php

class WDRegistry
{
	/**
	 * Assigned variables
	 *
	 * @var array
	 */
	protected static $_aVars = array();

	/* ==================================================================================================== */

	/**
	 * Overwrite and protect the magic methods
	 */
	private function __construct() {}
	private function __clone() {}

	/* ==================================================================================================== */

	/**
	 * Check the register state of a key
	 *
	 * @param string $sKey
	 * @return bool
	 */
	public static function exists($sKey)
	{
		self::_validateKey($sKey);

		return array_key_exists($sKey, (array)self::$_aVars);
	}


	/**
	 * Get a registered value by key
	 *
	 * @param string $sKey
	 * @return mixed value
	 */
	public static function get($sKey)
	{
		self::_validateKey($sKey);

		if(!isset(self::$_aVars[$sKey]))
		{
			throw new Exception('Key "' . $sKey . '" is not registered!');
		}

		return self::$_aVars[$sKey];
	}


	/**
	 * Get all registered variables as an array
	 *
	 * @return array
	 */
	public static function getVars()
	{
		return self::$_aVars;
	}


	/**
	 * Register a value by key
	 *
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public static function set($sKey, &$mValue)
	{
		self::_validateKey($sKey);

		self::$_aVars[$sKey] = $mValue;
	}


	/**
	 * Unregister a value by key
	 *
	 * @param string $sKey
	 */
	public static function unregister($sKey)
	{
		self::_validateKey($sKey);

		if(isset(self::$_aVars[$sKey]))
		{
			unset(self::$_aVars[$sKey]);
		}
	}

	/* ==================================================================================================== */

	/**
	 * Validate the key
	 * 
	 * @param $sKey
	 * @return bool
	 */
	private static function _validateKey($sKey)
	{
		if(gettype($sKey) == 'string')
		{
			if(preg_match('/^([a-z_])+([a-z_0-9:])*$/i', $sKey))
			{
				return true;
			}
		}

		throw new Exception('Invalid key! Use unblanked string key.');
	}
}