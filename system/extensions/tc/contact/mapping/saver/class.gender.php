<?php

class Ext_TC_Contact_Mapping_Saver_Gender extends Ext_TC_Frontend_Mapping_Abstract_Saver {
	
	public function execute(\Ext_TC_Basic $oEntity, \Ext_TC_Frontend_Form_Field_Abstract $oField) {		
		/* @var $oEntity Ext_TC_Contact */
		$iUnformatedValue = (int)$oField->getValue(false);
		// Wenn kein Anrede angegeben wurde aber ein Geschlecht dann wird die Anrede anhand des Geschlechtes ermittelt
		if(
			$iUnformatedValue > 0 &&
			(int)$oEntity->salutation === 0
		) {			
			$oEntity->salutation = $oEntity->gender;
		}		
	}
	
}

