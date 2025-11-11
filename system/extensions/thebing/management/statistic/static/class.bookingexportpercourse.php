<?php

class Ext_Thebing_Management_Statistic_Static_BookingExportPerCourse extends Ext_Thebing_Management_Statistic_Static_Abstract {

	protected $_iWeeksThisYear = 0;

	public static function getTitle() {
		return self::t('Buchungsexport pro Kurs');
	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param int $iYear
	 */
	public function __construct(Ext_Thebing_School $oSchool, $iYear) {
		$this->_aSchools = array($oSchool);
		$this->_sLanguage = $oSchool->getLanguage();

		$this->dFrom = new DateTime($iYear.'-01-01');
		$this->dUntil = new DateTime($iYear.'-12-31');

		// Wie viele Wochen hat das ausgewählte Jahr?
		$this->_iWeeksThisYear = (int)$this->dUntil->format('W');
		if($this->_iWeeksThisYear === 1) {
			$oTmpDate = clone $this->dUntil;
			$oTmpDate->sub(new DateInterval('P1W'));
			$this->_iWeeksThisYear = (int)$oTmpDate->format('W');
		}

		$this->_setColumns();
	}

	/**
	 * Liefert alle Jahre aller vorhandenen Kursbuchungen für das Select
	 * @return array
	 */
	public static function getJourneyCourseYears() {

		$sSql = "
			(
				SELECT
					DISTINCT YEAR(`from`)
				FROM
					`ts_inquiries_journeys_courses`
				WHERE
					`active` = 1
			) UNION (
				SELECT
					DISTINCT YEAR(`until`)
				FROM
					`ts_inquiries_journeys_courses`
				WHERE
					`active` = 1
			)
		";

		$aYears = DB::getQueryCol($sSql);

		return $aYears;

	}

	protected function _setColumns() {

//		$this->_aColumns[] = array(
//			'title' => self::t('Inquiry-ID'),
//			'value' => 'inquiry_id',
//		);

		$this->_aColumns[] = array(
			'title' => self::t('ID'),
			'value' => 'journey_course_id',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kundennummer'),
			'value' => 'customer_number',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Registrierungsdatum'),
			'value' => 'inquiry_created',
			'format' => 'date'
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kanal'),
			'value' => 'canal',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Agenturkategorie'),
			'value' => 'agency_category',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Quelle'),
			'value' => 'inquiry_referrer',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Agentur'),
			'value' => 'agency_name',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Provisionskategorie'),
			'value' => 'commission_category',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Agenturgruppe'),
			'value' => 'agency_groups',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Nachname'),
			'value' => 'customer_lastname',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Vorname'),
			'value' => 'customer_firstname',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Alter'),
			'value' => 'customer_age'
		);

		$this->_aColumns[] = array(
			'title' => self::t('Adresse'),
			'value' => 'customer_address',
		);

		$this->_aColumns[] = array(
			'title' => self::t('PLZ'),
			'value' => 'customer_zip'
		);

		$this->_aColumns[] = array(
			'title' => self::t('Stadt'),
			'value' => 'customer_city',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Land'),
			'value' => 'customer_country',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurs Startdatum'),
			'value' => 'course_from',
			'format' => 'date'
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurs Enddatum'),
			'value' => 'course_until',
			'format' => 'date'
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurswochen'),
			'value' => 'course_weeks',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurskategorie'),
			'value' => 'course_category',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurs'),
			'value' => 'course_name',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurs (Abkürzung)'),
			'value' => 'course_name_short',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Klassenraum'),
			'value' => 'course_classrooms',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Lehrer'),
			'value' => 'course_teachers',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Kurslektionen'),
			'value' => 'course_lessons',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Unterkunft'),
			'value' => 'accommodation',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Transfer'),
			'value' => 'transfer',
		);

		$this->_aColumns[] = array(
			'title' => self::t('Totaler Ertrag'),
			'value' => 'amount_total_net',
			'format' => 'amount',
			'summable' => true,
		);

		$this->_aColumns[] = array(
			'title' => self::t('Ertrag Kurs'),
			'value' => 'course_revenue',
			'format' => 'amount',
			'summable' => true
		);

		$this->_aColumns[] = array(
			'title' => self::t('Ertrag Unterkunft'),
			'value' => 'accommodation_revenue',
			'format' => 'amount',
			'show_once' => true,
			'summable' => true,
		);

		$this->_aColumns[] = array(
			'title' => self::t('Ertrag Transfer'),
			'value' => 'transfer_revenue',
			'format' => 'amount',
			'show_once' => true,
			'summable' => true,
		);

		$this->_aColumns[] = array(
			'title' => self::t('Zusätzliche Kursgebühren'),
			'value' => 'additionalcosts_course',
			'format' => 'amount',
			'show_once' => true,
			'summable' => true
		);

		$this->_aColumns[] = array(
			'title' => self::t('Zusätzliche Unterkunftsgebühren'),
			'value' => 'additionalcosts_accommodation',
			'format' => 'amount',
			'show_once' => true,
			'summable' => true
		);

		$this->_aColumns[] = array(
			'title' => self::t('Zusätzliche generelle Gebühren'),
			'value' => 'additionalcosts_general',
			'format' => 'amount',
			'show_once' => true,
			'summable' => true
		);

		$this->_addWeekColumns('course_week', self::t('Ertrag des Kurses'));
		$this->_addWeekColumns('lessons_week', self::t('Lektionen'));
		$this->_addWeekColumns('accommodation_week', self::t('Ertrag der Unterkunft'), true);
		$this->_addWeekColumns('commission_week', self::t('Provision'));
	}

	/**
	 * Fügt Spalten in der Anzahl der Wochen des Jahres hinzu
	 *
	 * @param string $sKey
	 * @param string $sLabel
	 * @param bool $bShowOnce
	 */
	protected function _addWeekColumns($sKey, $sLabel, $bShowOnce=false) {
		for($iWeek = 1; $iWeek < $this->_iWeeksThisYear + 1; $iWeek++) {
			$this->_aColumns[] = array(
				'title' => $sLabel.' '.self::t('Woche').' '.$iWeek,
				'value' => $sKey.'_'.$iWeek,
				'format' => 'number',
				'summable' => true,
				'show_once' => $bShowOnce
			);
		}
	}

	/**
	 * @return Collection
	 */
	protected function _getQueryData() {

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_i`.`created` `inquiry_created`,
				`ts_ijc`.`id` `journey_course_id`,
				`tc_cn`.`number` `customer_number`,
				-- `ts_i`.`tsp_transfer` `transfer_type`,
				`tc_r_i18n`.`name` `inquiry_referrer`,
				`ka`.`id` `agency_id`,
				`ka`.`ext_2` `agency_name`,
				`kagc`.`name` `agency_category`,
				GROUP_CONCAT(DISTINCT `kag`.`name`) `agency_groups`,
				GROUP_CONCAT(DISTINCT `kpg`.`name`) `commission_category`,
				`tc_c`.`lastname` `customer_lastname`,
				`tc_c`.`firstname` `customer_firstname`,
				`tc_c`.`birthday` `customer_birthday`,
				`tc_a`.`address` `customer_address`,
				`tc_a`.`zip` `customer_zip`,
				`tc_a`.`city` `customer_city`,
				`dc`.`cn_short_{$this->_sLanguage}` `customer_country`,
				`ts_ijc`.`from` `course_from`,
				`ts_ijc`.`until` `course_until`,
				`ts_ijc`.`weeks` `course_weeks`,
				`ktc`.`name_short` `course_name_short`,
				`ktc`.`name_{$this->_sLanguage}` `course_name`,
				JSON_EXTRACT(`ktc`.`lessons_list` , '$[0]') `course_lessons_per_week`,
				`ktc`.`per_unit` `course_per_unit`,
				GROUP_CONCAT(`ktc_combination`.`id`) `course_combination_ids`,
				GROUP_CONCAT(JSON_EXTRACT(`ktc_combination`.`lessons_list` , '$[0]')) `course_combination_lessons_per_week`,
				`ktcc`.`name_{$this->_sLanguage}` `course_category`,
				`ts_ijc`.`units` `course_lessons`,
				GROUP_CONCAT(DISTINCT `kcr`.`name`) `course_classrooms`,
				GROUP_CONCAT(DISTINCT CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`) SEPARATOR '; ') `course_teachers`,
				GROUP_CONCAT(`ts_ija`.`id`) `journey_accommodation_ids`,
				GROUP_CONCAT(`ts_ija`.`weeks`) `journey_accommodation_weeks`,
				GROUP_CONCAT(`kac`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_categories`,
				GROUP_CONCAT(`kar`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_roomtypes`,
				GROUP_CONCAT(`kam`.`short_{$this->_sLanguage}` SEPARATOR '{|}') `accommodation_mealtypes`,
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
				`kidvi`.`amount_provision` `item_amount_commission`,
				`kidvi`.`amount_discount` `item_amount_discount`,
				`kidvi`.`index_special_amount_net` `item_index_special_amount_net`,
				`kidvi`.`index_special_amount_net_vat` `item_index_special_amount_net_vat`,
				`kc`.`calculate` `item_costs_charge`,
				IF(`kac`.`accommodation_start` IS NOT NULL, `kac`.`accommodation_start`, `cdb2`.`accommodation_start`) `accommodation_start`
			FROM
				`ts_inquiries` `ts_i` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij`.`active` = 1 LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` AND
					`tc_c`.`active` = 1 LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN (
						`tc_contacts_to_addresses` AS `tc_cta` INNER JOIN
						`tc_addresses` AS `tc_a` INNER JOIN
						`tc_addresslabels` AS `tc_al`
					) ON
						`tc_cta`.`contact_id` = `tc_c`.`id` AND
						`tc_cta`.`address_id` = `tc_a`.`id` AND
						`tc_a`.`active` = 1 AND
						`tc_a`.`label_id` = `tc_al`.`id` AND
						`tc_al`.`active` = 1 AND
						`tc_al`.`type` = 'contact_address' LEFT JOIN
				`data_countries` `dc` ON
					`dc`.`cn_iso_2` = `tc_a`.`country_iso` LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ts_i`.`agency_id` LEFT JOIN
				`kolumbus_agency_categories` `kagc` ON
					`kagc`.`id` = `ka`.`ext_39` AND
					`kagc`.`active` = 1 LEFT JOIN (
						`kolumbus_agency_groups_assignments` AS `kaga` INNER JOIN
						`kolumbus_agency_groups` `kag`
					) ON
						`kaga`.`agency_id` = `ka`.`id` AND
						`kag`.`id` = `kaga`.`group_id` LEFT JOIN
				`ts_agencies_to_commission_categories` `kapg` ON
					`kapg`.`agency_id` = `ka`.`id` AND
					`kapg`.`active` = 1 AND (
						`ts_i`.`created` BETWEEN `kapg`.`valid_from` AND `kapg`.`valid_until` OR
						(
							`kapg`.`valid_until` = '0000-00-00' AND
							`ts_i`.`created` > `kapg`.`valid_from`
						) OR
						(
							`kapg`.`valid_from` = '0000-00-00' AND
							`kapg`.`valid_until` = '0000-00-00'
						)
					) LEFT JOIN
				`ts_commission_categories` `kpg` ON
					`kpg`.`id` = `kapg`.`group_id` AND
					`kpg`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity_id` = `ts_i`.`id` AND
					`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid`.`type` IN ( :invoice_types ) AND
					`kid`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					`kidvi`.`onPdf` = 1 LEFT JOIN
				`kolumbus_costs` `kc` ON
					`kc`.`id` = `kidvi`.`type_id` AND
					`kidvi`.`type` IN (
						'additional_general',
						'additional_course',
						'additional_accommodation'
					) LEFT JOIN
				-- Kursbuchungen werden über das Item gejoint, nicht über den Journey
				`ts_inquiries_journeys_courses` AS `ts_ijc` ON
					`ts_ijc`.`id` = `kidvi`.`type_id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 AND
					`kidvi`.`type` = 'course' LEFT JOIN
				-- Kursbuchungen nochmals joinen, alledings über Journey
				`ts_inquiries_journeys_courses` AS `ts_ijc2` ON
					`ts_ijc2`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc2`.`active` = 1 AND
					`ts_ijc2`.`visible` = 1 AND
					`ts_ijc2`.`from` <= :until AND
					`ts_ijc2`.`until` >= :from LEFT JOIN
				/* Erstmal drin gelassen da course_combination_ids im select existiert und Unterkurse gesammelt werden  */	
				`kolumbus_tuition_courses` AS `ktc` ON
					`ts_ijc`.`course_id` = `ktc`.`id` LEFT JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
					/* Normale Kurse verweisen in dem Programm nochmal auf sich selbst */
					`ts_tcps`.`type_id` != `ktc`.`id` AND
					`ts_tcps`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_courses` `ktc_combination` ON
					`ktc_combination`.`id` = `ts_tcps`.`type_id` AND
					`ktc_combination`.`active` = 1 LEFT JOIN
				`ts_tuition_coursecategories` `ktcc` ON
					`ktcc`.`id` = `ktc`.`category_id` AND
					`ktcc`.`active` = 1 LEFT JOIN (
						`kolumbus_tuition_blocks_inquiries_courses` AS  `ktbic` INNER JOIN
						`kolumbus_tuition_blocks` AS  `ktb` FORCE INDEX (PRIMARY) ON
							`ktbic`.`block_id` =  `ktb`.`id` AND
							`ktb`.`active` = 1 JOIN
						`ts_teachers` AS  `kt` ON
							`ktb`.`teacher_id` =  `kt`.`id` AND
							`kt`.`active` = 1 LEFT JOIN
						`kolumbus_classroom` `kcr` ON
							`kcr`.`id` = `ktbic`.`room_id` AND
							`kcr`.`active` = 1
					) ON
						`ktbic`.`inquiry_course_id` = `ts_ijc`.`id` AND
						`ktbic`.`active` = 1 LEFT JOIN
				-- Unterkünfte werden über die Journey gejoint, nicht das Item
				`ts_inquiries_journeys_accommodations` AS `ts_ija` ON
					`ts_ija`.`journey_id` = `ts_ij`.`id` AND
					`ts_ija`.`active` = 1 AND
					`ts_ija`.`visible` = 1 LEFT JOIN
				`kolumbus_accommodations_categories` `kac` ON
					`kac`.`id` = `ts_ija`.`accommodation_id` LEFT JOIN
				`kolumbus_accommodations_roomtypes` `kar` ON
					`kar`.`id` = `ts_ija`.`roomtype_id` LEFT JOIN
				`kolumbus_accommodations_meals` `kam` ON
					`kam`.`id` = `ts_ija`.`meal_id` LEFT JOIN
				`tc_referrers` `tc_r` ON
				 	`tc_r`.`id` = `ts_i`.`referer_id` LEFT JOIN
				`tc_referrers_i18n` `tc_r_i18n` ON
					`tc_r_i18n`.`referrer_id` = `tc_r`.`id` AND
					`tc_r_i18n`.`language_iso` = '{$this->_sLanguage}' LEFT JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`has_invoice` = 1 AND
				`ts_i`.`canceled` = 0 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_ij`.`school_id` IN ( :schools ) AND
				-- WHERE EXISTS ts_ijc, zum Überspringen unnötiger Buchungen
				`ts_ijc2`.`id` IS NOT NULL AND
				-- Items ignorieren, die nicht auf dem PDF drauf sind (LEFT JOIN)
				`kidvi`.`id` IS NOT NULL AND (
					`kidvi`.`type` != 'course' OR (
						`kidvi`.`type` = 'course' AND
						-- Kurs-Item muss in den Filterzeitraum reinfallen
						`ts_ijc`.`from` <= :until AND
						`ts_ijc`.`until` >= :from AND
						-- Kurs-Items ignorieren, dessen Journey-Kurs nicht sichtbar oder gelöscht ist (LEFT JOIN)
						`ts_ijc`.`active` = 1 AND
						`ts_ijc`.`visible` = 1
					)
				)
			GROUP BY
				`kidvi`.`id`
			ORDER BY
				`tc_cn`.`number`,
				`kidvi`.`index_from`
		";

		$oDocSearch = new Ext_Thebing_Inquiry_Document_Type_Search();

		$aSql = array(
			'invoice_types' => $oDocSearch->getSectionTypes('invoice_without_proforma'),
			'schools' => array_map(function($oSchool) {
				return $oSchool->id;
			}, $this->_aSchools),
			'from' => $this->dFrom->format('Y-m-d'),
			'until' => $this->dUntil->format('Y-m-d')
		);

		$oDB = DB::getDefaultConnection();
		$oResult = $oDB->getCollection($sSql, $aSql);
		#$oResult = DB::getQueryRows($sSql, $aSql);

		return $oResult;
	}

