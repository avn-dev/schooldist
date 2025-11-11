<?php
/**
 * GUI Designer Class
 */
class Ext_TC_Gui2_Designer {
	
	/**
	 * @var Ext_TC_Gui2_Design 
	 */
	protected $_oDesign;

	protected $_sSection = '';

	protected $_aDoubleElements = array();
	
	#############################################################
	## CACHING VARIABELS
	#############################################################
	protected static $_aHintCache = array();
	protected static $_aFullElementArrayCache = array();
	protected static $_aElementForFilterArrayCache = array();
	protected static $_aElementArrayCache = array();
	protected static $_aElements = array();
	protected static $_aFixElementsForSectionCache = array();
	protected static $_aDynamicElementsForSectionCache = array();
	protected static $_aFixListsCache = array();
	protected static $_aFlexFieldsCache = array();
	#############################################################

	public function __construct($mDesign) {
		
		// Set Design
		if(is_object($mDesign)){
			$this->_oDesign = $mDesign;
		} else if(is_numeric($mDesign)) {
			$this->_oDesign = Ext_TC_Gui2_Design::getInstance($mDesign);
		}

		// Set List of Double Elements for Hints
		//$this->_aDoubleElements = $this->_oDesign->findDoubleTabElements();
		
		// Section chache da wir ab und an das Object benutzen um an die Methoden zu kommen
		// nicht immer wird aber eine Design id übergeben
		// so kann es sonst passieren das keine elemente gefunden werdne
		if($this->_oDesign->id > 0){
			$oFactory = &$_SESSION['Gui2Designer']['factory'];
			$oFactory->sSection = $this->_oDesign->section;
			$this->_sSection = $this->_oDesign->section;
		}

	}

	/**
	 * Gibt das zugewiesene Design zurück
	 * 
	 * @return Ext_TC_Gui2_Design
	 */
	public function getDesign() {
		return $this->_oDesign;
	}
	
	public function setSection($sSection){
		$this->_sSection = $sSection;
	}

