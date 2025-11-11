<?php

/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
abstract class Ext_TC_Frontend_Form_Field_Abstract implements Ext_TC_Frontend_Form_Field_Interface_Field {
    use \TcFrontend\Traits\WithInputCleanUp;
	
	protected $_sInput = '';
	protected $_sId = '';
	protected $_sValue = null; // wichtig da wir wissen müssen ob kein wert gesetzt wurde oder ein leerersting gespeichert ist
	protected $_sFormatedValue = '';
	protected $_bRequired = null;
	protected $_aErrors = array();
	protected $_aMessages = array();
	protected $_aCssClasses = array();
	protected $_sTemplateType = 'input';
	protected $_iPosition = 0;
    protected $_sMappingIdentifier = '';
	protected $_iPage = null;
	protected $_sType = '';
	protected $_aDataAtrtibutes = [];
    static public $iFieldCount = 0;
	
	/**
	 * @var Ext_TC_Frontend_Mapping_Field 
	 */
	protected $_oMapping;
	
	/**
	 * @var Ext_TC_Frontend_Template_Field 
	 */
	protected $_oTemplate;
	
	/**
	 * @var Ext_TC_Frontend_Form 
	 */
	protected $_oForm;
	
	/**
	 *
	 * @var \SmartyWrapper 
	 */
	static protected $_oSmarty;
	
	protected $_aConfig = array();


	public function __construct(Ext_TC_Frontend_Form $oForm, Ext_TC_Frontend_Mapping_Field $oMapping, Ext_TC_Frontend_Template_Field $oTemplate) {
		$this->_oForm		= $oForm;
		$this->_oMapping	= $oMapping;
		$this->_oTemplate	= $oTemplate;
		self::$iFieldCount++;
		$this->_iPosition	= self::$iFieldCount;
	}
	
	public function setForm(Ext_TC_Frontend_Form $oForm) {
		$this->_oForm = $oForm;
	}
	
	public function removeForm() {
		$this->_oForm = null;
	}
	
	// get config
	public function __get($sName) {
		return $this->_aConfig[$sName];
	}
	
	// set config
	public function __set($sName, $mValue) {
		$this->_aConfig[$sName] = $mValue;
	}
	
	/**
	 * @param bool $bStatus
	 */
	public function overwriteRequiredStatus($bStatus) {
		$this->_bRequired = $bStatus;
	}

	protected function isEntityField(): bool {
		return (!$this->isMetableField() && !$this->isIndividualField());
	}

	protected function isIndividualField(): bool {
		return strpos($this->getEntityFieldName(), \Ext_TC_Mapping_Abstract::PREFIX_INDIVIDUAL_FIELD) === 0;
	}

	protected function isMetableField(): bool {
		return strpos($this->getEntityFieldName(), \Ext_TC_Mapping_Abstract::PREFIX_METABLE_FIELD) === 0;
	}

	/**
	 * Gibt HTML für das Eingabeelement zurück
	 * 
	 * @return string
	 */
	public function getInput(){
		
		$oSmarty = $this->_getSmarty();
		
		$sTemplate = $this->_oTemplate->template;

		// Wenn kein individuelles Template vorhanden, Default nehmen
		if(empty($sTemplate)) {
			try {
				/* @var $oFrontendTemplate Ext_TC_Frontend_Template */
				$oFrontendTemplate = $this->_oTemplate->getJoinedObject('frontend_template');
				$sTemplate = $oFrontendTemplate->getDefaultTemplateContent($this->_sTemplateType);
			} catch (Exception $exc) {
				$sTemplate = '';
			}
		}

		$oSmarty->assign('oField', $this);

		$this->manipulateSmartyObject($oSmarty);
		
		$sContent = $oSmarty->fetch('string:'.$sTemplate);

		return $sContent;

	}
	
	public function manipulateSmartyObject(\SmartyWrapper $oSmarty) { }
	
