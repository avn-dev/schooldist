<?php

/**
 * https://redmine.thebing.com/redmine/issues/9077
 *
 * Verschollene Zahlungen suchen und als Overpayment zuweisen (umbuchen):
 *
 * Beim Umstellen der CN-Bezahlungen (#5976) wurde Bug #2936 reaktiviert:
 * Beim Aktualisieren von CNs werden die Payment Items der CN zugewiesen durch verknüpfte Items.
 *
 * Dafür gab es zwar schon eimmal einen Check, aber der hat nur eine E-Mail geschickt
 * und nichts gemacht. Dazu kommt, dass heutzutage auch CNs Bezahlungen haben können.
 */
class Ext_Thebing_System_Checks_Payments_WrongItemAllocation extends Ext_TC_System_Check {

	private $aLog = [];

	public function getTitle() {
		return 'Wrong payment item allocation';
	}

	public function getDescription() {
		return 'Check for wrong allocation of payment items after invoice refresh.';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_inquiries_payments_items');
		Util::backupTable('kolumbus_inquiries_payments_overpayment');

		DB::begin(__CLASS__);

		$this->reallocateLostPayments();
		$this->reallocateLostOverPayments();

		DB::commit(__CLASS__);

		if(!empty($this->aLog)) {
			usort($this->aLog, function($aLog1, $aLog2) {
				return $aLog1['inquiry']->id > $aLog2['inquiry']->id;
			});

			$this->sendReport(join("\n", array_column($this->aLog, 'log')));
		}

		Ext_Gui2_Index_Stack::executeCache();

		return true;

	}

	/**
	 * Umgeschriebene Items, die nicht von einer CN stammen, aber nun einer CN zugewiesen sind, wieder auf eine Rechnung umschreiben
	 */
	private function reallocateLostPayments() {

		$sSql = "
			SELECT
				`kipi`.`id`,
				`kipi`.`amount_inquiry`,
				`kipi`.`amount_school`,
				`kipi`.`currency_inquiry`,
				`kipi`.`currency_school`,
				`kip`.`id` `payment_id`,
				`kip`.`inquiry_id`
			FROM
				`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`id` = `kipi`.`payment_id` AND
					`kip`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id`
			WHERE
				`kipi`.`active` = 1 AND
				/* CN kann nur Typ 4 und 5 haben */
				`kip`.`type_id` IN (1, 2, 3) AND
				`kid`.`type` = 'creditnote'
		";

		$aResult = (array)DB::getQueryRows($sSql);

		// Items pro Buchung und Zahlung summieren und löschen
		$aOverpayments = [];
		foreach($aResult as $aItem) {

			$sKey = $aItem['inquiry_id'].'_'.$aItem['payment_id'];

			if(!isset($aOverpayments[$sKey])) {
				$aOverpayments[$sKey] = [
					'inquiry_id' => $aItem['inquiry_id'],
					'payment_id' => $aItem['payment_id'],
					'amount_inquiry' => 0,
					'amount_school' => 0,
					'currency_inquiry' => $aItem['currency_school'],
					'currency_school' => $aItem['currency_inquiry']
				];
			}

			$aOverpayments[$sKey]['amount_inquiry'] += $aItem['amount_inquiry'];
			$aOverpayments[$sKey]['amount_school'] += $aItem['amount_school'];

			DB::updateData('kolumbus_inquiries_payments_items', ['active' => 0], '`id` = '.$aItem['id']);

		}

		// Overpayments generieren
		foreach($aOverpayments as $aData) {

			$oInquiry = Ext_TS_Inquiry::getInstance($aData['inquiry_id']);

			// In der Datenbank gelöschte Buchungen funktionieren nicht
			if(!$oInquiry->exist()) {
				continue;
			}

			$aInquiryOverpayments = $oInquiry->getOverpayments('invoice_without_proforma');

			// Wenn Overpayments vorhanden: Overpayment mit passender payment_id suchen
			$oOverpayment = null;
			if(!empty($aInquiryOverpayments)) {
				foreach($aInquiryOverpayments as $oTmpOverpayment) {
					if($oTmpOverpayment->payment_id == $aData['payment_id']) {
						$oOverpayment = $oTmpOverpayment;
						break;
					}
				}
			}

			if($oOverpayment === null) {
				// Irgendeine Rechnung suchen für Zuweisung (darf nur keine CN sein)
				$oDocument = $oInquiry->getLastDocument('invoice_without_proforma');

				// Bei MA wurde eine stornierte Buchung inkl. aller Dokumente gelöscht
				if(
					$oDocument === null &&
					!$oInquiry->isActive()
				) {
					continue;
				}

				$oOverpayment = new Ext_Thebing_Inquiry_Payment_Overpayment();
				$oOverpayment->payment_id = $aData['payment_id'];
				$oOverpayment->inquiry_document_id = $oDocument->id;
				$oOverpayment->currency_inquiry = $aData['currency_inquiry'];
				$oOverpayment->currency_school = $aData['currency_school'];
			}

			$oOverpayment->amount_inquiry += $aData['amount_inquiry'];
			$oOverpayment->amount_school += $aData['amount_school'];
			$oOverpayment->save();

			$this->appendLog($oInquiry, $oOverpayment, $aData['amount_inquiry']);

		}

	}

