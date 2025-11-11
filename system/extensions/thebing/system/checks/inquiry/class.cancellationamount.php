<?php

/**
 * Check überprüft, ob Stornobeträge bei Buchungen korrekt sind
 * Ticket #6133, Bug wurde in #5818 eingebaut
 */
class Ext_Thebing_System_Checks_Inquiry_CancellationAmount extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Check cancellation amount of bookings';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check cancellation amount of bookings';
		return $sDescription;
	}

	public function executeCheck() {

		$oLog = Log::getLogger('check_inquiry_cancellationamount');
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "
			SELECT
				`ts_i`.`id`
			FROM
				`ts_inquiries` `ts_i`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`canceled` != 0
		";

		$aInquiryIds = DB::getQueryCol($sSql);

		$iCounter = 1;
		foreach($aInquiryIds as $iInquiryId) {
			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

			try {

				$oInquiry->getAmount(false, true);

			} catch(Exception $e) {

				// Bei uralten Buchungen kann validate() Exceptions auslösen
				$oDate = DateTime::createFromFormat('U', $oInquiry->created);
				if($oDate > new DateTime('2011-12-31')) {
					$oLog->addError('Inquiry getAmount Exception', array($e->getMessage(), $oInquiry->aData));
				}

			}

			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}
			
			$iCounter++;

		}

		// Dokumente, die durch $oInquiry->save() (durch $oInquiry->getAmount()) eingefügt wurden, raustilgen
		Ext_Gui2_Index_Stack::clearStack();

		// Und gewünschte Buchungen neu einfügen
		foreach($aInquiryIds as $iInquiryId) {
			// Prio 1, da 0 bei großen Kunden zu Problemen führt
			Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 1);
		}

		Ext_Gui2_Index_Stack::save();

		// Aktuellen Stack direkt ausführen
		#Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

}