	/**
	 * Query-Rohdaten bearbeiten
	 *
	 * @param Collection $oCollection
	 * @return array
	 */
	protected function _prepareData(Collection $oCollection) {

		$aInquiries = array();
		$aJourneyCourses = array();
		$aJourneyAccommodationItems = array();

		$aTransferTypes = Ext_Thebing_Data::getTransferList();

		foreach($oCollection as $aItem) {
			$iInquiryId = $aItem['inquiry_id'];

			// Wenn Buchung noch nicht vorhanden, dann erstes Item als Buchung setzen
			if(!isset($aInquiries[$iInquiryId])) {
				$aInquiries[$iInquiryId] = $aItem;
			}

			// Totale
			$aInquiries[$iInquiryId]['amount_total_net'] += $this->getItemAmount($aItem);

			// Beträge aufteilen
			switch($aItem['item_type']) {
				case 'course':

					// Daten pro Kursbuchung
					$aJourneyCourses[$aItem['item_type_id']] = array(
						'inquiry_id' => $iInquiryId,
						'revenue' => $this->getItemAmount($aItem),
						'commission' => $aItem['item_amount_commission'],
						'from' => $aItem['course_from'],
						'until' => $aItem['course_until'],
						'weeks' => $aItem['course_weeks'],
						'category' => $aItem['course_category'],
						'lessons_per_week' => $aItem['course_lessons_per_week'],
						'combination_ids' => $aItem['course_combination_ids'],
						'combination_lessons_per_week' => $aItem['course_combination_lessons_per_week'],
						'per_unit' => (int)$aItem['course_per_unit'],
						'lessons' => $aItem['course_lessons'],
						'name' => $aItem['course_name'],
						'name_short' => $aItem['course_name_short'],
						'classrooms' => $aItem['course_classrooms'],
						'teachers' => $aItem['course_teachers']
					);

					break;
				case 'accommodation':
				case 'extra_nights':
				case 'extra_weeks':

					$fItemAmount = $this->getItemAmount($aItem);

					// Items zur Unterkunt separat sammeln
					// Diese müssen später auf die Wochen aufgeteilt werden!
					$aJourneyAccommodationItems[] = array(
						'journey_accommodation_id' => $aItem['item_type_id'],
						'from' => $aItem['item_from'],
						'until' => $aItem['item_until'],
						'revenue' => $fItemAmount,
						'commission' => $aItem['item_amount_commission']
					);

					$aInquiries[$iInquiryId]['accommodation_revenue'] += $fItemAmount;

					break;
				case 'transfer':
					$aInquiries[$iInquiryId]['transfer_revenue'] += $this->getItemAmount($aItem);
					break;
				case 'additional_general':
				case 'additional_course':
				case 'additional_accommodation':
					$aSplit = explode('_', $aItem['item_type']);
					$aInquiries[$iInquiryId]['additionalcosts_'.$aSplit[1]] += $this->getItemAmount($aItem);
					break;
			}

		}

		// Daten pro Buchung
		foreach($aInquiries as &$aInquiry) {

			// Kanal (Unterscheidung Agenturbuchung und Direktbuchung)
			if($aInquiry['agency_id'] > 0) {
				$aInquiry['canal'] = self::t('Agentur');
			} else {
				$aInquiry['canal'] = self::t('Direkt');
			}

			// Unterkunftbezeichung und Transfertyp
			$aInquiry['accommodation'] = $this->_buildAccommodationLabel($aInquiry);
			$aInquiry['transfer'] = $aTransferTypes[$aInquiry['transfer_type']];

			// Informationen über Wochen der Unterkunftsbuchungen kommen über GROUP_CONCAT; hier verarbeiten
			$aJourneyAccommodationWeeks = array();
			$aJourneyAccommodationIdsSplit = explode(',', $aInquiry['journey_accommodation_ids']);
			$aJourneyAccommodationWeeksSplit = explode(',', $aInquiry['journey_accommodation_weeks']);
			foreach($aJourneyAccommodationIdsSplit as $i => $iJourneyAccommodationId) {
				$aJourneyAccommodationWeeks[$iJourneyAccommodationId] = $aJourneyAccommodationWeeksSplit[$i];
			}

			// Aufteilung der Unterkunftsbeträge auf die Wochenspalten
			// Alle Unterkunft-Dokumentpositionen der Buchung durchlaufen
			foreach($aJourneyAccommodationItems as $aJourneyAccommodationItem) {

				$iWeeks = $aJourneyAccommodationWeeks[$aJourneyAccommodationItem['journey_accommodation_id']];

				$dItemFrom = new DateTime($aJourneyAccommodationItem['from']);

				// Starttag der Unterkunftswoche beachten #7627
				if(Ext_TC_Util::convertWeekdayToInt($aInquiry['accommodation_start']) > 4) {
					$dItemFrom->add(new DateInterval('P1W'));
				}

				// Unterkunftswochen »einfach« durchlaufen (analog zu Kursbuchungen)
				for($iWeek = 0; $iWeek < $iWeeks; $iWeek++) {
					$iCalendarWeek = $iWeek + (int)$dItemFrom->format('W');
					$aInquiry['accommodation_week_'.$iCalendarWeek] += $aJourneyAccommodationItem['revenue'] / $iWeeks;

					// Provisionen der Unterkunftsbeträge nicht direkt in die Spalte schreiben
					// Diese Beträge dürfen nur einmal (erste Kursbuchung) angezeigt werden, werden also nur dann addiert
					$aInquiry['commission_weeks'][$iCalendarWeek] += $aJourneyAccommodationItem['commission'] / $iWeeks;
				}
			}

		}

		// Daten pro Kursbuchung
		// => Finales Daten-Array aufbauen
		$aData = $this->_prepareJourneyCourseData($aInquiries, $aJourneyCourses);

		return $aData;
	}

