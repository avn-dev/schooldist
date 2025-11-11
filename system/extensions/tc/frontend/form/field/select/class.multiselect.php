<?php

class Ext_TC_Frontend_Form_Field_Select_Multiselect extends Ext_TC_Frontend_Form_Field_Select {
		
	protected $_sTemplateType = 'multiselect';

	public function getName() {
		return parent::getName().'[]';
	}

	public function getOptions($bUnsetEmptyOption = false, $bGrouped = false, $sLanguage = null){
		$aOptions = parent::getOptions(true, false, $sLanguage);
		return $aOptions;
	}
	
}

