<?php

class Ext_TC_Frontend_Form {
	
	/**
	 * @var Ext_TC_Frontend_Mapping
	 */
	protected $_oMapping;
	
	/**
	 * the Entity (WDBasic or other Entity Object)
	 * @var Ext_TC_Frontend_Form_Entity_Interface 
	 */
	protected $_oEntity;

	/**
	 * @var Ext_TC_Frontend_Template 
	 */
	protected $_oTemplate;
	
	/**
	 * @var Ext_TC_Frontend_Combination 
	 */
	protected $_oCombination;
	/**
	 *
	 * @var Ext_TC_Frontend_Form_FlexValues 
	 */
	protected $_oFlexValues;
	
	/**
	 * the type of the Mapping
	 * @var string 
	 */
	protected $_sMappingType	= 'frontend_form';
	
	protected $_sNamePrefix		= 'save';
	
	protected $_sFormGroupIdentifier = '';
	
	protected $_sSuffix = '';

	protected $_bPermanentForm = false;

	/**
	 * alle benutzte kind forms
	 * @var Ext_TC_Frontend_Form <array>
	 */
	protected $_aActiveForms	= array();
	
	protected $_aActiveFields	= array();

	protected $_aActiveFieldsFlag = [];
	
	protected $_aUnusedMarkedFields = [];
	
	/**
	 * Fehlerstrings
	 * @var array 
	 */
	protected $_aErrors			= array();
	
	/**
	 * Caching Array for the Field objects
	 * @var array 
	 */
	protected $_aFieldCache		= array();
	
	protected $_iFormCount		= 1;
	
	/**
	 * Flags for selections
	 * 
	 * @var array
	 */
	protected $_aSelectionFlags = array();

	/**
	 * @var Ext_TC_Frontend_Form 
	 */
	protected $_oParent			= null;

	/**
	 * @var Ext_TC_Frontend_Form 
	 */
	protected $_oFirstParent	= null;
	
	protected $_sGuiDesignerSection = '';
	protected $_iGuiDesignerObject	= 0;
	protected $_bSaved				= false;
	protected $_bRequired			= false;
	
	protected $sFormKey;

	public function __construct(Ext_TC_Frontend_Template &$oTemplate, Ext_TC_Frontend_Form_Entity_Interface &$oEntity, $sMappingType = 'frontend_form', $oParentForm = null) {
		$this->_oTemplate		= $oTemplate;
		$this->_oEntity			= $oEntity;
		$this->_oMapping		= $this->getEntityMapping($sMappingType);				
		$this->_sMappingType	= $sMappingType;
		$this->_oParent			= $oParentForm;
		
		if($oParentForm === null){
			// self::clearSession();
		}
		
		$this->_oFlexValues = Ext_TC_Factory::getObject('Ext_TC_Frontend_Form_FlexValues');
	}	

	/**
	 * get the parent form if exist
	 * @return Ext_TC_Frontend_Form|boolean
	 */
	public function getParent(){
		if($this->_oParent){
			return $this->_oParent;
		}
		return false;
	}
	
	public function setPermanent() {
		$this->_bPermanentForm = true;
	}
	
	public function isPermanent() {
		return (bool) $this->_bPermanentForm;
	}
	
	/**
	 * set the Frontend Combination
	 * @param Ext_TC_Frontend_Combination $oCombination 
	 */
	public function setCombination($oCombination){
		if($oCombination){
			$this->_oCombination = $oCombination;
		}
	}
	
	/**
	 * get the frontend combination
	 * @return Ext_TC_Frontend_Combination 
	 */
	public function getCombination(){
		return $this->_oCombination;
	}
	
	/**
	 * @return string|null
	 */
	public function getInterfaceLanguage() {
		
		if($this->_oCombination instanceof Ext_TC_Frontend_Combination) {
			return $this->_oCombination->getLanguage();
		}
		
		return null;
	}

	public function getL10N() {
		return new \Tc\Service\Language\Frontend($this->getInterfaceLanguage());
	}

	/**
	 * set the prefix for the name attributes
	 * @param type $sPrefix 
	 */
	public function setNamePrefix($sPrefix){
		$this->_sNamePrefix = $sPrefix;
	}
	
	/**
	 * set the name of the form group
	 * @param type $sPrefix 
	 */
	public function setGroupNamePrefix($sPrefix){		
		$this->_sFormGroupIdentifier = $sPrefix;
	}
	
	/**
	 * Setzt den Suffix für das Form-Objekt. Wichtig für Kontakt-Details
	 * 
	 * @param string $sSuffix
	 */
	public function setSuffix($sSuffix) {
		$this->_sSuffix = $sSuffix;
	}
	
	/**
	 * Liefert den Suffix des Form-Objektes
	 * 
	 * @return string
	 */
	public function getSuffix() {
		return $this->_sSuffix;
	}
	
