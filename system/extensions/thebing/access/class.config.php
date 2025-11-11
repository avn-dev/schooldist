<?php

class Ext_Thebing_Access_Config {
	
	static $sDatabaseTable = 'kolumbus_access_config';
 	
 	static protected $oConfig = NULL;
 	static protected $aConfig = array();
	
 	/**
 	 * Holt eine Instance
 	 * und läd die daten in den cache
 	 * @return Object
 	 */
	static public function getInstance(){
		
		if (self::$oConfig === NULL)  {
			self::$oConfig = new Ext_Thebing_Access_Config();
		}

		return self::$oConfig;
		
	}
	
	
	public function __construct(){
		
		self::$aConfig = $this->getCompleteConfig();
		
	}
	
	
	/**
	 * Gibt ein Array mit allen vorhanden Config einträge zurück
	 * der Config name ist der key
	 * @return Array
	 */
	public function getCompleteConfig(){
		
		$sSql = " SELECT * FROM #table ";
		$aSql = array('table'=>self::$sDatabaseTable);
		$aTempData = DB::getPreparedQueryData($sSql,$aSql);
		$aConfigData = array();
		foreach($aTempData as $aData){
			$aConfigData[$aData['config']] = $aData['value'];
		}

		// Prüfen ob die wichtigstens Einstellungen schon angelegt wurden
		// Wenn nicht Default werte schreiben
		if(!key_exists('access_file_version',$aConfigData)){
			$this->setConfig('access_file_version', 0);
		}
//		if(!key_exists('access_file_server',$aConfigData)){
//			$this->setConfig('access_file_server', 'https://live0.thebing.com');
//		}
		$this->setConfig('access_file_server', 'https://fidelo.com');
		
		return $aConfigData;
	}
	
	/**
	 * Set ein Config Value
	 * Löscht und trägt neu ein
	 * @param $sConfig
	 * @param $mValue
	 * @return bol
	 */
	public function __set($sConfig,$mValue){
		return $this->setConfig($sConfig,$mValue);
	}
	
	/**
	 * Set ein Config Value
	 * Löscht und trägt neu ein
	 * @param $sConfig
	 * @param $mValue
	 * @return bol
	 */
	public function setConfig($sConfig,$mValue){
		
		$sSql = " DELETE FROM #table WHERE `config` = :config ";
		$aSql = array(
					'table'=>self::$sDatabaseTable,
					'config'=>$sConfig
				);
		$bSuccess = DB::executePreparedQuery($sSql,$aSql);

		$sSql = " INSERT 
						#table 
					SET 
						`config` = :config,
						`value` =:value ";
		$aSql['value'] = $mValue;		
		$bSuccess = DB::executePreparedQuery($sSql,$aSql);
		if($bSuccess){
			// Cache neu setzten
			self::$aConfig[$sConfig] = $mValue;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Set ein Config Value
	 * Löscht und trägt neu ein
	 * @param $sConfig
	 * @param $mValue
	 * @return bol
	 */
	static public function set($sConfig,$mValue){
		$oConfig = self::getInstance();
		return $oConfig->setConfig($sConfig,$mValue);
	}
	
	
	
	/**
	 * Gibt einen Config Eintrag aus dem Cache zurück
	 * @param $sConfig
	 * @return unknown_type
	 */
	public function __get($sConfig){
		return $this->getConfig($sConfig);
	}

	/**
	 * Gibt einen Config Eintrag aus dem Cache zurück
	 * @param $sConfig
	 * @return unknown_type
	 */
	public function getConfig($sConfig){
		
		if(key_exists($sConfig,self::$aConfig)){
			return self::$aConfig[$sConfig];
		} else {
			return false;
		}
		
	}

	/**
	 * Gibt einen Config Eintrag aus dem Cache zurück
	 * @param $sConfig
	 * @return unknown_type
	 */
	static public function get($sConfig){
		
		$oConfig = self::getInstance();
		return $oConfig->getConfig($sConfig);
	}
	
}