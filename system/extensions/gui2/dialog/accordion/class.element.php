<?php

class Ext_Gui2_Dialog_Accordion_Element {
	
	protected $_sTitle = '';

	public $mContent = '';	
	
	protected $_oAccordion = null;
	
	protected $_aElements = array();
	
	public $aOptions = array();
	
	public function __construct($sTitle, Ext_Gui2_Dialog_Accordion $oAccordion) {
		$this->_oAccordion = $oAccordion;
		$this->_sTitle = (string) $sTitle;
	}
	
	public function generate() {
		
		$sId = (string)$this->aOptions['id'];	

		$oElement = new Ext_Gui2_Html_Div();
		$oElement->class = 'panel box box-default';
		if($this->aOptions['close'] === true) {
			$oElement->class .= ' collapsed-box';
		}

		$oElement->setElement('<div class="box-separator"></div>');

		$oSwitch = new Ext_Gui2_Html_Div();
		$oSwitch->class = 'box-header with-border';
		$oSwitch->setDataAttribute('toggle', 'collapse');
		$oSwitch->setDataAttribute('parent', '#'.$this->_oAccordion->getId());
		$oSwitch->setDataAttribute('target', '#'.$sId);


		if(
			isset($this->aOptions['data']) &&
			is_array($this->aOptions['data'])
		) {
			foreach($this->aOptions['data'] as $sKey => $sValue) {
				$oSwitch->setDataAttribute($sKey, (string) $sValue);
			}
		}
		
		$oH3 = new Ext_Gui2_Html_H3();
		$oH3->class = 'box-title';

		$oH3->setElement($this->_sTitle);
				
		$oSwitch->setElement($oH3);

		$oDivTools = new Ext_Gui2_Html_Div();
		$oDivTools->class = 'box-tools pull-right';

		$oButton = new Ext_Gui2_Html_Button();
		//$oButton->class = 'btn btn-gray btn-xs btn-box-tool';

		$oI = new Ext_Gui2_Html_I();
		if($this->aOptions['close'] === false) {
			$oI->class = 'fa fa-minus';
		} else {
			$oI->class = 'fa fa-plus';
		}
		$oButton->setElement($oI);

		$oDivTools->setElement($oButton);
		$oSwitch->setElement($oDivTools);

		$oContent = new Ext_Gui2_Html_Div();
		$oContent->id = $sId;
		$oContent->class = 'panel-collapse collapse';
		
		if($this->aOptions['close'] === false) {
			$oContent->class = 'in';
		}
		
		if(isset($this->aOptions['style'])) {
			$oContent->style .= (string) $this->aOptions['style'];
		}
		
		$sCssClasses = '';
		if(
			isset($this->aOptions['no_padding']) &&
			$this->aOptions['no_padding'] === true
		) {
			$sCssClasses = 'no-padding';
		}
		
		$sStyle = '';
		if(isset($this->aOptions['style'])) {
			$sStyle .= $this->aOptions['style'];
		}
		
		$sContent = '<div class="box-body '.$sCssClasses.'" style="'.$sStyle.'">';
		if(empty($this->_aElements)) {
			$sContent .= $this->mContent;
		} else {			
			foreach($this->_aElements as $oSubElement) {
				$oDiv = $oSubElement->generate();
				$sContent .= $oDiv->generateHtml();
			}
		}
		$sContent .= '</div>';
		
		$oContent->setElement($sContent);
		
		$oElement->setElement($oSwitch);
		$oElement->setElement($oContent);
		
		return $oElement;
	}
	
	public function generateHtml() {
		$oElement = $this->generate();
		return $oElement->generateHtml();
	}
	
	public function setContent($mContent) {
		$this->mContent = $mContent;
	}
	
	/**
	 * 
	 * @param string $sTitle
	 * @param array $aOptions
	 * @return Ext_Gui2_Dialog_Accordion_Element
	 */
	public function createElement($sTitle, $aOptions = array()) {
		$oElement = $this->_oAccordion->createElement($sTitle, $aOptions);
		$oElement->bSubElement = true;
				
		return $oElement;
	}
	
	public function addSubElement(Ext_Gui2_Dialog_Accordion_Element $oElement) {
		$this->_aElements[] = $oElement;
	}
}
