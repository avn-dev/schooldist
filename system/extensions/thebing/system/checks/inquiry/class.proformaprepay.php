<?php

class Ext_Thebing_System_Checks_Inquiry_ProformaPrepay extends GlobalChecks {

	public function getTitle() {
		return 'Enable payment status for proformas';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_inquiries`
			WHERE
				`active` = 1 AND
				`has_proforma` = 1 AND
				`has_invoice` = 0
		";

		$aInquiryIds = DB::getQueryCol($sSql);

		if(!empty($aInquiryIds)) {
			foreach($aInquiryIds as $iInquiryId) {
				$this->addProcess(array(
					'inquiry_id' => $iInquiryId
				));

			}
		}

		return true;

	}

	public function executeProcess(array $aData) {

		$iInquiryId = (int)$aData['inquiry_id'];
		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

		$oDocument = $oInquiry->getLastDocument('proforma');
		if($oDocument === null) {
			return true;
		}

		$oVersion = $oDocument->getLastVersion();

		$oInquiry->disableUpdateOfCurrentTimestamp();
		$oInquiry->savePrepayCache($oVersion);

		// Buchung sofort aktualisieren
		Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 0);
		Ext_Gui2_Index_Stack::executeCache();

		return true;

	}

}
