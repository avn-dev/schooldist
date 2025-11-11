<?php
/*
 * Name des Providers
 */
class Ext_Thebing_Gui2_Format_Transfer_ProviderName extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oSchool = $oSchool = Ext_Thebing_School::getSchoolFromSession();

		// Alle TransferProvider der Schule
		$aTransferProvider	= $oSchool->getTransferProvider(true);
		// Alle Familien die auch als Provider fungieren können der Schule
		$aTransferAcc		= $oSchool->getTransferLocations(true);

		$sName = '';
		if(
			isset($aResultData['provider_type']) &&
			isset($aResultData['provider_id'])
		){
			// Transferlisten
			if($aResultData['provider_type'] == 'provider'){
				$sName = $aTransferProvider[$aResultData['provider_id']];
			}elseif($aResultData['provider_type'] == 'accommodation'){
				$sName = $aTransferAcc[$aResultData['provider_id']];
			}
		}elseif(
			isset($aResultData['object_id']) &&
			isset($aResultData['object'])
		){
			// Mailhistory
			if($aResultData['object'] == 'Ext_Thebing_Pickup_Company'){
				$sName = $aTransferProvider[$aResultData['object_id']];
			}elseif($aResultData['object'] == 'Ext_Thebing_Accommodation'){
				$sName = $aTransferAcc[$aResultData['object_id']]; 
			}
		}
		
		return $sName;
	}

}