	/**
	 * get the Field Positioen
	 * @return type 
	 */
	public function getPosition(){
		return $this->_iPosition;
	}


	/**
	 * get the label from the Mapping
	 * @return string 
	 */
	public function getLabel(){
		
		$sLanguage = Ext_TC_System::getInterfaceLanguage();
		
		$sLabel = Ext_TC_Placeholder_Abstract::translateFrontend($this->_oTemplate->label, $sLanguage);
		
		return $sLabel;

	}
	
	/**
	 * get the description from the Mapping
	 * @return string 
	 */
	public function getDescription(){
		
		$sLanguage = Ext_TC_System::getInterfaceLanguage();
		
		$sDescription = $this->_oTemplate->getDescription($sLanguage);
        
		return $sDescription;

	}
	
	/**
	 * @param string $sAttribute
	 * @param mixed $mValue
	 */
	public function addDataAttribute($sAttribute, $mValue) {
		
		if(strpos($sAttribute, 'data-') === -1) {
			$sAttribute = 'data-'.$sAttribute;
		}
		
		$this->_aDataAtrtibutes[$sAttribute] = $mValue;
	}
	
	public function getDataAttribute($sAttribute, $mDefault = null) {
		
		if($this->hasDataAttribute($sAttribute)) {
			return $this->_aDataAtrtibutes[$sAttribute];
		}
		
		return $mDefault;
	}
	
	public function hasDataAttribute($sAttribute) {
		return isset($this->_aDataAtrtibutes[$sAttribute]);
	}
	
	public function getDataAttributes() {
		return $this->_aDataAtrtibutes;
	}
	
	/**
	 * get the ID from the Mapping
	 * @return string 
	 */
	public function getId(){

		if(empty($this->_sId)) {
			
			//$sField = $this->_oTemplate->field;
			
			$sPrefix = $this->_oForm->getNamePrefix();
			$sPrefix = str_replace('][', '_', $sPrefix);
			$sPrefix = str_replace('[', '_', $sPrefix);
			$sPrefix = str_replace(']', '_', $sPrefix);
			
			$sField = $sPrefix;
			
			if($this->_oMapping->hasConfig('field_id_suffix')) {
				$sField .= $this->_oMapping->getConfig('field_id_suffix');
			} else {
				$sField .= $this->_oTemplate->field;
			}

			return $sField;
		}
		
		return $this->_sId;
	}
	
	public function setId($sId) {
		$this->_sId = $sId;		
	}
	
	/**
	 * get the Name of the Element
	 * @return string 
	 */
	public function getName(){
		$sPlaceholder	= $this->_oTemplate->placeholder;
		$sPrefix		= $this->_oForm->getNamePrefix();
		$sName			= $sPrefix.'['.$sPlaceholder.']';
		return $sName;
	}
	
	/**
	 * gibt den Entity Feld Namen zurück,
	 * wird vorallem für validieren und speichern benötigt
	 * @return string 
	 */
	public function getEntityFieldName($bWithPrefix = true){
		$sIdentifier = $this->_oTemplate->getEntityFieldName();

		if(!$bWithPrefix) {
			$aPrefixes = [
				Ext_TC_Mapping_Abstract::PREFIX_INDIVIDUAL_FIELD,
				Ext_TC_Mapping_Abstract::PREFIX_METABLE_FIELD
			];

			$sIdentifier = str_replace($aPrefixes, '', $sIdentifier);
		}

		return $sIdentifier;
	}
	
	/**
	 * gibt den Entity Feld Namen zurück,
	 * wird vorallem für validieren und speichern benötigt
	 * @return string 
	 */
	public function getEntityFieldAlias(){
		$sIdentifier = $this->_oTemplate->getEntityFieldAlias();
		return $sIdentifier;
	}

	/**
	 * Gibt den Platzhalter des Feldes zurück
	 * @return string 
	 */
	public function getPlaceholder(){
		$sPlaceholder = $this->_oTemplate->placeholder;
		return $sPlaceholder;
	}
	
