<?php

class Ext_TC_Frontend_Mapping_Setter_SetFormattedValue extends Ext_TC_Frontend_Mapping_Abstract_ValueSet {
	
	public function setValue(\Ext_TC_Frontend_Form_Field_Abstract $oField, $mValue) {
		$oField->setFormatedValue($mValue);
	}

}

