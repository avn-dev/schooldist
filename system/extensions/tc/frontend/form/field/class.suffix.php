<?php

class Ext_TC_Frontend_Form_Field_Suffix implements Ext_TC_Frontend_Form_Field_Interface_Field {
	
	protected $oForm;

	public function __construct(Ext_TC_Frontend_Form $oForm) {
		$this->oForm = $oForm;
	}
	
	public function getInput() {
		return '<input type="hidden" name="'.$this->oForm->getNamePrefix().'[suffix]" value="'.$this->oForm->getSuffix().'" />';
	}
	
}

