<?php

class Ext_TC_Placeholder_Helper_Flexible_Fields extends Ext_TC_Placeholder_Helper_Flexible_Abstract {
	
	/**
	 * Sections, für die die Platzhalter gesucht werden sollen
	 * @var array 
	 */
	public $aSections = array();
	
	/**
	 * enthält für die sections die WDBasic-Klassen
	 * @var array 
	 */
	protected $_aSectionAllocations = array();

	/**
	 * Section aus dem GUI-Designer, falls gesetzt (inquiry/enquiry/students)
	 * @var string 
	 */
	public $sGuiDesignerSectionKey;
	
	/**
	 * Daten, die für die Sections eingetragen wurden
	 * @var array 
	 */
	protected static $_aSectionData = array();
	
	/**
	 * alle Felder der Sections, für die Platzhalter definiert wurden
	 * @var array 
	 */
	protected $_aSectionFields = array();

	/**
	 *
	 * @var WDBasic
	 */
	public $oWDClass;
	
	/**
	 * Konstruktor
	 * @param SmartyWrapper $oSmarty
	 */
	public function __construct($oSmarty) {
		parent::__construct($oSmarty);
		
		$aSectionAllocation = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getSectionAllocations');
		$this->_aSectionAllocations = $aSectionAllocation;
	}

	/**
	 * liefert alle Platzhalter für die angegebenen Sections
	 * @return array
	 */
	public function getPlaceholder() {

		if(empty($this->aSections)) {
			return array();
		}

		$aReturn = array();		
		foreach($this->aSections as $sSection) {

			$sWDBasic = $this->_getWDBasicAllocation($sSection);

			if(empty($this->_aPlaceholderCache[$sSection][(string)$this->sGuiDesignerSectionKey])) {
				$this->_prepareFlexibleFieldsPlaceholder($sSection);
			}

			if(
				!empty($sWDBasic) &&
				!empty($this->_aPlaceholderCache[$sSection][(string)$this->sGuiDesignerSectionKey])
			) {				
				if(!empty($aReturn[$sWDBasic])) {

					foreach($this->_aPlaceholderCache[$sSection][(string)$this->sGuiDesignerSectionKey] as $aData) {
						$aReturn[$sWDBasic][] = $aData;
					}
					
				} else {
					$aReturn[$sWDBasic] = $this->_aPlaceholderCache[$sSection][(string)$this->sGuiDesignerSectionKey];
				}
			}

		}		

		return $aReturn;
	}
	
	/**
	 * holt alle Platzhalter für die übergebenen Sections
	 * @param string $sSection
	 */
	protected function _prepareFlexibleFieldsPlaceholder($sSection) {

		$aFields = $this->_getSectionFields($sSection, $this->sGuiDesignerSectionKey);

		if(!empty($aFields)) {
			
			foreach($aFields as $oField) {

				$aAttribute = $this->_createPlaceholderAttribute($oField);
				
				$sPlaceholder = $this->_preparePlaceholder($oField->placeholder);
				
				$aTempPlaceholder = array(
					$sPlaceholder => $aAttribute
				);
				
				$this->_aPlaceholderCache[$sSection][(string)$this->sGuiDesignerSectionKey][] = $aTempPlaceholder;		

				// Element Smarty zuweisen
				if($this->bAssignData === true) {	
					$this->_assignVariable('oFlexibleField'.$oField->id, $oField);
				}
				
			}
		}
	}

	/**
	 * Objekt des Feldes
	 * @param int $iGuiDesign
	 * @return Ext_TC_Flexibility
	 */
	protected function _getFieldObject($iFieldId) {
		$oField = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getInstance', array($iFieldId));
		return $oField;
	}
	
	/**
	 * liefert die WDBasic-Klasse für die section
	 * @param string $sSection
	 * @return string
	 */
	protected function _getWDBasicAllocation($sSection) {
		
		$aSectionData = $this->_getSectionData($sSection);

		$sSectionCategory = $aSectionData['category'];

		$sWDBasic = $this->_aSectionAllocations[$sSectionCategory];
		
		if($sWDBasic == 'GUI_SPECIAL') {
			$sWDBasic = get_class($this->oWDClass);
		}
		
		return $sWDBasic;
	}

	/**
	 * Daten von der Section
	 * @param string $sSection
	 * @return array
	 */
	protected function _getSectionData($sSection) {
		
		if(empty(self::$_aSectionData[$sSection])) {
			$sSql = "
				SELECT 
					*
				FROM
					`tc_flex_sections`
				WHERE
					`type` = :type AND
					`active` = 1
				LIMIT 1
			";

			$aSql = array('type' => $sSection);

			$aData = (array) DB::getQueryData($sSql, $aSql);
			
			self::$_aSectionData[$sSection] = reset($aData);			
		}		
		
		return self::$_aSectionData[$sSection];
	}
	
	/**
	 * liefert alle Felder, für die Platzhalter definiert wurden
	 * @param string $sSection
	 * @return array
	 */
	protected function _getSectionFields($sSection, $sGuiDesignerSectionKey) {

		if(empty($this->_aSectionFields[$sSection][(string)$sGuiDesignerSectionKey])) {

			$aFields = Ext_TC_Flexibility::getFields($sSection, true);

			if(!empty($sGuiDesignerSectionKey)) {
				
				$aUsageSectionMapping = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getFieldUsageSectionMapping');

				foreach($aFields as $iField => $oField) {					
					if(isset($aUsageSectionMapping[$oField->usage])) {
						
						$aMapping = $aUsageSectionMapping[$oField->usage];
						// Wenn die Section des GUI Designers nicht in dem Mapping des Usage auftaucht muss dieses Feld entfernt werden 
						if(!in_array($sGuiDesignerSectionKey, $aMapping)) {
							unset($aFields[$iField]);
						}
					}
				}
			}
			
			$this->_aSectionFields[$sSection][(string)$sGuiDesignerSectionKey] = $aFields;

		}

		return $this->_aSectionFields[$sSection][(string)$sGuiDesignerSectionKey];
	}


	/**
	 * baut ein Array auf, welches nachher von der Platzhalterklasse verarbeitet wird
	 * @param Ext_TC_Flexibility $oField
	 * @return array
	 */
	protected function _createPlaceholderAttribute(Ext_TC_Flexibility $oField) {

		$aAttribute = [
			'type'				=> 'flexible_field',
			'label'				=> $oField->description,
			'element_id'		=> (int) $oField->id,
			'translate_label'	=> false
		];

		if($oField->isRepeatableContainer()) {

			$aAttribute['type'] = 'loop';
			$aAttribute['loop'] = 'flex_field_childs';
			// source wird immer benötigt, sonst gibt es eine Exception
			$aAttribute['source'] = $oField->getId();
			$aAttribute['variable_name'] = 'oFlexContainer'.$oField->getId();

		}

		return $aAttribute;
	}
	
}
