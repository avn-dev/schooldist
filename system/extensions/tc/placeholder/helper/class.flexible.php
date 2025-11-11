<?php

class Ext_TC_Placeholder_Helper_Flexible {
	
	/**
	 * Section aus dem GUI-Designer (inquiry/enquiry/students)
	 * @var string 
	 */
	public $sGuiDesignerSectionKey = '';
	
	/**
	 * Sections der Flexiblen Felder
	 * @var array 
	 */
	public $aFlexibleFieldsSections = array();
		
	/**
	 * gibt an, ob die Platzhalterdaten in das Smartyobjekt geschrieben werden sollen
	 * @var boolean 
	 */
	public $bAssignData = true;
	
	/**
	 * Smarty object
	 * @var SmartyWrapper
	 */
	protected $_oSmarty = null;

	/**
	 * Array mit allen flexiblen Platzhaltern
	 * @var array 
	 */
	protected $_aFlexiblePlaceholder = array();

	/**
	 * Objekte, auf die sich die Platzhalter beziehen sollen
	 * @var array 
	 */
	protected $_aObjects = array();
	
	/**
	 * Helper-Klasse für Platzhalter des Gui-Designers 
	 * @var Ext_TC_Placeholder_Helper_Flexible_GuiDesigner 
	 */
	protected $_oGuiDesignPlaceholderObject = null;
	
	/**
	 * Helper-Klasse für Platzhalter der flexiblen Felder
	 * @var Ext_TC_Placeholder_Helper_Flexible_Fields 
	 */
	protected $_oFlexibleFieldsPlaceholderObject = null;

	/**
	 * Konstruktor der flexiblen Platzhalter
	 * @param SmartyWrapper $oSmarty
	 */
	public function __construct($oSmarty) {	
		$this->_oSmarty		= $oSmarty;		
	}
	
	/**
	 * liefert alle flexiblen Platzhalter (Gui-Designer, flexible Felder, ...) zurück
	 * 
	 * array(
	 *		[sWDBasic] => array(
	 *			[0] => array(
	 *				'platzhalter' => array(
	 *					''
	 *				)
	 *			)
	 *		)
	 * )
	 * 
	 * @return array
	 */
	public function getFlexiblePlaceholder($oWDBasic) {
		
		$sWDBasic = get_class($oWDBasic);

		$this->_prepareGuiDesignerPlaceholder($oWDBasic);
		$this->_prepareFlexibleFieldsPlaceholder($oWDBasic);

		return (array)($this->_aFlexiblePlaceholder[$sWDBasic] ?? []);
	}
	
	/**
	 * Objekte die berücksichtigt werden müssen (z.B TA Gui-Designer ist an Büros gebunden)
	 * @param array $aObjects
	 */
	public function setObjects($aObjects){
		$this->_aObjects = (array) $aObjects;
	}

	/**
	 * holt alle Platzhalter des Gui-Designers
	 */
	protected function _prepareGuiDesignerPlaceholder() {
		
		$oGuiDesignerPlaceholder = $this->_oGuiDesignPlaceholderObject;
		if(empty($this->_oGuiDesignPlaceholderObject)) {
			$oGuiDesignerPlaceholder = new Ext_TC_Placeholder_Helper_Flexible_GuiDesigner($this->_oSmarty);
			$oGuiDesignerPlaceholder->setObjects($this->_aObjects);
			$this->_oGuiDesignPlaceholderObject = $oGuiDesignerPlaceholder;
		}
		
		$oGuiDesignerPlaceholder->sSection		= $this->sGuiDesignerSectionKey;
		$oGuiDesignerPlaceholder->bAssignData	= $this->bAssignData;
		
		$aPlaceholder = $oGuiDesignerPlaceholder->getPlaceholder();
				
		$this->_mergeFlexiblePlaceholder($aPlaceholder);
	}
	
	/**
	 * holt alle Platzhalter der flexiblen Felder
	 */
	protected function _prepareFlexibleFieldsPlaceholder($oWDBasic) {		

		if(is_null($this->_oFlexibleFieldsPlaceholderObject)) {
			$this->_oFlexibleFieldsPlaceholderObject = new Ext_TC_Placeholder_Helper_Flexible_Fields($this->_oSmarty);
			$this->_oFlexibleFieldsPlaceholderObject->setObjects($this->_aObjects);
		}

		$oFlexibleFieldsPlaceholder = $this->_oFlexibleFieldsPlaceholderObject;
		$oFlexibleFieldsPlaceholder->sGuiDesignerSectionKey	= $this->sGuiDesignerSectionKey;
		$oFlexibleFieldsPlaceholder->aSections				= $this->aFlexibleFieldsSections;
		$oFlexibleFieldsPlaceholder->bAssignData			= $this->bAssignData;
		$oFlexibleFieldsPlaceholder->oWDClass				= $oWDBasic;

		$aPlaceholder = $oFlexibleFieldsPlaceholder->getPlaceholder();

		$this->_mergeFlexiblePlaceholder($aPlaceholder);		
	}
	
	/**
	 * übergebene Platzhalter zu den bereits geholten Platzhaltern hinzufügen
	 * @param array $aPlaceholder
	 */
	protected function _mergeFlexiblePlaceholder($aPlaceholder) {		
		foreach($aPlaceholder as $sWDBasicClass => $aPlaceholderData) {
			if(!empty($this->_aFlexiblePlaceholder[$sWDBasicClass])) {
				$this->_aFlexiblePlaceholder[$sWDBasicClass] = array_merge($this->_aFlexiblePlaceholder[$sWDBasicClass], $aPlaceholderData);
			} else {
				$this->_aFlexiblePlaceholder[$sWDBasicClass] = $aPlaceholderData;
			}
		}		
	}
	
}
