<?php

/**
 * Ableitung der Excpetion-Klasse um neben der Nachricht einen Key übergeben zu können
 */
class Ext_TC_Exception extends Exception {
	
	protected $_sKey = '';
	
	/**
	 * Konstruktor mir verändertem zweitem Parameter
	 * @param string $sMessage
	 * @param string $sKey
	 * @param Exception $oPrevious 
	 */
	public function __construct($sMessage, $mKey=null, Exception $oPrevious=null) {

		$this->_sKey = $mKey;

		$iCode = 0;

		if(is_integer($mKey) === true) {
			$iCode = $mKey;
		}

		if(Ext_TC_Util::getPHPVersion() >= 5.3)
		{
			// Dies führt zu einem FATAL ERROR bei PHP-Version < 5.3, da der dritte Parameter erst ab 5.3 vorhanden ist!
			parent::__construct($sMessage, $iCode, $oPrevious);
		}
		else
		{
			parent::__construct($sMessage, $iCode);
		}

	}	

	/**
	 * Gibt den Key zurück
	 * @return string|int
	 */
	public function getKey() {
		
		return $this->_sKey;
		
	}
	
}