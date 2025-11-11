<?php

class Ext_TC_Contact_Mapping_Saver_Salutation extends Ext_TC_Frontend_Mapping_Abstract_Saver {
	
	public function execute(\Ext_TC_Basic $oEntity, \Ext_TC_Frontend_Form_Field_Abstract $oField) {			
		/* @var $oEntity Ext_TC_Contact */
		$iUnformatedValue = (int)$oField->getValue(false);
		// Wenn noch kein Geschlecht gesetzt wurde kann dieses von der Anrede abgeleitet werden
		if(
			$iUnformatedValue > 0 &&
			(int)$oEntity->gender === 0
		) {
			$oEntity->gender = $oEntity->salutation;
		}
	}
	
}

