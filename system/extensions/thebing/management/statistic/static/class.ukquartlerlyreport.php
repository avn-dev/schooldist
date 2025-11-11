<?php

/**
 * Statische Statistik – UK quarterly report
 *
 * @TODO Auf neue Struktur umstellen (Daten von Quic benutzen)
 */
class Ext_Thebing_Management_Statistic_Static_UkQuartlerlyReport extends Ext_Thebing_Management_Statistic_Static_Abstract {

	public static function getTitle() {
		return self::t('Quarterly report');
	}

	public static function isExportable() {
		return true;
	}

	protected function _getColumns() {

		$aColumns = array();

		$aColumns[] = array(
			'title' => self::t('Comm'),
			'color' => 'booking',
			'value' => 'weeks_agencies',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Non-Comm'),
			'color' => 'booking',
			'value' => 'weeks_direct',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Adult'),
			'color' => 'booking',
			'value' => 'weeks_adults',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Juniors'),
			'color' => 'booking',
			'value' => 'weeks_juniors',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		foreach($this->getCourseTypes() as $sKey=>$sCourseType) {
			$aColumns[] = array(
				'title' => self::t($sCourseType),
				'color' => 'service',
				'value' => 'weeks_course_type_'.$sKey,
				'format' => 'amount',
				'width' => Ext_TC_Util::getTableColumnWidth('number'),
				'summable' => true,
				'show_once' => true
			);
		}

		$aColumns[] = array(
			'title' => self::t('Other'),
			'color' => 'service',
			'value' => 'weeks_course_type_others',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		$aColumns[] = array(
			'title' => self::t('Total student weeks'),
			'color' => 'margin',
			'value' => 'weeks_total',
			'format' => 'amount',
			'width' => Ext_TC_Util::getTableColumnWidth('number'),
			'summable' => true,
			'show_once' => true
		);

		return $aColumns;
	}

	/**
	 * Hinweis: Eine Kurswoche wird nur gezählt, wenn der Kurs mehr als oder 
	 * gleich 10 Lektionen pro Woche hat. Bei den wöchentlichen Kursen kann man 
	 * es sehr leicht heraus finden da die Angabe in dem Kurs gespeichert ist. 
	 * Bei Lektionskursen muss die gebuchte Anzahl der Lektionen durch die 
	 * Wochenanzahl geteilt werden um das heraus zu finden.
	 * 
	 * @return array
	 */
	protected function _getQueryData() {

		$aWeeksParts = [];
		foreach($this->getCourseTypes() as $sKey=>$sCourseType) {
			$aWeeksParts[] = " IF(`ktc`.`uk_quarterly_course_type` = '".$sKey."', calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_course_type_".$sKey."` ";
		}
		$sWeeksPart = join(",\n", $aWeeksParts);

		$sSql = "
			SELECT
				`tc_c`.`nationality` `country_iso`,
				`ktc`.`uk_quarterly_course_type`,
				calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`) `weeks_total`,

				IF(`ts_i`.`agency_id` != 0,calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_agencies`,
				IF(`ts_i`.`agency_id` = 0,calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_direct`,
				
				IF(getAge(`tc_c`.`birthday`) >= `cdb2`.`adult_age`, calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_adults`,
				IF(getAge(`tc_c`.`birthday`) < `cdb2`.`adult_age`, calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_juniors`,
				
				SUM(IF(
					`ktc`.`per_unit` = 0,
					`ts_ijclc`.`lessons`,
					IF(`ts_ijclc`.`weeks` > 0, `ts_ijclc`.`lessons` / `ts_ijclc`.`weeks`, 0)
				)) `combination_weeks`,
				
				IF(`ktc`.`uk_quarterly_course_type` = '', calcWeeksFromCourseDates(:from, :until, `ts_ijc`.`from`, `ts_ijc`.`until`), 0) `weeks_course_type_others`,
				
				{$sWeeksPart}
			FROM
				`ts_inquiries` `ts_i` LEFT JOIN 
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` LEFT JOIN 
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND 
					`ts_ijc`.`active`      = 1 AND
					`ts_ijc`.`for_tuition` = 1 LEFT JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
					`ts_tcps`.`active` = 1 AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' LEFT JOIN
				`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
					`ts_ijclc`.`journey_course_id` = `ts_ijc`.`id` AND
					`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` LEFT JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`active` = 1  AND
					`ktc`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." AND
					`ktc`.`id` = `ts_tcps`.`type_id` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itoc` ON
					`ts_i`.`id` = `ts_itoc`.`inquiry_id` AND
					`ts_itoc`.`type` = 'traveller' LEFT  JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.id = `ts_itoc`.`contact_id` LEFT  JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`has_invoice` = 1 AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i`.`canceled` = 0 AND 
				`ts_ijc`.`from` <= :until AND
				`ts_ijc`.`until` >= :from
				{WHERE}
			GROUP BY
				`ts_ijc`.`id`
			HAVING
				`combination_weeks` >= 10
		";

		$aSql = array(
			'from' => $this->dFrom->format('Y-m-d'),
			'until' => $this->dUntil->format('Y-m-d')
		);

		// Filter-WHERE-Teile hinzufügen
		$this->_addWherePart($sSql, $aSql);

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		return $aResult;
	}

	/**
	 * Daten für die Ausgabe vorbereiten (Summen, GROUP_CONCAT trennen, Format…)
	 *
	 * @param array $aQueryData
	 * @return array
	 */
	protected function _prepareData(array $aQueryData, $aCountries, $aColumns) {
		$aData = array();
		
		$aCountries = array_flip($aCountries);
		$aCountries = array_values($aCountries);

		end($aCountries);
		$iLastCountryKey = key($aCountries);
		$iOtherCountryKey = $iLastCountryKey+1;
		
		foreach($aQueryData as $aItem) {

			$iKey = array_search($aItem['country_iso'], $aCountries);
			if($iKey === false) {
				$iKey = $iOtherCountryKey;
			}
			
			// Labels fangen erst bei 1 an
			$iKey++;

			foreach($aColumns as $aColumn) {
				if($aItem[$aColumn['value']] > 0) {
					$aData[$iKey][0][''][null][$aColumn['value']] += ceil($aItem[$aColumn['value']]);
					$aData['-'][0][''][null][$aColumn['value']] += ceil($aItem[$aColumn['value']]);
				}
			}

		}
		
		return $aData;
	}

	protected function _getReportData(&$aColumns, &$aData, &$aColumnColors, &$aLabels, &$aColumnWidths) {

		$aCountries = $this->getCountries();
		$aColumns = $this->_getColumns();
		
		$aQueryData = $this->_getQueryData();
		$aData = $this->_prepareData($aQueryData, $aCountries, $aColumns);

		$iLabelKey = 1;
		foreach($aCountries as $sIso=>$sCountry) {
			$aLabels[$iLabelKey] = array(
				'title' => $sCountry
			);
			$iLabelKey++;
		}

		// Andere Länder
		$aLabels[] = array(
			'title' => self::t('Other')
		);
		
		// Summe
		$aLabels['-'] = array(
			'title' => self::t('Sum')
		);

		$aColumnWidths = array('auto');

		foreach($aColumns as $aColumn) {
			$aLabels[1]['data'][$aColumn['value']] = array(
				'title' => $aColumn['title']
			);
			$aColumnColors[$aColumn['value']]['color_light'] = str_replace('#', '', \TsStatistic\Generator\Statistic\AbstractGenerator::getColumnColor($aColumn['color']));
			$aColumnWidths[$aColumn['value']] = '120px';
		}

	}
	
	public function render() {

		$aColumns = array();
		$aData = array();
		$aColumnColors = array();
		$aLabels = array();
		$aColumnWidths = array();

		$this->_getReportData($aColumns, $aData, $aColumnColors, $aLabels, $aColumnWidths);

		$oSmarty = new SmartyWrapper();
		$oSmarty->assign('aData', $aData);
		$oSmarty->assign('aColumns', $aColumns);
		$oSmarty->assign('aColors', $aColumnColors);
		$oSmarty->assign('aLabels', $aLabels);
		$oSmarty->assign('iListType', 1);
		$oSmarty->assign('aColumnWidths', $aColumnWidths);

		$sOutput = $oSmarty->fetch(Ext_Thebing_Management_PageBlock::getTemplatePath().'result.tpl');

		return $sOutput;
	}

	public function getExport() {

		$aColumns = array();
		$aData = array();
		$aColumnColors = array();
		$aLabels = array();
		$aColumnWidths = array();

		$this->_getReportData($aColumns, $aData, $aColumnColors, $aLabels, $aColumnWidths);

		$oStatistic = new Ext_Thebing_Management_Statistic;
		$oStatistic->list_type = 1;
		$oStatistic->title = self::getTitle();

		$oResult = new Ext_Thebing_Management_PageBlock_Result($oStatistic, array(), $aColumns);
		$oResult->setLabels($aLabels);
		$oResult->setData($aData);

		// Export Data
		$oExport = new Ext_Thebing_Management_PageBlock_Export($oStatistic, $oResult);
		$oExport->setColors($aColumnColors);
		$oExport->export();

	}
	
	/**
	 * Die Statistik soll nur bestimmte Länder berücksichtigen
	 * @return array
	 */
	public function getCountries() {
		
		$sLanguage = System::getInterfaceLanguage();
		
		$aCountryLabels = Data_Countries::getList($sLanguage);

		$aCountries = array(
			"AL",
			"DZ",
			"AO",
			"AR",
			"AM",
			"AT",
			"AZ",
			"BH",
			"BY",
			"BE",
			"BO",
			"BA",
			"BR",
			"BG",
			"KH",
			"CA",
			"CL",
			"CN",
			"CO",
			"CR",
			"HR",
			"CY",
			"CZ",
			"DK",
			"EC",
			"EG",
			"EE",
			"FI",
			"FR",
			"GE",
			"DE",
			"GR",
			"HK",
			"HU",
			"IS",
			"IN",
			"ID",
			"IR",
			"IQ",
			"IL",
			"IT",
			"JP",
			"JO",
			"KZ",
			"KW",
			"KG",
			"LV",
			"LB",
			"LY",
			"LI",
			"LT",
			"LU",
			"MO",
			"MK",
			"MY",
			"MX",
			"MD",
			"MC",
			"MN",
			"ME",
			"MA",
			"NP",
			"NL",
			"NO",
			"OM",
			"PK",
			"PS",
			"PA",
			"PY",
			"PE",
			"PH",
			"PL",
			"PT",
			"QA",
			"RO",
			"RU",
			"SA",
			"RS",
			"SG",
			"SK",
			"SI",
			"KR",
			"ES",
			"SE",
			"CH",
			"SY",
			"TW",
			"TJ",
			"TH",
			"TN",
			"TR",
			"TM",
			"UA",
			"AE",
			"GB",
			"UY",
			"UZ",
			"VE",
			"VN",
			"YE"
		);
		
		$aCountries = array_flip($aCountries);
		
		$aCountries = array_intersect_key($aCountryLabels, $aCountries);

		return $aCountries;
	}

	/**
	 * @return array
	 */
	private function getCourseTypes() {

		$aCourseTypes = \Ext_Thebing_Tuition_Course_Gui2::getUkQuarterlyReportCourseTypes(true, false);

		// Quic-Typen entfernen
		unset($aCourseTypes['teacher_development']);
		unset($aCourseTypes['summer_winter_camps']);

		return $aCourseTypes;

	}

}