	/**
	 * Wenn Items umgeschrieben wurden, die CN aber nochmals aktualisiert wurde,
	 * gab es evtl. die Items nicht mehr und der Betrag wurde als Overpayment zugewiesen.
	 * Das wird durch die obige Methode aber nicht erkannt, da die Payment-Items gelöscht wurden.
	 */
	private function reallocateLostOverPayments() {

		// Nach Bearbeitung der CN könnte der Betrag als Overpayment neu zugewiesen worden sein (Items gelöscht)
		$sSql = "
			SELECT
				`kipo`.`id`,
				`kip`.`inquiry_id`
			FROM
				`kolumbus_inquiries_payments_overpayment` `kipo` INNER JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`id` = `kipo`.`payment_id` AND
					`kip`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kipo`.`inquiry_document_id`
			WHERE
				`kipo`.`active` = 1 AND
				/* CN kann nur Typ 4 und 5 haben */
				`kip`.`type_id` IN (1, 2, 3) AND
				`kid`.`type` = 'creditnote'
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			$oOverpayment = Ext_Thebing_Inquiry_Payment_Overpayment::getInstance($aRow['id']);
			$oInquiry = Ext_TS_Inquiry::getInstance($aRow['inquiry_id']);

			// In der Datenbank gelöschte Buchungen funktionieren nicht
			if(!$oInquiry->exist()) {
				continue;
			}

			// Irgendeine Rechnung suchen für Zuweisung (darf nur keine CN sein)
			$oDocument = $oInquiry->getLastDocument('invoice_without_proforma');

			$oOverpayment->inquiry_document_id = $oDocument->id;
			$oOverpayment->save();

			$this->appendLog($oInquiry, $oOverpayment, $oOverpayment->amount_inquiry);

		}

	}

	private function appendLog(Ext_TS_Inquiry $oInquiry, Ext_Thebing_Inquiry_Payment_Overpayment $oOverpayment, $fAmount) {

		// Index-Beträge in der Buchung aktualisieren
		$oInquiry->calculatePayedAmount();
		Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);

		$sLog =  'Customer: '.$oInquiry->getCustomer()->getCustomerNumber().' ('.$oInquiry->id.'), ';
		$sLog .= 'inquiry created: '.Ext_Thebing_Format::LocalDate($oInquiry->created).', ';
		$sLog .= 'allocated overpay amount: '.$fAmount.' '.Ext_Thebing_Currency::getInstance($oOverpayment->currency_inquiry)->getSign();
		$this->aLog[] = ['log' => $sLog, 'inquiry' => $oInquiry];

	}

}