<?php

class MVC_View {
	
	/**
	 * Transferarray mit allen Daten des Views
	 * 
	 * @var array
	 */
	protected $_aTransfer = array();
	
	/**
	 * @var string
	 */
	protected $_sExtension;
	
	/**
	 * @var string
	 */
	protected $_sController;
	
	/**
	 * @var string
	 */
	protected $_sAction;
	
	/**
	 * HTTP Status Code
	 * @var int
	 */
	protected $_iHTTPCode = 200;
	
	/**
	 * Setz Variablen
	 * 
	 * @param string $sExtension
	 * @param string $sController
	 * @param string $sAction 
	 */
	public function __construct($sExtension, $sController, $sAction) {
		
		$this->_sExtension = $sExtension;
		$this->_sController = $sController;
		$this->_sAction = $sAction;
		
	}
	
	/**
	 * Wert in View setzen
	 * 
	 * @param string $sName
	 * @param string $mValue 
	 */
	public function set($sName, $mValue) {
		
		$this->_aTransfer[$sName] = $mValue;
		
	}

	public function merge($sName, array $mValue) {

		$this->_aTransfer[$sName] = array_merge((array)$this->_aTransfer[$sName], $mValue);

	}

	/**
	 * @param array $aTransfer
	 */
	public function setAll(array $aTransfer) {
		$this->_aTransfer = $aTransfer;
	}
	
	/**
	 * Wert aus View lesen
	 * 
	 * @param string $sName
	 */
	public function get($sName) {
		
		if(!isset($this->_aTransfer[$sName])) {
			return null;
		}
		
		return $this->_aTransfer[$sName];
		
	}	
	
	/**
	 * View ausgeben 
	 */
	public function render() {
		
		$sJson = json_encode($this->_aTransfer);

		header('Content-type: application/json; charset=utf-8', true, $this->getHTTPCode());

		echo $sJson;
		
	}
	
	/**
	 * Setzt den HTTP-Code
	 * @param int $iHttpCode
	 */
	public function setHTTPCode($iHttpCode) {
		$this->_iHTTPCode = (int)$iHttpCode;
	}
	
	/**
	 * Gibt den HTTP-Code zurück
	 * @return int
	 */
	public function getHTTPCode(){
		return $this->_iHTTPCode;
	}
	
}