<?php

class Ext_Thebing_Tuition_Report_Result {

	// Default GUI description
	protected static $_sDescription	= 'Thebing » Tuition » Own overview';

	// school object
	protected $_oSchool;

	protected $_oReport;

	protected $aColumns;

	// Date from object
	protected $_oDateFrom;

	// Date till object
	protected $_oDateTill;

	// School default language
	protected $_sDefaultLangFrontend;
	protected $_sDefaultLangBackend;

	// Selected days
	protected $_aDays				= array();

	// Selected travellers
	protected array $travellers = [];

	// Selected class times
	protected $_aTimes				= array();

	// Selected days times
	protected $_aDaysTimes			= array();

	// Prepared fields widths
	protected $_aWidths				= array();

	// The placeholders
	protected $_aPlaceholders		= array();

	protected $_aDaysShort = [];

	// State
	protected $_aFilterData			= array();

	public $bExport = false;

	private static $aCache = [];

	// request object
	protected $request;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 *
	 * @param int $iReportID
	 * @param int $iWeekStart
	 */
	public function __construct($aConfig) {

		$iReportID		= $aConfig['report_id'];
		$iWeekStart		= $aConfig['week'];
		$sExportType	= $aConfig['export_type'];

		$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();

		$this->_oReport = new Ext_Thebing_Tuition_Report($iReportID);

		$aColumns = $this->_oReport->columns;

		foreach($aColumns as $aColumn) {
			$this->aColumns[$aColumn['position']] = $aColumn;
		}

		$this->_oDateFrom = new WDDate((int)$iWeekStart);

		$this->_oDateTill = new WDDate($this->_oDateFrom);

		$this->_oDateTill->add(1, WDDate::WEEK)->sub(1, WDDate::SECOND);

		$this->_aFilterData = $aConfig['filter_data'];

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oSchool = Ext_Thebing_School::getInstance((int)\Core\Handler\SessionHandler::getInstance()->get('sid'));

		$this->_sDefaultLangFrontend = $oSchool->getInterfaceLanguage();
		$this->_sDefaultLangBackend = System::getInterfaceLanguage();

		$this->_aDaysShort = Ext_Thebing_Util::getLocaleDays($this->_sDefaultLangBackend, 'abbreviated');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "SET SESSION group_concat_max_len = 1048576";
		DB::executeQuery($sSQL);
	}

	/* ==================================================================================================== */

	/**
	 * Get data for HTML output
	 *
	 * @return string
	 */
	public function getTable() {

		if(
			!empty($this->request->input('course_start_from')) &&
			!empty($this->request->input('course_start_until'))
		) {
			$dateTimeFrom = new \DateTime($this->request->get('course_start_from'));
			$dateTimeUntil = new \DateTime($this->request->get('course_start_until'));

			if($dateTimeFrom > $dateTimeUntil) {
				$sMessage = '<h3>' . L10N::t('Die "Kursstartzeit von" muss vor der "Kursstartzeit bis" liegen', self::$_sDescription) . '</h3>';

				return [
					'code'		=> false,
					'message'	=> $sMessage
				];
			}
		}

		$aResults = $this->_getPreparedResults();

		if(!empty($aResults)) {
			$sCode = $this->_fetchCode($aResults);

			// Clear code for javascript
			$sCode = str_replace(array("\t", "\n", "\r"), '', $sCode);

			$aReturn = array(
				'code' => $sCode
			);
		} else {
			$sMessage = '<h3>' . L10N::t('Für diesen Zeitraum stehen keine Daten zur Verfügung', self::$_sDescription) . '</h3>';

			$aReturn = array(
				'code'		=> false,
				'message'	=> $sMessage
			);
		}

		return $aReturn;
	}


	/**
	 * Show PDF
	 */
	public function getPDF() {

		$aResults = $this->_getPreparedResults();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$oReportPDF = new Ext_Thebing_Tuition_Pdf_Report($this->_oReport, $this->_oSchool);

		$oPDF = $oReportPDF->getPDF();

		$oPDF->AddPage();

		$i = count($aResults);

		$sHeaderTemp = $this->_oReport->header;
		$sFooterTemp = $this->_oReport->footer;

		foreach((array)$aResults as $iKey => $aValue) {

			$oPlaceholder = new Ext_Thebing_Tuition_Placeholder($this, $this->_oDateFrom, $this->_aPlaceholders[$iKey], $aValue);

			$sCode = $this->_fetchCode([$iKey => $aValue], true);

			/*
			 * PDF Clonen zum prüfen ob die Tabelle auf die nächste Seite passt
			 * @todo Da gibt es auch Transaktionen für (startTransaction und rollbackTransaction/commitTransaction)
			 */
			$oPDFTemp = clone $oPDF;

			$sHeader = $oPlaceholder->replace($sHeaderTemp);
			$sFooter = $oPlaceholder->replace($sFooterTemp);

			$sHeader = str_replace('{current_page}', $oPDFTemp->getAliasNumPage(), $sHeader);
			$sHeader = str_replace('{total_pages}', $oPDFTemp->getAliasNbPages(), $sHeader);

			$sFooter = str_replace('{current_page}', $oPDFTemp->getAliasNumPage(), $sFooter);
			$sFooter = str_replace('{total_pages}', $oPDFTemp->getAliasNbPages(), $sFooter);

			// Beim Umbruch fängt es jede Seite eh neu an und braucht daher keine Prüfung
			if($this->_oReport->break != 1) {

				$iOldPages = $oPDFTemp->PageNo();

				$oPDFTemp->writeHTML($sHeader);
				$oPDFTemp->Ln(5);
				$oPDFTemp->writeHTML($sCode);

				$iNewPages = $oPDFTemp->PageNo();

				// Wenn die nächste Tabelle nicht mehr auf die Seite passt, dann neue Seite anlegen
				if($iOldPages != $iNewPages) {
					$oPDF->AddPage();
				}

			}

			$oPDF->writeHTML($sHeader);

			$oPDF->Ln(5);

			$oPDF->writeHTML($sCode);

			$oPDF->writeHTML($sFooter);

			if($i > 1) {
				$oPDF->Ln(5);
			}

			if($this->_oReport->break == 1 && $i-- > 1) {
				$oPDF->AddPage();
			}

		}

		$sFile = \Util::getCleanFileName($this->_oReport->title . '_' . date('Y-m-d') . '.pdf');

		$oPDF->Output($sFile, 'I');

	}

	/**
	 * Exportiert die Übersichten als CSV Datei
	 */
	public function getCSV(){

		$aResults = $this->_getPreparedResults();

		$sHeaderTemp = $this->_oReport->header;
		$sFooterTemp = $this->_oReport->footer;

		#$sSeparator = ';';
		#$sCharset = 'CP1252';
		#$sCharset = 'UTF-8';

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$sCharset = $oSchool->getCharsetForExport();
		$sSeparator = $oSchool->getSeparatorForExport();

		$sFilename = \Util::getCleanFileName($this->_oReport->title . '_' . date('Y-m-d') . '.csv');

		$sCodeAll = '';
		header('Content-type: text/x-csv');
		header('Content-Disposition: attachment; filename="'.$sFilename);

		foreach((array)$aResults as $iKey => $aValue){

			$oPlaceholder = new Ext_Thebing_Tuition_Placeholder($this, $this->_oDateFrom, $this->_aPlaceholders[$iKey]);

			$sHeader = $oPlaceholder->replace($sHeaderTemp);
			$sFooter = $oPlaceholder->replace($sFooterTemp);

			$sCodeAll .= '"' . Ext_TC_Util::prepareDataForCSV(strip_tags($sHeader), $sCharset) . '"' . $sSeparator . "\n";

			$sCode = $this->_fetchDataForCSV([$iKey => $aValue], $sCharset, $sSeparator);
			$sCodeAll .= $sCode;

			$sCodeAll .= '"' . Ext_TC_Util::prepareDataForCSV(strip_tags($sFooterTemp), $sCharset) . '"' . $sSeparator . "\n";

		}

		echo $sCodeAll;

		die();

	}

	/**
	 * Formatiert die Daten für den CSV Export
	 * @param array $aResults
	 * @return string
	 */
	protected function _fetchDataForCSV($aResults, $sCharset, $sSeparator = ';'){
		$sCode = '';

		$this->_prepareWidths();
		$aWidths	= $this->_aWidths;
		$aColors	= $this->_getFieldsColors();
		$aDays		= $this->_aDays;

		foreach((array)$aResults as $groupId=>$aTable) {
			foreach($aTable['data'] as $subGroupId=>$aData) {

				if(!empty($aData['title'])) {
					$sCode .= '"' . Ext_TC_Util::prepareDataForCSV($aData['title']) . '"' . $sSeparator . "\n";
				}

				// Überschriften
				foreach((array)$aWidths as $aWidth) {
					if ($aWidth['column_id'] == 76) {
						if (!empty($this->travellers[$groupId])) {
							foreach ($this->travellers[$groupId] as $travellerLabel) {
								$sCode .= '"' . Ext_TC_Util::prepareDataForCSV($travellerLabel, $sCharset) . '"' . $sSeparator;
							}
						}
					} elseif ($aWidth['column_id'] != 34) {
						if(!empty($aWidth['label'])) {
							$columnTitle = $aWidth['label'];
						} else {
							$columnTitle = $aColors[$aWidth['column_id']]['title'];
						}
						$sCode .= '"' . Ext_TC_Util::prepareDataForCSV($columnTitle, $sCharset) . '"' . $sSeparator;
					}else{
						foreach((array)$aDays as $iDay => $sDay){
							$sCode .= '"' . Ext_TC_Util::prepareDataForCSV($sDay, $sCharset) . '"' . $sSeparator;
						}
					}
				}
				$sCode .= "\n";

				// Eigentliche Daten
				foreach((array)$aData['data'] as $iKey => $aLine){
					foreach((array)$aWidths as $iColumnId => $aWidth){
						if ($aWidth['column_id'] == 76) {
							if (!empty($this->travellers[$groupId])) {
								foreach((array)$this->travellers[$groupId] as $index => $travellerLabel) {
									$sCode .= '" "' . $sSeparator;
								}
							}
						} elseif ($aWidth['column_id'] != 34){
							$sCode .= '"';
							foreach((array)$aLine[$iColumnId] as $iSubKey => $sValue){
								$sCode .= Ext_TC_Util::prepareDataForCSV($sValue, $sCharset) . ' ';
							}
							$sCode .= '"' . $sSeparator;
						}else{
							foreach((array)$aDays as $iDay => $sDay){
								if($aLine[$iColumnId][$iDay]){
									$sCode .= '" "' . $sSeparator;
								}else{
									$sCode .= '"X"' . $sSeparator;
								}

							}
						}
					}
					$sCode .= "\n";
				}

				$sCode .= "\n\n";

			}
		}

		return $sCode;
	}






	/* ==================================================================================================== */

