<?php

class Ext_TS_System_Checks_Inquiry_Storno_Amount extends GlobalChecks {
	
	protected $_aSchools = array();
	
	public function getTitle() {
		return 'Booking cancellation';
	}
	
	public function getDescription() {
		return 'Edit wrong amount columns of cancelled bookings';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackup = Ext_Thebing_Util::backupTable('ts_inquiries');
		if($bBackup == false) {
			__pout('Backup error!');
			return false;
		}
		
		DB::begin('Ext_TS_System_Checks_Inquiry_Storno_Amount');
		
		try {			
			$aCancelledInquiries = $this->_getCancelledInquiries();			
			$iCount = 0;
			
			foreach($aCancelledInquiries as $aInquiry) {
				// Gesamtbetrag aller Rechnungen (ohne Storno)
				$fInvoiceAmount	= $this->_getDocumentsAmount($aInquiry['id'], 'invoice_without_storno');
				// Betrag der Stornorechnung
				$fStornoAmount = $this->_getDocumentsAmount($aInquiry['id'], 'storno') * (-1);			
				// gespeicherter Betrag der Buchung
				$fInquiryAmount	= (float) $aInquiry['amount'];

				if(
					$fInvoiceAmount > 0 &&
					// Wenn der Stornobetrag gleich dem Gesamtbetrag aller Rechnungen (ohne Storno) ist, muss in der ts_inquiry 
					// die Betragsspalten auf 0 stehen
					Ext_Thebing_Util::compareFloat($fInvoiceAmount, $fStornoAmount) === 0 &&
					$fInquiryAmount != 0
				) {
					$aData = array(
						'amount_initial'	=> 0,
						'amount'			=> 0,
						'canceled_amount'	=> 0
					);
					DB::updateData('ts_inquiries', $aData, ' `id` = '.$aInquiry['id']);
					Ext_Gui2_Index_Stack::add('ts_inquiry', $aInquiry['id'], 0);
					++$iCount;
				}				
			}

			if($iCount > 0) {
				Ext_Gui2_Index_Stack::executeCache();
			}
			
		} catch (Exception $ex) {
			DB::rollback('Ext_TS_System_Checks_Inquiry_Storno_Amount');
			__pout($ex);
			return false;
		}
		
		DB::commit('Ext_TS_System_Checks_Inquiry_Storno_Amount');
		return true;
	}
	
	/**
	 * Liefert alle stornierten Buchungen
	 * 
	 * @return array
	 */
	protected function _getCancelledInquiries() {
		
		$sSql = "
			SELECT
				`ts_i`.*,
				`ts_ij`.`school_id`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`active` = 1
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`group_id` = 0 AND 
				`ts_i`.`canceled` > 0
		";
		
		return (array) DB::getQueryData($sSql);		
	}
	
	/**
	 * Liefert den Gesamtbetrag einer Buchungen bezogen auf bestimmte Dokumententypen
	 * 
	 * @param int $iInquiryId
	 * @param string $sType
	 * @return float
	 */
	protected function _getDocumentsAmount($iInquiryId, $sType) {
		$mReturn = Ext_Thebing_Inquiry_Document_Search::search($iInquiryId, $sType, true, false);
		$fAmount = 0;

		foreach($mReturn as $aDocument)  {			
			$fAmount += $this->_getAmount($aDocument['latest_version_id']);			
		}
		
		return $fAmount;
	}

	/**
	 * Liefert den Betrag einer Version
	 * 
	 * @param int $iVersion
	 * @return float
	 */
	protected function _getAmount($iVersion) {
		
		$sSql = "
			SELECT
				SUM(`kidvi`.`amount_net`) `amount`
			FROM
				`kolumbus_inquiries_documents_versions` `kidv` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					`kidvi`.`onPdf` = 1
			WHERE
				`kidv`.`id` = :version AND
				`kidv`.`active` = 1
			GROUP BY
				`kidv`.`id`
		";
		
		$fAmount = 0;
		if($iVersion > 0) {
			$fAmount = (float) DB::getQueryOne($sSql, array('version' => $iVersion));		
		}
		
		return $fAmount;
	}
	
	/**
	 * Liefert Informationen zu den Kontakten einer Buchung
	 * 
	 * @param int $iInquiry
	 * @return array
	 */
	protected function _getCustomerData($iInquiry) {
		
		$sSql = "
			SELECT
				`ts_ic`.`type`,
				`tc_c`.`firstname`,
				`tc_c`.`lastname`,
				`tc_cn`.`number`
			FROM
				`ts_inquiries_to_contacts` `ts_ic` LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_ic`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id`
			WHERE
				`ts_ic`.`inquiry_id` = :inquiry
		";

		$aData = (array) DB::getQueryData($sSql, array('inquiry' => $iInquiry));

		return $aData;
	}
	
	/**
	 * Liefert den Namen einer Schule
	 * 
	 * @param int $iSchool
	 * @return string
	 */
	protected function _getSchool($iSchool) {
		if(!isset($this->_aSchools[$iSchool])) {			
			$sSql = "
				SELECT
					`ext_1`
				FROM
					`customer_db_2`
				WHERE
					`id` = :school
			";
			
			$this->_aSchools[$iSchool] = DB::getQueryOne($sSql, array('school' => $iSchool));			
		}
		
		return $this->_aSchools[$iSchool];
	}
}
