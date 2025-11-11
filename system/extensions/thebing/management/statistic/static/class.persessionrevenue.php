<?php

use PhpOffice\PhpSpreadsheet;

/**
 * Statische Statistik – Ertrag pro Leistungszeitraum (ELC)
 * https://redmine.thebing.com/redmine/issues/5601
 *
 * Schüler aller Kurse inklusive Rechnungspositionen (aufgeteilt nach Leistungszeitraum) auflisten, gruppiert nach Kursen
 */
class Ext_Thebing_Management_Statistic_Static_PerSessionRevenue extends Ext_Thebing_Management_Statistic_Static_Abstract {

	protected $bExport = false;
	protected $bUsePayments = false;

	public static function getTitle() {
		return self::t('Ertrag pro Zeitraum');
	}

	public static function isExportable() {
		return true;
	}

	protected function _getColumns() {
		$aColumns = array();

		// Da der Kunde recht verpeilt ist, Labels je nach Statistik austauschen
		if(!$this->bUsePayments) {
			$sAmountLabel = 'Ertrag';
			$sTotalAmountLabel = 'Totaler Ertrag';
			$sOtherAmountLabel = 'Andere Erträge';
		} else {
			$sAmountLabel = 'Bezahlung';
			$sTotalAmountLabel = 'Totale Bezahlung (Zeitraum)';
			$sOtherAmountLabel = 'Andere Bezahlbeträge';
		}

		$aColumns[] = array(
			'title' => self::t('Nachname'),
			'color' => 'booking',
			'value' => 'customer_lastname',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
			'title' => self::t('Vorname'),
			'color' => 'booking',
			'value' => 'customer_firstname',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
			'title' => self::t('Kundennummer'),
			'color' => 'booking',
			'value' => 'customer_number',
			'width' => Ext_TC_Util::getTableColumnWidth('customer_number')
		);

		$aColumns[] = array(
			'title' => self::t('Rechnungs-Nr.'),
			'color' => 'booking',
			'value' => 'document_numbers',
			'width' => Ext_TC_Util::getTableColumnWidth('document_number')
		);

		$aColumns[] = array(
			'title' => self::t('Agentur'),
			'color' => 'booking',
			'value' => 'agency_name',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
			'title' => self::t('Schülerstatus'),
			'color' => 'booking',
			'value' => 'customer_status',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
//			'title' => self::t('Kurswochen (Komplette Periode)'),
			'title' => 'Course weeks (complete period)',
			'color' => 'service',
			'value' => 'weeks_period_booking',
			'width' => 65,
			'summable' => true
		);

		$aColumns[] = array(
//			'title' => self::t('Kurswochen (Periode, fakturiert)'),
			'title' => 'Course weeks (complete period, invoiced)',
			'color' => 'service',
			'value' => 'weeks_period',
			'width' => 65,
			'summable' => true
		);

		/*$aColumns[] = array(
			'title' => self::t('Woche (Totale)'),
			'color' => 'service',
			'value' => 'weeks_total',
			'width' => 65,
			'summable' => true
		);*/

		$aColumns[] = array(
			'title' => 'Course start',
			'color' => 'service',
			'value' => 'course_start',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
			'title' => 'Course end',
			'color' => 'service',
			'value' => 'course_end',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
//			'title' => self::t('Startdatum Kurs (komplette Periode)'),
			'title' => 'Course start (complete period)',
			'color' => 'service',
			'value' => 'course_start_period',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
//			'title' => self::t('Enddatum Kurs (komplette Periode)'),
			'title' => 'Course end (complete period)',
			'color' => 'service',
			'value' => 'course_end_period',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
			'title' => self::t('Startdatum (Unterkunft)'),
			'color' => 'service',
			'value' => 'accommodation_start',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
			'title' => self::t('Enddatum (Unterkunft)'),
			'color' => 'service',
			'value' => 'accommodation_end',
			'format' => 'date',
			'width' => Ext_TC_Util::getTableColumnWidth('date')
		);

		$aColumns[] = array(
			'title' => self::t('Kurs'),
			'color' => 'service',
			'value' => 'course_name',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
			'title' => self::t('Unterkunft'),
			'color' => 'service',
			'value' => 'accommodation',
			'width' => Ext_TC_Util::getTableColumnWidth('name')
		);

		$aColumns[] = array(
			'title' => self::t($sTotalAmountLabel),
			'color' => 'revenue',
			'value' => 'amount_total_net',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t($sAmountLabel.' Kurs'),
			'color' => 'revenue',
			'value' => 'course_revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true
		);

		$aColumns[] = array(
			'title' => self::t($sAmountLabel.' Unterkunft'),
			'color' => 'revenue',
			'value' => 'accommodation_revenue',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t($sAmountLabel.' Transfer'),
			'color' => 'revenue',
			'value' => 'transfer_revenue',
			'format' =>'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t($sAmountLabel.' Versicherung'),
			'color' => 'revenue',
			'value' => 'insurance_revenue',
			'format' =>'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$this->_setAdditionalCostsRows($aColumns);

		$aColumns[] = array(
			'title' => self::t($sOtherAmountLabel),
			'color' => 'revenue',
			'value' => 'other_revenues',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Unterkunftskosten'),
			'color' => 'margin',
			'value' => 'accommodation_costs',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Unterkunftsgewinn'),
			'color' => 'margin',
			'value' => 'accommodation_profit',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('amount'),
			'summable' => true,
			'show_once' => true
		);

		return $aColumns;
	}

	/**
	 * ELC möchte für jede Zusatzgebühr eine eigene Spalte
	 * @param array $aColumns
	 */
	protected function _setAdditionalCostsRows(&$aColumns) {

		$sSql = "
			SELECT
				`id`,
				`name_".$this->_sLanguage."` `name`
			FROM
				`kolumbus_costs`
			WHERE
				`active` = 1 AND
				`idSchool` IN ( :schools )
			ORDER BY
				-- Sortierung: Generell, Kurs, Unterkunft
				FIELD(`type`, 2, 0, 1)
		";

		$aCosts = DB::getQueryRows($sSql, array(
			'schools' => $this->getSchools(true)
		));

		foreach($aCosts as $aCost) {
			$aColumns[] = array(
				'title' => $aCost['name'],
				'color' => 'revenue',
				'value' => 'additionalcost_'.$aCost['id'],
				'format' => 'amount',
				'width' => Ext_TC_Util::getTableColumnWidth('amount'),
				'summable' => true,
				'show_once' => true
			);
		}
	}

	/**
	 * @return Collection
	 */
	protected function _getQueryData() {

		$sPaymentSubQuery = '';
		$sWhere = '';
		if($this->bUsePayments) {
			$sPaymentSubQuery = "
				,
				(
					SELECT
						SUM(`kipi`.`amount_inquiry`)
					FROM
						`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipi`.`payment_id` AND
							`kip`.`active` = 1 AND
							`kip`.`type_id` IN (1, 2, 3)
					WHERE
						`kipi`.`item_id` = `kidvi`.`id`
				) `payment_amount`
			";

			$sWhere = "
				AND ABS(`ts_i`.`amount_payed`) != 0
			";
		}

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ktc`.`id` `course_id`, -- NULL bei Nicht-Kurs-Item
				`ktc`.`name_{$this->_sLanguage}` `course_name`, -- NULL bei Nicht-Kurs-Item
				`tc_c`.`firstname` `customer_firstname`,
				`tc_c`.`lastname` `customer_lastname`,
				`tc_cn`.`number` `customer_number`,
				GROUP_CONCAT(DISTINCT `kid`.`document_number` SEPARATOR ', ') `document_numbers`,
				MIN(`ts_ija`.`from`) `accommodation_start`,
				MAX(`ts_ija`.`until`) `accommodation_end`,
				GROUP_CONCAT(`kac`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_categories`,
				GROUP_CONCAT(`kar`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_roomtypes`,
				GROUP_CONCAT(`kam`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_mealtypes`,
				`ts_ijc`.`weeks` `weeks_total`, -- NULL bei Nicht-Kurs-Item
				`ts_ijc`.`from` `course_start`, -- NULL bei Nicht-Kurs-Item
				`ts_ijc`.`until` `course_end`, -- NULL bei Nicht-Kurs-Item
				`ka`.`ext_2` `agency_name`,
				`kss`.`text` `customer_status`,
				`kid`.`type` `document_type`,
				`kidv`.`tax` `item_tax_type`,
				`kidvi`.`id` `item_id`,
				`kidvi`.`type` `item_type`,
				`kidvi`.`type_id` `item_type_id`,
				`kidvi`.`index_from` `item_from`,
				`kidvi`.`index_until` `item_until`,
				`kidvi`.`tax` `item_tax`,
				`kidvi`.`amount` `item_amount`,
				`kidvi`.`amount_net` `item_amount_net`,
				`kidvi`.`amount_discount` `item_amount_discount`,
				`kidvi`.`index_special_amount_gross` `item_index_special_amount_gross`,
				`kidvi`.`index_special_amount_gross_vat` `item_index_special_amount_gross_vat`,
				`kidvi`.`index_special_amount_net` `item_index_special_amount_net`,
				`kidvi`.`index_special_amount_net_vat` `item_index_special_amount_net_vat`,
				`kc`.`calculate` `item_costs_charge`,
				GROUP_CONCAT(`kap`.`id`) `accommodation_payments_ids`,
				GROUP_CONCAT(`kap`.`amount`) `accommodation_payments_amounts`,
				GROUP_CONCAT(CONCAT(`ts_ijc_journey`.`id`, ',', `ts_ijc_journey`.`course_id`, ',', `ts_ijc_journey`.`from`, ',', `ts_ijc_journey`.`until`, ',', `ts_ijc_journey`.`weeks`) SEPARATOR ';') `journey_courses`
				{$sPaymentSubQuery}
			FROM
				`ts_inquiries` `ts_i` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 INNER JOIN
				/* Buchung muss irgendeine Kursbuchung im Zeitraum haben */
				`ts_inquiries_journeys_courses` `ts_ijc_journey` ON
					`ts_ijc_journey`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc_journey`.`from` <= :until AND
					`ts_ijc_journey`.`until` >= :from AND
					`ts_ijc_journey`.`active` = 1 AND
					`ts_ijc_journey`.`visible` = 1 LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_student_status` `kss` ON
					`kss`.`id` = `ts_i`.`status_id` AND
					`kss`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`entity_id` = `ts_i`.`id` AND
					`kid`.`type` IN ( :invoice_types ) AND
					`kid`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					`kidvi`.`onPdf` = 1 LEFT JOIN
				-- Kurse (wonach gruppiert wird) werden über das Item gejoint, nicht über den Journey
				-- Ist das Item kein Kurs-Item, sind das NULL-Werte
				`ts_inquiries_journeys_courses` AS `ts_ijc` ON
					`ts_ijc`.`id` = `kidvi`.`type_id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 AND
					`kidvi`.`type` = 'course' LEFT JOIN
				`kolumbus_tuition_courses` AS `ktc` ON
					`ts_ijc`.`course_id`= `ktc`.`id` LEFT JOIN
				-- Unterkünfte werden über die Journey gejoint, nicht das Item
				`ts_inquiries_journeys_accommodations` AS `ts_ija` ON
					`ts_ija`.`journey_id` = `ts_ij`.`id` AND
					`ts_ija`.`active` = 1 AND
					`ts_ija`.`visible` = 1 AND (
						-- Unterkunftszeitraum muss auch in den Filterzeitraum fallen
						`ts_ija`.`from` <= :until AND
						`ts_ija`.`until` >= :from
					) LEFT JOIN
				`kolumbus_accommodations_categories` `kac` ON
					`kac`.`id` = `ts_ija`.`accommodation_id` LEFT JOIN
				`kolumbus_accommodations_roomtypes` `kar` ON
					`kar`.`id` = `ts_ija`.`roomtype_id` LEFT JOIN
				`kolumbus_accommodations_meals` `kam` ON
					`kam`.`id` = `ts_ija`.`meal_id` LEFT JOIN
				`kolumbus_accommodations_payments` `kap` ON
					`kap`.`inquiry_accommodation_id` = `ts_ija`.`id` LEFT JOIN
				-- Zusätzliche Gebühren joinen
				-- Ist das Item keine zusätzliche Gebühr, sind das NULL-Werte
				`kolumbus_costs` `kc` ON (
					`kc`.`id` = `kidvi`.`type_id` AND
					`kidvi`.`type` IN (
						'additional_general',
						'additional_course',
						'additional_accommodation'
					)
				) LEFT JOIN

				-- JOINs für Filter
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
				`ts_i`.`canceled` = 0 AND
				-- Items ignorieren, die nicht auf dem PDF drauf sind (LEFT JOIN)
				`kidvi`.`id` IS NOT NULL AND (
					`kidvi`.`type` != 'course' OR (
						-- Kurs-Item muss in den Filterzeitraum reinfallen
						`ts_ijc`.`from` <= :until AND
						`ts_ijc`.`until` >= :from AND
						-- Kurs-Items ignorieren, dessen Journey-Kurs nicht sichtbar oder gelöscht ist (LEFT JOIN)
						`kidvi`.`type` = 'course' AND
						`ts_ijc`.`active` = 1 AND
						`ts_ijc`.`visible` = 1
					)
				)
				{$sWhere}
				{WHERE}
			GROUP BY
				`kidvi`.`id`
		";

		$oDocSearch = new Ext_Thebing_Inquiry_Document_Type_Search();

		$aSql = array(
			'invoice_types' => $oDocSearch->getSectionTypes('invoice_without_proforma'),
			'from' => $this->dFrom->format('Y-m-d'),
			'until' => $this->dUntil->format('Y-m-d')
		);

		// Filter-WHERE-Teile hinzufügen
		$this->_addWherePart($sSql, $aSql);

		$oDb = DB::getDefaultConnection();
		$oCollection = $oDb->getCollection($sSql, $aSql);

		return $oCollection;
	}

	/**
	 * Daten für die Ausgabe vorbereiten (Summen, GROUP_CONCAT trennen, Format…)
	 *
	 * @param Collection $oQueryResult
	 * @return array
	 */
	protected function _prepareData(Collection $oQueryResult) {

		$aData = [];
		$aCourses = [];
		$aCourseNames = [];
		$aInquiries = [];

		// Value der Column als Key setzen, damit schnell darauf zugegriffen werden kann
		$aColumns = $this->_getColumns();
		foreach($aColumns as $iKey => $aColumn) {
			unset($aColumns[$iKey]);
			$aColumns[$aColumn['value']] = $aColumn;
		}

		// Jede Row des Querys entspricht einem Item
		foreach($oQueryResult as $aItem) {

			// Erstbestens Item der Buchung als Buchung setzen, da der Query schon einige Daten liefert
			if(!isset($aInquiries[$aItem['inquiry_id']])) {
				$aInquiries[$aItem['inquiry_id']] = $aItem;
			}

			$aInquiries[$aItem['inquiry_id']]['amount_total_net'] += $this->getItemAmount($aItem);
			$aJourneyCourses = explode(';', $aItem['journey_courses']);

			switch($aItem['item_type']) {
				case 'course':

					// Vormerken, dass es diesen Kurs gibt, da nach Kursen gruppiert wird
					// in_array ist nötig, da der Kunde ansonsten pro Rechnung (mit Kurs-Position) erscheinen würde
					if(
						empty($aCourses[$aItem['course_id']]) ||
						!in_array($aItem['inquiry_id'], $aCourses[$aItem['course_id']])
					) {
						$aCourses[$aItem['course_id']][] = $aItem['inquiry_id'];
						$aCourseNames[$aItem['course_id']] = $aItem['course_name'];
					}

					// Ab hier werden Werte pro Kurs gesetzt, die nachher (bei der Kurs-Gruppierung) wieder aufgeteilt werden
					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_name'] = $aItem['course_name'];
					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['weeks_total'] = $aItem['weeks_total'];
					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_start'] = $aItem['course_start'];
					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_end'] = $aItem['course_end'];

					// Wochenperiode ausrechnen:
					// Wie viele Wochen dieses Kurses in den Filterzeitraum fallen
					$oCourseStart = new DateTime($aItem['course_start']);
					$oCourseEnd = new DateTime($aItem['course_end']);
					$iDays = \Core\Helper\DateTime::getDaysInPeriodIntersection($this->dFrom, $this->dUntil, $oCourseStart, $oCourseEnd);
					$iWeeks = ceil($iDays / 7);
					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['weeks_period'] = $iWeeks;

					$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_revenue'] += $this->getItemAmount($aItem, ['split' => true]);

					foreach ($aJourneyCourses as $aJourneyCourse) {
						[$iJourneyCourseId, $iCourseId, $sFrom, $sUntil] = explode(',', $aJourneyCourse);
						if (
							$aItem['course_id'] != $iCourseId ||
							!empty($aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['weeks_period_booking'][$iJourneyCourseId])
						) {
							continue;
						}
						$iWeeks = ceil(\Core\Helper\DateTime::getDaysInPeriodIntersection($this->dFrom, $this->dUntil, new DateTime($sFrom), new DateTime($sUntil)) / 7);
						$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['weeks_period_booking'][$iJourneyCourseId] = $iWeeks;
						$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_start_period'][] = new DateTime($sFrom);
						$aInquiries[$aItem['inquiry_id']]['course_data'][$aItem['course_id']]['course_end_period'][] = new DateTime($sUntil);
					}

					break;
				case 'accommodation':
				case 'extra_nights':
				case 'extra_weeks':
					$aInquiries[$aItem['inquiry_id']]['accommodation_revenue_total'] += $this->getItemAmount($aItem);
					$aInquiries[$aItem['inquiry_id']]['accommodation_revenue'] += $this->getItemAmount($aItem, ['split' => true]);
					break;
				case 'transfer':
					$aInquiries[$aItem['inquiry_id']]['transfer_revenue'] += $this->getItemAmount($aItem, ['split' => true]);
					break;
				case 'additional_general':
				case 'additional_course':
				case 'additional_accommodation':
					// Jede Zusatzgebühr hat eine eigene Spalte
					$aInquiries[$aItem['inquiry_id']]['additionalcost_'.$aItem['item_type_id']] += $this->getItemAmount($aItem, ['split' => true]);
					break;
				case 'special':
					// Specials werden bei der jeweiligen Position abgezogen
					break;
				case 'insurance':
					$aInquiries[$aItem['inquiry_id']]['insurance_revenue'] += $this->getItemAmount($aItem, ['split' => true]);
					break;
				default:
					$aInquiries[$aItem['inquiry_id']]['other_revenues'] += $this->getItemAmount($aItem, ['split' => true]);
					break;
			}

		}

		// Jede Buchung durchlaufen und Werte setzen
		foreach($aInquiries as &$aInquiry) {

			// Unterkunftsbezeichnung zusammenbauen
			$aInquiry['accommodation'] = $this->_buildAccommodationLabel($aInquiry);

			// Unterkunftsmargen ausrechnen
			if(!empty($aInquiry['accommodation_payments_amounts'])) {

				// Zum Splitten der Margen gesamten Unterkunftszeitraum verwenden
				$oFirstAccommodationStart = new DateTime($aInquiry['accommodation_start']);
				$oLastAccommodationEnd = new DateTime($aInquiry['accommodation_end']);

				$aAccommodationPaymentAmounts = explode(',', $aInquiry['accommodation_payments_amounts']);
				$fAccommodationCosts = array_sum($aAccommodationPaymentAmounts);
				$fAccommodationProfit = $aInquiry['accommodation_revenue_total'] - $fAccommodationCosts;

				// Margen afu den Zeitraum splitten
				$aInquiry['accommodation_costs'] = Ext_TC_Util::getSplittedAmountByDates($fAccommodationCosts, $this->dFrom, $this->dUntil, $oFirstAccommodationStart, $oLastAccommodationEnd);
				$aInquiry['accommodation_profit'] = Ext_TC_Util::getSplittedAmountByDates($fAccommodationProfit, $this->dFrom, $this->dUntil, $oFirstAccommodationStart, $oLastAccommodationEnd);
			}
		}

		// Referenz der foreach löschen, da Variable unten wieder benutzt wird
		// Ansonsten gibt es ein komisches Verhalten!
		unset($aInquiry);

		// Kurse nach Namen Sortieren
		// Das muss bereits hier passieren, da manche Zellen pro Buchung nicht doppelt angezeigt werden
		uasort($aCourseNames, function($sName1, $sName2) {
			return strcmp($sName1, $sName2);
		});

		// Jetzt alle Kurs durchlaufen und Daten finalisieren
		$aPassedInquiries = array();
		foreach($aCourseNames as $iCourseId => $sCourseName) {
			$aInquiryIds = $aCourses[$iCourseId];
			$aSum = array();

			foreach($aInquiryIds as $iInquiryId) {
				$aInquiry = $aInquiries[$iInquiryId];

				// Werte sind pro Kurs, also richtige Daten aus dem Array setzen
				$aInquiry = array_merge($aInquiry, $aInquiry['course_data'][$iCourseId]);

				$aInquiry['weeks_period_booking'] = array_sum($aInquiry['weeks_period_booking']);
				$aInquiry['course_start_period'] = min($aInquiry['course_start_period']);
				$aInquiry['course_end_period'] = min($aInquiry['course_end_period']);
				if ($aInquiry['course_start_period'] < $this->dFrom) {
					$aInquiry['course_start_period'] = $this->dFrom;
				}
				if ($aInquiry['course_end_period'] > $this->dUntil) {
					$aInquiry['course_end_period'] = $this->dUntil;
				}

				// Einmalig anzeigen und summieren
				foreach($aColumns as $sKey => $aColumn) {

					// Wenn eine Buchung mehrfach vorkommt,	dürfen die Beträge nur in der ersten Tabelle angezeigt werden
					if(
						!empty($aColumn['show_once']) &&
						in_array($iInquiryId, $aPassedInquiries)
					) {
						$aInquiry[$sKey] = '';
					}

					// Spalte summieren
					if(!empty($aColumn['summable'])) {
						$aSum[$sKey] += (float)$aInquiry[$sKey];
					}

				}

				$aPassedInquiries[] = $iInquiryId;
				$aData[$iCourseId][] = $aInquiry;
			}

			$aData[$iCourseId][] = $aSum;
		}

		// Werte formatieren
		foreach($aData as &$aInquiries) {
			foreach($aInquiries as &$aInquiry) {
				foreach($aColumns as $sKey => $aColumn) {
					if(!empty($aColumn['format'])) {

						if($aColumns[$sKey]['format'] === 'date') {
							// Datum nach Schuleinstellungen konvertieren
							if(!is_null($aInquiry[$sKey])) {
								if (!$aInquiry[$sKey] instanceof DateTime) {
									$aInquiry[$sKey] = new DateTime($aInquiry[$sKey]);
								}
								$oDate = $aInquiry[$sKey];
								$aInquiry[$sKey] = Ext_Thebing_Format::LocalDate($oDate->getTimestamp());
							}

						} elseif(
							!$this->bExport &&
							$aColumns[$sKey]['format'] === 'amount'
						) {
							// Währung formatieren
							// Im Export übernimmt PHPExcel das Formatieren!
							if(Ext_TC_Util::compareFloat($aInquiry[$sKey], 0, 2) > 0) {
								$aInquiry[$sKey] = Ext_Thebing_Format::Number($aInquiry[$sKey], null, reset($this->_aSchools)->id);
							} else {
								$aInquiry[$sKey] = '';
							}
						}
					}
				}
			}
		}

		return $aData;

	}

	public function render() {
		$oQueryData = $this->_getQueryData();
		$aData = $this->_prepareData($oQueryData);

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('aCourses', $aData);
		$oSmarty->assign('aColumns', $this->_getColumns());
		$oSmarty->assign('aColors', \TsStatistic\Generator\Statistic\AbstractGenerator::getColumnColors());

		$sOutput = $oSmarty->fetch(Ext_Thebing_Management_PageBlock::getTemplatePath().'static/persessionrevenue.tpl');

		return $sOutput;
	}

	/**
	 * @TODO Auf \TcStatistic\Generator\Table\Excel umstellen
	 */
	public function getExport() {
		$this->bExport = true;

		$aColumns = $this->_getColumns();
		$aColors = \TsStatistic\Generator\Statistic\AbstractGenerator::getColumnColors();

		$oQueryData = $this->_getQueryData();
		$aData = $this->_prepareData($oQueryData);

		$iColumnCount = count($aColumns) - 1;
		$aHeadStyle = array(
			'font' => array(
				'bold' => true
			),
			'fill' => array(
				'fillType' => PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'color' => array(
					'rgb' => ''
				)
			)
		);

		$oExcel = new PhpSpreadsheet\Spreadsheet();
		$oExcel->setActiveSheetIndex(0);
		//PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$iExcelRow = 1;

		foreach($aData as $aCourse) {
			$sLastColLetter = Ext_Thebing_Util::getColumnCodeForExcel($iColumnCount);

			// Kursname
			$aHeadStyle['fill']['color']['rgb'] = 'EEEEEE';
			$oExcel->getActiveSheet()->mergeCells('A'.$iExcelRow.':'.$sLastColLetter.$iExcelRow);
			$oExcel->getActiveSheet()->getStyle('A'.$iExcelRow.':'.$sLastColLetter.$iExcelRow)->applyFromArray($aHeadStyle);
			$oExcel->getActiveSheet()->setCellValue('A'.$iExcelRow, $aCourse[0]['course_name']);
			$iExcelRow++;

			// Header-Spalten
			foreach($aColumns as $iColumnIndex => $aColumn) {
				$sColumCol = Ext_Thebing_Util::getColumnCodeForExcel($iColumnIndex);

				$aHeadStyle['fill']['color']['rgb'] = str_replace('#', '', $aColors[$aColumn['color']]['color_light']);
				$oExcel->getActiveSheet()->getStyle($sColumCol.$iExcelRow.':'.$sLastColLetter.$iExcelRow)->applyFromArray($aHeadStyle);
				$oExcel->getActiveSheet()->setCellValue($sColumCol.$iExcelRow, $aColumn['title']);

				$oExcel->getActiveSheet()->getColumnDimension($sColumCol)->setAutoSize(true);
			}

			$iExcelRow++;

			// Daten
			foreach($aCourse as $aRow) {
				foreach($aColumns as $iColumnIndex => $aColumn) {
					$sColumCol = Ext_Thebing_Util::getColumnCodeForExcel($iColumnIndex);

					if($aColumn['summable']) {
						// Als Nummernzelle setzen
						if(is_numeric($aRow[$aColumn['value']])) {
							$oExcel->getActiveSheet()->getCell($sColumCol.$iExcelRow)->setValueExplicit($aRow[$aColumn['value']], PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
							$oExcel->getActiveSheet()->getStyle($sColumCol.$iExcelRow)->getNumberFormat()->setFormatCode(PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
						}
					} else {
						$oExcel->getActiveSheet()->setCellValue($sColumCol.$iExcelRow, $aRow[$aColumn['value']]);
					}
				}

				$iExcelRow++;
			}

			// Letzte Spalte ist Summenzeile
			$aHeadStyle['fill']['color']['rgb'] = 'EEEEEE';
			$oExcel->getActiveSheet()->getStyle('A'.($iExcelRow - 1).':'.$sLastColLetter.($iExcelRow - 1))->applyFromArray($aHeadStyle);

			// 1 Zeile Abstand zwischen den Kursen
			$iExcelRow++;
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . str_replace(' ', '_', self::getTitle()) . '.xlsx"');
		header('Cache-Control: max-age=0');

		$oWriter = new PhpSpreadsheet\Writer\Xlsx($oExcel);
		$oWriter->save('php://output');
	}
}