	/**
	 * Fetch the HTML code
	 *
	 * @param bool $bPDF
	 * @return string
	 */
	protected function _fetchCode($aResults, $bPDF = false) {

		$this->_prepareWidths();

		$aColors = $this->_getFieldsColors();
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		// Column 34 (Tage) anders sortieren bei einem anderen Kurs-Starttag der Schule
		if($oSchool->course_startday != 1) {
			for($i=1; $i < $oSchool->course_startday; $i++) {
				if(isset($this->_aDays[$i])) {
					$sDay = $this->_aDays[$i];
					unset($this->_aDays[$i]);
					$this->_aDays[$i] = $sDay;
				}
			}
		}

		$oSmarty = new SmartyWrapper();

		if(
			$bPDF === true &&
			$this->_oReport->min_rows > 0
		) {
			foreach($aResults as &$subResults) {
				foreach($subResults['data'] as &$aTable) {
					if(count($aTable['data']) < $this->_oReport->min_rows) {
						for($iCount=count($aTable['data']); $iCount < $this->_oReport->min_rows; $iCount++) {
							$aTable['data'][] = [];
						}
					}
				}
			}
		}

		$oSmarty->assign('oReport', $this->_oReport);
		$oSmarty->assign('aData', $aResults);
		$oSmarty->assign('aColors', $aColors);
		$oSmarty->assign('aWidths', $this->_aWidths);
		$oSmarty->assign('aDays', $this->_aDays);
		$oSmarty->assign('travellers', $this->travellers);
		$oSmarty->assign('bPDF', $bPDF);
		$oSmarty->assign('timzone', $this->_oSchool->timezone);
		$oSmarty->assign('language', $this->_sDefaultLangBackend);

		$sCode = $oSmarty->fetch(Ext_Thebing_Tuition_Report_Gui2::getTemplatePath() . 'result.tpl');

		return $sCode;
	}


