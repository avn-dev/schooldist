<?php

class Ext_Thebing_System_Checks_Inquiry_ServiceStart extends GlobalChecks {

	public function getTitle() {
		return 'Update inquiry service periods';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Util::backupTable('ts_inquiries');

		$aInquiries = DB::getQueryCol("
			SELECT
				id
			FROM
			    ts_inquiries
		");

		foreach ($aInquiries as $iInquiryId) {
			$this->addProcess(['inquiry_id' => $iInquiryId]);
		}

		return true;

	}

	public function executeProcess(array $aData) {

		$oInquiry = Ext_TS_Inquiry::getInstance($aData['inquiry_id']);
		$oInquiry->refreshServicePeriod();

		DB::executePreparedQuery("
			UPDATE
				ts_inquiries
			SET
				changed = changed,
				service_from = :service_from,
				service_until = :service_until
			WHERE
				id = :id
		", $oInquiry->getData());

	}

}