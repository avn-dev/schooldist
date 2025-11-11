<?php

class Ext_TS_Accounting_Bookingstack_Gui2_Format_AddressName extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		switch($aResultData['address_type']) {
			case 'agency':
				$sName = $aResultData['address_type_object_name'];
				break;
			case 'address':
			default:				
				$oFormat = new Ext_TC_Gui2_Format_Name('address_firstname', 'address_lastname');
				$sName = $oFormat->format($mValue, $oColumn, $aResultData);
				break;			
		}
		
		return $sName;
	}
	
}