	/**
	 * Prüft, ob für das Form-Objekt ein Suffix gesetzt wurde
	 * 
	 * @return boolean
	 */
	public function hasSuffix() {		
		$sSuffix = $this->getSuffix();
		
		if(!empty($sSuffix)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Liefert das Mapping für die Entität des Formulars
	 * 
	 * @param string $sMappingType
	 * @return Ext_TC_Frontend_Mapping
	 */
	protected function getEntityMapping($sMappingType) {
		return $this->_oEntity->getMapping($sMappingType);
	}
	
	/**
	 * 
	 * 
	 * @return string
	 */
	public function displaySuffixField() {
		if($this->hasSuffix()) {
			$oFormField = new Ext_TC_Frontend_Form_Field_Suffix($this);
			return $oFormField->getInput();
		}
		
		return '';
	}
	
	/**
	 * speichert die Werte von individuellen Feldern vom Typ "Kontakt bezogen auf Buchung" in ein
	 * Objekt, da diese erst nach dem Speichern des Entity-Objekts gespeichert werden können 
	 * 
	 * @param Ext_TC_Basic $oEntity
	 * @param mixed $mValue
	 */
	public function setFlexValue(Ext_TC_Basic $oEntity, Ext_TC_Gui2_Design_Tab_Element $oTabElement, $mValue, $oParentEntity = null) {
		$this->_oFlexValues->set($oEntity, $oTabElement, $mValue, $oParentEntity);			
	}
	
	/**
	 * Liefert die Werte von individuellen Feldern vom Typ "Kontakt bezogen auf Buchung"
	 * 
	 * @param Ext_TC_Gui2_Design_Tab_Element $oTabElement
	 * @return mixed
	 */
	public function getFlexValue(Ext_TC_Gui2_Design_Tab_Element $oTabElement) {
		$mValue = $this->_oFlexValues->getValue($this->_oEntity, $oTabElement);				
		return $mValue;
	}
	
	/**
	 * get the name prefix
	 * @return sting 
	 */
	public function getNamePrefix(){
		return $this->_sNamePrefix;
	}
	
	/**
	 * @return Ext_TC_Frontend_Template 
	 */
	public function getTemplate(){
		return $this->_oTemplate;
	}
		
	/**
	 * set the information for GUi Design Fields
	 */
	public function setGuiDesignerInfo($sSection, $iObject){
		$this->_sGuiDesignerSection = $sSection;
		$this->_iGuiDesignerObject	= $iObject;
	}
	
	/**
	 * get the gui design field section
	 * @return string 
	 */
	public function getGuiDesignerSection(){
		return $this->_sGuiDesignerSection;
	}
	
	/**
	 * get the gui design field object id
	 * @return string 
	 */
	public function getGuiDesignerObject(){
		return $this->_iGuiDesignerObject;
	}
	
	public static function clearSession(){
		
	}
	
	/**
	 * get the Form Field Object
	 * @param string $sIdentifier 
	 * @return Ext_TC_Frontend_Form_Field_Abstract
	 */
	public function getField($sIdentifier, $bForceCreate = false){

		$oFormField = false;
		// wenn Mapping vorhanden
		if($this->_oMapping){
			
			$aActiveFields = array();

			if(!$bForceCreate){
				$aActiveFields = $this->_getActiveFields();
			}
		
			if(empty($aActiveFields[$sIdentifier])){
				$oFormField = $this->_getFormField($sIdentifier);
				$this->setActiveFieldFlag($this->_sFormGroupIdentifier, $sIdentifier);
				
				$this->_aActiveFields[$sIdentifier] = $oFormField;
			} else {
				$oFormField = $aActiveFields[$sIdentifier];
			}
			
		} else {
			throw new Exception('No Mapping Information');
		}
	
		//return
		return $oFormField;
	}
	
	/**
	 * check the priority ( position ) of the Fields
	 * @param Ext_TC_Frontend_Form_Field_Abstract $oFieldA
	 * @param Ext_TC_Frontend_Form_Field_Abstract $oFieldB
	 * @return Ext_TC_Frontend_Form_Field_Abstract
	 */
	public function checkFieldPriority($oFieldA, $oFieldB, $oCurrentField){
				
		if(
			$oFieldA instanceof Ext_TC_Frontend_Form_Field_Abstract &&
			$oFieldB instanceof Ext_TC_Frontend_Form_Field_Abstract &&
			$oCurrentField instanceof Ext_TC_Frontend_Form_Field_Abstract
		){
			
			$iA			= $oFieldA->getPosition();
			$iB			= $oFieldB->getPosition();
			$iCurrent	= $oCurrentField->getPosition();

			// das späteste was vor dem aktuellen liegt
			if(
				$iA < $iCurrent &&
				(
					$iA > $iB ||
					$iB > $iCurrent
				)	
			){
				return $oFieldA;
			} else if(
				$iB < $iCurrent &&
				(
					$iB > $iA ||
					$iA > $iCurrent
				)
			){

				return $oFieldB;
			}
			// Sonst ist das aktuelle am größten
			return false;
			
		} else if($oFieldA){
			return $oFieldA;
		} else if($oFieldB){
			return $oFieldB;
		}
		
		return false;
	}


	/**
	 * check if the form has an active field with the given Identifier
	 * @param string $sIdentifier
	 * @return boolean 
	 */
	public function hasActiveField($sIdentifier){
		
		$aActiveFields = $this->_getActiveFields();
		
		if(!empty($aActiveFields[$sIdentifier])){
			return true;
		}
		return false;
	}
	
	/**
	 * check if the form has an active field with the given "Mapping" Identifier
	 * @param string $sMappingIdentifier
	 * @return boolean 
	 */
	public function hasActiveMappingField($sMappingIdentifier){
		
		$oField = $this->getActiveFieldByEntityFieldName($sMappingIdentifier);
		
		if($oField){
			return true;
		}
		
		return false;
	}
	
	/**
	 * check if the form has an active field with the given "Mapping" Identifier
	 * @param string $sEntityFieldName
	 * @return Ext_TC_Frontend_Form_Field_Abstract 
	 */
	public function getActiveFieldByEntityFieldName($sEntityFieldName){
		
		// Falls alias mit angegeben, rausfiltern da der allias pro form einmalig ist
		$sEntityFieldName = explode('.', $sEntityFieldName);
		$sEntityFieldName = reset($sEntityFieldName);
		
		
		$aActiveFields = $this->_getActiveFields();

		foreach($aActiveFields as $sIdentifier => &$oFormField){
			$oTemplateField = $oFormField->getTemplate();
			if($oTemplateField->getEntityFieldName() == $sEntityFieldName){
				return $oFormField;
			}
		}
		
		return false;
	}

	/**
	 * reset all error messages 
	 */
	public function resetAllErrors(){
		$this->_aErrors = array();
		
		$aActiveFields = $this->_getActiveFields();
		
		foreach ($aActiveFields as $oField) {
			$oField->resetErrors();
		}
		foreach ($this->_aActiveForms as $oForm) {
			if(is_object($oForm)){
				$oForm->resetAllErrors();
			}
		}
	}
	
	public function setRequired(){
		$this->_bRequired = true;
	}
	
	public function isRequired(){
		return $this->_bRequired;
	}

	public function addError(string $sError, string $sErrorMessage) {
		$this->_aErrors[$sError] = $sErrorMessage;
	}

	/**
	 * get all Errors of the Form (and form childs) and (optional) of all Fields of the forms
	 * @param bool $bIncludeFieldErrors
	 * @return array 
	 */
	public function getErrors($bIncludeFieldErrors = false){
		// Fehler holen
		$aErrors = $this->_aErrors;
		
		// wenn auch feld fehler
		if($bIncludeFieldErrors){
			
			$aActiveFields = $this->_getActiveFields();
			
			// dann felder durchgehen
			foreach($aActiveFields as &$oField){
				/* @var Ext_TC_Frontend_Form_Field_Abstract $oField */
				// Fehler holen
				$aFieldErrors = $oField->getErrors();
				$aFieldErrors = array_values($aFieldErrors);
				// mergen
				$aErrors = array_merge($aErrors, $aFieldErrors);
			}
		}
		
		// Kindformulare durchgehen
		foreach($this->_aActiveForms as &$oForm){
			
			if(is_object($oForm)){
				// Fehler des Kind Formulars holen
				$aSubFormErrors = $oForm->getErrors($bIncludeFieldErrors);
				$aSubFormErrors = array_values($aSubFormErrors);
				// mergen
				$aErrors = array_merge($aErrors, $aSubFormErrors);
			}
		}
		
		// zurückgeben
		return $aErrors;
	}
	
	/**
	 * get all forms
	 * @param string $sChildIdentifier
	 * @param boolean $bCreateIfNotExist
	 * @return Ext_TC_Frontend_Form 
	 */
	public function getForms($sChildIdentifier, $bCreateIfNotExist = false){
		
		$aForms = array();
		
		foreach($this->_aActiveForms as $sIdentifier => &$oForm){
			if(is_object($oForm)){
				$aIdentifier = explode('_', $sIdentifier);
				$sIdentifier = $aIdentifier[0];
				if($sIdentifier == $sChildIdentifier){
					if(isset($aIdentifier[1])) {
						$aForms[$aIdentifier[1]] = $oForm;
					} else {
						$aForms[] = $oForm;
					}
				}
			}
		}
		
		if(
			$bCreateIfNotExist &&
			empty($aForms)
		){
			$aForms[] = $this->addForm($sChildIdentifier);
		}
		
		return $aForms;
	}


	/**
	 * get all Child Forms for the given Identifier
	 * @param string $sChildIdentifier
	 * @return Ext_TC_Frontend_Form <array> 
	 */
	public function addForm($sChildIdentifier, $iCount = 1, $sSuffix = ''){
		
		$oForm = $this->getForm($sChildIdentifier, $iCount);		

		if(!$oForm){
			$iKey = ($iCount * -1) + 1;
			$oChild = $this->_oEntity->addChild($sChildIdentifier, $iKey);
			
			if($oChild) {	
				$oForm = $this->addChildEntity($oChild, $sChildIdentifier, $iCount, $sSuffix);			
			}
		}
		
		return $oForm;
	}
		
	/**
	 * @param Ext_TC_Basic $oEntity
	 * @param string $sChildIdentifier
	 * @param int $iCount
	 * @param string $sSuffix
	 * @return Ext_TC_Frontend_Form
	 */
	public function addChildEntity(Ext_TC_Basic $oEntity, $sChildIdentifier, $iCount, $sSuffix = '') {
		$this->_iFormCount = $iCount;
		$oForm = $this->generateChildForm($oEntity);

		if(is_object($oForm)) {
					
			// Eindeitiger String für eine "Gruppe" von Formularen z.b alle Journey Courses etc...
			// wird für die active field benötigt da diese übergreifend sein müssen
			$sCurrentPrefix = $this->_sFormGroupIdentifier;
			if(!empty($sCurrentPrefix)){
				$sCurrentPrefix .= '_';
			}
			// suffix ist nötig damit bei z.b contact details nicht bei phone_mobile das phone_private feld geprüft wird
			// aActiveFields darf in diesen Fällen nicht pro "form" gruppiert werden
			$sCurrentPrefix .= $sChildIdentifier.$sSuffix;//.'_'.$iCount;
			$oForm->setGroupNamePrefix($sCurrentPrefix);

			// create prefix of child form
			$sChildPrefix = $this->_sNamePrefix.'['.$sChildIdentifier.']['.$iCount.']';
			$oForm->setNamePrefix($sChildPrefix);

			if(!empty($sSuffix)) {
				$oForm->setSuffix($sSuffix);
			}

			$sFormKey = $sChildIdentifier.'_'.$iCount;
			$oForm->setFormKey($sFormKey);
			$this->_aActiveForms[$sFormKey] = $oForm;					
		}
		
		return $oForm;
	}
	
	public function setFormKey($sFormKey) {
		$this->sFormKey = $sFormKey;
	}
	
	public function getFormCount(){
		return $this->_iFormCount;
	}
	
	public function removeForm($sIdentifier, $mCount){

		$sChildIdentifier = $sIdentifier.'_'.$mCount;

		$oForm = $this->_aActiveForms[$sChildIdentifier];
				
		if(
			$oForm instanceof self &&
			$oForm->isPermanent()
		) {
			return;
		}
		
		unset($this->_aActiveForms[$sChildIdentifier]);	
				
		if(is_numeric($mCount)){
			$mCount = ($mCount * -1) + 1; // dmait es bei "0" anfängt
		}

		try {
			$this->_oEntity->removeChild($sIdentifier, $mCount);
		} catch (Exception $exc) {

		}
	}

	/**
	 * Generiert ein Formular für ein Childobjekt
	 * 
	 * @param WDBasic $oChild
	 * @return Ext_TC_Frontend_Form
	 */
	protected function generateChildForm(&$oChild) {		
		$oForm = Ext_TC_Factory::getObject('Ext_TC_Frontend_Form', array(&$this->_oTemplate, &$oChild, $this->_sMappingType, $this));
		$oForm->setCombination($this->_oCombination);
		return $oForm;
	}

	/**
	 * get a existing Form object
	 * @param string $sChildIdentifier
	 * @return Ext_TC_Frontend_Form 
	 */
	public function getForm($sChildIdentifier, $iCount = 1){

		$sChildIdentifier = $sChildIdentifier.'_'.$iCount;

		$oForm = $this->_aActiveForms[$sChildIdentifier];
		if($oForm){
			// Sicher gehen das die Kombination da ist
			$oForm->setCombination($this->_oCombination);
			return $oForm;
		}	
		
		return false;
	}
	
	/**
	 * @param string $sChildIdentifier
	 * @param int $iCount
	 * @return bool
	 */
	public function hasForm($sChildIdentifier, $iCount = 1) {
		return ($this->getForm($sChildIdentifier, $iCount) instanceof self);
	}
	/**
	 * @param string $sChildIdentifier
	 * @param string $sSuffix
	 * @return bool
	 */
	public function getFormWithSuffix($sChildIdentifier, $sSuffix) {
		
		foreach($this->_aActiveForms as $sChildIdentifierCount => $oChildForm) {			
			if(strpos($sChildIdentifierCount, $sChildIdentifier) === false) {
				continue;
			} else if($oChildForm->getSuffix() === $sSuffix) {
				return $oChildForm;
			}			
		}
		
		return false;
	}
	
	/**
	 * @param string $sChildIdentifier
	 * @param string $sSuffix
	 * @return bool
	 */
	public function hasFormWithSuffix($sChildIdentifier, $sSuffix) {
		return ($this->getFormWithSuffix($sChildIdentifier, $sSuffix) instanceof self);
	}

	/**
	 * check if the form exist
	 * @param string $sChildIdentifier
	 * @return boolean
	 */
	public function hasActiveForm($sChildIdentifier, $iCount = 1){
		//$sChildIdentifier = $sChildIdentifier.'_'.$iCount;
		$oForm = $this->getForm($sChildIdentifier, $iCount);
		if($oForm){
			return true;
		}
		return false;
	}

	/**
	 * check for Errors
	 * @param boolean $bIncludeFieldErrors
	 * @return boolean 
	 */
	public function hasError($bIncludeFieldErrors = false){
		// Fehler holen
		$aErrors = $this->getErrors($bIncludeFieldErrors);
		// prüfen
		if(empty($aErrors)){
			return false;
		}
		return true;
	}
	
	/**
	 * Validate the form, if the Parameter $bOnlyExistingValues was true it will be only check the field who was currently used
	 * @param boolean $bUnsetEmptyForms
	 * @param boolean $bCheckRequired
	 * @return boolean 
	 */
	public function validate($bUnsetEmptyForms = true, $bCheckRequired = true, $bResetInvalidValues = false){

		// Entity holen
		$oEntity		= $this->_getEntity();
		
		$bValidate		= $this->_validateActiveFields($bCheckRequired); 

		// Kinder Validieren
		$bChildValidate = $this->_validateChilds($bUnsetEmptyForms, $bResetInvalidValues);

		// object validieren
		$mEntityValidate = $oEntity->validate(false);
		
		$bEntityValidate = true;
		
		$oLanguage = $this->getL10N();
		
		// Wenn das validieren fehler wirft
		if(
			(
				!empty($mEntityValidate) &&
				is_array($mEntityValidate)
			) ||
			$mEntityValidate === false
		) {
			
			$bEntityValidate = $bResetInvalidValues;

			// Wenn es ein Array mit Fehler ist
			if(is_array($mEntityValidate)) {
				
				// Fehler durchegehen (format entspricht der wdbasic validate)
				foreach($mEntityValidate as $sAlias => $aErrors){

					// Bei "." exploden
					$aTemp			= explode('.', $sAlias);
					// Letzter Eintrag enspricht der DB Column
					$sEntityColumn	= array_pop($aTemp);
					// Forumularfeld suchen
					$oField			= $this->getActiveFieldByEntityFieldName($sEntityColumn);			
						
					if($bResetInvalidValues === true) {
						// Wenn man zum Beispiel eine Seite zurückspringt soll keine Fehlermeldung kommen.
						// Der Wert wird zurückgesetzt
						if($oField) {
							$oField->setValue(null);
							$oField->setEntityValue();
						}
						
					}else {
						foreach((array)$aErrors as $sError){

							// Fehler Übersetzung suchen
							$sErrorMessage = Ext_Gui2_Data::convertErrorKeyToMessage($sError);
							$sErrorMessage = $oLanguage->translate($sErrorMessage);

							// wenn es das feld gibt wird der fehler diesem object zu geordnet
							if($oField){

								$sErrorMessage = str_replace('%s', $oField->getLabel(), $sErrorMessage);

								$oField->addError($sErrorMessage, $sError);
							} else {
								$this->addError($sError, $sErrorMessage);
							}
 						}
					}
				}
			}
		}
		// Wenn alle Validierungen erfolgreich waren
		if(
			$bValidate &&
			$bChildValidate &&
			$bEntityValidate
		){
			return true;
		}

		//fehlschlag
		return false;
		
	}
	
	/**
	 * save the complete Form 
	 */
	public function save() {

		// Wenn ok
		if(!$this->_bSaved) {

			// speichern
			$this->_oEntity->saveParents();
			$this->_oEntity->save();	
			$this->_bSaved = true;

			// Index aktualisieren		
			//Ext_Gui2_Index_Stack::executeCache();

			return true;
		}

		return false;
	}
	
	public function updateUnusedTemplateFieldStatus() {

		if(empty($this->_aUnusedMarkedFields)) {
			return true;
		}
		
		array_walk($this->_aUnusedMarkedFields, function($iTemplateField) {
			$oTemplateField = Ext_TC_Frontend_Template_Field::getInstance($iTemplateField);
			$oTemplateField->updateUsedStatus();
		});
		
	}
	
	/**
	 * speichert die individuellen Felder vom Typ "Kontakt bezogen auf Buchung/Anfrage" zu dem richtigen Objekt ab
	 */
	public function saveFlexValues() {
		$this->_oFlexValues->save($this->_oEntity);
	}
	
	/**
	 * Liefert das Hilfsobjekt für die individuellen Felder vom Typ "Kontakt bezogen auf Buchung/Anfrage"
	 * 
	 * @return Ext_TC_Frontend_Form_FlexValues
	 */
	public function getFlexValueObject() {
		return $this->_oFlexValues;
	}
	
	/**
	 * get the WDBasic object from the Mapping
	 * @return Ext_TC_Basic
	 */
	public function getEntity(){
		return $this->_oEntity;
	}

	
	/**
	 * search the first parent form ( inquiry/enquiry form )
	 * @return Ext_TC_Frontend_Form 
	 */
	public function searchFirstParent(){
		
		if(is_null($this->_oFirstParent))
		{
			$oParentForm	= $this->getParent();

			if($oParentForm){
				$oParentForm->searchFirstParent();
			} else {
				$oParentForm = $this;
			}

			$this->_oFirstParent = $oParentForm;
		}

		return $this->_oFirstParent;
	}
	
	public function setTemplateField($oField, $sIdentifier){
		$this->_aFieldCache['template'][$sIdentifier] = $oField;
	}
	
	/**
	 * check if all Field are empty 
	 */
	public function checkAllFieldEmpty(){
				
		$bEmpty = true;		
		$aActiveFields = $this->_getActiveFields();

		if(empty($aActiveFields)) {
			return false;
		}
		
		foreach ($aActiveFields as $oField) {
			$mValue = $oField->getValue(false);
			if(
				(
					!empty($mValue) &&
					$mValue != '0000-00-00'
				) ||
				$oField->isRequired()
			){
				$bEmpty = false;
			}
		}
				
		return $bEmpty;
	}
	
	/**
	 * Set the flag into cache array
	 * 
	 * @param string $sFlag 
	 */
	public function setSelectionFlag($sFlag)
	{
		$this->_aSelectionFlags[$sFlag] = true;
	}

	/**
	 * Check the flag in cache array
	 * 
	 * @param string $sFlag 
	 * @return bool
	 */
	public function hasSelectionFlag($sFlag)
	{
		if(isset($this->_aSelectionFlags[$sFlag]))
		{
			return true;
		}

		return false;
	}

	/**
	 * Alle Felder auf der aktuellen Seite
	 *
	 * @return Ext_TC_Frontend_Form_Field_Abstract[]
	 */
	public function getAllActiveFields(): array {

		$aActiveFields = $this->_getActiveFields();

		foreach($this->_aActiveForms as $oChildForm) {
			$aActiveFields = array_merge($aActiveFields, $oChildForm->_getActiveFields());
		}

		return $aActiveFields;
	}

	/**
	 * gibt alle Aktiven Felder zurück
	 * hier wird zuerst allgemein geschaut ob die "Gruppe" felder hat
	 * und je feld wird dann speziell zum aktuellen formular geschaut ob es das feld schon gibt
	 * wenn nicht wird es angelegt. Dies kann der fall sein wenn das Formular komplett leer war und dadurch gelöscht wurde!
	 * beim neu hinzufügen des Formulares wären die Felder nicht bekannt wenn wir das nicht über die "Gruppe" merken wurden
	 * da die felder durch das löschen jedoch weg sind müssen wir sie ggf. neu erzeugen
	 * @return Ext_TC_Frontend_Form_Field_Abstract[]
	 */
	public function _getActiveFields(){
		$aActiveFieldFlags = $this->getActiveFieldFlags($this->_sFormGroupIdentifier);		
		$aActiveFields = (array)$aActiveFieldFlags['fields'];

		$aFields		= array();
		foreach($aActiveFields as $sIdentifier => $bCheck){
			if($bCheck){
				
				$oField = $this->_aActiveFields[$sIdentifier];
				
				if($oField){
					$aFields[$sIdentifier] = $oField;
				} else {
                    try {
                        $oField = $this->getField($sIdentifier, true);
                    } catch (Exception $exc) {

                    }
                    if($oField) {
                        $aFields[$sIdentifier] = $oField;
					}
				}
			}
		}
		return $aFields;
	}
	
	/**
	 * @param string $sFormGroupIdentifier
	 * @param string $sIdentifier
	 * @param bool $bCheckParent
	 */
	public function setActiveFieldFlag($sFormGroupIdentifier, $sIdentifier, $bCheckParent = true){
		if(
			$bCheckParent === true &&
			$this->_oParent instanceof Ext_TC_Frontend_Form
		) {
			$this->_oParent->setActiveFieldFlag($sFormGroupIdentifier, $sIdentifier, false);
		} else {
			$this->setActiveFieldFlagToProperty($sFormGroupIdentifier, $sIdentifier);
		}
	}

	/**
	 * @param string $sFormGroupIdentifier
	 * @return array
	 */
	public function getActiveFieldFlags($sFormGroupIdentifier){
		
		$aActiveFieldFlags = [];
		
		if(isset($this->_aActiveFieldsFlag[$sFormGroupIdentifier])) {
			$aActiveFieldFlags = $this->getActiveFieldFlagsFromProperty($sFormGroupIdentifier);
		} elseif($this->_oParent instanceof Ext_TC_Frontend_Form) {
			$aActiveFieldFlags = $this->_oParent->getActiveFieldFlags($sFormGroupIdentifier);
		}
		
		return $aActiveFieldFlags;
	}
	
	/**
	 * @param string $sFormGroupIdentifier
	 * @return array
	 */
	protected function getActiveFieldFlagsFromProperty($sFormGroupIdentifier) {
		return $this->_aActiveFieldsFlag[$sFormGroupIdentifier];
	}
	
	/**
	 * @param string $sFormGroupIdentifier
	 * @param string $sIdentifier
	 */
	protected function setActiveFieldFlagToProperty($sFormGroupIdentifier, $sIdentifier) {
		$this->_aActiveFieldsFlag[$sFormGroupIdentifier]['fields'][$sIdentifier] = true;
	}
	
	/**
	 * set the Value of all Active Fields
	 * @param Ext_TC_Frontend_Form_Entity_Interface $oEntity 
	 */
	protected function _setActiveFieldValues(){

		$oEntity = $this->_oEntity;
		
		
		$aActiveFields = $this->_getActiveFields();

		// Aktive Felder durchgehen und setzen/validieren
		foreach($aActiveFields as $oField){
			
			//Entity Feld name holen
			$sField = $oField->getEntityFieldName();
			$mValue = $oField->getValue(false);
			// Sonst wert setzten
			$oEntity->$sField = $mValue;
			
		}
		
	}
	
	/**
	 * validiert alle aktiven felder
	 * @return boolean 
	 */
	protected function _validateActiveFields($bCheckRequired = true){
		 
		$bValidate = true;
		
		$aActiveFields = $this->_getActiveFields();
		
		$oLanguage = $this->getL10N();
		
		// Aktive Felder durchgehen und setzen/validieren
		foreach($aActiveFields as $oField){
			/* @var Ext_TC_Frontend_Form_Field_Abstract $oField */
			
			$mValue = $oField->getValue(false);
				
			// Prüfen, ob das Feld selbst validiert werden soll
			$bFieldValidation = $this->checkValidationOfFieldMandatory($oField);
			
			// Wenn leer aber pflicht => fehler
			if(
				$bFieldValidation &&
				(
					empty($mValue) ||
					$mValue === "0000-00-00"
				) &&
				$oField->isRequired() &&
				$bCheckRequired === true
			){
				$sErrorMessage = $oField->getMessage('mandatoryfield');
				$sErrorMessage = $oLanguage->translate($sErrorMessage);
				
				$sErrorMessage = $oField->replacePlaceholdersInMessage($sErrorMessage);
				
				$oField->addError($sErrorMessage);
				$bValidate = false;
			}
			
			if(!empty($mValue)){
				$bNoValues = false;
			}

			$oField->setEntityValue();			
		}
		
		return $bValidate;
	}
	
	/**
	 * Prüft, ob ein Feld validiert werden soll
	 * 
	 * @param Ext_TC_Frontend_Form_Field_Abstract $oField
	 * @return boolean
	 */
	protected function checkValidationOfFieldMandatory(Ext_TC_Frontend_Form_Field_Abstract $oField) {		
		
		$oTemplateField = $oField->getTemplate();

		if($oTemplateField->mandatory_field) {		
			$aDependencies = $oTemplateField->getValidationDependencies();

			if(!empty($aDependencies)) {
				// Wenn die Validierung von einem Elternelement abhängig ist muss erst der Value
				// der Elternelemente überprüft werden
				foreach($aDependencies as $oDependency) {
					$oParentField = $oDependency->getParentField();	

					$oParentFormField = $this->searchField($oParentField->placeholder);

					if($oParentFormField) {
						$mValue = $oParentFormField->getValue(false);

						$aDependencyValues = $oDependency->field_values;

						if(
							!empty($aDependencyValues) &&
							!in_array($mValue, $aDependencyValues)
						) {
							return false;
						}
					}
				}			
			}
		}
		
		return true;
	}
	
	/**
	 * sucht rekursive nach einem Feld in einem Formular
	 * 
	 * @param string $sPlaceholder
	 * @return Ext_TC_Frontend_Form_Field_Abstract
	 */
	public function searchField($sPlaceholder) {
		
		$oField = null;
		
		if(!empty($sPlaceholder)) {
			if(isset($this->_aActiveFields[$sPlaceholder])) {
				$oField = $this->_aActiveFields[$sPlaceholder];
			} else if($this->_oParent instanceof Ext_TC_Frontend_Form) {
				$oField = $this->_oParent->searchField($sPlaceholder);
			}
		}
		
		return $oField;
	}
	
	/**
	 * get all Messages of the Template
	 * @return Ext_TC_Frontend_Template_Message <array> 
	 */
	protected function _getTemplateMessages(){
		$aMessages = (array)$this->_oTemplate->errormessages;
		$aTemplateMessages = array();
		foreach($aMessages as $aMessage){
			$oMessage = new Ext_TC_Frontend_Template_Message();
			$oMessage->setType($aMessage['type']);
			$oMessage->setValue($aMessage['message']);
			$aTemplateMessages[] = $oMessage;
		}
		
		return $aTemplateMessages;
	}
	
	/**
	 * get all Css of the Template
	 * @return Ext_TC_Frontend_Template_Css <array> 
	 */
	protected function _getTemplateCss(){
		$aCssList = (array)$this->_oTemplate->errormessages;
		$aTemplateCss = array();
		foreach($aCssList as $aCss){
			$oCss = new Ext_TC_Frontend_Template_Css();
			$oCss->setType($aCss['type']);
			$oCss->setValue($aCss['class']);
			$aTemplateCss[] = $oCss;
		}
		return $aTemplateCss;
	}
	
	/**
	 * validiert alle Kind Formulare
	 * 
	 * @param bool $bUnsetEmptyForms
	 * @return bool
	 */
	protected function _validateChilds($bUnsetEmptyForms = true, $bResetInvalidValues = false) {

		$aValid = array();

		// Kinder durchgehen
		foreach($this->_aActiveForms as $sKey => $oForm) {

			// Validieren
			if(is_object($oForm)) {

				// Wenn alle felder leer dann nimm das formular raus!
				if(
					$oForm->checkAllFieldEmpty() && 
					$bUnsetEmptyForms &&
					!$oForm->isRequired()
				){
					$aTemp = explode('_', $sKey);
					$this->removeForm($aTemp[0], $aTemp[1]);
					continue;
				} else {
					// Prüfen, ob Pflichtfelder validiert werden sollen
					$bCheckRequired = $this->checkValidationOfChildFormMandatory($oForm);
					$aValid[] = $oForm->validate($bUnsetEmptyForms, $bCheckRequired, $bResetInvalidValues);
				}
				
			} else {
				$aValid[] = false;
			}
		}

		// wenn mind. 1 Kind fehlerhaft war
		if(in_array(false, $aValid)){
			return false;
		}
		
		// ansonsten ist alles OK
		return true;
	}
	
	/**
	 * Wird beim Validieren von Childobjekten aufgerufen. Hier kann entschieden werden ob ein Childobjekt
	 * auf required geprüft werden soll
	 * 
	 * @param Ext_TC_Frontend_Form $oForm
	 * @return boolean
	 */
	protected function checkValidationOfChildFormMandatory(Ext_TC_Frontend_Form $oForm) {
		return true;
	}


	/**
	 * get the WDBasic object from the Mapping
	 * @return Ext_TC_Frontend_Form_Entity_Interface
	 */
	protected function _getEntity(){
		return $this->_oEntity;
	}
	
	/**
	 * setzt den Default wert falls noch kein Wert gesetzt wurde
	 * @param Ext_TC_Frontend_Form_Field_Abstract $oFormField 
	 */
	protected function _setDefaultFieldValue($oFormField) {

		// wenn noch kein Wert vorhanden ist muss er aus der Enity geladen werden
		$mValue = $oFormField->getValue(false);

		if(empty($mValue)){
			// Wert setzten
			$oFormField->setValue($mValue);
			// werte in das Entity setzten
			$oFormField->setEntityValue();
		}

	}
	
	/**
	 * get the Form Field Object for the Identifier
	 * @param string $sIdentifier
	 * @return \Ext_TC_Frontend_Form_Field_Input 
	 */
	protected function _getFormField($sIdentifier){
		
		$aCache = $this->_aFieldCache['form'];
		
		if(!isset($aCache[$sIdentifier])) {
			
			$sCacheKey = 'Ext_TC_Frontend_Form::_getFormField_'.$this->sFormKey.'_'.$this->_oTemplate->id.'_'.$sIdentifier;
			
			// Klappt noch nicht
			$oFormField = WDCache::get($sCacheKey);
			$oFormField = null;

			if($oFormField === null) {

				// get the Mapping field
				$oMappingField = $this->_getMappingField($sIdentifier);
				$oTemplateField = $this->_getTemplateField($sIdentifier);
				$oFormField = $this->_getFormFieldObject($oMappingField, $oTemplateField);
				
				if($oFormField){
					$oFormField->setIdentifier($sIdentifier);
				}
				// Form-Object entfernen vor dem Cachen
				$oFormField->removeForm();
				
				WDCache::set($sCacheKey, (24*60*60), $oFormField);
				
			}
            	
			if($oFormField->getTemplate()->isUnused()) {
				$this->markFieldUnusedStatus($oTemplateField);
			}
			
			$oFormField->setForm($this);
			// default werte setzen
			$this->_setDefaultFieldValue($oFormField);
			
			
		} else {
			$oFormField = $aCache[$sIdentifier];
		}
		
		$this->_aFieldCache['form'][$sIdentifier] = $oFormField;
		
		if($oFormField){
			return $oFormField;
		}
				
		throw new Exception('No form field found for field type with identifier "'.$sIdentifier.'" and display type "'.$sType.'"!');
	}

	protected function markFieldUnusedStatus(Ext_TC_Frontend_Template_Field $oTemplateField) {

		if($this->_oParent instanceof self) {
			$this->_oParent->markFieldUnusedStatus($oTemplateField);
		} else {
			$this->_aUnusedMarkedFields[$oTemplateField->getId()] = $oTemplateField->getId();
		}
		
	}
	
	/**
	 * @param Ext_TC_Frontend_Mapping_Field $oMappingField
	 * @param Ext_TC_Frontend_Template_Field $oTemplateField
	 * @return Ext_TC_Frontend_Form_Field_Abstract
	 */
	protected function _getFormFieldObject(Ext_TC_Frontend_Mapping_Field $oMappingField, Ext_TC_Frontend_Template_Field $oTemplateField) {
		$oFormField = false;	
		
		// enstprechende Form Field Klasse zurückgeben
		switch ($oTemplateField->display) {
			case 'input':			
				$oFormField = new Ext_TC_Frontend_Form_Field_Input($this, $oMappingField, $oTemplateField);
				break;
			case 'select_grouped':
				$oFormField = new Ext_TC_Frontend_Form_Field_Select_Grouped($this, $oMappingField, $oTemplateField);
				break;
			case 'select_grouped_date':
				$oFormField = new Ext_TC_Frontend_Form_Field_Select_Grouped_Date($this, $oMappingField, $oTemplateField);
				break;
			case 'select':
				$oFormField = new Ext_TC_Frontend_Form_Field_Select($this, $oMappingField, $oTemplateField);
				break;
			case 'multiselect':
				$oFormField = new Ext_TC_Frontend_Form_Field_Select_Multiselect($this, $oMappingField, $oTemplateField);
				break;
			case 'radio':
				$oFormField = new Ext_TC_Frontend_Form_Field_Select_Radio($this, $oMappingField, $oTemplateField);
				break;
			case 'date':
				$oFormField = new Ext_TC_Frontend_Form_Field_Date($this, $oMappingField, $oTemplateField);
				break;
			case 'time':
				$oFormField = new Ext_TC_Frontend_Form_Field_Time($this, $oMappingField, $oTemplateField);
				break;
			case 'textarea':
				$oFormField = new Ext_TC_Frontend_Form_Field_Textarea($this, $oMappingField, $oTemplateField);
				break;
			case 'birthday':
			case 'birthday_select':
			case 'birthdate_select':
				$oFormField = new Ext_TC_Frontend_Form_Field_Birthday($this, $oMappingField, $oTemplateField);
				break;
			case 'birthday_date':
			case 'birthdate_date':
				$oFormField = new Ext_TC_Frontend_Form_Field_BirthdayDate($this, $oMappingField, $oTemplateField);
				break;
			case 'location_select':
				$oFormField = new Ext_TC_Frontend_Form_Field_Location($this, $oMappingField, $oTemplateField);
				break;
			case 'checkbox':
				$oFormField = new Ext_TC_Frontend_Form_Field_Checkbox($this, $oMappingField, $oTemplateField);
				break;
			case 'checkbox_text':
				$oFormField = new Ext_TC_Frontend_Form_Field_Extrafield($this, $oMappingField, $oTemplateField);
				break;
			case 'referrer':
				$oFormField = Ext_TC_Factory::getObject('Ext_TC_Frontend_Form_Field_Referrer', array($this, $oMappingField, $oTemplateField));
				break;
			case 'phone':
				$oFormField = new Ext_TC_Frontend_Form_Field_Phone($this, $oMappingField, $oTemplateField);
				break;
		}
		
		return $oFormField;
	}
	
	/**
	* give the Template Field objetc
	* @param string $sIdentifier
	* @return Ext_TC_Frontend_Template_Field
	* @throws Exception 
	*/
	protected function _getTemplateField($sIdentifier){
	
		$aCache = $this->_aFieldCache['template'];
		
		if(!isset($aCache[$sIdentifier])){
			$sCacheKey = 'tc_frontend_form_template_fields_'.$this->_oTemplate->id;
			$aTemplateFields = WDCache::get($sCacheKey);
			
			if($aTemplateFields === null) {
				$aTemplateFields = array();
			}
			
			if(!isset($aTemplateFields[$sIdentifier])) {				
				$oField = $this->_oTemplate->getField($sIdentifier);
				$aTemplateFields[$sIdentifier] = $oField->id;
				WDCache::set($sCacheKey, 86400, $aTemplateFields);
			} else {
				$oField = Ext_TC_Frontend_Template_Field::getInstance((int) $aTemplateFields[$sIdentifier]);
			}
		} else {
			$oField = $aCache[$sIdentifier];
		}
		
		$this->_aFieldCache['template'][$sIdentifier] = $oField;
		
		if($oField instanceof Ext_TC_Frontend_Template_Field){
			return $oField;
		}
		
		throw new Exception('No template field found for identifier "'.$sIdentifier.'"!');
	}

	/**
	 * get the Mapping Field for the Identifier
	 * @param string $sIdentifier
	 * @return Ext_TC_Mapping_Field_Interface
	 * @throws Exception 
	 */
	protected function _getMappingField($sIdentifier){
		
		$aCache = $this->_aFieldCache['mapping'];

		if(
			!isset($aCache[$sIdentifier]) || 
			$aCache[$sIdentifier] === false
		){
			// MappingField holen
			$oTemplateField = $this->_getTemplateField($sIdentifier);
			$sFieldName		= $oTemplateField->getEntityFieldName($sIdentifier);
			
			if(mb_strpos($sFieldName, 'individual.') !== false){

				$oParentForm = $this->searchFirstParent();
				
				$iGuiDesignerElementId = (int) str_replace('individual.', '', $sFieldName);
				$oGuiDesignerElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iGuiDesignerElementId);
				
				$oFlexField = Ext_TC_Flexibility::getInstance($oGuiDesignerElement->special_type);

				$sGuiDesignerSection = $oParentForm->getGuiDesignerSection();
				if($oFlexField->usage === 'enquiry') {					
					// Für die Agentur abgefangen damit man flexible Felder befüllen kann, 
					// welche sich nur auf Anfragen beziehen					 
					$sGuiDesignerSection = 'enquiry';
				}
				
				$oMapping = new Ext_TC_Frontend_Gui2_Design_Mapping($sGuiDesignerSection, $oParentForm->getGuiDesignerObject());
			} else if(mb_strpos($sFieldName, 'checkbox') === false){
				$oMapping = $this->_oMapping;
			} else {
				$oMapping = new Ext_TC_Frontend_Extrafield_Mapping();
			}

			$oMappingField = $oMapping->getField($sFieldName);
			if($oMappingField){
				//$oMappingField->addConfig('identifier', $sIdentifier);
				$oMappingField->addConfig('name', $sFieldName);
			}
			
		} else {
			$oMappingField = $aCache[$sIdentifier];
		}

		$this->_aFieldCache['mapping'][$sIdentifier] = $oMappingField;
		
		// Wenn Mapping gefunden wurde
		if($oMappingField){
			return $oMappingField;
		}
	
		throw new Exception('No mapping field information for identifier "'.$sIdentifier.'" and entity field "'.$sFieldName.'"');
		
	}
	
	/**
	 * set all Values into the Form
	 * @param array $aValues 
	 */
	public function setValues($aValues, $bUnsetEmptyForms = true) {

		$aFields = $this->_getActiveFields();

		// Felder durchgehen
		foreach($aFields as $sIdentifier => $oField){
			// Nur wenn ein Wert übermittelt wurde setzten ( sonst gilt der bereits gesetzte Wert , z.b bei Seite 2 die elemente der seite 1 )
			if(isset($aValues[$sIdentifier])){
				$mValue		= $aValues[$sIdentifier];
				
				$oField->setFormValue($mValue);

				unset($aValues[$sIdentifier]);
			}
		}

		// Kind forms
		$aChildForms = $this->_aActiveForms;
	
		// Grupierte Daten
		$aIdentifierList = array();
		
		// Bisherige Childs durchgehen und gruppieren ( und false setzten für keine werte übermittelt )
		foreach($aChildForms as $sIdentifier => $oForm){
			// Identifier bauen
			$aIdentifier	= explode('_', $sIdentifier);
			$sIdentifier	= reset($aIdentifier);
			$iCount			= end($aIdentifier);
			
			$aIdentifierList[$sIdentifier][$iCount] = false;
		}
		
		// Values durchgehen und dazu gruppieren ( und werte setzten )
		foreach((array)$aValues as $sIdentifier => $aTempSubValues) {	
			if($sIdentifier === 'suffix') {
				continue;
			}
			
			if(is_array($aTempSubValues)){
				foreach($aTempSubValues as $iCount => $aSubValues){
					$aIdentifierList[$sIdentifier][$iCount] = (array)$aSubValues;
				}
			}
		}

		// Grupperierte Daten durchgehen ( je form type)
		foreach($aIdentifierList as $sIdentifier => $aCounts){
			// einzelne forms eines types durchgehen
			foreach($aCounts as $iCount => $aSubValues){

				// object holen
				$oForm = $this->getForm($sIdentifier, $iCount);

				$bEmpty = true;
				$bHasNoneArrayValue = false;
				// Schauen ob daten übermittelt wurden, wenn nicht dann wird das Form rausgenommen
				foreach((array)$aSubValues as $sSubIdentifier => $mTemp) {					
					if($sSubIdentifier === 'suffix') {
						continue;
					}
					
					if(!is_array($mTemp)){
						$bHasNoneArrayValue = true;
					}
					if(
						!empty($mTemp) &&
						!is_array($mTemp)
					){			
						$bEmpty = false;
						// Wenn ein Value vorhanden ist kann der Durchlauf der SubValues komplett abgebrochen werden, da die Values auf jeden Fall
						// gesetzt werden müssen
						break;
					}
					
					// Wenn der Value ein Array ist dann handelt es sich um Values eines Child-Forms. Hier müssen auch 
					// die Values geprüft werden da dort ggf. Values vorhanden sind. Wenn diese nicht geprüft werden 
					// verfallen die Values der ChildForms
					if(is_array($mTemp)) {
						foreach ($mTemp as $aSubSubValues) {							
							
							if(!is_array($aSubSubValues)) {
								continue;
							}
							// Values des ChildForms überprüfen. Wenn es dort einen Value gibt muss $bEmpty auf false
							// gesetzt werden damit die Values in das ChildForm gesetzt werden
							foreach($aSubSubValues as $sSubSubIdentifier => $mSubTemp) {		
								if($sSubSubIdentifier === 'suffix') {
									continue;
								}
								
								if(
									!empty($mSubTemp) &&
									!is_array($mSubTemp)
								){				
									$bEmpty = false;
									// Wenn ein Value vorhanden ist kann der Durchlauf der SubValues komplett abgebrochen werden, da die Values auf jeden Fall
									// gesetzt werden müssen
									break 3;
								}
							}
						}
					}					
				}

				// Wenn nur array werte darf es nicht gelöscht werden
				// da es scheinbar auf weiterführenden seiten für unterinhalte genutzt wird, kann also sein das die werte schon egsetzt waren und er dahe rnicht leer ist
				if(
					!$bHasNoneArrayValue &&
					$bEmpty
				){
					$bEmpty = false;
				}

				if(
					$aSubValues !== false &&
					// wenn Values vorhanden sind müssen diese gesetzt werden
					$bEmpty == false
				){
					// Try da evt in den VARS ein KEY vorkam der gleich aufgebaut ist aber gar nichts mit dem form zu tun hatte und so kein child geholt werden kann...
					try {					
						// Wenn es das form noch nicht gibt erzeuge es!
						if(!$oForm){
							$sSuffix = '';

							if(isset($aSubValues['suffix'])) {
								$sSuffix = (string) $aSubValues['suffix'];
								unset($aSubValues['suffix']);
							}

							$oForm = $this->addForm($sIdentifier, $iCount, $sSuffix);
						}
						if($oForm){
							// setze die werte
							$oForm->setValues($aSubValues, $bUnsetEmptyForms);
						}

					} catch (Exception $exc) {

					}
					// Wenn false wurden keine Werte übermittelt	
				} else if(
					$oForm &&
					$bEmpty &&
					$aSubValues !== false
				){
					$oForm = $this->getForm($sIdentifier, $iCount);
					if($oForm) {
						if($bUnsetEmptyForms === true) {
							if(!$oForm->isRequired()) {												
								$bCheckRequiredFormFields = $oForm->hasRequiredFields();

								if($bCheckRequiredFormFields === false){
									// Wenn es in dem Formular keine Pflichtfelder gab kann das komplette
									// Formular entfernt werden da die Werte nicht berücksichtigt werden müssen
									$this->removeForm($sIdentifier, $iCount);
								} else {
									// Wenn das Formular Pflichtfelder besitzt müssen die Werte dieser Pflichtfelder zurückgesetzt
									// werden da ansonsten die alten Werte erhalten bleiben
									$oForm->setValues($aSubValues, $bUnsetEmptyForms);
								}
							}
						} else {
							// Wenn die Formobjekte nicht entfernt werden sollen (Request) dann müssen die Werte 
							// trotzdem gesetzt werden, damit ggf. leere Eingaben auch gesetzt werden
							$oForm->setValues($aSubValues, $bUnsetEmptyForms);
						}
					}
				}
			}
		}
	}

	/**
	 * Prüft ob dem Formular Pflichtfelder hinzugefügt wurden
	 * 
	 * ACHTUNG: Manche Pflichtfelder haben eine Abhängigkeit zu anderen Feldern und 
	 * gelten nur als Pflichtfelder sobald die Bedingung auf die anderen Felder zutrifft.
	 * Diese Überprüfung kann mit dem Parameter gesteuert werden
	 * 
	 * @param bool $bCheckValidationDependencies
	 * @return boolean
	 */
	protected function hasRequiredFields($bCheckValidationDependencies = true) {		
		
		$aActiveFields = $this->_getActiveFields();
		$bRequired = false;
		
		foreach($aActiveFields as $oField) {
			
			$oTemplateField = $oField->getTemplate();
			
			if(
				$oTemplateField->mandatory_field == 1 &&
				( 
					$bCheckValidationDependencies === false ||
					$this->checkValidationOfFieldMandatory($oField) === true
				)
			) {
				$bRequired = true;
				break;
			}
			
		}
		
		return $bRequired;
	}
	
}