	/**
	 * setzt den identifier Namen zurück,
	 * @return string 
	 */
	public function setIdentifier($sIdentifier){
        $this->_sMappingIdentifier = $sIdentifier;
	}
    
    /**
	 * gibt den identifier Namen zurück,
	 * @return string 
	 */
	public function getIdentifier(){
		//$sIdentifier = $this->_oMapping->getConfig('identifier');
        $sIdentifier = $this->_sMappingIdentifier;
		return $sIdentifier;
	}

	/**
	 * get all errors
	 * @return array
	 */
	public function getErrors(){
		return $this->_aErrors;
	}
	
	/**
	 * adds an Error
	 * @param type $sError 
	 */
	public function addError($sError, $sErrorKey = 'mandatoryfield'){
		$this->_aErrors[$sErrorKey] = $sError;
	}
	
	/**
	 * check for errors
	 * @return boolean 
	 */
	public function hasError(){
		$aErrors = $this->getErrors();
		if(!empty($aErrors)){
			return true;
		}
		return false;
	}
	
	/**
	 * get the Value
	 * @param boolean $bFormated
	 * @return string 
	 */
	public function getValue($bFormated = true, $sLanguage = null){

		if(
			$this->_sFormatedValue === '' &&
			$this->_sValue === null
		) {
			$this->setValue($this->getEntityValue());
		}
		
		if($bFormated){
			return $this->_sFormatedValue;
		} else {
			return $this->_sValue;
		}
	}
	
	/**
	 * Gibt den Typ des Felder zurück (input, select, ...)
	 * @return string
	 */
	public function getTemplateType() {
		return $this->_sTemplateType;
	}
	
	/**
	 * check if the field are required
	 * @return boolean 
	 */
	public function isRequired(){
		
		if(is_bool($this->_bRequired)) {
			return $this->_bRequired;
		}
		
		$bRequired = $this->_oTemplate->mandatory_field;
		if($bRequired){
			return true;
		}
		return false;
	}
	
	/**
	 * check if the field are editable
	 * @return boolean 
	 */
	public function isEditable(){
		$bCheck = $this->_oTemplate->editable;
		if($bCheck){
			return true;
		}
		return false;
	}
	
	/**
	 * format the value
	 * @param string $sValue
	 * @return string 
	 */
	public function formatValue($sValue){
		$oFormat = $this->_oMapping->getFormat();
		
		if($oFormat){
			$sValue = $oFormat->format($sValue);
		}
		return $sValue;
	}
	
	/**
	 * format the value
	 * @param string $sValue
	 * @return string 
	 */
	public function unformatValue($sValue){
		$oFormat = $this->_oMapping->getFormat();
		if($oFormat){
			$sValue = $oFormat->convert($sValue);	
		}
	
		return $sValue;
	}
	
	/**
	 * set the UNFORMATED Value
	 * @param string $sValue 
	 */
	public function setValue($sValue){
		$this->_sValue = $sValue;
		$this->_sFormatedValue = $this->formatValue($sValue);
	}
	
	/**
	 * set the Value
	 * @param string $sValue 
	 */
	public function setFormatedValue($sFormatedValue){
		$this->_sValue = $this->unformatValue($sFormatedValue);
		$this->_sFormatedValue = $sFormatedValue;
	}
	
	/**
	 * @param mixed $mValue
	 * @throws \InvalidArgumentException
	 */
	public function setFormValue($mValue) {

		$mValue = $this->escapeValue($mValue);

		if($this->hasMappingConfig('setter')) {			
			$sSetterClass = $this->getMappingConfig('setter');			
			$oSetter = \Ext_TC_Factory::getObject($sSetterClass);			
		} else {
			$oSetter = new \Ext_TC_Frontend_Mapping_Setter_SetFormattedValue();
		}
		
		if(!($oSetter instanceof \Ext_TC_Frontend_Mapping_Abstract_ValueSet)) {
			throw new \InvalidArgumentException('Unknown field value setter!');
		}
		
		$oSetter->setValue($this, $mValue);

	}
	
