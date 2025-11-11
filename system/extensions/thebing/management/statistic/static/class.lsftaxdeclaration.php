<?php

/**
 * Statische Statistik – Komischer Steuer-Report für LSF
 */
class Ext_Thebing_Management_Statistic_Static_LsfTaxDeclaration extends Ext_Thebing_Management_Statistic_Static_Abstract {

	protected $bExport = false;

	public static function getTitle() {
		return self::t('LSF Tax Declaration');
	}

	public static function isExportable() {
		return true;
	}

	protected function getColumns() {
		$aColumns = array();

		$aColumns['agency_name'] = array(
			'title' => 'Agency',
			'color' => 'agency',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns['document_number'] = array(
			'title' => 'Invoice number',
			'color' => 'booking',
			'width' => Ext_TC_Util::getTableColumnWidth('document_number')
		);

		$aColumns['customer_number'] = array(
			'title' => 'Student ID',
			'color' => 'booking',
			'width' => Ext_TC_Util::getTableColumnWidth('customer_number')
		);

		$aColumns['customer_lastname'] = array(
			'title' => 'Last name',
			'color' => 'booking',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns['customer_firstname'] = array(
			'title' => 'First name',
			'color' => 'booking',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns['first_payment_date'] = array(
			'title' => 'First payment date',
			'color' => 'revenue',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
		);

		$aColumns['first_payment_amount'] = array(
			'title' => 'First payment amount',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['amount_total'] = array(
			'title' => 'Total revenue (based on main invoice, incl. Cancellation, incl. VAT)',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['amount_accommodation'] = array(
			'title' => 'Accommodation revenue (based on main invoice), incl. Cancellation, incl. VAT)',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['amount_total_without_accommodation'] = array(
			'title' => 'Total revenue minus accommodation revenue',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['amount_total_vat'] = array(
			'title' => 'Total taxes (based on main invoice)',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['declaration_amount'] = array(
			'title' => 'Amount for declaration',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['free_agency_tax_amount'] = array(
			'title' => 'Tax free amount of agency',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		$aColumns['declaration_amount_direct_booker'] = array(
			'title' => 'Amount to declare for direct booking',
			'color' => 'revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount')
		);

		return $aColumns;
	}

	protected function getQueryData() {

		$sSql = "
			SELECT
				`kid`.`id` `document_id`,
				`kid`.`document_number`,
				`kid`.`type` `document_type`,
				`tc_cn`.`number` `customer_number`,
				`tc_c`.`firstname` `customer_firstname`,
				`tc_c`.`lastname` `customer_lastname`,
				`ka`.`ext_1` `agency_name`,
				`kip`.`date` `first_payment_date`,
				`kip`.`amount_inquiry` `first_payment_amount`,
				`kidv`.`tax` `item_tax_type`,
				`kidvi`.`type` `item_type`,
				`kidvi`.`index_from` `item_from`,
				`kidvi`.`index_until` `item_until`,
				`kidvi`.`tax` `item_tax`,
				`kidvi`.`amount` `item_amount`,
				`kidvi`.`amount_net` `item_amount_net`,
				`kidvi`.`amount_provision` `item_amount_commission`,
				`kidvi`.`amount_discount` `item_amount_discount`,
				`kidvi`.`index_special_amount_net` `item_index_special_amount_net`,
				`kidvi`.`index_special_amount_net_vat` `item_index_special_amount_net_vat`,
				IF(`ka`.`id` IS NULL, 1, 0) `direct_booking`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`inquiry_id` = `ts_i`.`id` AND
					`kid`.`type` IN ( :invoice_types ) AND
					`kid`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					`kidvi`.`onPdf` = 1 LEFT JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`active` = 1 AND
					-- Nur erste Zahlung des Dokuments ist relevant, alle anderen sollen ignoriert werden, daher als Subselect
					`kip`.`id` = (
						SELECT
							`kip_sub`.`id`
						FROM
							`kolumbus_inquiries_payments` `kip_sub` INNER JOIN
							-- Über neue Tabelle gehen, da Joins auf Items und CN-Tabelle zusammen nicht funktioniert (OR-Join)
							`ts_documents_to_inquiries_payments` `ts_dtip` ON
								`ts_dtip`.`payment_id` = `kip_sub`.`id`
						WHERE
							`kip_sub`.`active` = 1 AND
							`ts_dtip`.`document_id` = `kid`.`id`
						ORDER BY
							`kip_sub`.`date`
						LIMIT
							1
					) LEFT JOIN
				-- JOINs für Statistik-Filter
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id` LEFT JOIN (
					`tc_contacts_to_addresses` AS `tc_cta` INNER JOIN
					`tc_addresses` AS `tc_a` INNER JOIN
					`tc_addresslabels` AS `tc_al`
				) ON
					`tc_cta`.`contact_id` = `tc_c`.`id` AND
					`tc_cta`.`address_id` = `tc_a`.`id` AND
					`tc_a`.`active` = 1 AND
					`tc_a`.`label_id` = `tc_al`.`id` AND
					`tc_al`.`active` = 1 AND
					`tc_al`.`type` = 'contact_address' LEFT JOIN (
						`kolumbus_agency_groups_assignments` AS `kaga` INNER JOIN
						`kolumbus_agency_groups` `kag`
					) ON
						`kaga`.`agency_id` = `ka`.`id` AND
						`kag`.`id` = `kaga`.`group_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`has_invoice` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`kip`.`date` BETWEEN :from AND :until
				{WHERE}
			GROUP BY
				`kidvi`.`id`
			ORDER BY
				`direct_booking`,
				`kip`.`date`,
				`kid`.`document_number` DESC
		";

		$oDocSearch = new Ext_Thebing_Inquiry_Document_Type_Search();

		$aSql = array(
			'invoice_types' => $oDocSearch->getSectionTypes('invoice_with_creditnote'),
			'from' => $this->dFrom->format('Y-m-d'),
			'until' => $this->dUntil->format('Y-m-d')
		);

		// Filter-WHERE-Teile hinzufügen
		$this->_addWherePart($sSql, $aSql);

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		return $aResult;

	}

	protected function prepareData(array $aQueryData) {

		$aColumns = $this->getColumns();
		$aDocuments = array();
		$aData = array();

		foreach($aQueryData as $aItem) {

			if(!isset($aDocuments[$aItem['document_id']])) {
				$aDocuments[$aItem['document_id']] = $aItem;
			}

			$bCN = $aItem['document_type'] === 'creditnote';

			if(
				$aItem['item_type'] === 'accommodation' ||
				$aItem['item_type'] === 'extra_nights' ||
				$aItem['item_type'] === 'extra_weeks'
			) {
				$aDocuments[$aItem['document_id']]['amount_accommodation'] += $this->getItemAmount($aItem, array('tax' => 1, 'commission_only' => $bCN));
			}

			$aDocuments[$aItem['document_id']]['amount_total'] += $this->getItemAmount($aItem, array('tax' => 1, 'commission_only' => $bCN));
			$aDocuments[$aItem['document_id']]['amount_total_vat'] += $this->getItemAmount($aItem, array('tax' => 2, 'commission_only' => $bCN));

		}

		// Rückwärts, da Ext_Thebing_Management_PageBlock_Result krsort() macht; +1 wegen Summenspalte
		$iData = count($aDocuments) + 1;
		foreach($aDocuments as $iDocumentId => $aDocumentData) {

			// Bezahlbetrag muss bei einer CN umgedreht werden…
			if($aDocumentData['document_type'] === 'creditnote') {
				$aDocumentData['first_payment_amount'] *= -1;
			}

			// Seltsame Werte ausrechnen, die nur der Kunde braucht
			$aDocumentData['amount_total_without_accommodation'] = $aDocumentData['amount_total'] - $aDocumentData['amount_accommodation'];
			$aDocumentData['declaration_amount'] = $aDocumentData['amount_total_vat'] / 0.2;
			$aDocumentData['free_agency_tax_amount'] = $aDocumentData['amount_total_without_accommodation'] - $aDocumentData['declaration_amount'];

			// Feld mit gleichem Wert soll nochmal angezeigt werden, aber nur bei Direktbuchern
			if($aDocumentData['direct_booking']) {
				$aDocumentData['declaration_amount_direct_booker'] = $aDocumentData['declaration_amount'];
			}

			foreach($aColumns as $sKey => $aColumn) {

				// Werte formatieren
				if($aColumn['format'] === 'date') {
					$oDate = new DateTime($aDocumentData[$sKey]);
					$aDocumentData[$sKey] = Ext_Thebing_Format::LocalDate($oDate->getTimestamp());
				} elseif(
					!$this->bExport &&
					$aColumn['format'] === 'amount'
				) {
					$aDocumentData[$sKey] = Ext_Thebing_Format::Number(round($aDocumentData[$sKey], 2), null, reset($this->_aSchools)->id);
				}

				// Werte in Statistiken-Struktur schreiben
				$aData[$iData][$sKey] = $aDocumentData[$sKey];
			}

			$iData--;
		}

		return $aData;

	}

	protected function getReportData() {

		$aReportData = array(
			'data' => array(),
			'labels' => array(),
			'colors' => array(),
			'widths' => array()
		);

		$aQueryData = $this->getQueryData();
		$aReportData['data'] = $this->prepareData($aQueryData);

		$aColumns = $this->getColumns();

		// Kopfzeile
		foreach($aColumns as $sKey => $aColumn) {
			$aReportData['labels'][1]['data'][$sKey] = array('title' => $aColumn['title']);
			$aReportData['colors'][$sKey]['color_light'] = str_replace('#', '', \TsStatistic\Generator\Statistic\AbstractGenerator::getColumnColor($aColumn['color']));
			$aReportData['widths'][$sKey] = $aColumn['width'];
		}

		return $aReportData;
	}

	public function render() {

		$aReportData = $this->getReportData();

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('aData', $aReportData['data']);
		$oSmarty->assign('aColumns', array());
		$oSmarty->assign('aColors', $aReportData['colors']);
		$oSmarty->assign('aLabels', $aReportData['labels']);
		$oSmarty->assign('iListType', 2);
		$oSmarty->assign('aColumnWidths', $aReportData['widths']);

		$sOutput = $oSmarty->fetch(Ext_Thebing_Management_PageBlock::getTemplatePath().'result.tpl');

		return $sOutput;

	}

	public function getExport() {

		$this->bExport = true;
		$aReportData = $this->getReportData();

		$oStatistic = new Ext_Thebing_Management_Statistic;
		$oStatistic->list_type = 2;
		$oStatistic->title = self::getTitle();

		$oResult = new Ext_Thebing_Management_PageBlock_Result($oStatistic, array(), array());
		$oResult->setLabels($aReportData['labels']);
		$oResult->setData($aReportData['data']);

		// Export Data
		$oExport = new Ext_Thebing_Management_PageBlock_Export($oStatistic, $oResult);
		$oExport->setColors($aReportData['colors']);
		$oExport->export();

	}

}
