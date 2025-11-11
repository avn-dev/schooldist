<?php

/**
 * @author Mehmet Durmaz
 */
class Ext_TC_Index_Search_Result implements Ext_TC_Statement_Result_Interface
{
	/**
	 *
	 * @var Ext_TC_Index_Search_Mapping
	 */
	protected $_oIndexMapping;
	
	protected $_aData = array();

	/**
	 *
	 * @param Ext_TC_Index_Search_Mapping $oIndexMapping
	 * @param array $aData
	 */
	public function __construct(Ext_TC_Index_Search_Mapping $oIndexMapping, array $aData)
	{
		$this->_oIndexMapping = $oIndexMapping;
		
		$this->_aData	= $aData;
	}
	
	/**
	 * Wert für ein Feld bekommen anhand der Mapping-Daten, wenn in mehreren Mappings ein  Feld
	 * nicht vorkommt, dann braucht man den Alias nicht angeben
	 * @param string $sField
	 * @param string $sAlias
	 * @return mixed 
	 */
	public function getFieldValue($sField, $sAlias=false)
	{
		$sIndexField	= $this->_oIndexMapping->getIndexFieldName($sField, $sAlias);
		
		$mValue			= $this->getValue($sIndexField);
		
		return $mValue;
	}
	
	public function getScalarValue($sField) {
		
		if(isset($this->_aData[$sField])) {
			
			$mReturn = $this->_aData[$sField];
			if(is_array($mReturn)) {
				$mReturn = reset($mReturn);
			}
			
			return $mReturn;
			
		} else {
			return null;
		}

	}
	
	/**
	 * Wert direkt ansprechen und holen, mit Aliasstring zusammen
	 * @param string $sField
	 * @return mixed 
	 */
	public function getValue($sField) {

		if(isset($this->_aData[$sField])) {
			return $this->_aData[$sField];
		} else {
			return null;
		}

	}
	
	/**
	 * Alle Daten holen
	 * 
	 * Falls die Daten als Array vorliegen und einem genauen Schema entsprechen ("array(0=>'WERT')"), dann wird nur der 
	 * skalare Wert zurückgegeben.
	 * 
	 * @return array 
	 */
	public function getAllData() {

		$aReturn = $this->_aData;

		foreach($aReturn as &$mValue) {
			if(
				is_array($mValue) &&
				isset($mValue[0]) &&
				count($mValue) === 1
			) {
				$mValue = reset($mValue);
			}
		}

		return $aReturn;
	}

}