	/**
	 *
	 * @param Ext_TC_Gui2_Design_Tab_Element $oElement 
	 */
	public function checkForDoubleEntry($oElement){
		
		$sHash = $oElement->generateDesignerID();
		if(key_exists($sHash, $this->_aDoubleElements)){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Translate
	 * @param string $sTrans
	 * @return string
	 */
	public function t($sTrans){
		$oFactory = $_SESSION['Gui2Designer']['factory'];
		return L10N::t($sTrans, $oFactory->sL10NPath);
	}
	
	/**
	 * Generate a Hint for Information that the user must save the dialog
	 * @return html 
	 */
	public function getPleaseSaveHint($bDisplay = true){
		
		$sHint = self::$_aHintCache['save'][$bDisplay];
		
		if(empty($sHint)){
			
			$oDialog = new Ext_Gui2_Dialog();
			$sHintTitle = $this->t('Hinweis');
			$sHintMessage = $this->t('Sie müssen einmal speichern um das Layout verändern zu können');
			$oHint = $oDialog->createNotification($sHintTitle, $sHintMessage, 'info');
			$oHint->class = 'please_save_hint';
			if(!$bDisplay){
				$oHint->style = "display:none;";
			}
			$sHint = $oHint->generateHTML();
			
			self::$_aHintCache['save'][$bDisplay] = $sHint;
			
		}
		
		return $sHint;
	}
	
	/**
	 * Generate a Hint for Double Elements
	 * @return html 
	 */
	public function getDoubleElementHint(){
		
		$sHint = self::$_aHintCache['double'];
		
		if(empty($sHint)){
			
			$oDialog = new Ext_Gui2_Dialog();
			$oHint = $oDialog->createNotification($this->t('Warnung'), $this->t('Dieses Element ist bereits vorhanden'), 'hint');
			$sHint = $oHint->generateHTML();
			
			self::$_aHintCache['double'] = $sHint;
			
		}
		
		return $sHint;
	}
	
	/**
	 * Generate a Hint for Unknown Elements
	 * @return html 
	 */
	public function getUnknownElementHint(){
		
		$sHint = self::$_aHintCache['unknown'];
		
		if(empty($sHint)){
		
			$oDialog = new Ext_Gui2_Dialog();
			$oHint = $oDialog->createNotification($this->t('Achtung'), $this->t('Dieses Element ist im aktuellen Context nicht verfügbar'), 'error');
			$sHint = $oHint->generateHTML();
			
			self::$_aHintCache['unknown'] = $sHint;
			
		}
		
		return $sHint;
	}
	
	/**
	 * get the Template Path
	 * @return type 
	 */
	static public function getTemplatePath(){
		return \Util::getDocumentRoot().'system/extensions/tc/gui2/designer/template/'; 
	}

	public function getElementForFilterArray(){

		$aArray = self::$_aElementForFilterArrayCache[$bOnlyUnused];

		if(empty($aArray)){

			$aFix = $this->getFixElements($bOnlyUnused);

			$aDynamic = $this->getDynamicElements();

			$aFlexFields = $this->getFlexFields();

			$aArray = array();

			foreach ($aFix as $oElement) {
				if($oElement->filterelement == 1){
					$aTemp = $oElement->getArray();
					$aTemp['hash'] = $oElement->generateDesignerID();
					$aArray[] = $aTemp;
				}
			}

			foreach ($aDynamic as $oElement) {
				if($oElement->filterelement == 1){
					$aTemp = $oElement->getArray();
					$aTemp['hash'] = $oElement->generateDesignerID();
					$aArray[] = $aTemp;
				}
			}

			foreach ($aFlexFields as $oFlexField) {
				if($oElement->filterelement == 1){
					$aTemp = $oFlexField->getArray();
					$aTemp['hash'] = $oFlexField->generateDesignerID();
					$aArray[] = $aTemp;
				}
			}
			
			self::$_aElementForFilterArrayCache[$bOnlyUnused] = $aArray;

		}

		return $aArray;
	}
	
	/**
	 * get an Array with all Elements
	 * and ALL information as Array
	 * @return type 
	 */
	public function getFullElementArray($bOnlyUnused = true, $bDynamicSkipId=false) {
		
		$aArray = self::$_aFullElementArrayCache[$bOnlyUnused];

		if(empty($aArray)){
			
			$aFix = $this->getFixElements($bOnlyUnused);

			$aDynamic = $this->getDynamicElements();

			$aFlexFields = $this->getFlexFields($bOnlyUnused);
			
			$aArray = array();

			$checkRequiredSettingDisplay = function(Ext_TC_Gui2_Design_Tab_Element $oElement) {
				return (
					$oElement->required_setting ||
					in_array($oElement->type, ['input', 'textarea', 'html', 'date', 'select'])
				);
			};

			foreach ($aFix as $oElement) {
				/* @var Ext_TC_Gui2_Design_Tab_Element $oElement */
				$aTemp = $oElement->getArray();
				$aTemp['hash'] = $oElement->generateDesignerID();
				$aTemp['required_setting'] = $checkRequiredSettingDisplay($oElement);
				$aArray[] = $aTemp;
			}

			foreach ($aDynamic as $oElement) {
				/* @var Ext_TC_Gui2_Design_Tab_Element $oElement */
				$aTemp = $oElement->getArray();
				$aTemp['hash'] = $oElement->generateDesignerID($bDynamicSkipId);
				$aTemp['required_setting'] = $checkRequiredSettingDisplay($oElement);
				$aArray[] = $aTemp;
			}

			foreach ($aFlexFields as $oFlexField) {
				$aTemp = $oFlexField->getArray();
				$aTemp['hash'] = $oFlexField->generateDesignerID($bDynamicSkipId);
				// Individuelle Felder haben eine eigene Pflichtfeld-Einstellung
				$aTemp['required_setting'] = false;
				$aArray[] = $aTemp;
			}

			self::$_aFullElementArrayCache[$bOnlyUnused] = $aArray;
			
		}

		return $aArray;
	}
	
	/**
	 * get an Array with all Elements
	 * Key is the Has and Name is the Value
	 * @return type 
	 */
	public function getElementArray($bOnlyUnused = true){
		
		$aArray = self::$_aElementArrayCache[$bOnlyUnused];
		
		if(empty($aArray)){
			
			$aFix = $this->getFixElements($bOnlyUnused);
			$aDynamic = $this->getDynamicElements();
			$aFlexFields = $this->getFlexFields($bOnlyUnused);

			$aArray = array();

			foreach ($aFix as $oElement) {
				$aArray[$oElement->generateDesignerID()] = $oElement->getName();
			}

			foreach ($aDynamic as $oElement) {
				$aArray[$oElement->generateDesignerID(true)] = $oElement->getName();
			}
			
			foreach ($aFlexFields as $oFlexField) {
				$aArray[$oFlexField->generateDesignerID(true)] = $oFlexField->getName();
			}
			self::$_aElementArrayCache[$bOnlyUnused] = $aArray;
			
		}
		
		return $aArray;
	}
	
	/**
	 * Searching for an Element with the Hash
	 * @param string $sHash
	 * @return Ext_TC_Gui2_Design_Tab_Element
	 */
	public function findElementWithHash($sHash){
		
		$oElement = self::$_aElements[$sHash];
		
		if(empty($oElement)){

			$aFix = (array)$this->getFixElements();
			$aDynamic = (array)$this->getDynamicElements();
			$aFlexFields = (array)$this->getFlexFields(false);

			$aList = array_merge($aFix, $aDynamic, $aFlexFields);

			foreach ($aList as $oListElement) {

				/**
				 * Vergleich nach Hash mit und ohne ID, da es je nach Kontext unterschiedlich ist
				 * #1440
				 */
				if(
					$oListElement->generateDesignerID() == $sHash ||
					$oListElement->generateDesignerID(true) == $sHash
				) {
					$oElement = $oListElement;
					break;
				}
			}
			
			if(!$oElement){
				$oElement = false;
			}
			
			self::$_aElements[$sHash] = $oElement;
			
		}
		
		return $oElement;
	}
	
	
	/**
	 * Gernerate HTML for Dialog
	 * @return html
	 */
	public function generateHtml(){
		
		// Get Design
		$oDesign = $this->_oDesign;
		
		// Hinweis Box generieren
		$bDisplay = true;

		if($oDesign->id > 0){
			$bDisplay = false;
		}

		// Create Please Save Hint
		// and Add this Hint ever! if the Design is edditing
		// we need this for saving hint at a later timepoint
		$sCode = $this->getPleaseSaveHint($bDisplay);
		
		// If Edit Design
		if($oDesign->id > 0){

			// Smarty starten
			$oSmarty = new SmartyWrapper();

			// Variablen zuweisen
			$oSmarty->assign('sRemoveIconPath', Ext_TC_Util::getIcon('delete'));
			$oSmarty->assign('sEditIconPath', Ext_TC_Util::getIcon('edit'));
			$oSmarty->assign('sAddIconPath', Ext_TC_Util::getIcon('add'));
			$oSmarty->assign('sFixElementTitle', $this->t('Feste Elemente'));
			$oSmarty->assign('sFlexFieldsTitle', $this->t('Individuelle Felder'));
			$oSmarty->assign('sDynamicElementTitle', $this->t('Dynamische Elemente'));
			$oSmarty->assign('sHint', $sHint);
			$oSmarty->assign('oDesign', $oDesign);
			$oSmarty->assign('oDesigner', $this);

			// Rendern
			$sCode .= $oSmarty->fetch(self::getTemplatePath() . 'designer.tpl');
			
		}
		
		return $sCode;
	}
	
	/**
	 * get a List of Fix Elements for the SR Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element 
	 */
	public function getFixElementsForSection($sSection = '', $bOnlyUnused = true) {

		// Get Section
		if($sSection == ""){
			$sSection = $this->_sSection;
		}

		if(empty($sSection)) {
			throw new Exception('Section is empty!');
		}

		$aElements = (array)self::$_aFixElementsForSectionCache[$sSection][$bOnlyUnused];

		if(empty($aElements)){
			
			// Get Factory
			$oFactory = $_SESSION['Gui2Designer']['factory'];

			// Get mapping list section -> data class
			$aSections = $oFactory->aSectionDataClassList;

			// Get Data Class for Section
			$sClass = $aSections[$sSection];

			if(class_exists($sClass)){

				// get Designer
				$oDesignerData = new $sClass(0);

				if($oDesignerData instanceof Ext_TC_Gui2_Designer_Data){

					// Get All Elements
					$aElements = $oDesignerData->getFixTabElements();
					// Get All Current Elements
					if($bOnlyUnused){
						$aCurrentElements = $this->_oDesign->getTabElements();
					}
					
					foreach((array)$aElements as $iKey => $oElement){
						// If only Unused Elements
						if($bOnlyUnused){
							// Alle bereits benutzen Elemente rauswerfen
							foreach($aCurrentElements as $oCurrentElement){
								if($oCurrentElement->generateDesignerID() == $oElement->generateDesignerID()){
									unset($aElements[$iKey]);
									break;
								}
							}
						}
						
						$oElement->designer_section = $this->_oDesign->section;
					}
					
					$aElements = (array)$aElements;

					// Keys reseten
					$aElements = array_values($aElements);
					
					self::$_aFixElementsForSectionCache[$sSection][$bOnlyUnused] = $aElements;
					
					return $aElements;

				}

			} 
		}
		
		return $aElements;
	}
	
	/**
	 * get a List of Fix Elements for the SR Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element 
	 */
	public function getDynamicElementsForSection($sSection = '') {

		// Get Section
		if($sSection == ""){
			$sSection = $this->_sSection;
		}

		if(empty($sSection)) {
			throw new Exception('Section is empty!');
		}

		$aElements = (array)self::$_aDynamicElementsForSectionCache[$sSection];

		if(empty($aElements)){
			
			// Get Factory
			$oFactory = $_SESSION['Gui2Designer']['factory'];

			// Get Mapping Section -> Data Class
			$aSections = $oFactory->aSectionDataClassList;
			// Get Data Class
			$sClass = $aSections[$sSection];

			if(class_exists($sClass)){
				// Get Designer
				$oDesignerData = new $sClass(0);

				if($oDesignerData instanceof Ext_TC_Gui2_Designer_Data){
					// Get All Elements
					$aElements = $oDesignerData->getDynamicTabElements();

					self::$_aDynamicElementsForSectionCache[$sSection] = $aElements;
					
					return $aElements;

				}

			} 
		}
		
		return (array)$aElements;
	}
	
	/**
	 * get a List of Fix Lists for the current Designer
	 * @return array 
	 */
	public function getFixLists($bOnlyUnused = true){

		$aList = self::$_aFixListsCache[$bOnlyUnused];
		
		if(empty($aList)){
		
			$aChildElements = array();
			$aOtherElementList = array();

			$aElements = $this->getFixElements($bOnlyUnused);

			foreach((array)$aElements as $oElement){

				if(
					$oElement->allowed_parent !== null &&
					$oElement->allowed_parent !== ''
				) {
					$aChildElements[$oElement->allowed_parent][] = $oElement;
				} else {
					$aOtherElementList[] = $oElement;
				}

			}

			$i = 0;

			$aElements = $this->getFixElements(false);

			foreach((array)$aElements as $oElement){

				if(
					$oElement->type == "content" &&
					$oElement->special_type != ""
				){
					$aList[$i]['self'] = $oElement;
					$aList[$i]['childs'] = (array)$aChildElements[$oElement->generateDesignerID()];
					$i++;
				}

			}

			$aList[$i]['self'] = false;
			$aList[$i]['childs'] = $aOtherElementList;
			
			self::$_aFixListsCache[$bOnlyUnused] = $aList;
			
		}
		
		return $aList;

	}
	
	/**
	 * get a List of Fix Elements for the current Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element[]
	 */
	public function getFixElements($bOnlyUnused = true){
		$aList = $this->getFixElementsForSection('', $bOnlyUnused);
		return $aList;
	}
	
	/**
	 * get a List of Dynamic Elements for the SR Designer
	 * @return Ext_TC_Gui2_Design_Tab_Element 
	 */
	public function getDynamicElements(){
		$aList = $this->getDynamicElementsForSection();
		return $aList;
	}	
	
	/**
	 * Alle passenden flexiblen Felder zurückgeben
	 */
	public function getFlexFields($bOnlyUnused = true) {

		if(!isset(self::$_aFlexFieldsCache[$this->_sSection][$bOnlyUnused])) {

			$aAllowedUsages = array(
				'inquiry' => array(
					'inquiry',
					'enquiry_inquiry',
					'student',
					'student_inquiry'
				),
				'enquiry' => array(
					'enquiry',
					'enquiry_inquiry',
					'student',
					'student_inquiry'
				),
				'students' => array()
			);

			$oRepo = Ext_TC_Flexibility::getRepository();

			$oSection = Ext_TC_Flexible_Section::getGuiDesignerSection();

			$aCriteria = array(
				'section_id'=>$oSection->id
			);
			$aFlexFields = $oRepo->findBy($aCriteria);

			$aAllowedUsage = $aAllowedUsages[$this->_sSection];

			if($bOnlyUnused){
				$aCurrentElements = $this->_oDesign->getTabElements();
			}

			self::$_aFlexFieldsCache[$this->_sSection] = array(); 
			foreach($aFlexFields as $oFlexField) {
				if(in_array($oFlexField->usage, $aAllowedUsage)) {
					$bUseable = true;
					
					if(
						$bOnlyUnused &&
						strpos($oFlexField->usage, 'student') === false
					){
						// Alle bereits benutzen Elemente rauswerfen
						foreach($aCurrentElements as $oCurrentElement){
							
							if($oCurrentElement->generateDesignerID() === 'element_' . $oFlexField->generateDesignerID()) {
								$bUseable = false;
								break;
							}
						}
					}
					
					if($bUseable) {
						self::$_aFlexFieldsCache[$this->_sSection][$bOnlyUnused][] = $oFlexField;
					}
				}
			}
			
		}
		
		return (array)self::$_aFlexFieldsCache[$this->_sSection][$bOnlyUnused];
	}

}
