<?php

class Ext_Thebing_System_Checks_Inquiry_RefreshAmountCache extends GlobalChecks {

	public function getTitle() {
		return 'Refresh Inquiry Amount Cache';
	}

	public function getDescription() {
		return 'Refresh all amount/amount payed columns of inquiries (background task).';
	}

	public function executeCheck($sImportKey = null) {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_inquiries`
			WHERE
				`active` = 1 AND
				/* Uralte Buchungen verursachen Fehler */
				`created` >= '2013-01-01'
			ORDER BY
				`id` DESC
		";

		$aIds = (array)DB::getQueryCol($sSql);
		foreach($aIds as $iId) {
			$this->addProcess(['id' => $iId]);
		}

		return true;

	}

	public function executeProcess(array $aData) {

		$oInquiry = Ext_TS_Inquiry::getInstance($aData['id']);
		$oInquiry->disableUpdateOfCurrentTimestamp();

		// Erwarteter Betrag in Schule
		$oInquiry->getAmount(false, true);

		// Erwarteter Betrag vor Anreise
		$oInquiry->getAmount(true, true);

		// Alle Zahlungen
		$oInquiry->calculatePayedAmount();

		// Die drei Methoden oben rufen eh alle save() auf…
		Ext_Gui2_Index_Stack::executeCache();

		return true;

	}

}