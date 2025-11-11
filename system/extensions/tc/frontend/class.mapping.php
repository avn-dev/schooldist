<?php

class Ext_TC_Frontend_Mapping extends Ext_TC_Mapping_Abstract {
	
	/**
	 * Felder die benutzt werden
	 * @var type 
	 */
	protected $_aUsedFields = array();
	/**
	 * Gibt an ob das Mapping gecached werden soll
	 * @var bool 
	 */
	protected $bCacheMapping = true;
	
	/**
	 * @see parent ( wegen return wert , autoverfolständigung )
	 * @param string $sFieldName
	 * @return Ext_TC_Frontend_Mapping_Field 
	 */
	public function getField($sFieldName) {
		return parent::getField($sFieldName);
	}

	/**
	 * 
	 * @param string $sWDBasic
	 * @param string $sType
	 */
	public function __construct($sWDBasic, $sType) {		
		$this->_sWDBasic	= $sWDBasic;
		$this->_sType		= $sType;

		$this->createUsedFieldObjects();
		$this->configureMappingFields();		
	}
		
	/**
	 * konfiguriert die Mapping-Objekte für die Mappingklasse
	 * - hier ist ein Cache von 24h eingebaut da sich die Mappinginformationen selten ändern
	 */
	protected function configureMappingFields() {
		$aFields = null;		
		
		if($this->bCacheMapping) {
			$sCacheKey = 'tc_frontend_mapping_configured_fields_' . get_class($this) . '_' . \Ext_TC_System::getInterfaceLanguage().'_'.(int) $this->bIgnoreDatabaseFields;		
			$aFields = WDCache::get($sCacheKey);
		}
			
		if($aFields === null) {
			$this->_configure();
			
			if($this->bCacheMapping) {
				WDCache::set($sCacheKey, 86400, $this->_aFields);
			}
		} else {
			$this->_aFields = array_merge($this->_aFields, $aFields);
		}		
	}

	/**
	 * Generiert die Mapping-Objekte für die Mappingklasse
	 * - hier ist ein Cache von 24h eingebaut da sich die Mappinginformationen selten ändern
	 */
	protected function createUsedFieldObjects() {
		$sCacheKey = 'tc_frontend_mapping_used_fields_' . get_class($this) . '_' . \Ext_TC_System::getInterfaceLanguage().'_'.(int) $this->bIgnoreDatabaseFields;		
		$aFields = WDCache::get($sCacheKey);		

		if(
			$aFields === null &&
			$this->bIgnoreDatabaseFields === false
		) {			
			$oWDBasic = new $this->_sWDBasic();
			$aTableFields = (array)$oWDBasic->getTableFields();
			$sTableName = $oWDBasic->getTableName();

			if(isset($aTableFields[$sTableName])) {
				$aTableFields = (array)$aTableFields[$sTableName];
			}

			foreach($this->_aUsedFields as $sField){
				foreach($aTableFields as $sColumn => $aInfo){
					if($sColumn == $sField){
						// Feld erzeugen
						$oField = $this->createField($aInfo);
						$this->addField($sColumn, $oField);
					}
				}
			}
			
			$aFields = $this->_aFields;			
			
			WDCache::set($sCacheKey, 86400, $aFields);
		} else {
			
			if($aFields === null) {
				$aFields = [];
			}
			
			$this->_aFields = array_merge($this->_aFields, $aFields);
		}
		
	}

	protected function _configure() {
		
	}
	
	/**
	 * Mapping-Feld Objekte für die einzelnen Spalten
	 * @param array $aConfig
	 * @return Ext_TC_Frontend_Mapping_Field 
	 */
	public function createField($aConfig, $bOriginal=false)
	{
		$oField = new Ext_TC_Frontend_Mapping_Field($aConfig, $bOriginal);
		
		$sType = $oField->getConfig('type');

		$aAllowedInputTypes = array();

		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		switch ($sType) {
			case 'integer':
			case 'enum':
				$aAllowedInputTypes['select']	= $aTypes['select'];
				break;
			case 'text':
				$aAllowedInputTypes['input']	= $aTypes['input'];
				$aAllowedInputTypes['textarea'] = $aTypes['textarea'];
				break;
			case 'string':
			case 'time':
			case 'char':
				$aAllowedInputTypes['input']	= $aTypes['input'];
				break;
			case 'date':
			case 'timestamp':
				$aAllowedInputTypes['date']		= $aTypes['date'];
				$aAllowedInputTypes['input']	= $aTypes['input'];
				break;
			default:
				break;
		}

		$oField->addConfig('allowed_input_types', $aAllowedInputTypes);
		
		return $oField;
	}

}
