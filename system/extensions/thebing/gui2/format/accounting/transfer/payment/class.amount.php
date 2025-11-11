<?
class Ext_Thebing_Gui2_Format_Accounting_Transfer_Payment_Amount extends Ext_Thebing_Gui2_Format_Amount {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(!is_numeric($mValue)){
			
			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($aResultData['inquiry_transfer_id']);
			$iTransferProvider = $aResultData['provider_id'];
			$oPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($oTransfer, $iTransferProvider);

			if($oPackage){
				$aResultData['currency_id'] = $oPackage->currency_id;
				$mValue = $oPackage->amount_cost;
			} else {
				$mValue = 0;
			}
		}

		$oFormat = new Ext_Thebing_Gui2_Format_Amount();
			$mValue = $oFormat->format($mValue, $oColumn, $aResultData);
			return $mValue;

	}

}