	/**
	 * Get the basic query
	 *
	 * @param array &$aSQL
	 * @return string
	 */
	protected function _getBasicQuery(&$aSQL) {

		$school = $this->_oSchool;

		$sSubPartProgress = Ext_Thebing_Tuition_Progress::getSqlSubPart('<= :sTILL');

		$sFlexFieldsSql = "
			SELECT
				GROUP_CONCAT(DISTINCT CONCAT(`tc_fsf`.`id`, '{::}', `tc_fsf`.`type`, '{::}', `kfsfv`.`value`, '{::}', IFNULL(`kfsfov`.`title`, '')) SEPARATOR '{_}')
			FROM
				`tc_flex_sections_fields_values` `kfsfv` INNER JOIN
				`tc_flex_sections_fields` `tc_fsf` ON
					`tc_fsf`.`id` = `kfsfv`.`field_id` AND
					`tc_fsf`.`active` = 1 LEFT JOIN
				`tc_flex_sections_fields_options` AS `kfsfo` ON
					`kfsfv`.`field_id` = `kfsfo`.`field_id` AND
					`kfsfo`.`id` = `kfsfv`.`value` AND
					`kfsfo`.`active` = 1 LEFT JOIN
				`tc_flex_sections_fields_options_values` AS `kfsfov` ON
					`kfsfo`.`id` = `kfsfov`.`option_id` AND
					`kfsfov`.`lang_id` = '{$this->_sDefaultLangFrontend}'
		";

		$selectPart = "
					STRAIGHT_JOIN
					`ktb`.`class_id` AS `tmp_grouping_block_id`,
					`ktb`.`id` AS `block_id`,
					GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktcl`.`name` SEPARATOR '{_}') AS
						`placeholder_class_name_36`,
					GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktcl`.`start_week` SEPARATOR '{_}') AS
						`placeholder_class_from_966`,
					GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktcl`.`weeks` SEPARATOR '{_}') AS
						`placeholder_class_to_967`,
					CONCAT('X{::}', COUNT(DISTINCT `ki`.`id`)) AS
						`placeholder_total_number_of_students_per_class_37`,
					GROUP_CONCAT(DISTINCT `ktbd`.`block_id`, '{::}', CONCAT(`ktbd`.`day`) SEPARATOR '{_}') AS
						`placeholder_weekdays_39`,
					GROUP_CONCAT(DISTINCT `kt`.`id`, '{::}', `kt`.`firstname`, '{::}',`ktb`.`teacher_id` ORDER BY `ktb`.`week`, `ktbd`.`day`, `ktt`.`from` SEPARATOR '{_}') AS
						`placeholder_teacher_firstname_31`,
					GROUP_CONCAT(DISTINCT `kt`.`id`, '{::}', `kt`.`lastname`, '{::}',`ktb`.`teacher_id`  SEPARATOR '{_}') AS
						`placeholder_teacher_lastname_32`,
					GROUP_CONCAT(DISTINCT `kt`.`id`, '{::}', CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`), '{::}',`ktb`.`teacher_id`  SEPARATOR '{_}') AS
						`placeholder_teacher_name_31`,
					GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', CONCAT(`cdb1`.`lastname`, ', ', `cdb1`.`firstname`) SEPARATOR '{_}') AS
						`placeholder_student_name_1`,
					GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `kcr`.`name` SEPARATOR '{_}') AS
						`placeholder_room_30`,
					GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ksb`.`title` SEPARATOR '{_}') AS
						`placeholder_building_28`,
					GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ksf`.`title` SEPARATOR '{_}') AS
						`placeholder_floor_29`,
					GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktul`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}') AS
						`placeholder_level_40`,
					GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ktc`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}') AS
						`placeholder_courses_8`,
					GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktb`.`description` ORDER BY `ktb`.`week` SEPARATOR '{_}') `placeholder_class_content_60`,
					GROUP_CONCAT(
						DISTINCT CONCAT(
							`ktbd`.`block_id`,
							'{::}',
							`ktbd`.`day`,
							'{::}',
							`ktt`.`from`,
							'{::}',
							`ktt`.`until`
						) 
						ORDER BY `ktbd`.`day`, `ktt`.`from`
						SEPARATOR '{_}'
					) AS `placeholder_weekdays_times_41`,
					GROUP_CONCAT(                           
						DISTINCT CONCAT(   
							`ktbd`.`block_id`,              
							'{::}',  
							`ktt`.`from`,                   
							'{::}',                         
							`ktt`.`until`                   
						) SEPARATOR '{_}'                   
					) AS `placeholder_times_49`,
					`ki`.`id` AS `inquiry_id`,
					`kic`.`id` AS `inquiry_course_id`,
					COUNT(DISTINCT `ktbd`.`day`) `days_sum`,
					COUNT(DISTINCT `kab`.`id`) `absence_days_sum`,
					`ktt`.`lessons` `lessons`,
					GROUP_CONCAT(DISTINCT `kab`.`item_id`) `absence_teachers`,
					GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ktlg`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}') AS `placeholder_course_language_968`,
					/* Flexible Felder als einzelne Subselects, da ein OR im WHERE bei vielen Values (der selektierten Felder) nicht mehr performant ist */
					(
						/* Kundendaten: Kurs */
						{$sFlexFieldsSql}
						WHERE
							`kfsfv`.`field_id` IN( :aFlexes ) AND 
							`tc_fsf`.`section_id` = 4 AND
							`kfsfv`.`item_id` = `ki`.`id`
					) `flex_fields_4`,
					(
						/* Klassenplanung: Anwesenheit */
						{$sFlexFieldsSql}
						WHERE
							`kfsfv`.`field_id` IN( :aFlexes ) AND
							`tc_fsf`.`section_id` = 38 AND
							`kfsfv`.`item_id` = `ktbic`.`id`
					) `flex_fields_38`,
					(
						/* Kundendaten: Kursbuchung (pro Kurs) */
						{$sFlexFieldsSql}
						WHERE
							`kfsfv`.`field_id` IN( :aFlexes ) AND
							`tc_fsf`.`section_id` = 46 AND
							`kfsfv`.`item_id` = `kic`.`id`
					) `flex_fields_46`
		";

		// Gruppierung nach Klassen, wenn auch leere Klassen angezeigt werden soll, Query anders aufbauen
		if(
			$school->tuition_show_empty_classes &&
			$this->_oReport->group_by == 1
		) {

			$sSQL = "
				SELECT
					".$selectPart."
					{SELECT}
				FROM
					`customer_db_2` AS `cdb2` LEFT JOIN
					`kolumbus_tuition_classes` `ktcl` ON
						`ktcl`.`school_id` = `cdb2`.`id` AND
						`ktcl`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks` AS `ktb` ON
						`ktcl`.`id` = `ktb`.`class_id` AND
						`ktb`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_inquiries_courses` AS `ktbic`	ON
						`ktb`.`id` = `ktbic`.`block_id` AND
						`ktbic`.`active` = 1 LEFT JOIN
					`ts_inquiries_journeys_courses` AS `kic` ON
						`ktbic`.`inquiry_course_id` = `kic`.`id` AND
						`kic`.`active` = 1 AND
						`kic`.`visible` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_days` AS `ktbd` ON
						`ktb`.`id` = `ktbd`.`block_id` LEFT JOIN
					`kolumbus_tuition_templates` AS `ktt` ON
						`ktb`.`template_id` = `ktt`.`id` AND
						`ktt`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_classes_courses` `ktclc` ON
						`ktclc`.`class_id` = `ktcl`.`id` LEFT JOIN
					`kolumbus_tuition_courses` AS `ktc` ON
						`ktclc`.`course_id` = `ktc`.`id` AND
						`ktc`.`active` = 1 LEFT JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 LEFT JOIN
					`ts_inquiries` AS `ki` ON
						`ki`.`id` = `ts_i_j`.`inquiry_id` AND
						`ki`.`active` = 1 AND
						`ki`.`canceled` = 0 LEFT JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
					`tc_contacts` AS `cdb1` ON
						`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
						`cdb1`.`active` = 1 LEFT JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`ts_inquiries_journeys_courses` AS `kic2` ON
						`ts_i_j`.`id` = `kic2`.`journey_id` AND
						`kic2`.`active` = 1 AND
						`kic2`.`visible` = 1 LEFT JOIN
					`kolumbus_tuition_courses` AS `ktc2` ON
						`kic2`.`course_id` = `ktc2`.`id` AND
						`ktc2`.`active` = 1 LEFT JOIN
					`ts_tuition_coursecategories` `ktcc` ON
						`ktcc`.`id` = `ktc`.`category_id` AND
						`ktcc`.`active` = 1 LEFT JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
					    `ts_tcps`.`id` = `ktbic`.`program_service_id` AND 
						`ts_tcps`.`type` = '". \TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' LEFT JOIN	
					`kolumbus_tuition_courses` `ktc_allocation` ON
						`ts_tcps`.`type_id` = `ktc_allocation`.`id` AND
						`ktc_allocation`.`active` = 1 LEFT JOIN
					`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc_` ON
						`ts_ijclc_`.`journey_course_id` = `ktbic`.`inquiry_course_id` AND
						`ts_ijclc_`.`program_service_id` = `ktbic`.`program_service_id` LEFT JOIN
					`kolumbus_classroom` `kcr` ON
						`ktbic`.`room_id` = `kcr`.`id` AND
						`kcr`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
						`ktbst`.`block_id` = `ktb`.`id` AND
						`ktbst`.`day` = `ktbd`.`day` AND
						`ktbst`.`active` = 1 LEFT JOIN
					`ts_teachers` `kt` ON
						`kt`.`id` = IF(
							`ktbst`.`lessons` >= `ktt`.`lessons`,
							`ktbst`.`teacher_id`,
							`ktb`.`teacher_id`
						) LEFT JOIN
					`ts_teachers` `kt_with_sub` ON
						`kt_with_sub`.`id` = `ktb`.`teacher_id` OR
						`kt_with_sub`.`id` = `ktbst`.`teacher_id` LEFT JOIN
					`kolumbus_tuition_blocks_days` AS `ktbd_filter`					ON
						`ktb`.`id` = `ktbd_filter`.`block_id` LEFT JOIN
					/* Externes Level (Level vom Block bzw. Klasse) */
					`ts_tuition_levels` AS `ktul`									ON
						`ktb`.`level_id` = `ktul`.`id` AND
						`ktul`.`active` = 1 LEFT JOIN
					/* Externes Level (Level von Kursbuchung) */
					`ts_tuition_levels` AS `ktul_`								ON
						`kic`.`level_id` = `ktul_`.`id` AND
						`ktul_`.`active` = 1 LEFT JOIN
					`kolumbus_school_floors` AS `ksf`							ON
						`kcr`.`floor_id` = `ksf`.`id` AND
						`ksf`.`active` = 1 LEFT JOIN
					`kolumbus_school_buildings` AS `ksb`						ON
						`ksf`.`building_id` = `ksb`.`id` AND
						`ksb`.`active` = 1 LEFT JOIN
					`data_languages` AS `kls`									ON
						`cdb1`.`language` = `kls`.`iso_639_1` LEFT JOIN
					`data_countries` AS `kc`								ON
						`cdb1`.`nationality` = `kc`.`cn_iso_2` LEFT JOIN
					`kolumbus_student_status` AS `kss`							ON
						`ki`.`status_id` = `kss`.`id` AND
						`kss`.`active` = 1 LEFT JOIN
					`ts_companies` AS `ka`									ON
						`ki`.`agency_id` = `ka`.`id` AND
						`ka`.`active` = 1 LEFT JOIN
					`ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
						`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND
						`ts_j_t_v_d`.`traveller_id` = `cdb1`.`id` LEFT JOIN
					`kolumbus_visum_status` AS `kvs`							ON
						`ts_j_t_v_d`.`status` = `kvs`.`id` AND
						`kvs`.`active` = 1 LEFT JOIN
					`ts_placementtests_results` AS `kpr`					ON
						`kpr`.`inquiry_id` = `ki`.`id` AND
						`kpr`.`active` = 1 LEFT JOIN
					/* Level über Placementtest */
					`ts_tuition_levels` AS `__ktul`								ON
						`kpr`.`level_id` = `__ktul`.`id` AND
						`__ktul`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_attendance` AS `kta`						ON
						`ktbic`.`id` = `kta`.`allocation_id` AND
						`kta`.`active` = 1 LEFT JOIN
					`ts_tuition_courselanguages` `ktlg` ON
						`ktlg`.`id` = `kic`.`courselanguage_id` LEFT JOIN
					/* Interner Fortschritt (aktuelle Woche im Filter) */
					`kolumbus_tuition_progress` AS `ktp`						ON
						`ktp`.`inquiry_id` = `ki`.`id` AND
						`ktp`.`courselanguage_id` = `ktlg`.`id` AND
						`ktp`.`active` = 1 AND
						`ktp`.`week` = :sFROM LEFT JOIN
					/* Interner Fortschritt (aktuellste Woche im Filterzeitraum) */
					`kolumbus_tuition_progress` AS `ktp_`						ON
						`ktp_`.`inquiry_id` = `ki`.`id` AND
						`ktp_`.`courselanguage_id` = `ktlg`.`id` AND
						`ktp_`.`active` = 1 AND
						`ktp_`.`week` =
						(
							".$sSubPartProgress."
						) LEFT JOIN
					/* Internes Level (akutelle Woche im Filter) */
					`ts_tuition_levels` AS `_ktul`								ON
						`ktp`.`level` = `_ktul`.`id` AND
						`_ktul`.`active` = 1 LEFT JOIN
					/* Internes Level (aktuellste Woche im Filterzeitraum) */
					`ts_tuition_levels` AS `_ktul_`								ON
						`ktp_`.`level` = `_ktul_`.`id` AND
						`_ktul_`.`active` = 1 LEFT JOIN
					`kolumbus_absence` `kab` ON
						`kab`.`item_id` = `kt`.`id` AND
						`kab`.`item` = 'teacher' AND
						`kab`.`active` = 1 AND
						getRealDateFromTuitionWeek(
							`ktb`.`week`,
							`ktbd`.`day`,
							`cdb2`.`course_startday`
						) BETWEEN `kab`.`from` AND `kab`.`until` LEFT JOIN
					`tc_contacts_details` `tc_c_d_phone` ON
						`tc_c_d_phone`.`contact_id` = `cdb1`.`id` AND
						`tc_c_d_phone`.`type` = 'phone_private' AND
						`tc_c_d_phone`.`active` = 1 LEFT JOIN
					`tc_contacts_details` `tc_c_d_comment` ON
						`tc_c_d_comment`.`contact_id` = `cdb1`.`id` AND
						`tc_c_d_comment`.`type` = 'comment' AND
						`tc_c_d_comment`.`active` = 1 LEFT JOIN
					`tc_contacts_to_emailaddresses` `tc_c_to_e` ON
						`tc_c_to_e`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`tc_emailaddresses` `tc_e` ON
						`tc_e`.`id` = `tc_c_to_e`.`emailaddress_id` AND
						`tc_e`.`active` = 1 AND
						`tc_e`.`master` = 1 LEFT JOIN
					`tc_contacts_to_addresses` `tc_c_to_a` ON
						`tc_c_to_a`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`tc_addresses` `tc_a` ON
						`tc_a`.`id` = `tc_c_to_a`.`address_id` AND
						`tc_a`.`active` = 1 LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ki`.`group_id` LEFT JOIN
					`ts_inquiries_tuition_index` `titi` ON
						`titi`.`inquiry_id` = `ki`.`id` AND
						`titi`.`week` = :sFROM
				WHERE
					`cdb2`.`active` = 1 AND
					`cdb2`.`id` = :iSchoolID AND
					DATE(`ktb`.`week`) = :sFROM AND
					IF(
						:group_by = 2,
						`kab`.`id` IS NULL,
						1
					)
			";

		} else {

			$sSQL = "
				SELECT
					".$selectPart."
					{SELECT}
				FROM
					`customer_db_2` AS `cdb2`								INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`school_id` = `cdb2`.`id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` AS `ki`								ON
						`ki`.`id` = `ts_i_j`.`inquiry_id` AND
						`ki`.`active` = 1 AND
						`ki`.`canceled` = 0									INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` AS `cdb1`									ON
						`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
						`cdb1`.`active` = 1									INNER JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id`					INNER JOIN
					`ts_inquiries_journeys_courses` AS `kic`						ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`kic`.`active` = 1 AND
						`kic`.`visible` = 1 INNER JOIN
					`ts_inquiries_journeys_courses` AS `kic2`						ON
						`ts_i_j`.`id` = `kic2`.`journey_id` AND
						`kic2`.`active` = 1 AND
						`kic2`.`visible` = 1 INNER JOIN
					`kolumbus_tuition_courses` AS `ktc`					ON
						`kic`.`course_id` = `ktc`.`id` AND
						`ktc`.`active` = 1 LEFT JOIN
					`kolumbus_tuition_courses` AS `ktc2`					ON
						`kic2`.`course_id` = `ktc2`.`id` AND
						`ktc2`.`active` = 1 LEFT JOIN
					`ts_tuition_coursecategories` `ktcc` ON
						`ktcc`.`id` = `ktc`.`category_id` AND
						`ktcc`.`active` = 1 INNER JOIN
					`kolumbus_tuition_blocks_inquiries_courses` AS `ktbic` FORCE INDEX(ktbic_2)	ON
						`kic`.`id` = `ktbic`.`inquiry_course_id` AND
						`ktbic`.`active` = 1								INNER JOIN
					`kolumbus_tuition_blocks` AS `ktb`							ON
						`ktbic`.`block_id` = `ktb`.`id` AND
						`ktb`.`active` = 1 INNER JOIN
					`kolumbus_tuition_blocks_days` AS `ktbd`					ON
						`ktb`.`id` = `ktbd`.`block_id` AND
						getRealDateFromTuitionWeek(
							`ktb`.`week`,
							`ktbd`.`day`,
							`cdb2`.`course_startday`
						) BETWEEN `kic`.`from` AND `kic`.`until` INNER JOIN
					`kolumbus_tuition_templates` AS `ktt`						ON
						`ktb`.`template_id` = `ktt`.`id` AND
						`ktt`.`active` = 1 LEFT JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
					    `ts_tcps`.`id` = `ktbic`.`program_service_id` AND 
						`ts_tcps`.`type` = '". \TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' LEFT JOIN	
					`kolumbus_tuition_courses` `ktc_allocation` ON
						`ts_tcps`.`type_id` = `ktc_allocation`.`id` AND
						`ktc_allocation`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc_` ON
						`ts_ijclc_`.`journey_course_id` = `ktbic`.`inquiry_course_id` AND
						`ts_ijclc_`.`program_service_id` = `ktbic`.`program_service_id` INNER JOIN
					`kolumbus_tuition_classes` `ktcl`							ON
						`ktcl`.`id` = `ktb`.`class_id`						LEFT JOIN
					`kolumbus_classroom` AS `kcr`								ON
						`ktbic`.`room_id` = `kcr`.`id` AND
						`kcr`.`active` = 1									LEFT OUTER JOIN
					`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
						`ktbst`.`block_id` = `ktb`.`id` AND
						`ktbst`.`day` = `ktbd`.`day` AND
						`ktbst`.`active` = 1 LEFT JOIN
					`ts_teachers` `kt` ON
						`kt`.`id` = IF(
							`ktbst`.`lessons` >= `ktt`.`lessons`,
							`ktbst`.`teacher_id`,
							`ktb`.`teacher_id`
						) LEFT JOIN
					`ts_teachers` `kt_with_sub` ON
						`kt_with_sub`.`id` = `ktb`.`teacher_id` OR
						`kt_with_sub`.`id` = `ktbst`.`teacher_id` INNER JOIN
					`kolumbus_tuition_blocks_days` AS `ktbd_filter`					ON
						`ktb`.`id` = `ktbd_filter`.`block_id` AND
						getRealDateFromTuitionWeek(
							`ktb`.`week`,
							`ktbd`.`day`,
							`cdb2`.`course_startday`
						) BETWEEN `kic`.`from` AND `kic`.`until` LEFT OUTER JOIN
					/* Externes Level (Level vom Block bzw. Klasse) */
					`ts_tuition_levels` AS `ktul`									ON
						`ktb`.`level_id` = `ktul`.`id` AND
						`ktul`.`active` = 1 LEFT OUTER JOIN
					/* Externes Level (Level von Kursbuchung) */
					`ts_tuition_levels` AS `ktul_`								ON
						`kic`.`level_id` = `ktul_`.`id` AND
						`ktul_`.`active` = 1								LEFT OUTER JOIN
					`kolumbus_school_floors` AS `ksf`							ON
						`kcr`.`floor_id` = `ksf`.`id` AND
						`ksf`.`active` = 1									LEFT OUTER JOIN
					`kolumbus_school_buildings` AS `ksb`						ON
						`ksf`.`building_id` = `ksb`.`id` AND
						`ksb`.`active` = 1									LEFT OUTER JOIN
					`data_languages` AS `kls`									ON
						`cdb1`.`language` = `kls`.`iso_639_1`				LEFT OUTER JOIN
					`data_countries` AS `kc`								ON
						`cdb1`.`nationality` = `kc`.`cn_iso_2`				LEFT OUTER JOIN
					`kolumbus_student_status` AS `kss`							ON
						`ki`.`status_id` = `kss`.`id` AND
						`kss`.`active` = 1									LEFT OUTER JOIN
					`ts_companies` AS `ka`									ON
						`ki`.`agency_id` = `ka`.`id` AND
						`ka`.`active` = 1									LEFT OUTER JOIN
					`ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
						`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND
						`ts_j_t_v_d`.`traveller_id` = `cdb1`.`id`			LEFT OUTER JOIN
					`kolumbus_visum_status` AS `kvs`							ON
						`ts_j_t_v_d`.`status` = `kvs`.`id` AND
						`kvs`.`active` = 1									LEFT OUTER JOIN
					`ts_placementtests_results` AS `kpr`					ON
						`kpr`.`inquiry_id` = `ki`.`id` AND
						`kpr`.`active` = 1									LEFT OUTER JOIN
					/* Level über Placementtest */
					`ts_tuition_levels` AS `__ktul`								ON
						`kpr`.`level_id` = `__ktul`.`id` AND
						`__ktul`.`active` = 1								LEFT OUTER JOIN
					`kolumbus_tuition_attendance` AS `kta`						ON
						`ktbic`.`id` = `kta`.`allocation_id` AND
						`kta`.`active` = 1 LEFT JOIN
					`ts_tuition_courselanguages` `ktlg` ON
						`ktlg`.`id` = `kic`.`courselanguage_id`				LEFT OUTER JOIN
					/* Interner Fortschritt (aktuelle Woche im Filter) */
					`kolumbus_tuition_progress` AS `ktp`						ON
						`ktp`.`inquiry_id` = `ki`.`id` AND
						`ktp`.`courselanguage_id` = `ktlg`.`id` AND
						`ktp`.`active` = 1 AND
						`ktp`.`week` = :sFROM								LEFT OUTER JOIN
					/* Interner Fortschritt (aktuellste Woche im Filterzeitraum) */
					`kolumbus_tuition_progress` AS `ktp_`						ON
						`ktp_`.`inquiry_id` = `ki`.`id` AND
						`ktp_`.`courselanguage_id` = `ktlg`.`id` AND
						`ktp_`.`active` = 1 AND
						`ktp_`.`week` =
						(
							".$sSubPartProgress."
						)													LEFT OUTER JOIN
					/* Internes Level (akutelle Woche im Filter) */
					`ts_tuition_levels` AS `_ktul`								ON
						`ktp`.`level` = `_ktul`.`id` AND
						`_ktul`.`active` = 1								LEFT OUTER JOIN
					/* Internes Level (aktuellste Woche im Filterzeitraum) */
					`ts_tuition_levels` AS `_ktul_`								ON
						`ktp_`.`level` = `_ktul_`.`id` AND
						`_ktul_`.`active` = 1 LEFT JOIN
					`kolumbus_absence` `kab` ON
						`kab`.`item_id` = `kt`.`id` AND
						`kab`.`item` = 'teacher' AND
						`kab`.`active` = 1 AND
						getRealDateFromTuitionWeek(
							`ktb`.`week`,
							`ktbd`.`day`,
							`cdb2`.`course_startday`
						) BETWEEN `kab`.`from` AND `kab`.`until` LEFT JOIN
					`tc_contacts_details` `tc_c_d_phone` ON
						`tc_c_d_phone`.`contact_id` = `cdb1`.`id` AND
						`tc_c_d_phone`.`type` = 'phone_private' AND
						`tc_c_d_phone`.`active` = 1 LEFT JOIN
					`tc_contacts_details` `tc_c_d_comment` ON
						`tc_c_d_comment`.`contact_id` = `cdb1`.`id` AND
						`tc_c_d_comment`.`type` = 'comment' AND
						`tc_c_d_comment`.`active` = 1 LEFT JOIN
					`tc_contacts_to_emailaddresses` `tc_c_to_e` ON
						`tc_c_to_e`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`tc_emailaddresses` `tc_e` ON
						`tc_e`.`id` = `tc_c_to_e`.`emailaddress_id` AND
						`tc_e`.`active` = 1 AND
						`tc_e`.`master` = 1 LEFT JOIN
					`tc_contacts_to_addresses` `tc_c_to_a` ON
						`tc_c_to_a`.`contact_id` = `cdb1`.`id` LEFT JOIN
					`tc_addresses` `tc_a` ON
						`tc_a`.`id` = `tc_c_to_a`.`address_id` AND
						`tc_a`.`active` = 1 LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ki`.`group_id` LEFT JOIN
					`ts_inquiries_tuition_index` `titi` ON
						`titi`.`inquiry_id` = `ki`.`id` AND
						`titi`.`week` = :sFROM
				WHERE
					`cdb2`.`active` = 1 AND
					`cdb2`.`id` = :iSchoolID AND
					DATE(`kic`.`until`) >= :sFROM AND
					DATE(`kic`.`from`) <= :sTILL AND
					DATE(`ktb`.`week`) = :sFROM AND
					IF(
						:group_by = 2,
						`kab`.`id` IS NULL,
						1
					)";
		}

		$sSQL .= "
					{WHERE}
				GROUP BY
					{GROUP_BY}
				ORDER BY
					{ORDER_BY}
			";

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sWhere = "";

		if(!empty($this->request->input('search'))) {
			$aSQL['search'] = '%'.$this->request->input('search').'%';
			$sWhere .= " AND (
			`cdb1`.`firstname` LIKE :search OR 
			`cdb1`.`lastname` LIKE :search OR
		 	`ktcl`.`name` LIKE :search OR
		  	`tc_c_n`.`number` LIKE :search
		  	)";
		}
		if(!empty($this->request->input('course_id'))) {
			// ktc_allocation, da ktc_allocation über Blockzuweisung geht, ktc aber über Kursbuchungen
			$aSQL['course_id'] = (int)$this->request->input('course_id');
			$sWhere .= " AND `ktc_allocation`.`id` = :course_id";
		}
		if(!empty($this->request->input('course_category_id'))) {
			$aSQL['course_category_id'] = (int)$this->request->input('course_category_id');
			$sWhere .= " AND `ktcc`.`id` = :course_category_id";
		}
		if(!empty($this->request->input('teacher_id'))) {
			$aSQL['teacher_id'] = (int)$this->request->input('teacher_id');
			$sWhere .= " AND `kt`.`id` = :teacher_id";
		}
		if(!empty($this->request->input('inbox_id'))) {
			$aSQL['inbox_id'] = (string)$this->request->input('inbox_id');
			$sWhere .= " AND `ki`.`inbox` = :inbox_id";
		}

		if(!empty($this->request->input('weekday'))) {
			$aSQL['weekday'] = (int)$this->request->input('weekday');
			$sWhere .= " AND `ktbd_filter`.`day` = :weekday";
		}
		if(!empty($this->request->input('tuition_template'))) {
			$aSQL['tuition_template'] = (int)$this->request->input('tuition_template');
			$sWhere .= " AND `ktb`.`template_id` = :tuition_template";
		}
		if(!empty($this->request->input('class_color'))) {
			$aSQL['class_color'] = (int)$this->request->input('class_color');
			$sWhere .= " AND `ktcl`.`color_id` = :class_color";
		}
		if(!empty($this->request->input('course_start_from'))) {
			$aSQL['course_start_from'] = $this->request->input('course_start_from');
			$sWhere .= " AND `ktt`.`from` >= :course_start_from";
		}
		if(!empty($this->request->input('course_start_until'))) {
			$aSQL['course_start_until'] = $this->request->input('course_start_until');
			$sWhere .= " AND `ktt`.`from` <= :course_start_until";
		}

		if(!empty($this->request->input('courselanguage_id'))) {
			$aSQL['courselanguage_id'] = $this->request->input('courselanguage_id');
			$sWhere .= " AND `kic`.`courselanguage_id` = :courselanguage_id";
		}

		$sSQL = str_replace('{WHERE}', $sWhere, $sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aSQL['iSchoolID'] = (int)$this->_oSchool->getId();
		$aSQL['sFROM'] = $this->_oDateFrom->get(WDDate::DB_DATE);
		$aSQL['sTILL'] = $this->_oDateTill->get(WDDate::DB_DATE);
		$aSQL['group_by']	= $this->_oReport->group_by;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		// TODO Die Flex-Felder sollten je nach Section getrennt werden, damit die Sub-Querys noch besser filtern können
		$aFlexes = $aSelects = array();

		$aUnique[] = [];
		foreach((array)$this->_oReport->columns as $aColumn) {
			$columnId = $aColumn['column_id'];
			$setting = $aColumn['setting'];

			// Nur nach dem Setting schauen wenn es auch eins gibt, sonst nur nach der Spalte.
			if (!empty($setting)) {
				if(isset($aUnique[$columnId][$setting])) {
					continue;
				}
			} elseif(isset($aUnique[$columnId])) {
				continue;
			}


			if($columnId < 0) {
				$aFlexes[] = $columnId * -1;
			} elseif($columnId > 0) {
				$sSelect = $this->_getSelectField($aColumn, $aSQL);
				$sSelect .= " AS `field_" . (int)$columnId;
//				// Nur nach dem Setting schauen wenn es auch eins gibt, sonst nur nach der Spalte.
				if (!empty($setting)) {
					$sSelect .= "_". $setting;
				}
				$sSelect .= "` ";
				$aSelects[] = $sSelect;
			}

			// Nur nach dem Setting schauen wenn es auch eins gibt, sonst nur nach der Spalte.
			if (!empty($setting)) {
				$aUnique[$columnId][$setting] = true;
			} elseif(isset($aUnique[$columnId])) {
				$aUnique[$columnId] = true;
			}


		}

		$aSQL['aFlexes'] = $aFlexes;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // GROUP BY

		$groupBy = [];

		switch($this->_oReport->group_by) {
			case 1: // Klasse
				$groupBy[] = " `tmp_grouping_block_id` ";
				$aSelects[] = " `ktb`.`class_id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ktcl`.`name` AS `tmp_group_select_title` ";
				break;
			case 2: // Lehrer
				$groupBy[] = " `kt`.`id` ";
				$aSelects[] = " `kt`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`) AS `tmp_group_select_title` ";
				break;
			case 3: // Raum
				$groupBy[] = " `kcr`.`id` ";
				$aSelects[] = " `kcr`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `kcr`.`name` AS `tmp_group_select_title` ";
				break;
			case 4: // Kurs
				$groupBy[] = " `ktc`.`id` ";
				$aSelects[] = " `ktc`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ktc`.`name_" . $this->_sDefaultLangFrontend . "` AS `tmp_group_select_title` ";
				break;
			case 5: // Gebäude
				$groupBy[] = " `ksb`.`id` ";
				$aSelects[] = " `ksb`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ksb`.`title` AS `tmp_group_select_title` ";
				break;
			case 6: // Etage
				$groupBy[] = " `ksf`.`id` ";
				$aSelects[] = " `ksf`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ksf`.`title` AS `tmp_group_select_title` ";
				break;
			case 7: // Schüler
				$groupBy[] = " `ki`.`id` ";

				// Schüler basierend auf Schüler: Nur eine Tabelle anzeigen (tmp_group_select_id), aber mehrere Zeilen (GROUP BY)
				if($this->_oReport->start_with == 3) {
					$aSelects[] = " NULL `tmp_group_select_id` ";
					$aSelects[] = " NULL `tmp_group_select_title` ";
				} else {
					$aSelects[] = " `cdb1`.`id` `tmp_group_select_id` ";
					$aSelects[] = " CONCAT(`cdb1`.`lastname`, ', ', `cdb1`.`firstname`) `tmp_group_select_title` ";
				}

				break;
			case 8: // Niveau
				$groupBy[] = " `ktb`.`level_id` ";
				$aSelects[] = " `ktul`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ktul`.`name_" . $this->_sDefaultLangFrontend . "` AS `tmp_group_select_title` ";
				break;
			case 9: // Block
				$groupBy[] = " `ktb`.`id` ";
				$aSelects[] = " `ktb`.`id` AS `tmp_group_select_id` ";
				$aSelects[] = " `ktcl`.`name` AS `tmp_group_select_title` ";
				break;
			case 10: // Keine
				$aSelects[] = " 0 AS `tmp_group_select_id` ";
				$aSelects[] = " '' AS `tmp_group_select_title` ";
				break;
		}

		switch($this->_oReport->sub_group) {
			case 'days':
				$groupBy[] = " `ktbd`.`day` ";
				$aSelects[] = " `ktbd`.`day` AS `tmp_subgroup_select_id` ";
				$aSelects[] = " 
					getRealDateFromTuitionWeek(
						`ktb`.`week`,
						`ktbd`.`day`,
						`cdb2`.`course_startday`
					) AS `tmp_subgroup_select_title` ";
				break;
		}

		switch($this->_oReport->start_with) {
			case 1: // Klasse
				$groupBy[] = " `tmp_grouping_block_id` ";
				break;
			case 2: // Lehrer
				$groupBy[] = " `kt`.`id` ";
				break;
			case 3: // Schüler
				$groupBy[] = " `ki`.`id` ";
				break;
			case 4: // Block
				$groupBy[] = " `ktb`.`id` ";
				break;
			case 5: // Tag
				$groupBy[] = " `ktbd`.`day` ";
				break;
		}

		$aOrderBy = array();

		$aOrderBy[] = '`tmp_group_select_title`';

		if($this->_oReport->sub_group) {
			$aOrderBy[] = '`tmp_subgroup_select_title`';
		}

		// Tabelle nach ausgewählter Spalte sortieren
		$aOrderByFields = $this->getOrderByFields();
		if(array_key_exists($this->_oReport->order_by_column, $aOrderByFields)) {
			$aOrderBy[] = $aOrderByFields[$this->_oReport->order_by_column];
		}

		$sOrderBy = implode(',', $aOrderBy);

		$sSelect = implode(', ', $aSelects);
		if(!empty($sSelect)) {
			$sSelect = ', '.$sSelect;
		}

		$sSQL = str_replace(
			array('{SELECT}', '{GROUP_BY}', '{ORDER_BY}'),
			array($sSelect, implode(', ', $groupBy), $sOrderBy),
			$sSQL
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $sSQL;
	}


	/**
	 * Get day short by day number
	 *
	 * @param int $iDay
	 * @return string
	 */
	protected function _getDayShort($iDay) {

		if(
			isset($this->_aDaysShort[$iDay])
		) {
			return $this->_aDaysShort[$iDay];
		} else {
			return $iDay;
		}

		return $sDay;
	}

	/**
	 * Get fields colors
	 *
	 * @return array
	 */
	protected function _getFieldsColors() {

		$aColors = array();

		$aFields = Ext_Thebing_Tuition_Report_Gui2::getColumnsData();

		foreach((array)$aFields as $iGroupID => $aGroup) {
			foreach((array)$aGroup['fields'] as $iKey => $field) {
				$aColors[$iKey] = array(
					'color'	=> $aGroup['color'],
					'title'	=> $field->label
				);
			}
		}

		return $aColors;
	}

	protected function getField(int $fieldId) {
		$fields = $this->getFields();

		return $fields[$fieldId] ?? null;
	}

	protected function getFields() {

		$sCacheKey = __METHOD__;

		if(empty(self::$aCache[$sCacheKey])) {

			self::$aCache[$sCacheKey] = [];

			$aFields = Ext_Thebing_Tuition_Report_Gui2::getColumnsData();

			foreach((array)$aFields as $iGroupID => $aGroup) {
				foreach((array)$aGroup['fields'] as $iKey => $aField) {
					self::$aCache[$sCacheKey][$iKey] = $aField;
				}
			}
		}

		return self::$aCache[$sCacheKey];
	}

	/**
	 * Get prepared results by array from DB
	 *
	 * @return array
	 */
	protected function _getPreparedResults() {

		$aSQL = array();

		$sQuery = $this->_getBasicQuery($aSQL);

		$aResults = DB::getPreparedQueryData($sQuery, $aSQL);

		// TODO : DEBUG
		if($_REQUEST['debug_results']) {
			$oDB = DB::getDefaultConnection();
			__pout($oDB->getLastQuery());
			__pout($aSQL);
			__pout($aResults);
		}

		$aResults = $this->_prepareResults($aResults);

		// TODO : DEBUG
		if($_REQUEST['debug_results']) {
			__pout($aResults);
		}

		return $aResults;
	}

	/**
	 * Get the select statement
	 *
	 * @param int $iColumnID
	 * @return string
	 */
	protected function _getSelectField(array $column, array &$aSQL) {

		$iColumnID = $column['column_id'];

		// Subquery für gebuchte Stunden
		$sGetBookedLessonsQueryPart = "
			COALESCE(
				(SELECT
					SUM(`ts_ijclc_sub`.`absolute`)						
				FROM
					`ts_inquiries_journeys` `ts_ij_sub` INNER JOIN
					`ts_inquiries_journeys_courses` `ts_ijc_sub` ON
						`ts_ijc_sub`.`journey_id` = `ts_ij_sub`.`id` AND
						`ts_ijc_sub`.`active` = 1 INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps_sub` ON
						`ts_tcps_sub`.`program_id` = `ts_ijc_sub`.`program_id` AND
						`ts_tcps_sub`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
						`ts_tcps_sub`.`active` = 1 INNER JOIN
					`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc_sub` ON
						`ts_ijclc_sub`.`journey_course_id` = `ts_ijc_sub`.`id` AND
						`ts_ijclc_sub`.`program_service_id` = `ts_tcps_sub`.`id`
				WHERE
					`ts_ij_sub`.`inquiry_id` = `ki`.`id` AND
					`ts_ij_sub`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ij_sub`.`active` = 1)
			, 0)
		";

		switch($iColumnID)
		{
			case 1: // Name
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `cdb1`.`lastname`, '{::}', `cdb1`.`firstname` SEPARATOR '{_}')";
			case 2: // Vorname
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `cdb1`.`firstname` SEPARATOR '{_}')";
			case 3: // Kundennummer
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `tc_c_n`.`number` SEPARATOR '{_}')";
			case 4: // Alter
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', getAge(`cdb1`.`birthday`) SEPARATOR '{_}')";
			case 5: // Muttersprache
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kls`.`name_" . $this->_sDefaultLangBackend . "` SEPARATOR '{_}')";
			case 6: // Nationalität
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kc`.`nationality_" . $this->_sDefaultLangBackend . "`, '{::}', `kc`.`cn_iso_2` SEPARATOR '{_}')";
			case 7: // Schülerstatus
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kss`.`text` SEPARATOR '{_}')";
			case 8: // Gebuchte Kurse
				return "GROUP_CONCAT(DISTINCT `ktc2`.`id`, '{::}', `ktc2`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}')";
			case 9: // Wochenanzahl pro gebuchten Kurs
				return "''";
			//return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `kic`.`id` SEPARATOR '{_}')";
			case 10: // Aktuelle Woche / Wochenanzahl pro gebuchten Kurs
				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `kic`.`id` SEPARATOR '{_}')";
			case 11: // Erstes Startdatum (Buchung, Kurs-Leistungen)
				return "GROUP_CONCAT(DISTINCT `ki`.`id` SEPARATOR '{_}')";
//				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', UNIX_TIMESTAMP(`ktcl`.`start_week`) SEPARATOR '{_}')";
			case 12: // Letztes Enddatum (Buchung, Kurs-Leistungen)
				return "GROUP_CONCAT(DISTINCT `ki`.`id` SEPARATOR '{_}')";
//				return "
//					GROUP_CONCAT(
//						DISTINCT `kic`.`id`, '{::}',
//						/* Full days - don't look on sommer-time */
//						((UNIX_TIMESTAMP(`ktcl`.`start_week`) + 43200) + `ktcl`.`weeks` * 604800 - 259200)
//						SEPARATOR '{_}'
//					)
//				";
			case 13: // Gebuchtes Level pro Kurs
				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ktul_`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}')";
			case 14: // Testscore
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kpr`.`score` SEPARATOR '{_}')";
			case 15: // Telefonnummer
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `tc_c_d_phone`.`value` SEPARATOR '{_}')";
			case 16: // E-Mail-Adresse
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `tc_e`.`email` SEPARATOR '{_}')";
			case 18: // Visum (Art)
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kvs`.`name` SEPARATOR '{_}')";

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Course data

			case 19: // Kurs
				return "GROUP_CONCAT(DISTINCT `ktc`.`id`, '{::}', `ktc`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}')";
			case 20: // Wochenanzahl
				return "''";
			//return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `ktb`.`class_id` SEPARATOR '{_}')";
			case 21: // Aktuelle Woche / Wochenanzahl der Buchung
				return "GROUP_CONCAT(DISTINCT CONCAT(`titi`.`current_week`, '/', `titi`.`total_weeks`) SEPARATOR '{_}')";
			case 22: // Startdatum (Kurs)
				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', UNIX_TIMESTAMP(`kic`.`from`) SEPARATOR '{_}')";
			case 23: // Enddatum (Kurs)
				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', UNIX_TIMESTAMP(`kic`.`until`) SEPARATOR '{_}')";
			case 24: // Aktuelle Score (der ausgewählten Woche)
				return "GROUP_CONCAT(DISTINCT `kic`.`id`, '{::}', `kta`.`score` SEPARATOR '{_}')";
			case 25: // Anwesenheit
				return ""; // Hat eh nicht funktioniert #2778
			case 26: // Aktuelles Niveau vom Schüler
				return "
					GROUP_CONCAT(
						DISTINCT `ki`.`id`, '{::}',
						IF(
							`_ktul`.`id` IS NOT NULL,
							`_ktul`.`name_" . $this->_sDefaultLangFrontend . "`,
							IF(
								`_ktul_`.`id` IS NOT NULL,
								`_ktul_`.`name_" . $this->_sDefaultLangFrontend . "`,
								`ktul`.`name_" . $this->_sDefaultLangFrontend . "`
							)
						)
						SEPARATOR '{_}')
				";
			case 27: // Status der Buchung (New, Continuous, ...)
				return "GROUP_CONCAT(DISTINCT `ki`.`id` SEPARATOR '{_}')";

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Location

			case 28: // Gebäude
				return "GROUP_CONCAT(DISTINCT `ksb`.`id`, '{::}', `ksb`.`title` SEPARATOR '{_}')";
			case 29: // Etage
				return "GROUP_CONCAT(DISTINCT `ksf`.`id`, '{::}', `ksf`.`title` SEPARATOR '{_}')";
			case 30: // Raum
				return "GROUP_CONCAT(DISTINCT `kcr`.`id`, '{::}', `kcr`.`name` SEPARATOR '{_}')";

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Teacher

			case 31: // Vorname
				return "GROUP_CONCAT(DISTINCT CONCAT(`kt`.`id`, '{::}', `kt`.`firstname`, '{::}'), '{::}', `ktb`.`teacher_id` SEPARATOR '{_}')";
			case 32: // Nachname
				return "GROUP_CONCAT(
							DISTINCT 
							CONCAT(
								`kt`.`id`, 
								'{::}', 
								`kt`.`lastname`, 
								'{::}', 
								`kt`.`firstname`
							), 
							'{::}', 
							`ktb`.`teacher_id`
							ORDER BY IF(
								`ktb`.`teacher_id` = `kt`.`id` OR :group_by != 2,
								`ktt`.`from`,
								`ktbst`.`from`
							)
							SEPARATOR '{_}'
						)";
			case 33: // Name pro Block mit Ersatzlehrer
				return "GROUP_CONCAT(
							DISTINCT 
							CONCAT(
								`kt_with_sub`.`id`, 
								'{::}', 
								`kt_with_sub`.`lastname`, 
								'{::}', 
								`kt_with_sub`.`firstname`
							), 
							'{::}', 
							`ktb`.`teacher_id`,
							'{::}', 
							IF(
								`ktb`.`teacher_id` = `kt_with_sub`.`id` OR :group_by != 2,
								`ktt`.`from`,
								`ktbst`.`from`
							),
							'{::}',
							COALESCE(`ktt`.`lessons`, 0),
							'{::}',
							COALESCE(`ktbst`.`lessons`, 0)
							
							ORDER BY IF(
								`ktb`.`teacher_id` = `kt_with_sub`.`id` OR :group_by != 2,
								`ktt`.`from`,
								`ktbst`.`from`
							)
							SEPARATOR '{_}'
						)";
			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Other

			case 34: // Wochentage (Mo/Di/Mi/...)
				return "GROUP_CONCAT(DISTINCT `ktbd`.`day`, '{::}', `ktbd`.`day` SEPARATOR '{_}')";
			case 35: // Uhrzeit (hh:mm - hh:mm)
				return "GROUP_CONCAT(
							DISTINCT 
							`ktb`.`id`,
							'{::}',
							CONCAT(
								LEFT(
									IF(
										`ktb`.`teacher_id` = `kt`.`id` OR :group_by != 2,
										`ktt`.`from`,
										`ktbst`.`from`
									)
									,5
								),
								' - ',
								LEFT(
									IF(
										`ktb`.`teacher_id` = `kt`.`id` OR :group_by != 2,
										`ktt`.`until`,
										`ktbst`.`until`
									)
									,5
								)
							) 
							ORDER BY IF(
								`ktb`.`teacher_id` = `kt`.`id` OR :group_by != 2,
								`ktt`.`from`,
								`ktbst`.`from`
							)
							SEPARATOR '{_}'
						)";
			case 36: // Klassenname
				return "GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktcl`.`name` SEPARATOR '{_}')";
			case 37: // Anzahl der Schüler
				return "COUNT(DISTINCT `ki`.`id`)";
			case 38: // Name der Schule
				return "GROUP_CONCAT(DISTINCT `cdb2`.`id`, '{::}', `cdb2`.`ext_1` SEPARATOR '{_}')";
			case 39: // Unterichtstage
				return "
					IF(
						`ktb`.`teacher_id` = `kt`.`id` OR :group_by != 2,
						GROUP_CONCAT(DISTINCT `ktbd`.`block_id`, '{::}', CONCAT(`ktbd`.`day`) SEPARATOR '{_}'),
						GROUP_CONCAT(DISTINCT `ktbst`.`block_id`, '{::}', CONCAT(`ktbst`.`day`) SEPARATOR '{_}')
					)
				";
			case 40: // Niveau der Klasse
				return "GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktul`.`name_" . $this->_sDefaultLangFrontend . "` SEPARATOR '{_}')";
			case 42: // Zeilenzähler
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', 1 SEPARATOR '{_}')";
			case 43: // MAX-Zähler
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', 1 SEPARATOR '{_}')";
			case 44: // Verbleibende Stunden (Buchung)
			case 53: // Verbleibende Stunden (Kurs)

				if($iColumnID == 44) {
					$sSubPartSumOfLessons = Ext_Thebing_School_Tuition_Allocation::getSumOfInquiryLessonsSubSql('ki.id');
					$sSubPartSumOfLessonsUnit = Ext_Thebing_School_Tuition_Allocation::getSumOfInquiryLessonsSubSql('ki.id');
				} else {
					$sSubPartSumOfLessons = Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_allocation.id', ':sFROM');
					$sSubPartSumOfLessonsUnit = Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_allocation.id');
					// Query überschreiben, da hier nur die tatsächlichen Kurswerte berücksichtigt werden dürfen
					$sGetBookedLessonsQueryPart = "
						COALESCE(`ts_ijclc_`.`lessons`, 0)
					";
				}

				return "
					GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}',
					(
						".$sGetBookedLessonsQueryPart." -
						IF(
							`ts_ijclc_`.`lessons_unit` = 'per_week',
							COALESCE(
								(
									".$sSubPartSumOfLessons."
								)
								, 0),
							COALESCE(
								(
									".$sSubPartSumOfLessonsUnit."
								)
								, 0)
						)
					)
					SEPARATOR '{_}')
				";
			case 45: // Zugewiesene Stunden (Buchung)
			case 54: // Zugewiesene Stunden (Kurs)

				if($iColumnID == 45) {
					$sSubPartSumOfLessons = Ext_Thebing_School_Tuition_Allocation::getSumOfInquiryLessonsSubSql('ki.id');
					$sSubPartSumOfLessonsUnit = Ext_Thebing_School_Tuition_Allocation::getSumOfInquiryLessonsSubSql('ki.id');
				} else {
					$sSubPartSumOfLessons = Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_allocation.id', ':sFROM');
					$sSubPartSumOfLessonsUnit = Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_allocation.id');
				}

				return "
					GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}',
					IF(
						`ts_ijclc_`.`lessons_unit` = 'per_week',
						COALESCE(
							(
								".$sSubPartSumOfLessons."
							)
							, 0),
						COALESCE(
							(
								".$sSubPartSumOfLessonsUnit."
							)
							, 0)
					)
					SEPARATOR '{_}')
				";

			case 46: // Gebuchte Stunden (Buchung)
				return "
					GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}',
					".$sGetBookedLessonsQueryPart."
					SEPARATOR '{_}')
				";

			case 47:
				return "
					(
						SELECT
							GROUP_CONCAT(
								DISTINCT CONCAT(
									`kic_sub`.`from`,
									':',
									`kic_sub`.`until`
								)
							)
						FROM
							`ts_inquiries_journeys_courses` `kic_sub` INNER JOIN
							`ts_inquiries_journeys` `ts_i_j_sub` ON
								`ts_i_j_sub`.`id` = `kic_sub`.`journey_id` AND
								`ts_i_j_sub`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
								`ts_i_j_sub`.`active` = 1
						WHERE
							`kic_sub`.`active` = 1 AND
							`ts_i_j_sub`.`inquiry_id` = `ki`.`id`
						GROUP BY
							`ts_i_j_sub`.`inquiry_id`
					)
				";
			case 48: // Geschlecht
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `cdb1`.`gender` SEPARATOR '{_}')";
			case 50: // Gruppen
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kg`.`name` SEPARATOR '{_}')";
			case 51: // Gruppen (Kürzel)
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kg`.`short` SEPARATOR '{_}')";
			case 52: // Status (Kurs)
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `kic`.`id` SEPARATOR '{_}')";
			case 55: // Gebuchte Stunden (Kurs)
				return "
					GROUP_CONCAT(
						DISTINCT `ki`.`id`, 
						'{::}',
						COALESCE(`ts_ijclc_`.`lessons`, 0)
						SEPARATOR '{_}'
					)
				";
			case 56: // Kurswochen (absolut)
			case 57: // Kurswochen (relativ)
				return "GROUP_CONCAT(DISTINCT `ki`.`id` SEPARATOR '{_}')";
			case 58: // Kurs (kurzfrom)
				return "GROUP_CONCAT(DISTINCT `ktc`.`id`, '{::}', `ktc`.`name_short` SEPARATOR '{_}')";
			case 59: // Gebuchte Kurse (kurzform)
				return "GROUP_CONCAT(DISTINCT `ktc2`.`id`, '{::}', `ktc2`.`name_short` SEPARATOR '{_}')";
			case 60: // Inhalt (Block)
				return "GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', `ktb`.`description` ORDER BY `ktb`.`week` SEPARATOR '{_}')";
			case 61: // Geburtsdatum des Schülers
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `cdb1`.`birthday` SEPARATOR '{_}')";
			case 62: // Gesamtbetrag Buchung
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `ki`.`amount` + `ki`.`amount_initial`, '{::}', `ki`.`currency_id` SEPARATOR '{_}')";
			case 63: // Offener Betrag Buchung
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `ki`.`amount` + `ki`.`amount_initial` - `ki`.`amount_payed`, '{::}', `ki`.`currency_id` SEPARATOR '{_}')";
			case 64: // Anwesenheit absolut
				$sInquiryAllAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', array(
					'inquiry_id' => '`ki`.`id`'
				));
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', ( {$sInquiryAllAttendance} ) SEPARATOR '{_}')";
			case 65: // Anwesenheit absolut (erwartet)
				$sInquiryAllExpecteAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', [
					'inquiry_id' => '`ki`.`id`',
					'expected' => true
				]);
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', ( {$sInquiryAllExpecteAttendance} ) SEPARATOR '{_}')";
			case 75: // Datum des Tages
				return "GROUP_CONCAT(DISTINCT `ktb`.`id`, '{::}', DATE_ADD(`ktb`.`week`, INTERVAL `ktbd`.`day` - 1 DAY) SEPARATOR '{_}')";
			case 76: // Schüler
				return "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', CONCAT(`cdb1`.`lastname`, ', ', `cdb1`.`firstname`) SEPARATOR '{_}')";
			default:

				$field = $this->getField($iColumnID);

				if(
					$field instanceof TsTuition\Model\Report\Field &&
					$field->hasSelectField()
				) {

					$selectField = $field->getSelectField($column, $column['setting']);

					$aSQL += $field->getQueryParameters();

					return $selectField;
				}

		}
	}


	/**
	 * Prepare single field
	 *
	 * @param int $iColumnID
	 * @param string $sData
	 * @return array
	 */
	protected function _prepareField($aColumn, $sData, $aResult, $bPlaceholder = false, $iGroupID = null) {

		$iColumnID = $aColumn['column_id'];

		$aRows = array();

		$aLines = explode('{_}', $sData);

		$aCache39 = array();

		$sColumnSetting = $aColumn['setting'] ?? '';

		// Lehrer und Ersatzlehrer pro Block zusammenfügen
		if($iColumnID == 33) {

			$aTmp = [];
			foreach($aLines as $iKey => &$sLine) {
				$aLine = explode('{::}', $sLine);

				if(!isset($aTmp[$aLine[4]])) {
					$aTmp[$aLine[4]] = [
						'lessons' => $aLine[5],
						'teachers' => []
					];
				}

				// Ersatzlehrer
				if($aLine[0] != $aLine[3]) {
					$aTmp[$aLine[4]]['lessons'] -= $aLine[6];
					$aTmp[$aLine[4]]['teachers'][] = $aLine;
				} else {
					$aTmp[$aLine[4]]['teachers']['main'] = $aLine;
				}

			}

			$aLines = [];
			foreach($aTmp as $aTeachers) {

				// Wenn alle Lektionen von Ersatzlehrern unterrichtet werden
				if($aTeachers['lessons'] <= 0) {
					unset($aTeachers['teachers']['main']);
				}

				$aLine = [];
				foreach($aTeachers['teachers'] as $aTeacher) {
					$aLine[] = $this->getTeacherRow($aTeacher, $sColumnSetting, $aResult);
				}
				$aLines[] = implode('; ', $aLine);
			}

		}

		foreach((array)$aLines as $iKey => $sLine) {

			$aLine = explode('{::}', $sLine);

			if(
				$iColumnID == 9	||
				$iColumnID == 10 ||
				$iColumnID == 52
			) {
				// Nur wenn eine Inquiry Course ID da ist
				if(
					!empty($aLine[1])
				) {

					$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aLine[1]);

					switch($iColumnID) {
						case 9:
							$aLine[1] = $oInquiryCourse->getTuitionIndexValue('total_weeks', $this->_oDateFrom);
							break;
						case 10:
							$aLine[1] = $oInquiryCourse->getTuitionIndexValue('current_week', $this->_oDateFrom). '/' . $oInquiryCourse->getTuitionIndexValue('total_weeks', $this->_oDateFrom);
							break;
						case 52: // Status (Kurs)
							$aLine[1] = $oInquiryCourse->getTuitionIndexValue('state', $this->_oDateFrom);
							break;
					}

				}
			}
			elseif($iColumnID == 20) {
				// Nur wenn eine Class ID da ist
				if(
					!empty($aLine[1])
				) {
					$oClass = Ext_Thebing_Tuition_Class::getInstance($aLine[1]);
					$aLine[1] = $oClass->weeks;
				}
			}
			elseif(
				$iColumnID == 11 ||
				$iColumnID == 12 ||
				$iColumnID == 27 ||
				$iColumnID == 56 ||
				$iColumnID == 57
			) {
				$oInquiry = Ext_TS_Inquiry::getInstance($aLine[0]);

				switch($iColumnID) {
					case 11: // Erstes Startdatum (Buchung, Kurs-Leistungen)
						$aLine[1] = $oInquiry->getFirstCourseStart(true);
						break;
					case 12: // Letztes Startdatum (Buchung, Kurs-Leistungen)
						$aLine[1] = $oInquiry->getLatestCourseEnd();
						break;
					case 27: // Status (Buchung)
						$aLine[1] = $oInquiry->getTuitionIndexValue('state', $this->_oDateFrom);
						break;
					case 56: // Kurswochen (absolut)
						$aLine[1] = $oInquiry->getTuitionIndexValue('total_course_weeks');
						break;
					case 57: // Kurswochen (relativ)
						$aLine[1] = $oInquiry->getTuitionIndexValue('total_course_duration');
						break;
				}

			}
			elseif(
				$iColumnID == 39
			) // Unterichtstage
			{
				$aCache39[$aLine[0]][] =  $aLine[1];
			}

			if(
				$iColumnID == 34
			) { // Wochentage

				$aRows[$aLine[1]] = $aLine[1];

				$sDay = $this->_getDayShort($aLine[1]);

				if(!$bPlaceholder) {
					$this->_aDays[$aLine[1]] = $sDay;

					ksort($this->_aDays);
				}
			} elseif ($iColumnID == 76) { // Travellers speichern, nach Gruppen ID, für Spalten
				$aRows[$aLine[1]] = $aLine[1];
				if (
					!$this->travellers[$iGroupID] ||
					!in_array($aLine[1], $this->travellers[$iGroupID])
				) {
					$this->travellers[$iGroupID][] = $aLine[1];
				}
			}
			elseif($iColumnID == 41) // Wochentage mit Zeiten
			{
				$sDay = $this->_getDayShort($aLine[1]);

				$sFrom = substr($aLine[2], 0, -3);
				$sUntil = substr($aLine[3], 0, -3);

				$sBreak = '';
				if(isset($aRows[$aLine[1]])){
					// Zeilenumbruch
					#$sBreak = '<br/>';
				}

				$this->_aDaysTimes[$iGroupID][$aLine[1].$sFrom.$sUntil] = $sBreak . $sDay . " " . $sFrom . " - " . $sUntil;

				ksort($this->_aDaysTimes[$iGroupID]);

				$aRows[$aLine[1]] = $sDay . " " . $sFrom . " - " . $sUntil;
			}
			elseif($iColumnID == 49) // Unterrichtzeiten
			{
				$sFrom = substr($aLine[1], 0, -3);
				$sUntil = substr($aLine[2], 0, -3);

				$this->_aTimes[$iGroupID][$sFrom.$sUntil] = $sFrom . " - " . $sUntil;

				$aRows[$sFrom.$sUntil] = $sFrom . " - " . $sUntil;
			}
			elseif(
				$iColumnID == 11 ||
				$iColumnID == 12 ||
				$iColumnID == 22 ||
				$iColumnID == 23 ||
				$iColumnID == 75
			) // Wochentage mit Zeigen
			{
				$aRows[] = Ext_Thebing_Format::LocalDate($aLine[1]);
			}
			elseif(
				//$iColumnID == 25 || // Anwesenheit
				$iColumnID == 64 || // Anwesenheit absolut
				$iColumnID == 65 // Anwesenheit absolut (erwartet)
			) {
				$aRows[] = Ext_Thebing_Format::Number($aLine[1]) . ' %';
			}
			// Nummer formatieren
			elseif(
				$iColumnID == 44 ||
				$iColumnID == 45 ||
				$iColumnID == 46 ||
				$iColumnID == 53 ||
				$iColumnID == 54 ||
				$iColumnID == 55
			) {
				$aRows[] = Ext_Thebing_Format::Number($aLine[1]);
			}
			// Währungsbetrag formatieren
			elseif(
				$iColumnID == 62 ||
				$iColumnID == 63
			) {
				$aRows[] = Ext_Thebing_Format::Number($aLine[1], $aLine[2]);
			}
			elseif(
				$iColumnID==31 ||
				$iColumnID==32
			) {

				$aRows[] = $this->getTeacherRow($aLine, $sColumnSetting, $aResult);

			} elseif($iColumnID==48) {

				$iGender	= $aLine[1];
				$aGenders	= Ext_TC_Util::getGenders(false);


				if(
					isset($aGenders[$iGender])
				) {
					$aLine[1] = $aGenders[$iGender];
				}
				else
				{
					$aLine[1] = '';
				}

				$aRows[] = $aLine[1];
			}
			elseif($iColumnID == 60) {
				if(isset($aLine[1])) {
					$oFormat = new Ext_Thebing_Gui2_Format_School_Tuition_Block_Description(!$this->bExport);
					$aRows[] = $oFormat->format($aLine[1]);
				}
			} elseif($iColumnID == 1) {

				if(count($aLine) > 2) {
					if(
						!empty($sColumnSetting) &&
						$sColumnSetting === 'full'
					) {
						$aRows[] = $aLine[1].', '.$aLine[2];
					} else {
						$aRows[] = $aLine[1];
					}
				} else {
					$aRows[] = $aLine[1];
				}

			} else {

				$field = $this->getField($iColumnID);
				if(
					$field instanceof TsTuition\Model\Report\Field &&
					!empty($field->hasPrepareField())
				) {
					$aRows[] = $field->getPrepareField($aLine, $aColumn);
				} else {

					if(count($aLine) > 1) {
						$aRows[] = $aLine[1];
					} else {
						$aRows[] = $aLine[0];
					}

				}
			}
		}

		// Unterrichtstage
		if($iColumnID == 39) {

			$aRows = [];
			$aDays = [];

			foreach((array)$aCache39 as $aCache) {
				foreach($aCache as $sValue){
					$aDays[] = $sValue;
				}
			}

			$aDays = array_filter($aDays);
			$aDays = array_unique($aDays);

			$bShort = true;
			if(
				!empty($sColumnSetting) &&
				$sColumnSetting === 'full'
			) {
				$bShort = false;
			}

			$aRows[] = \Ext_Thebing_Util::buildJoinedWeekdaysString($aDays, $this->_sDefaultLangBackend, $bShort);

		}

		return $aRows;
	}

	protected function getTeacherRow($aLine, $sColumnSetting, $aResult) {

		$aAbsenceTeachers	= (array)$aResult['absence_teachers'];
		$iAbsenceDays		= (int)$aResult['absence_days_sum'];
		$iAllDays			= (int)$aResult['days_sum'];
		$iTeacherId			= (int)$aLine[0];
		$iTeacherIdMain		= (int)$aLine[3];

		if($sColumnSetting === 'full') {
			$sName = $aLine[1].', '.$aLine[2];
		} elseif($sColumnSetting === 'firstname') {
			$sName = $aLine[2];
		} else {
			$sName = $aLine[1];
		}

		if($iAllDays==$iAbsenceDays) {

		} elseif(in_array($iTeacherId,$aAbsenceTeachers)) {
			$sRow = '<span style="color:'.Ext_Thebing_Util::getColor('red').'">'.$sName.'</span>';
		} elseif($iTeacherId!=$iTeacherIdMain) {
			$sRow = '<span style="color:'.Ext_Thebing_Util::getColor('substitute_teacher').'">'.$sName.'</span>';
		} else {
			$sRow = $sName;
		}

		return $sRow;
	}

	/**
	 * Prepare flexible fields
	 *
	 * @param string $sData
	 * @return array
	 */
	protected function _prepareFlexFields($sData) {

		$aFields = array();
		$aLines = explode('{_}', $sData);

		foreach((array)$aLines as $iKey => $sLine)  {
			$aLine = explode('{::}', $sLine);
			$iKey = $aLine[0] * -1;

			if($aLine[1] == 2) {
				// Typ 2: Checkbox
				$aYesNo = Ext_TC_Util::getYesNoArray();
				$aFields[$iKey] = $aYesNo[$aLine[2]];
			} elseif($aLine[1] == 5) {
				// Typ 5: Select
				$aFields[$iKey] = $aLine[3];
			} elseif($aLine[1] == 8) {
				// Typ 8: Multiselect
				$sCacheKey = 'flex_options_'.$aLine[0];
				if(!isset(self::$aCache[$sCacheKey])) {
					self::$aCache[$sCacheKey] = Ext_Thebing_Flexibility::getOptions($aLine[0], $this->_sDefaultLangFrontend);
				}
				$aOptions = json_decode($aLine[2], true);
				$aFields[$iKey] = implode(', ', array_intersect_key(self::$aCache[$sCacheKey], array_flip($aOptions)));
			} else {
				$aFields[$iKey] = $aLine[2];
			}

		}

		return $aFields;
	}


	/**
	 * Prepare results from DB
	 *
	 * @param array $aResults
	 * @return array
	 */
	protected function _prepareResults($aResults) {

		$aList = array();

		$aCounter = $aMaxCounter = $aMaxes = $maxesForTravellers = [];

		// Bei Aufteilung nach Tag und eine Zeile pro Lektion.
		if (
			$this->_oReport->start_with == 5 &&
			$this->_oReport->per_lesson
		) {
			$results = [];
			foreach ((array)$aResults as $result) {
				$results[] = $result;
				for ($i = 1; $i < (int)$result['lessons']; $i++) {
					$results[] = $result;
				}
			}
			$aResults = $results;
		}

		foreach((array)$aResults as $aResult) {

			$aNew = $aFlex = $aPlaceholder = [];

			// Filter nach Buchungsstatus
			if($this->_aFilterData['state_booking'] !== 'all') {
				$oInquiry = Ext_TS_Inquiry::getInstance($aResult['inquiry_id']);
				$aWeekStates = explode(' ', $oInquiry->getTuitionIndexValue('state', $this->_oDateFrom));
				if(!in_array($this->_aFilterData['state_booking'], $aWeekStates)) {
					continue;
				}
			}

			// Filter nach Kursstatus
			if($this->_aFilterData['state_course'] !== 'all') {
				$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aResult['inquiry_course_id']);
				$aWeekStates = explode(' ', $oInquiryCourse->getTuitionIndexValue('state', $this->_oDateFrom));
				if(!in_array($this->_aFilterData['state_course'], $aWeekStates)) {
					continue;
				}
			}

			$iGroupID = $aResult['tmp_group_select_id'];
			$iSubGroupID = $aResult['tmp_subgroup_select_id'] ?? 0;

			$aList[$iGroupID]['title'] = $aResult['tmp_group_select_title'];
			$aList[$iGroupID]['group_id'] = $iGroupID;
			$aList[$iGroupID]['subgroup_id'] = $iSubGroupID;
			$aList[$iGroupID]['inquiry_id'] = $aResult['inquiry_id'];

			$aList[$iGroupID]['data'][$iSubGroupID]['title'] = $aResult['tmp_subgroup_select_title'] ?? '';

			foreach([4, 38, 46] as $iFlexSectionId) {
				if(!empty($aResult['flex_fields_'.$iFlexSectionId])) {
					$aFlex += $this->_prepareFlexFields($aResult['flex_fields_'.$iFlexSectionId]);
				}
			}

			$iCurrentWeek = $this->_oDateFrom->get(WDDate::TIMESTAMP);

			foreach((array)$this->aColumns as $aColumn) {

				$iColumnID = $aColumn['column_id'];
				$setting = $aColumn['setting'];
				$iPosition = $aColumn['position'];

				// Nur nach dem Setting schauen wenn es auch eins gibt, sonst nur nach der Spalte.
				if (!empty($setting)) {
					$sValue = $aResult['field_'.$iColumnID.'_'.$setting];
				} else {
					$sValue = $aResult['field_'.$iColumnID];
				}


				if($iColumnID == 42) {
					$aNew[$iPosition] = (int)++$aCounter[$iGroupID];
					$columnCountColumnPosition = $iPosition;
				} elseif($iColumnID == 43) {
					if(
						$this->_oReport->group_by == 1 || // Klasse
						$this->_oReport->group_by == 9 // Block
					) {
						$oPlaceholder = new Ext_Thebing_School_Tuition_Block_Placeholder($aResult['block_id']);

						$iMax = $oPlaceholder->getClassRoomMax();

						$aMaxes[$iGroupID] = $iMax;

						$aNew[$iPosition] = (int)++$aMaxCounter[$iGroupID];

						$maxCountColumnPosition = $iPosition;
					}

				} elseif ($iColumnID == 76) {
					$oPlaceholder = new Ext_Thebing_School_Tuition_Block_Placeholder($aResult['block_id']);
					$iMax = $oPlaceholder->getClassRoomMax();
					$maxesForTravellers[$iGroupID] = $iMax;
					$aNew[$iPosition] = $this->_prepareField($aColumn, $sValue, $aResult, false, $iGroupID);
				} elseif($iColumnID==47) {
					if(!empty($sValue)) {
						$aAlInquiryCourses	= array();
						$aInquiriesCourses	= explode(',',$sValue);

						foreach($aInquiriesCourses as $sInquiryCourseData) {
							$aInquiryCourseData = explode(':',$sInquiryCourseData);
							$aAlInquiryCourses[] = array(
								'from'	=> $aInquiryCourseData[0],
								'until'	=> $aInquiryCourseData[1],
							);
						}

						$oInquiry			= new Ext_TS_Inquiry();
						$sAllWeeks			= $oInquiry->getAllInquiryCourseWeeks($aAlInquiryCourses, $iCurrentWeek);
						$aNew[$iPosition]	= $sAllWeeks;
					}

					// Flex
				} elseif($iColumnID < 0) {
					$aNew[$iPosition] = [$aFlex[$iColumnID]];
				} else {
					$aNew[$iPosition] = $this->_prepareField($aColumn, $sValue, $aResult, false, $iGroupID);
				}

			}

			$aList[$iGroupID]['data'][$iSubGroupID]['data'][] = $aNew;

			$iCounter++;
		}

		// Fehlende Tage vervollständigen
		/*if($this->_oReport->sub_group == 'days') {

			foreach($aList as $iGroupID=>&$data) {

				// Wochenende ist da?
				if(isset($data['data'][6]) || isset($data['data'][7])) {
					$e=7;
				} else {
					$e=5;
				}

				$dayAdded = false;

				for($i=1;$i<=$e;$i++) {
					if(!isset($data['data'][$i])) {
						$date = clone $this->_oDateFrom;
						$data['data'][$i] = [
							'title' => $date->add($i-1, WDDate::DAY)->get(WDDate::DB_DATE),
							'data' => [
								0 => [
									$maxCountColumnPosition => 1,
									$columnCountColumnPosition => 1,
								]
							]
						];

						$dayAdded = true;
					}
				}

				if($dayAdded) {
					ksort($data['data']);
				}

			}

		}*/

		foreach((array)$aResults as $aResult) {

			$iGroupID = $aResult['tmp_group_select_id'];

			foreach((array)$aResult as $sKey => $sValue) {

				if(substr($sKey, 0, 12) == 'placeholder_') {

					preg_match('/_([0-9]+)$/', $sKey, $aNumber);

					preg_match('/^placeholder_(.*?)_[0-9]+$/', $sKey, $aPlaceholders);

					$sPlaceholder = $aPlaceholders[1];

					$this->_aPlaceholders[$iGroupID][$sPlaceholder][] = $this->_prepareField(['column_id'=>$aNumber[1]], $sValue, $aResult, true, $iGroupID);

				}
			}
		}

		// Alle Klassenplätze sollen immer angezeigt werden, auffüllen mit ''
		if (!empty($maxesForTravellers)) {
			foreach ($maxesForTravellers as $groupID => $maxTravellers) {
				$this->travellers[$groupID] = array_pad($this->travellers[$groupID], max(1, $maxTravellers), '');
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // MAX-Counter column

		if(!empty($aMaxes)) {

			foreach((array)$aMaxes as $iGroupID => $iMax) {

				$aSubGroupIds = array_keys($aList[$iGroupID]['data']);

				foreach ($aSubGroupIds as $iSubGroupID) {

					while(count($aList[$iGroupID]['data'][$iSubGroupID]['data']) < $iMax) {

//					$oldSubGroupId = $iSubGroupID;
//
//					if ($oldSubGroupId == $iSubGroupID) {
//
//					}

						$aLast = end($aList[$iGroupID]['data'][$iSubGroupID]['data']);

						if (array_key_exists($maxCountColumnPosition, $aLast)) {
							$aLast[$maxCountColumnPosition]++;
						}

						if (array_key_exists($columnCountColumnPosition, $aLast)) {
							$aLast[$columnCountColumnPosition]++;
						}

						foreach ((array)$aLast as $iKey => $mValue) {
							if ($iKey != $maxCountColumnPosition && $iKey != $columnCountColumnPosition) {
								$aLast[$iKey] = '';
							}
						}

						$aList[$iGroupID]['data'][$iSubGroupID]['data'][] = $aLast;
					}
				}
			}

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aList;
	}

	/**
	 * Prepare column widths
	 */
	protected function _prepareWidths() {

		$iTotalWidth = 0;
		foreach($this->_oReport->columns as $aColumn) {

			$this->_aWidths[$aColumn['position']] = $aColumn;

			if(!empty($aColumn['label'])) {
				$this->_aWidths[$aColumn['position']]['label'] = $aColumn['label'];
			}
			if($aColumn['column_id'] == 34) {

				if (count($this->_aDays) <= 0) {
					$iWidth = 1;
				} else {
					$iWidth = (int)($aColumn['width'] / count($this->_aDays));
				}

				if ($iWidth <= 0) {
					$iWidth = 1;
				}

				if (!empty($this->_aWidths[$aColumn['position']]['setting'])) {
					$this->_aWidths[$aColumn['position']]['setting'] = (int)$this->_aWidths[$aColumn['position']]['setting'];
				} else {
					$this->_aWidths[$aColumn['position']]['setting'] = 1;
				}

				$aDays = $this->_aDays;
				if (!empty($this->_aWidths[$aColumn['position']]['label'])) {
					$aDayLabels = explode(',', $this->_aWidths[$aColumn['position']]['label']);
					if (count($aDayLabels) == 1) {
						foreach ($aDays as &$sDay) {
							$sDay = reset($aDayLabels);
						}
					} else {
						foreach ($aDayLabels as $iDay => &$sDayLabel) {
							$aDays[$iDay + 1] = $sDayLabel;
						}
					}
				}

				$iTotalWidth += $iWidth * count($aDays);

				$this->_aWidths[$aColumn['position']]['days'] = $aDays;
				$this->_aWidths[$aColumn['position']]['pdf'] = $iWidth;
			} elseif ($aColumn['column_id'] == 76) {
				$width = (int)$aColumn['width'];
				$iTotalWidth += $width;
				$this->_aWidths[$aColumn['position']]['travellers'] = $this->travellers;
				$this->_aWidths[$aColumn['position']]['pdf'] = $width;
			} else {

				$iTotalWidth += (int)$aColumn['width'];

				$this->_aWidths[$aColumn['position']]['pdf'] = $aColumn['width'];
			}

		}

		foreach($this->_aWidths as &$aWidth) {

			$aWidth['html'] = (int)($aWidth['pdf'] / ($iTotalWidth / 100));

			if($aWidth['html'] <= 0) {
				$aWidth['html'] = 1;
			}

		}
	}

	/**
	 * Liefert alle Felder für das ORDER BY
	 * @return array
	 */
	public function getOrderByFields() {
		return array(
			1 => '`cdb1`.`lastname`', // Nachname
			2 => '`cdb1`.`firstname`', // Vorname
			3 => '`tc_c_n`.`number`', // Kundennummer
			4 => 'getAge(`cdb1`.`birthday`)', // Alter
			5 => '`kls`.`name_'.$this->_sDefaultLangBackend.'`', // Muttersprache
			6 => '`kc`.`nationality_'.$this->_sDefaultLangBackend.'`', // Nationalität
			7 => '`kss`.`text`', // Schülerstatus
			//8 => '`ktc`.`name_'.$this->_sDefaultLang.'`', // Gebuchte Kurse
			//11 => '`kic`.`from`', // Startdatum pro Kurs
			//12 => '`kic`.`until`', // Enddatum pro Kurs
			//13 => '`ktul_`.`name_'.$this->_sDefaultLang.'`', // Gebuchte Level pro Kurs
			14 => '`kpr`.`score`', // Testscore
			15 => '`tc_c_d_phone`.`value`', // Telefonnummer
			16 => '`tc_e`.`email`', // E-Mail
			17 => '`ka`.`ext_2`', // Agentur (Abkürzung)
			18 => '`kvs`.`name`', // Visum (Art)
			19 => '`ktc`.`name_'.$this->_sDefaultLangFrontend.'`', // Kursname
			22 => '`ktcl`.`start_week`', // Startdatum
			//23 => 'BERECHNUNG?!', // Enddatum
			24 => '`kta`.`score`', // Aktuelle Score (der ausgewählten Woche)
			28 => '`ksb`.`title`', // Gebäude
			29 => '`ksf`.`title`', // Etage
			30 => '`kcr`.`name`', // Raum
			31 => '`kt`.`firstname`', // Vorname (Lehrer)
			32 => '`kt`.`lastname`', // Nachname (Lehrer)
			//34 => '`ktbd`.`day`', // Wochentage
			35 => '`ktt`.`from`', // Unterrichtszeit
			36 => '`ktcl`.`name`', // Klassenname
			//37 => 'COUNT(DISTINCT `ki`.`id`)', // Anzahl der Schüler
			38 => '`cdb2`.`ext_1`', // Name der Schule
			39 => 'MIN(`ktbd`.`day`), `ktt`.`from`', // Unterrichtstage
			40 => '`ktul`.`name_'.$this->_sDefaultLangFrontend.'`', // Nivau der Klasse
			48 => '`cdb1`.`gender`', // Geschlecht
			50 => '`kg`.`name`', // Gruppe
			51 => '`kg`.`short`' // Gruppe (Kürzel)
		);
	}

	public function getReport() {
		return $this->_oReport;
	}

	public function setRequestObject(\MVC_Request $request) {

		$this->request = $request;
	}

}                               
