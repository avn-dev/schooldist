<?php

class Ext_Thebing_Gui2_Format_Communication_Application extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aApplications = Ext_Thebing_Communication::getApplications();
		
		if($mValue == 'customer')
		{
			$mValue = 'accommodation_communication_customer_agency';
		}

		return $aApplications[$mValue];

	}

}