	/**
	 * set the current value into the Entity 
	 */
	public function setEntityValue(){		
		$oEntity				= $this->_oForm->getEntity();
		$sEntityField			= $this->getEntityFieldName(false);

		$mValue = $this->escapeValue($this->_sValue);

		if ($this->isEntityField()) {

			$oEntity->$sEntityField	= $mValue;

		} else if ($this->isMetableField()) {

			return $oEntity->setMeta($sEntityField, $mValue);

		} else if ($this->isIndividualField()) {

			$iElement = (int)$sEntityField;

			// Äußerstes Eltern element suchen
			if($iElement > 0){
				$oParentForm	= $this->_searchFirstParentForm();
				$oParentEntity	= false;
				if($oParentForm){
					$oParentEntity = $oParentForm->getEntity();
				}
				
				$oElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iElement);
								
				if($oElement->type != 'flexibility') {
					$oEntity->setGuiDesignerValue($iElement, $mValue, $oParentEntity);
				} else {					
					$oField = Ext_TC_Flexibility::getInstance($oElement->special_type);
					
					if($oField->usage === 'student_inquiry') {
						$this->_oForm->setFlexValue($oEntity, $oElement, $mValue);
					} else {
						$oEntity->setFlexValue($oElement->special_type, $mValue);
					}					
				}
			}
		}
		
		$oSaverObject = $this->getEntitySaverObject();
		
