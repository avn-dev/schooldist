<?php

class Ext_TC_Error {
	
	static protected $oLog = NULL;
	static protected $aLog = array();
	
 	/**
 	 * Holt eine Instance
 	 * und läd die daten in den cache
 	 * @return Object
 	 */
	static public function getInstance(){
		
		if (self::$oLog === NULL)  {
			self::$oLog = new Ext_TC_Error();
		}

		return self::$oLog;
		
	}
	
	public function getLastLog(){		
		return end(self::$aLog);
	}
	
	static public function lastLog(){
		
		$oLog = self::getInstance();
		
		return $oLog->getLastLog();
	}
	
	
	public function saveLog($sLog, $aInfoArray = array()){

		self::$aLog[] = $sLog;

		if(!is_array($aInfoArray)){
			$aInfoArray = [$aInfoArray];
		}
		
		\Log::getLogger()->error($sLog, $aInfoArray);
		
	}
	
	static public function log($sLog,$aInfoArray = array()){
		
		$oLog = self::getInstance();
		$oLog->saveLog($sLog,$aInfoArray);
		
	}
	
}