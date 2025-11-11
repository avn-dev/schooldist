<?php
class Ext_TC_Frontend_Gui2_Design_Selection extends Ext_TC_Frontend_Form_Field_Select_Selection {

	/**
	 * @var Ext_TC_Gui2_Design_Tab_Element 
	 */
	protected $_oElement;
	
	public function __construct(Ext_TC_Gui2_Design_Tab_Element $oElement) {
		$this->_oElement = $oElement;
	}
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		if($this->_oElement->type == 'flexibility') {
			$aOptions = $this->getFlexibilityOptions();
		} else {
			$aOptions = $this->getDesignOptions();
		}
		
		if(!empty($aOptions)) {
			$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
		}

		return $aOptions;
	}
	
	public function getGroupedOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		if($this->_oElement->type == 'flexibility') {
			$aUnGroupedOptions = $this->getFlexibilityOptions(true);
		} else {
			$aUnGroupedOptions = $this->getDesignOptions();
		}

		$aOptions = array();
		$iCount = 0;
		foreach($aUnGroupedOptions as $sKey => $sValue) {
			
			if($sKey === Ext_TC_Flexible_Option::OPTION_SEPARATOR_KEY) {
				++$iCount;
				$aOptions[$iCount]['text'] = $sValue;
				continue;
			}
			
			$aOptions[$iCount]['options'][$sKey] = $sValue;
		}
	
		return $aOptions;
	}
	
	private function getFlexibilityOptions($bWithSeparator = false) {
		
		$sLang = Ext_TC_System::getInterfaceLanguage();
		if($this->checkConfig('frontend_form')){
			$oForm = $this->frontend_form;
			$oInquiryForm = $oForm->searchFirstParent();
			$oCombination = $oInquiryForm->getCombination();
			$sLang = $oCombination->getLanguage();
		}

		$aOptions = Ext_TC_Flexibility::getOptions($this->_oElement->special_type, $sLang, $bWithSeparator);
		
		return $aOptions;
	}
	
	private function getDesignOptions() {
		$aSelectOptions = $this->_oElement->getJoinedObjectChilds('select_options');
		$aOptions = [];	
		
		foreach((array)$aSelectOptions as $oOption){
			/* @var $oOption Ext_TC_Gui2_Design_Tab_Element_Selectoption */
			$aOptions[$oOption->id] = $oOption->getName();
		}
		
		return $aOptions;
	}
}