	/**
	 * Zeilen-spezifische Daten vorbereiten (pro Kursbuchung)
	 *
	 * @param array $aInquiries
	 * @param array $aJourneyCourses
	 * @return array
	 */
	protected function _prepareJourneyCourseData(&$aInquiries, &$aJourneyCourses) {

		$aData = array();
		$aIteratedInquiries = array();

		foreach($aJourneyCourses as $iJourneyCourseId => $aJourneyCourseData) {

			$aTmp = array();
			$iInquiryId = $aJourneyCourseData['inquiry_id'];

			$oDateFrom = new DateTime($aJourneyCourseData['from']);
			//$oDateUntil = new DateTime($aJourneyCourseData['until']);

			// Spalten, die schon in der Buchung drin sind, in die Zeile der Kursbuchung anzeigen
			foreach($this->_aColumns as $aColumn) {

				if(
					isset($aInquiries[$iInquiryId][$aColumn['value']]) && (
						// Manche Spalten dürfen nur einmal pro Buchung angezeigt werden (dann nur bei der ersten Kursbuchung)
						!isset($aColumn['show_once']) || (
							$aColumn['show_once'] === true &&
							!in_array($iInquiryId, $aIteratedInquiries)
						)
					)
				) {
					$aTmp[$aColumn['value']] = $aInquiries[$iInquiryId][$aColumn['value']];
				}

			}

			// Informationen pro Kurs einfügen
			$aTmp['journey_course_id'] = $iJourneyCourseId;
			foreach($aJourneyCourseData as $sField => $mData) {
				$aTmp['course_'.$sField] = $mData;
			}

			// Alter bei Kursstart
			$oDate = new DateTime($aInquiries[$iInquiryId]['customer_birthday']);
			$aTmp['customer_age'] = $oDate->diff($oDateFrom)->y;

			// Kursbetrag auf entsprechende Wochen aufteilen
			// Das kann hier so einfach gemacht werden (ohne DatePeriod), da Kurse nicht in vollen Wochen laufen müssen
			// 	Beispiel: Eine Kursbuchung, der Dienstag startet, läuft nur bis Freitag, also keine volle Woche
			// Außerdem müssen keine Ferien beachtet werden, da die Kursbuchungen schon durch diese dupliziert wurden
			$fJourneyCourseAmountPerWeek = $aJourneyCourseData['revenue'] / $aJourneyCourseData['weeks'];

			// Lektionswochen aufteilen: Wochenkurs oder Lektionskurs?
			if($aJourneyCourseData['per_unit'] !== \Ext_Thebing_Tuition_Course::TYPE_PER_UNIT) {

				if(is_null($aJourneyCourseData['combination_ids'])) {
					$fCourseLessonsPerWeek = $aJourneyCourseData['lessons_per_week'];
				} else {

					// Kombinationskurse müssen durchlaufen werden
					// Durch das GROUP_CONCAT() sind die Werte doppelt, daher die IDs durchlaufen
					$aCourseChilds = array();
					$aCourseChildIds = explode(',', $aJourneyCourseData['combination_ids']);
					$aCourseChildLessonsPerWeek = explode(',', $aJourneyCourseData['combination_lessons_per_week']);
					foreach($aCourseChildIds as $i => $iCourseId) {
						$aCourseChilds[$iCourseId] = (float)$aCourseChildLessonsPerWeek[$i];
					}

					// Da die IDs als Keys benutzt werden, funktioniert das
					$fCourseLessonsPerWeek = array_sum($aCourseChilds);
				}

				// Bei Wochenkurses ist die totale Lektionsanzahl Wochen * Lektionen pro Woche
				// Bei Lektionskursen kommt die Spalte direkt aus dem Query
				$aTmp['course_lessons'] = $aJourneyCourseData['weeks'] * $fCourseLessonsPerWeek;

			} else {
				// Bei Lektionskursen wird die totale Anzahl an Lektionen durch die totale Anzahl der Wochen geteilt
				$fCourseLessonsPerWeek = $aJourneyCourseData['lessons'] / $aJourneyCourseData['weeks'];
			}

			// Kurswochen »einfach« durchlaufen (analog zu Unterkunftsbuchungen)
			for($iWeek = 0; $iWeek < $aJourneyCourseData['weeks']; $iWeek++) {
				$iCalendarWeek = $iWeek + (int)$oDateFrom->format('W');
				$aTmp['course_week_'.$iCalendarWeek] = $fJourneyCourseAmountPerWeek;
				$aTmp['lessons_week_'.$iCalendarWeek] = $fCourseLessonsPerWeek;
				$aTmp['commission_week_'.$iCalendarWeek] = $aJourneyCourseData['commission'] / $aJourneyCourseData['weeks'];
			}

			// Die übrigen Provisionswerte dürfen nur bei der ersten Kursbuchung einer Buchung angezeigt werden
			// Da die Wochen nicht identisch sein müssen, darf das nicht in der obrigen Schleife passieren
			foreach((array)$aInquiries[$iInquiryId]['commission_weeks'] as $iWeek => $fAmount) {
				$aTmp['commission_week_'.$iWeek] += $fAmount;
			}

			$aIteratedInquiries[] = $iInquiryId;
			$aData[] = $aTmp;
		}

		return $aData;
	}

