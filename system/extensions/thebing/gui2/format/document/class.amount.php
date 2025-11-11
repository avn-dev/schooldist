<?php

class Ext_Thebing_Gui2_Format_Document_Amount extends Ext_Thebing_Gui2_Format_Amount {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$fAmount = '';

		if($aResultData['version_id'] > 0){
			$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance((int)$aResultData['version_id']);
			$oDocument = $oVersion->getDocument();
		} else {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance((int)$aResultData['id']);
		}

		$oInquiry = $oDocument->getInquiry();

		switch($oColumn->db_column) {

			case 'amount':
				if(!$oInquiry->hasGroup()) {
				 $fAmount		= $oVersion->getAmount();
				} else {
					$fAmount		= $oVersion->getGroupAmount();
				}
				break;
			case 'amount_before_arrival':
				if(!$oInquiry->hasGroup()) {
					$fAmount		= $oVersion->getAmount(true, false);
				} else {
					$fAmount		= $oVersion->getGroupAmount(true, false);
				}
				break;
			case 'amount_payed_before_arrival':
				if(!$oInquiry->hasGroup()) {
					$fAmount		= $oVersion->getAmount(true, false);
				} else {
					$fAmount		= $oVersion->getGroupAmount(true, false);
				}
				$fAmount -= $oDocument->getPayedAmount(0, 1);
				break;
			case 'amount_open_before_arrival':
				$fAmount = $oDocument->getPayedAmount(0, 1);
					break;
				case 'amount_at_school':
				if(!$oInquiry->hasGroup()) {
					$fAmount		= $oVersion->getAmount(false, true);
				} else {
					$fAmount		= $oVersion->getGroupAmount(false, true);
				}
				break;
			case 'amount_payed_at_school':
				$fAmount = $oDocument->getPayedAmount(0, 2);
					break;
				case 'amount_open_at_school':
				if(!$oInquiry->hasGroup()) {
					$fAmount		= $oVersion->getAmount(false, true);
				} else {
					$fAmount		= $oVersion->getGroupAmount(false, true);
				}
				$fAmount -= $oDocument->getPayedAmount(0, 2); 
				break;
			case 'cn_document_id':

				if($aResultData['cn_document_id'] > 0) {
					$oDocument = Ext_Thebing_Inquiry_Document::getInstance((int)$aResultData['cn_document_id']);
					$oInquiry = $oDocument->getInquiry();

					$oVersionCreditnote = $oDocument->getLastVersion();
					if(!$oInquiry->hasGroup()) {
						$fAmount = $oVersionCreditnote->getAmount(true, true);
					} else {
						$fAmount = $oVersionCreditnote->getGroupAmount(true, true);
					}
				}
				break;
		}

		if($fAmount !== ''){
			$oFormat = new Ext_Thebing_Gui2_Format_Amount();
			$fAmount = $oFormat->format($fAmount, $oColumn, $aResultData);
		}

		return $fAmount;

	}

}