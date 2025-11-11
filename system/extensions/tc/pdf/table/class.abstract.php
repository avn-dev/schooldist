<?php


abstract class Ext_TC_Pdf_Table_Abstract
{
	/**
	 *
	 * @var array
	 */
	protected $_aData = array();
	
	/**
	 *
	 * Daten setzen
	 *  
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function setData($sKey, $mValue)
	{
		//Werte die definiert werden dürfen
		$aAllowedData = $this->_getAllowedData();
		
		if(!in_array($sKey, $aAllowedData))
		{
			throw new Exception('Config ' . $sKey . ' is not allowed!');
		}
		
		$this->_aData[$sKey] = $mValue;
	}
	
	/**
	 *
	 * @param string $sKey
	 * @return mixed 
	 */
	public function getData($sKey)
	{
		if(!$this->hasData($sKey))
		{
			Throw new Exception('Data ' . $sKey . ' not found!');
		}
		
		return $this->_aData[$sKey];
	}
	
	/**
	 *
	 * Überprüfen ob ein bestimmter Wert schon gesetzt ist
	 * 
	 * @param type $sKey
	 * @return type 
	 */
	public function hasData($sKey)
	{
		if(array_key_exists($sKey, $this->_aData))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	abstract protected function _getAllowedData();
}