	/**
	 * Werte formatieren
	 *
	 * @param array $aData
	 * @param bool $bExport
	 */
	protected function _formatData(&$aData, $bExport=false) {

		$aColumns = $this->_aColumns;
		foreach($aColumns as $iKey => $aColumn) {
			unset($aColumns[$iKey]);
			$aColumns[$aColumn['value']] = $aColumn;
		}

		foreach($aData as &$aJourneyCourse) {
			foreach($aJourneyCourse as $sColumn => &$mData) {

				if(empty($aColumns[$sColumn]['format'])) {
					continue;
				}

				if(
					$aColumns[$sColumn]['format'] === 'date' &&
					!empty($mData)
				) {
					$oDate = new DateTime($mData);
					$mData = Ext_Thebing_Format::LocalDate($oDate->getTimestamp());
				} elseif(
					(
						$aColumns[$sColumn]['format'] === 'number' ||
						$aColumns[$sColumn]['format'] === 'amount'
					) /*&& Ext_TC_Util::compareFloat($mData, 0, 2) > 0 && (
						$mData > -1 &&
						$mData < 1
					)*/
				) {
					$mData = Ext_Thebing_Format::Number($mData, null, reset($this->_aSchools)->id);
				}
			}
		}
	}

	public function render() {

	}

	/**
	 * CSV-Export
	 *
	 * PHPExcel/XSL wäre bei der Zellenmenge viel zu langsam!
	 */
	public function getExport() {

		$aColumns = $this->_aColumns;
		$oCollection = $this->_getQueryData();
		$aData = $this->_prepareData($oCollection);
		$this->_formatData($aData, true);

		$sCharset = $this->_aSchools[0]->getCharsetForExport();
		$sSeparator = $this->_aSchools[0]->export_delimiter;
		$oExport = new Gui2\Service\Export\Csv(self::getTitle());
		$oExport->setCharset($sCharset);
		$oExport->setSeperator($sSeparator);
		$oExport->sendHeader();

		// Header
		$oExport->sendLine(array_map(function($aColumn) {
			return $aColumn['title'];
		}, $aColumns));

		// Daten
		foreach($aData as $aJourneyCourseData) {
			$aCSVRow = array();

			foreach($aColumns as $iColumnIndex => $aColumn) {

				if(isset($aJourneyCourseData[$aColumn['value']])) {
					$aCSVRow[$iColumnIndex] = $aJourneyCourseData[$aColumn['value']];
				} else {

					$aCSVRow[$iColumnIndex] = '';

					// Leere Betragsspalten sollen mit 0 angezeigt werden
					if(
						$aColumn['format'] === 'number' ||
						$aColumn['format'] === 'amount'
					) {
						$aCSVRow[$iColumnIndex] = 0;
					}
				}
			}

			$oExport->sendLine($aCSVRow);
		}

		$oExport->end();
	}

}
