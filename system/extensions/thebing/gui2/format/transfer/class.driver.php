<?php
class Ext_Thebing_Gui2_Format_Transfer_Driver extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			$mValue > 0 &&
			$aResultData['inquiry_transfer_id'] > 0 &&
			$aResultData['provider_id'] > 0
		){
			$sName = '';
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($aResultData['inquiry_transfer_id']);
			$aProviders = $oTransfer->getTransferProvider();
			$aProvider = $aProviders[$aResultData['provider_id']];
			if(!empty($aProvider['driver'][$mValue]['name'])){
				$sName = $aProvider['driver'][$mValue]['name'];
			}

			
			return $sName;
		}
	}

}