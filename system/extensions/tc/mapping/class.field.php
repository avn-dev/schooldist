<?php

/**
 * UML - https://redmine.thebing.com/redmine/issues/272 
 */
class Ext_TC_Mapping_Field
{
	/**
	 * Mögliche Konfigurationen 
	 */
	protected $_aConfigFields = array();
	
	/**
	 * Werte der config Felder
	 * @var array 
	 */
	protected $_aValues = array();
	
	/**
	 * Ist das Mapping-Feld ein Original-Feld?
	 * @var bool 
	 */
	protected $_bIsOriginal;
	
	/*
	 * The Format class
	 */
	protected $_oFormat;

	/**
	 *
	 * @param array $aConfig 
	 */
	public function __construct(array $aConfig, $bOriginal=false) {
		
		if($bOriginal) {
			$sType = $this->_getTypeFromDbType($aConfig['Type']);
		} else {
			$sType = 'keyword';
		}
		
		$this->addConfig('type', $sType);
		
		$this->_bIsOriginal = $bOriginal;
	}
	
	/**
	 *
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function addConfig($sConfig, $mValue) {
		if(in_array($sConfig, $this->_aConfigFields)) {
			$this->_aValues[$sConfig] = $mValue;
		}
	}
	
	/**
	 *
	 * @param string $sConfig 
	 */
	public function removeConfig($sConfig) {
		if(array_key_exists($sConfig, $this->_aValues)) {
			unset($this->_aValues[$sConfig]);
		}
	}
	
	/**
	 *
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function changeConfig($sConfig, $mValue) {
		if(
			in_array($sConfig, $this->_aConfigFields) &&
			array_key_exists($sConfig, $this->_aValues)
		) {
			$this->_aValues[$sConfig] = $mValue;
		}
	}
	
	/**
	 *
	 * @param string $sConfig
	 * @return mixed 
	 */
	public function getConfig($sConfig) {
		if(isset($this->_aValues[$sConfig])) {
			return $this->_aValues[$sConfig];
		}
	}
	
	public function hasConfig($sConfig) {
		if(isset($this->_aValues[$sConfig])) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Von den Describe Informationen über SQL rausfinden, welcher IndexTyp benutzt werden muss
	 * @param type $sDbType
	 * @return string 
	 */
	protected function _getTypeFromDbType($sDbType)
	{

		switch(true)
		{
			case strpos($sDbType, 'enum') === 0:
				$sType = 'enum';
				break;
			case strpos($sDbType, 'text') !== false:
				$sType = 'text';
				break;
			case strpos($sDbType, 'char') !== false:
				$sType = 'text';
				break;
			case strpos($sDbType, 'timestamp') !== false:
				$sType = 'text';
				break;
			case strpos($sDbType, 'date') !== false:
				$sType = 'date';
				break;
			case strpos($sDbType, 'time') === 0:
				$sType = 'time';
				break;
			default:
				$sType = 'integer';
		}
		
		return $sType;
	}

	/**
	 * Objekt als Array
	 * @return array 
	 */
	public function toArray()
	{
		return $this->_aValues;
	}
	
	/**
	 *
	 * @return bool 
	 */
	public function isOriginal()
	{
		return $this->_bIsOriginal;
	}
	
	/**
	 * add a Format to the field
	 * @param $oFormat 
	 */
	public function addFormat($oFormat){
		$this->_oFormat = $oFormat;
	}
	
	/**
	 * get the Format to the field
	 */
	public function getFormat(){
		$oFormat = $this->_oFormat;
		
		if(empty($oFormat)){
			$oFormat = false;
		}
		return $oFormat;
	}
}