<?php

/**
 * UML - https://redmine.thebing.com/redmine/issues/272
 */
interface Ext_TC_Mapping_Interface
{
	/**
	 *
	 * @param bool $bWithDbInformation
	 * @param bool $bWithOriginal
	 * @return array 
	 */
	public function getMappingSchema($bWithDbInformation=false, $bWithOriginal=false);
	
	/**
	 * Mapping-Feld Objekte für die einzelnen Spalten
	 * @param array $aConfig
	 * @param bool $bOriginal
	 * @return Ext_TC_Mapping_Interface 
	 */
	public function createField($aConfig, $bOriginal=false);
	
	/**
	 * Mapping-Feld hinzufügen
	 * @param string $sFieldName
	 * @param Ext_TC_Mapping_Interface $oField 
	 */
	public function addField($sFieldName, Ext_TC_Mapping_Field $oField);
	
	/**
	 *
	 * @param string $sFieldName
	 * @return Ext_TC_Mapping_Interface 
	 */
	public function getField($sFieldName);
	
	/**
	 * Alle Felder löschen
	 */
	public function reset();
	
	/**
	 * Formatklasse für ein Feld hinzufügen
	 * @param string $sField
	 * @param Ext_Gui2_View_Format_Abstract $oFormat 
	 */
	public function addFormat($sField, Ext_Gui2_View_Format_Abstract $oFormat);
	
	/**
	 *
	 * @param string $sField
	 * @return Ext_Gui2_View_Format_Abstract 
	 */
	public function getFormat($sField);
}