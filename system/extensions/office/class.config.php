<?php

global $oZendDB;

/**
 * The office configuration class
 */
class Ext_Office_Config {

	/**
	 * The Ext_Office_Config instance
	 */
	private static $_oInstance = null;


	/**
	 * The office configuration
	 */
	private static $_aConfig = array();


	/**
	 * The forbidden constructor
	 */
	private function __construct(){}


	/**
	 * The forbidden __clone function
	 */
	private function __clone(){}


	/**
	 * Creates singleton instance of this class
	 */
	public static function getInstance()
	{
		if(self::$_oInstance === null)
		{
			self::$_oInstance = new self;
			self::_getConfigData();
		}
		return self::$_aConfig;
	}

	public static function reset() {
		self::$_oInstance = null;
	}

	/**
	 * Sets the office configuration into the intern array
	 */
	private static function _getConfigData()
	{
		$sSQL = "SELECT `key`, `value` FROM `office_config`";
		self::$_aConfig = DB::getQueryPairs($sSQL);

		self::$_aConfig['vat']				= unserialize(self::$_aConfig['vat']);
		self::$_aConfig['payment']			= unserialize(self::$_aConfig['payment']);
		self::$_aConfig['activities']		= unserialize(self::$_aConfig['activities']);
		self::$_aConfig['units']			= unserialize(self::$_aConfig['units']);
		self::$_aConfig['absence_groups']	= unserialize(self::$_aConfig['absence_groups']);
		
		self::$_aConfig['document_columns']	= json_decode(self::$_aConfig['document_columns'], true);
	}
	
	public static function set($sKey, $mValue, $sLanguage=null) {

		self::getInstance();
		
		if($sLanguage !== null) {
			$sKey = $sKey.'_'.$sLanguage;
		}

		self::$_aConfig[$sKey] = $mValue;
		
		$sSql = "REPLACE INTO `office_config` SET `value` = :value, `key` = :key";
		$aSql = array(
			'key' => $sKey,
			'value' => $mValue
		);
		DB::executePreparedQuery($sSql, $aSql);

	}
	
	/**
	 * Gibt ein Array mit der kompletten Konfiguration zurück
	 * @return array
	 */
	public static function getAll() {
	
		self::getInstance();
		
		$aConfig = self::$_aConfig;
		
		return $aConfig;
		
	}

	public static function get($sKey, $sLanguage=null, $mDefault=null) {
		
		self::getInstance();
		
		if(
			$sLanguage !== null &&
			isset(self::$_aConfig[$sKey.'_'.$sLanguage])
		) {
			$mReturn = self::$_aConfig[$sKey.'_'.$sLanguage];
		} else {
			$mReturn = self::$_aConfig[$sKey];
		}

		if($mReturn === null) {
			$mReturn = $mDefault;
		}

		return $mReturn;
		
	}

	public static function getDefaultSalutation($sLanguage, $iGender) {
		
		$sDefaultSalutation = self::get('default_salutation', $sLanguage);
		$sSalutations = self::get('salutations', $sLanguage);
		$aSalutations = (array)json_decode($sSalutations, true);
		
		foreach($aSalutations as $aSalutation) {
			if($aSalutation['key'] == $sDefaultSalutation) {
				$sSalutation = $aSalutation['template_'.(int)$iGender];
			}
		}

		return (string)$sSalutation;

	}

}
