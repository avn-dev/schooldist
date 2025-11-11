<?php

/**
 * UML - https://redmine.thebing.com/redmine/issues/272 
 */
abstract class Ext_TC_Mapping_Abstract implements Ext_TC_Mapping_Interface
{
	const PREFIX_INDIVIDUAL_FIELD = 'individual.';

	const PREFIX_METABLE_FIELD = 'meta_';

	/**
	 *
	 * @var WDBasic 
	 */
	protected $_sWDBasic;
	
	/**
	 *
	 * @var Ext_TC_Mapping_Field <array> 
	 */
	protected $_aFields = array();
	
	/**
	 *
	 * @var string 
	 */
	protected $_sType;

	protected $sParentLabel;

	/**
	 *
	 * @var array
	 */
	protected $_aFormats = array();
	
	protected $bIgnoreDatabaseFields = false;


	/**
	 * Wir übergeben nur den Klassennamen und nicht das ganze Objekt, um memory_limit zu sparen
	 * @param string $sModel
	 * @param string $sType 
	 */
	public function __construct($sModel, $sType) {

		$this->_sWDBasic	= $sModel;
		
		$this->_sType		= $sType;

		if($this->bIgnoreDatabaseFields === false) {
		
			$oModel			= new $sModel();

			$aTableFields	= (array)$oModel->getTableFields();

			$sTableName		= $oModel->getTableName();

			if(isset($aTableFields[$sTableName])) {
				$aTableFields = (array)$aTableFields[$sTableName];
			}

			/**
			 * Mapping Informationen anhand der Tabellenstruktur automatisch bilden
			 */
			foreach($aTableFields as $sColumn => $aInfo) { 
				$oField = $this->createField($aInfo);
				$this->addField($sColumn, $oField);

				$sNameOriginal	= $sColumn . '_original';
				$oFieldOriginal = $this->createField($aInfo, true);
				$this->addField($sNameOriginal, $oFieldOriginal);
			}

			$oFormatUser = new Ext_Gui2_View_Format_UserName(true);

			$oFieldCreator = $this->getField('creator_id');

			if($oFieldCreator) {
				$this->addFormat('creator_id', $oFormatUser);
			}

			$oFieldEditor = $this->getField('editor_id');

			if($oFieldEditor) {
				$this->addFormat('editor_id', $oFormatUser);
			}
		}
	}
	
	/**
	 *
	 * @param bool $bWithDbInformation
	 * @return array 
	 */
	public function getMappingSchema($bWithDbInformation=false, $bWithOriginal=false) {
		//Kind kann(muss) Mapping-Schema verändern
		$this->_configure();

		$oWDBasic		= new $this->_sWDBasic();
		
		$sTableAlias	= $oWDBasic->getTableAlias();
		
		$aSchema		= array();
		
		foreach($this->_aFields as $sFieldName => $oField) {
			if(
				!$bWithOriginal &&
				$oField->isOriginal()
			) {
				continue;
			}
			
			//Key Anhand des Aliases aufbauen, damit sich mehrere Mapping-Klassen nicht in die Quere kommen
			if(!empty($sTableAlias)) {
				$sKey = $sTableAlias . '.' . $sFieldName;
			} else {
				$sKey = $sFieldName;
			}

			$aFieldConfig	=  $oField->toArray();
			
			if($bWithDbInformation)	{
				$aFieldConfig['db_alias']	= $sTableAlias;
				$aFieldConfig['db_column']	= $sFieldName;
				$aFieldConfig['class'] = $this->_sWDBasic;
			}
			
			$aSchema[$sKey] = $aFieldConfig;
		}
		
		return $aSchema;
	}

	public function addIndividualField($sFieldName, Ext_TC_Mapping_Field $oField) {
		$sFieldName = \Illuminate\Support\Str::start($sFieldName, self::PREFIX_INDIVIDUAL_FIELD);
		$this->addField($sFieldName, $oField);
	}

	public function addMetaField($sFieldName, Ext_TC_Mapping_Field $oField) {
		$sFieldName = \Illuminate\Support\Str::start($sFieldName, self::PREFIX_METABLE_FIELD);
		$this->addField($sFieldName, $oField);
	}

	/**
	 * Mapping-Feld hinzufügen
	 * @param string $sFieldName
	 * @param Ext_TC_Mapping_Field $oField 
	 */
	public function addField($sFieldName, Ext_TC_Mapping_Field $oField) {
		$this->_aFields[$sFieldName] = $oField;
	}
	
	/**
	 *
	 * @param string $sFieldName
	 * @return Ext_TC_Mapping_Field 
	 */
	public function getField($sFieldName) {
		if(isset($this->_aFields[$sFieldName])) {
			
			$oField = $this->_aFields[$sFieldName];
			
			if($this->sParentLabel !== null) {
				$oField->addConfig('parent_label', L10N::t($this->sParentLabel));
			}

			return $oField;
		}
			
		return false;		
	}
	
	/**
	 * Alle Felder löschen
	 */
	public function reset() {
		$this->_aFields = array();
	}
	
	
	/**
	 * Formatklasse für ein Feld hinzufügen
	 * @param string $sField
	 * @param Ext_Gui2_View_Format_Abstract $oFormat 
	 */
	public function addFormat($sField, Ext_Gui2_View_Format_Abstract $oFormat)
	{
		$oField = $this->getField($sField);
		$oField->addFormat($oFormat);
	}
	
	/**
	 *
	 * @param string $sField
	 * @return Ext_Gui2_View_Format_Abstract 
	 */
	public function getFormat($sField)
	{
		$oField = $this->getField($sField);
		$mFormat = $oField->getFormat();
		return $mFormat;
	}
	
	abstract protected function _configure();

}
