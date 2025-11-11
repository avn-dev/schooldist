<?php
/*
 * Name des Providers
 */
class Ext_Thebing_Gui2_Format_Transfer_Transferprovider extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		// Alle TransferProvider der Schule
		$aTransferProvider	= $oSchool->getTransferProvider(true);
	
		$sName = $aTransferProvider[$mValue];
		
		return $sName;
	}

}