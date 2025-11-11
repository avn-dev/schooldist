<?php

class Ext_Gui2_Dialog_Accordion {

	/**
	 * @var Ext_Gui2_Dialog_Accordion_Element[]
	 */
	protected $_aElements = array();

	/**
	 * @var string
	 */
	protected $_sId = '';

	/**
	 * @param string $sId
	 */
	public function __construct($sId=null) {
		
		if($sId === null) {
			$sId = \Util::generateRandomString(16);
		}
		
		$this->_sId = $sId;
	}

	/**
	 * @param string $sTitle
	 * @param array $aOptions
	 * @return Ext_Gui2_Dialog_Accordion_Element
	 */
	public function createElement($sTitle, $aOptions = array()) {		
		$oElement = new Ext_Gui2_Dialog_Accordion_Element($sTitle, $this);		
		$oElement->aOptions = $aOptions;
		return $oElement;
	}

	public function getId() {
		return $this->_sId;
	}
	
	/**
	 * @param Ext_Gui2_Dialog_Accordion_Element $oElement
	 */
	public function addElement(Ext_Gui2_Dialog_Accordion_Element $oElement) {
		$this->_aElements[] = $oElement;
	}

	/**
	 * @return Ext_Gui2_Html_Div
	 */
	public function generate() {

		$oAccordion = new Ext_Gui2_Html_Div();
		$oAccordion->id = $this->_sId;
		$oAccordion->class = 'box-group';
		foreach($this->_aElements as $iKey => $oElement) {
			
			if(!isset($oElement->aOptions['id'])) {
				$oElement->aOptions['id'] = $this->_sId . '_' . $iKey;
			}

			// https://api.jqueryui.com/accordion/
			$oDiv = $oElement->generate();
			$oAccordion->setElement($oDiv);
		}
		
		return $oAccordion;
	}

	/**
	 * @return string
	 */
	public function generateHtml() {
		
		$oAccordionDiv = $this->generate();
		
		return $oAccordionDiv->generateHTML();
	}
}
