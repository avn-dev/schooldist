<?php

class Ext_Thebing_Accounting_Agency_Payment_Bookings extends Ext_Thebing_Gui2_Data {

	/**
	 * bereitet den Query für die Methode getTableQueryData vor
	 */
	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {

		$this->_oDb = DB::getDefaultConnection();

		$sSql = "
				SELECT
					AUTO_SQL_CALC_FOUND_ROWS /* Notwendig für Paginierung */
					`kip`.*,
					`ka`.`id` `agency_id`,
					`kip`.`id` `id`,
					UNIX_TIMESTAMP(`kip`.`created`) `created`,
					UNIX_TIMESTAMP(`kip`.`changed`) `changed`,
					`kip`.`editor_id` `user_id`,
					`tc_c`.`lastname` `lastname`,
					`tc_c`.`firstname` `firstname`,
					`tc_c_n`.`number` `customerNumber`,
					GROUP_CONCAT(DISTINCT `kid`.`document_number` SEPARATOR ', ') `document_numbers`,
					`ts_i`.`currency_id` `currency_id`,
					`ts_i_j`.`school_id` `school_id`,
					`kaap`.`amount_currency` `payment_currency_id`,
					`kip`.`type_id` `payment_type_id`,
					`kip`.`amount_inquiry` `document_payed`, /* Items müssen nicht unbedingt vorhanden sein! */
					`ts_i`.`amount` + `ts_i`.`amount_initial` - `ts_i`.`amount_payed` `document_balance`, /* Offener Betrag insgesamt */
					(
						".Ext_Thebing_Agency_Payment::getAmountUsedSql("`kaap`.`id`", "`kaap`.`amount_currency`", [], "`kip`.`id`")."
					) + (
						0
						/*".Ext_Thebing_Agency_Payment::getAmountUsedManualCreditnotesSql("`kaap`.`id`", "`kip`.`id`")."*/
					) `allocated_amount`,
					(
						/* Summe der Beträge aller betroffenen Rechnungen (Rechnung vs. Creditnote) */
						SELECT
							SUM(
								CASE
									WHEN
										`kid_sub`.`type` = 'creditnote' /* Auszahlung Gutschrift */
									THEN
										`kidvp_sub`.`amount_provision` - `kidvp_sub`.`amount_discount_provision`
									WHEN
										INSTR(`kid_sub`.`type`, 'netto') != 0
									THEN
										`kidvp_sub`.`amount_net` - `kidvp_sub`.`amount_discount_net`
									ELSE
										`kidvp_sub`.`amount_gross` - `kidvp_sub`.`amount_discount_gross`
								END
							)
						FROM
							`ts_documents_to_inquiries_payments` `ts_dtip_sub` INNER JOIN
							`kolumbus_inquiries_documents` `kid_sub` ON
								`kid_sub`.`id` = `ts_dtip_sub`.`document_id` AND
								`kid_sub`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents_versions_priceindex` `kidvp_sub` ON
								`kidvp_sub`.`version_id` = `kid_sub`.`latest_version` AND
								`kidvp_sub`.`active` = 1
						WHERE
							`ts_dtip_sub`.`payment_id` = `kip`.`id`
					) `document_amount`
				FROM
					`kolumbus_inquiries_payments_agencypayments` `kipa` INNER JOIN
					`kolumbus_inquiries_payments` `kip` ON
						`kipa`.`payment_id` = `kip`.`id` LEFT JOIN
					`kolumbus_accounting_agency_payments` `kaap` ON
						`kipa`.`agency_payment_id` = `kaap`.`id` INNER JOIN
					`ts_companies` `ka` ON
						`ka`.`id` = `kaap`.`agency_id` LEFT JOIN
					`ts_documents_to_inquiries_payments` `ts_dtip` ON
						`ts_dtip`.`payment_id` = `kip`.`id` LEFT JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`id` = `ts_dtip`.`document_id` AND
						`kid`.`active` = 1 INNER JOIN
					-- Inquiry direkt über kip joinen, da nicht unbedingt Items vorhanden sein müssen
					`ts_inquiries` `ts_i` ON
						`ts_i`.`id` = `kip`.`inquiry_id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
						`tc_c`.`active` = 1 INNER JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
					`customer_db_2` `cdb2` ON
						`cdb2`.`id` = `ts_i_j`.`school_id`
				";

		$aSqlParts['where'] = " WHERE
			`kip`.`active` = 1
		";

		$aSqlParts['groupby'] = " `kip`.`id` ";

		$this->setParentGuiWherePartByRef($aSqlParts, $aSql);

	}

	/**
	 * @param string $sL10NDescription
	 * @return mixed
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);
		$aData['delete_question'] = L10N::t('Die Zahlung ist eventuell mit einer Gutschrift verrechnet worden. Wollen Sie die Zahlung trotzdem löschen?', $sL10NDescription);

		return $aData;
	}

}
