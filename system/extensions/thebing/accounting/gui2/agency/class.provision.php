<?php

class Ext_Thebing_Accounting_Gui2_Agency_Provision extends Ext_Thebing_Gui2_Data {

	/**
	 * @param string $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */

	public function executeGuiCreatedHook() {

		$this->_oGui->name = 'ts_accounting_commission_payout';
//		$this->_oGui->set = ''; // Darf nicht null sein (Legacy-HTML war leerer String)

	}
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false) {

		switch($sIconAction) {
			case 'payment':
				$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, 'entity_id');
				break;
		}

		return parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
	}

	/**
	 * @param string $sL10NDescription
	 * @return array
	 */
	public function getTranslations($sL10NDescription) {

		$aData = (array)parent::getTranslations($sL10NDescription);
		$aPaymentData = (array)Ext_Thebing_Util::getPaymentTranslations();

		$aData = array_merge($aData, $aPaymentData);

		return $aData;
	}

	/**
	 * @param string $sAlias
	 * @return string
	 */
	public static function getSubQueryForOverpaymentAmount($sAlias = 'kid_cn') {

		if($sAlias == 'kid_cn') {
			$sSelect = 'ROUND(SUM(`kipo`.`amount_inquiry`), 2) * -1 `overpayment_amount`';
		} else {
			$sSelect = 'ROUND(SUM(`kipo`.`amount_inquiry`), 2) `overpayment_amount`';
		}

		$sQuery = "
			COALESCE((
				SELECT
					".$sSelect."
				FROM
					`kolumbus_inquiries_payments_overpayment` `kipo`
				WHERE
					`kipo`.`inquiry_document_id` = `".$sAlias."`.`id` AND
					`kipo`.`active` = 1
			), 0)
		";

		return $sQuery;
	}

	/**
	 * @param string $sAlias
	 * @return string
	 */
	public static function getSubQueryForAmountPayed($sAlias = 'kid_cn') {

		if($sAlias == 'kid_cn') {
			$sSelect = 'SUM(`kipi`.`amount_inquiry`) * -1 `amount`';
		} else {
			$sSelect = 'SUM(`kipi`.`amount_inquiry`) `amount`';
		}

		$sQuery = "
			COALESCE((
				SELECT
					".$sSelect."
				FROM
					`kolumbus_inquiries_payments` `kip` INNER JOIN
					`kolumbus_inquiries_payments_items` `kipi` ON
						`kipi`.`payment_id` = `kip`.`id` INNER JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi` ON
						`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
					`kolumbus_inquiries_documents_versions` `kidv` ON
						`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
					`kolumbus_inquiries_documents` `kid_sub` ON
						`kid_sub`.`id` = `kidv`.`document_id`
				WHERE
					`kipi`.`active` = 1 AND
					`kip`.`active` = 1 AND
					`kid_sub`.`id` = `".$sAlias."`.`id`
				GROUP BY
					`kid_sub`.`id`			
			), 0)";

		return $sQuery;
	}

	/**
	 * Anmerkung: Es ist eine große Scheiße, dass in den Betragsspalten den Rechnungsposition mal netto und mal brutto drin steht.
	 * Je nach Schuleinstellung muss hier die USt. addiert werden.
	 *
	 * @return string
	 */
	public static function getSubQueryForAmount($sAmountColumn = 'amount_provision', $bOnlyItemsWithCommition=false) {

		$sQuery = "
			COALESCE((
				SELECT
					ROUND(
						SUM(
							IF(
								`kidvi_sub`.`amount_discount` > 0,
								(
									## Discount ausrechnen
									`kidvi_sub`.`".$sAmountColumn."` -
									(
										`kidvi_sub`.`".$sAmountColumn."` / 100 * `kidvi_sub`.`amount_discount`
									)
								),
								`kidvi_sub`.`".$sAmountColumn."`
							) *
							IF(
								`kidv_sub`.`tax` = 2,
								(
									(`kidvi_sub`.`tax` / 100) + 1
								),
								1
							)
						),
					2)
				FROM
					`kolumbus_inquiries_documents_versions` `kidv_sub` INNER JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi_sub` ON
						`kidvi_sub`.`version_id` = `kidv_sub`.`id`
				WHERE
					`kidv_sub`.`document_id` = `kid_cn`.`id` AND
					`kidv_sub`.`id` = `kid_cn`.`latest_version` AND
					`kidvi_sub`.`onPDF` = 1 AND
					`kidvi_sub`.`active` = 1
		";
		
		if($bOnlyItemsWithCommition === true) {
			$sQuery .= " AND `kidvi_sub`.`amount_provision` != 0";
		}
		
		$sQuery .= "
			), 0)
		";

		return $sQuery;
	}

	/*
	 *  Baut den Query zusammen und ruft die Daten aus der DB ab
	 *  @todo: das hat hier niks zu suchen
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {

		$this->_oDb = DB::getDefaultConnection();

		$this->setFilterValues($aFilter);
		
		$aTypes = (array)Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_brutto_without_proforma');
		$aTypes[] = 'storno';

		$sTypes = "'".implode("', '", $aTypes)."'";

		$sSql = "
			SELECT
				AUTO_SQL_CALC_FOUND_ROWS
				`kid`.*,
				`kid_cn`.`document_number` `document_number_cn`,
				`tc_c_n`.`number` `customer_number`,
				CONCAT(`cdb1`.`lastname`, `cdb1`.`firstname`) `name`,
				`cdb1`.`lastname`,
				`cdb1`.`firstname`,
				".self::getSubQueryForAmount()." `creditnote_amount`,
				-- Der Bezahlte Betrag setzt sich aus allen Bezahlungen inkl. Überbezahlung zusammen
				".self::getSubQueryForAmountPayed()." + ".self::getSubQueryForOverPaymentAmount()." `creditnote_payed`,
				".self::getSubQueryForAmount('amount', true)." `creditnote_gross_amount`,
				`ki`.`currency_id` `currency_id`,
				`ts_i_j`.`school_id` `school_id`,
				/*`ki`.`id` `id`,*/
				`ta_a_n`.`number` `agency_number`,
				`ki`.`number` `inquiry_number`,
				`ka`.`ext_1` `agency`,
				`ka`.`ext_2` `agency_short`,
				`kg`.`name` `group`,
				`kg`.`short` `group_short`,
				`kid_cn`.`created` `creditnote_created`,
				`kid_cn`.`creator_id` `creditnote_creator_id`,
				`kid_cn`.`changed` `creditnote_changed`,
				`kid_cn`.`editor_id` `creditnote_editor_id`,
				`kid`.`id` `document_id`,
				(
					SELECT
						ROUND(
							SUM(
								IF(
									INSTR(`kid`.`type`, 'netto') = 0,
									`kidvp`.`amount_gross` - `kidvp`.`amount_discount_gross`,
									`kidvp`.`amount_net` - `kidvp`.`amount_discount_net`
								) + IF(
									`kidv`.`tax` = 2,
									IF(
										INSTR(`kid`.`type`, 'netto') = 0,
										`kidvp`.`amount_vat_gross`,
										`kidvp`.`amount_vat_net`
									),
									0
								)
							),
						2)
					FROM
						`kolumbus_inquiries_documents_versions_priceindex` `kidvp`
					WHERE
						`kidvp`.`version_id` = `kidv`.`id` AND
						`kidvp`.`active` = 1
				) `invoice_amount`,
				-- Der Bezahlte Betrag setzt sich aus allen Bezahlungen inkl. Überbezahlung zusammen
				".self::getSubQueryForAmountPayed('kid')." + ".self::getSubQueryForOverPaymentAmount('kid')." `invoice_payed`,
				/*`kic`.`from` `course_from`,
				`kic`.`until` `course_until`,
				`kia`.`from` `accommodation_from`,
				`kia`.`until` `accommodation_until`,*/
				`ki`.`created` `booking_date`,
				`kid`.`created` `document_date`,
				`kid_cn`.`created` `document_date_cn`,
				`ts_dvp`.`date` `document_finalpay_due_date_cn`,
				COALESCE(ROUND(`ts_dvp`.`amount`, 2), 0) `document_finalpay_amount_cn`,
				`kidv_cn`.`path` `path_cn`,
				(
					SELECT
						GROUP_CONCAT(CONCAT(`ts_ijc_sub`.`id`, ',', `ts_ijc_sub`.`from`, ',', `ts_ijc_sub`.`until`) SEPARATOR ';')
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi_sub` INNER JOIN
						`ts_inquiries_journeys_courses` `ts_ijc_sub` ON
							`ts_ijc_sub`.`id` = `kidvi_sub`.`type_id` AND
							`ts_ijc_sub`.`active` = 1 AND
							`ts_ijc_sub`.`visible` = 1
					WHERE
						`kidvi_sub`.`version_id` = `kidv_cn`.`id` AND
						`kidvi_sub`.`active` = 1 AND
						`kidvi_sub`.`onPdf` = 1 AND
						`kidvi_sub`.`type` = 'course'
				) `cn_course_data`,
				`kidv`.`has_commissionable_items`,
				`kidv_cn`.`has_commissionable_items` `has_commissionable_items_cn`
			FROM
				`ts_inquiries` as `ki` LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` = `ki`.`group_id` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ki`.`agency_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ki`.`id` AND
					`ts_i_j`.`type` & '".Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1` ON
					`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
					`cdb1`.`active` = 1 INNER JOIN
				`tc_contacts_numbers` `tc_c_n` ON
					`tc_c_n`.`contact_id` = `cdb1`.`id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ki`.`id` AND
					`kid`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` AND
					`kidv`.`active` = 1 LEFT JOIN
				`ts_documents_to_documents` `kidc` ON
					`kidc`.`parent_document_id` = `kid`.`id` AND
					`kidc`.`type` = 'creditnote' LEFT JOIN
				`kolumbus_inquiries_documents` `kid_cn` ON
					`kid_cn`.`id` = `kidc`.`child_document_id` AND
					`kid_cn`.`active` = 1 LEFT JOIN
				`ts_documents_to_documents` `ts_dtd_cancellation` ON
					`ts_dtd_cancellation`.`child_document_id` = `kid`.`id` AND
					`ts_dtd_cancellation`.`type` = 'cancellation' LEFT JOIN
				`kolumbus_inquiries_documents` `kid_cancellation` ON
					`kid_cancellation`.`id` = `ts_dtd_cancellation`.`parent_document_id` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv_cn` ON
					`kidv_cn`.`id` = `kid_cn`.`latest_version` AND
					`kidv_cn`.`active` = 1 LEFT JOIN
				`ts_documents_versions_paymentterms` `ts_dvp` ON
					`ts_dvp`.`version_id` = `kidv_cn`.`id` AND
					`ts_dvp`.`active` = 1 AND
					`ts_dvp`.`type` = 'final' LEFT JOIN
				`ts_companies_numbers` `ta_a_n` ON
					`ta_a_n`.`company_id` = `ki`.`agency_id` LEFT JOIN

				/* Filter */
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`journey_id` = `ts_i_j`.`id` AND
					`kic`.`active` = 1 LEFT JOIN
				`ts_inquiries_journeys_accommodations` `kia` ON
					`kia`.`journey_id` = `ts_i_j`.`id` AND
					`kia`.`active` = 1 LEFT JOIN
				`ts_inquiries_journeys_transfers` `kit_arr` ON
					`kit_arr`.`journey_id` = `ts_i_j`.`id` AND
					`kit_arr`.`active` = 1 AND
					`kit_arr`.`transfer_type` = 1 LEFT JOIN
				`ts_inquiries_journeys_transfers` `kit_dep` ON
					`kit_dep`.`journey_id` = `ts_i_j`.`id` AND
					`kit_dep`.`active` = 1 AND
					`kit_dep`.`transfer_type` = 2

		";

		$aSqlParts = array();
		$aSqlParts['where'] = " WHERE
			`kid`.`type` IN (".$sTypes.") AND (
				`kid`.`type` != 'storno' OR
				`kid_cancellation`.`type` IN (".$sTypes.")
			) AND
			`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
			`ki`.`active` = 1 AND
			`ki`.`confirmed` > 0 AND
			`ki`.`agency_id` > 0
		";
		$aSqlParts['groupby'] = "
			`kid`.`id`
		";
		$aSqlParts['order_by'] = " ORDER BY
			`ki`.`id` DESC
		";

		$aSql = array();

		$iSchoolId = (int)Ext_Thebing_School::getSchoolFromSession()->id;
		if($iSchoolId > 0) {
			$aSqlParts['where'] .= " AND
				`ts_i_j`.`school_id` = :school_id
			";
			$aSql['school_id'] = $iSchoolId;
		}

		// Filter in den Where Part einbauen
		$this->setQueryFilterDataByRef($aFilter, $aSqlParts, $aSql);

		// IDs mit filtern falls übergeben
		$this->setQueryIdDataByRef($aSelectedIds, $aSqlParts, $aSql);

		// WHERE und GROUP BY an den SELECT anhängen
		$sSql .= $aSqlParts['where'];

		// Query um den GROUP BY Teil erweitern
		$this->setQueryGroupByDataByRef($sSql, $aSqlParts['groupby']);

		// HAVING an den SELECT anhängen
		$sSql .= $aSqlParts['having'];

		$aColumnList = $this->_oGui->getColumnList();

		// Query um den ORDER BY Teil erweitern und den Spalten die sortierung zuweisen
		$this->setQueryOrderByDataByRef($sSql, $aOrderBy, $aColumnList, $aSqlParts['orderby']);

		if(!empty($this->_oGui->_aTableData['limit'])) {
			$iLimit = $this->_oGui->_aTableData['limit'];
		}

		$iEnd = 0;

		if(!$bSkipLimit) {
			// LIMIT anhängen!
			$this->setQueryLimitDataByRef($iLimit, $iEnd, $sSql);
		}

		$aResult = $this->_getTableQueryData($sSql, $aSql, $iEnd, $iLimit);

		return $aResult;
	}

	public static function getSelectOptionsBasedOnFilter(\Ext_Gui2 $gui2)
	{
		$options = [
			'course_start' => $gui2->t('Kursstart'),
			'accommodation_start' => $gui2->t('Unterkunftsstart'),
			'all_start'	=> $gui2->t('Alles Start'),
			'booking' => $gui2->t('Buchungsdatum'),
			'course_end' => $gui2->t('Kursende'),
			'accommodation_end'	=> $gui2->t('Unterkunftsende'),
			'all_end' => $gui2->t('Alles Ende'),
			'course_contact' => $gui2->t('Leistungszeitraum'),
			'document_date' => $gui2->t('Rechnungsdatum'),
			'document_created' => $gui2->t('Erstellungsdatum der Rechnung'),
			'creditnote_date' => $gui2->t('Gutschriftsdatum'),
			'creditnote_created' => $gui2->t('Erstellungsdatum der Gutschrift'),
			'creditnote_due_date' => $gui2->t('Fälligkeitsdatum (Gutschrift)'),
		];

		asort($options);

		return $options;
	}

	static public function getSelectOptionsCreditFilter(Ext_Gui2 $gui2)
	{

		return [
			'no_cn'	=> $gui2->t('keine CN'),
			'prepay' =>	$gui2->t('Teilzahlung'),
			'payed' => $gui2->t('Bezahlt'),
			'not_payed'	=> $gui2->t('noch nicht bezahlt')
		];
	}

	static public function getSelectOptionsGrossPaymentFilter(\Ext_Gui2 $gui2)
	{

		return [
			'payed' => $gui2->t('Bezahlt'),
			'not_payed'	=> $gui2->t('noch nicht bezahlt')
		];
	}

	static public function getSelectOptionsAgenciesFilter()
	{
		$oClient = Ext_Thebing_Client::getInstance();

		return $oClient->getAgencies(true);
	}

	static public function getDefaultFilterFrom()
	{
		$oDate = new WDDate();
		$oDate->add(1, WDDate::DAY);
		$oDate->sub(6, WDDate::MONTH);
		$iLastYear = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iLastYear);
	}

	static public function getDefaultFilterUntil()
	{
		$oDate = new WDDate();
		$oDate->add(1, WDDate::DAY);
		$iNow = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iNow);
	}

	static public function getFilterQueryCreditFilter()
	{

		return [
			'no_cn' => 'COALESCE((SELECT COUNT(*) FROM `ts_documents_to_documents` WHERE `parent_document_id` = `kid`.`id` AND `type` = \'creditnote\'), 0) <= 0',
			'prepay' => Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmountPayed().' > 0 AND '.Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmountPayed().' < '.Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmount(),
			'payed' => Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmount().' <= '.Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmountPayed(),
			'not_payed' => Ext_Thebing_Accounting_Gui2_Agency_Provision::getSubQueryForAmountPayed().' <= 0',
		];
	}

}
