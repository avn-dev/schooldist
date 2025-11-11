<?php
/**
 * UML - https://redmine.thebing.com/redmine/issues/272 
 */
abstract class Ext_TC_Index_Mapping_Abstract extends Ext_TC_Mapping_Abstract
{
	
	/**
	 *
	 * @var array 
	 */
	protected $_aLikeSearch = array();

	/**
	 * Wenn ein Feld im Index suchbar sein soll, dann immer diese Funktion aufrufen 
	 * @param Ext_TC_Index_Mapping_Field $oField
	 * @param type $sAnalyser
	 * @param type $sTermVector 
	 */
	protected function _addAnalyser(Ext_TC_Mapping_Field $oField, $sAnalyser, $sTermVector=false)
	{
		$oField->changeConfig('index', true);
		//$oField->addConfig('analyzer', $sAnalyser);
		if(
			$sTermVector
		)
		{
			$oField->addConfig('term_vector', $sTermVector);
		}
	}
	
	/**
	 * Definieren welche Felder eine Ähnlichkeitssuche haben sollen
	 * @param string $sField 
	 * @return bool
	 */
	public function isLikeSearch($sField)
	{
		if(
			in_array($sField, $this->_aLikeSearch)
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Mapping-Feld Objekte für die einzelnen Spalten
	 * @param array $aConfig
	 * @return Ext_TC_Mapping_Field 
	 */
	public function createField($aConfig, $bOriginal=false)
	{
		$oField = new Ext_TC_Index_Mapping_Field($aConfig, $bOriginal);
		return $oField;
	}
	
}