		if($oSaverObject) {
			$oSaverObject->execute($oEntity, $this);
		}
	}
	
	/**
	 * Prüft, ob es für das Feld eine individuelle Saver-Klasse gibt und gibt ggf. das Objekt zurück
	 * 
	 * @return \Ext_TC_Frontend_Mapping_Abstract_Saver|null
	 * @throws UnexpectedValueException
	 */
	protected function getEntitySaverObject() {		
		$sSaverClass = $this->_oMapping->getConfig('saver');
		$oSaverObject = null;
		
		if(!empty($sSaverClass)) {
			$oSaverObject = new $sSaverClass();

			if(!($oSaverObject instanceof Ext_TC_Frontend_Mapping_Abstract_Saver)) {
				throw new UnexpectedValueException('Class "'.$sSaverClass.'" must be an instance of "Ext_TC_Frontend_Mapping_Saver_Abstract"');
			}
		}
		
		return $oSaverObject;
	}



	public function getEntityValue(){

		$oEntity				= $this->_oForm->getEntity();
		$sEntityField			= $this->getEntityFieldName(false);

		if ($this->isEntityField()) {

			return $oEntity->$sEntityField;

		} else if ($this->isMetableField()) {

			return $oEntity->getMeta($sEntityField);

		} else if ($this->isIndividualField()) {

			$iElement = (int)$sEntityField;

			// Äußerstes Eltern element suchen
			if($iElement > 0){
				$oParentForm	= $this->_searchFirstParentForm();
				$oParentEntity	= false;
				if($oParentForm){
					$oParentEntity = $oParentForm->getEntity();
				}
				
				$oElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iElement);
								
				if($oElement->type === 'flexibility') {
					$mValue = $this->_oForm->getFlexValue($oElement);
				} else {
					$mValue = $oEntity->getGuiDesignerValue($iElement);
				}
				
				/* @var $oEntity Ext_TC_Basic */
				return $mValue;
			}
		}

		return '';
	}
	
	/**
	 * search the first parent form ( inquiry/enquiry form )
	 * @return Ext_TC_Frontend_Form 
	 */
	protected function _searchFirstParentForm(){
		$oParentForm	= $this->_oForm->searchFirstParent();
		return $oParentForm;
	}

		/**
	 * reset all error messages 
	 */
	public function resetErrors(){
		$this->_aErrors = array();
	}
	
	/**
	 * get the Message for the given Type
	 * @param string $sType 
	 * @return string
	 */
	public function getMessage($sType = 'mandatoryfield'){
		
		//TODO mit den klassen ausm uml machen
		if(
			$this->_oTemplate->mandatory_field == 1 &&
			$sType == 'mandatoryfield' &&
			$this->_oTemplate->mandatory_field_error != ""				
		){
			$sMessage = $this->_oTemplate->mandatory_field_error;
			return $sMessage;
		}

		// Allgeminer Fehler holen
		$oTemplate = $this->_oForm->getTemplate();
		return $oTemplate->getMessageForType($sType);
	}
	
	public function replacePlaceholdersInMessage($sMessage) {
		return str_replace(
			array('{label}', '{value}'), 
			array(
				$this->getLabel(), 
				$this->getValue(true)
			), 
			$sMessage
		);
	}

	/**
	 * get the Message for the given Type
	 * @param string $sType 
	 * @return string
	 */
	public function getCssClass($sType = ''){
		
		if(empty($sType)){
			$sCss = (string)$this->_oMapping->getConfig('css');
		}

		if(empty($sType)){
			$bError = $this->hasError();
			if($bError){
				$sType = 'invalid';
			} else {
				$sType = 'default';
			}
		}

		//TODO mit den klassen ausm uml machen
		$oTemplate = $this->_oForm->getTemplate();
		$aCss = $oTemplate->getCss();
		
		foreach($aCss as $aCssData){
			if($aCssData['type'] == $sType){
				$sCss .= ' '.$aCssData['class'];
			}
		}
		
		return $sCss;
	}
	
	/**
	 * get a clear Smarty Object
	 * @return \SmartyWrapper 
	 */
	protected function _getSmarty(){
		$oSmarty = static::$_oSmarty;
		if(!$oSmarty){
			$oSmarty = new SmartyWrapper();
			static::$_oSmarty = $oSmarty;
		}
		$oSmarty->clearAllAssign();
		return $oSmarty;
	}
	
	/**
	 * get the field template 
	 * @return Ext_TC_Frontend_Template_Field
	 */
	public function getTemplate(){
		return $this->_oTemplate;
	}
	
	public function setConfig($sKey, $mValue) {
		$this->$sKey = $mValue;
	}
	
	public function getConfig($sKey) {
		return $this->$sKey;
	}	
	
	public function hasConfig($sKey) {
		if(isset($this->_aConfig[$sKey])) {
			return true;
		}
		
		return false;
	}
	
	public function hasMappingConfig($sKey) {
		return $this->_oMapping->hasConfig($sKey);
	}
	
	public function getMappingConfig($sKey) {
		return $this->_oMapping->getConfig($sKey);
	}
	
	/**
	 * Setzt die Seite in der das Feld eingebunden wurde
	 * @param int $iPage
	 */
	public function setPage($iPage) {
		$this->_iPage = (int) $iPage;
	}
	
	/**
	 * Liefert die Seite in der das Feld eingebunden wurde
	 * 
	 * @return int $iPage
	 */
	public function getPage() {
		return $this->_iPage;
	}

	public function setType($sType) {
		$this->_sType = (string) $sType;
	}
	
	public function getType() {
		return $this->_sType;
	}
	
	public function getSelection() {
		$oSelection = $this->_oMapping->getSelection();
		return $oSelection;
	}

	private function escapeValue($mValue) {

		if(is_array($mValue)) {
			foreach($mValue as $mKey => $mSubValue) {
				$mValue[$mKey] = $this->escapeValue($mSubValue);
			}
		} else if (is_string($mValue)) {
            // HTML-Tags entfernen (z.B. XSS)
            $mValue = $this->cleanUp($mValue);
		}

		return $mValue;
	}
}
