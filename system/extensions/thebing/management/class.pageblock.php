<?php

class Ext_Thebing_Management_PageBlock
{
	/**
	 * Werte formatieren
	 * @var bool
	 */
	public $bFormat = true;

	// The columns settings
	protected $_aColumnsSettings;

	// The results currency
	protected $_iCurrencyID;

	protected $_aFilter;

	// Selected filter dates
	protected $_aFilterDates;

	// Selected filter dates
	protected $_aFilterSchools;

	// Statistic
	protected $_oStatistic;

	// Smarty object
	protected $_oSmarty;

	// From WDDate instance
	protected $_oFrom;

	// Till WDDate instance
	protected $_oTill;

	// The school object
	protected $_oSchool;

	// First periods timestamp
	protected $_iMinTime;

	// Last periods timestamp
	protected $_iMaxTime;

	// The outer query
	protected $_sUseAsFrom;

	// The query GROUP BY data
	protected $_aQueryGroups = array();

	// The export flag
	protected $_bExport = false;

	// Query placeholder
	protected $_aQueryPlaceholder = array(
		'{PERIOD}',				'{SELECT}',					'{DOCUMENTS_JOIN_ADDON}',
		'{ACC_JOIN_ADDON}',		'{COURSES_JOIN_ADDON}',		'{TRANSFER_JOIN_ADDON}',
		'{JOINS}',				'{WHERE}',					'{GROUP_BY}',
		'{ORDER_BY}',			'{LIMIT}',					'{CONTACT_JOIN_ADDON}',
		'{HAVING}',				'{INSURANCES_JOIN_ADDON}',	'{BLOCKS_JOIN_ADDON}',
		'{ITEMS_JOIN_ADDON}',	'{ENQUIRIES_JOIN_ADDON}'	
	);

	/**
	 * Benötigte Query-Teile pro Query
	 * @var array
	 */
	protected $_aNeededParts = array();
	protected $_aUsedParts = array();
	protected $_bHasAnySubGroup = false;
	
	protected $_aParts = array(
		/*
			Enquiry-Spalten rauslöschen in der furchtbaren Checkbox-Liste!

			DELETE FROM
				`kolumbus_statistic_cols_definitions_access`
			WHERE `x_id` = 204 AND `y_id` IN (
					SELECT
						`id`
					FROM
						`kolumbus_statistic_cols_definitions`
					WHERE group_id = 4
			)
		 */
		'inquiry' => array(
			'agency' => array(
				'orderby' => ', `ka`.`id`, `kaga`.`group_id`, `kag`.`id`',
				'part' => 'LEFT JOIN
						`ts_companies` AS `ka`								ON
							`ts_i`.`agency_id`			= `ka`.`id`		AND
							`ka`.`active`				= 1					LEFT JOIN
						`kolumbus_agency_groups_assignments` AS `kaga`			ON
							`kaga`.`agency_id`			= `ka`.`id`			LEFT JOIN
						`kolumbus_agency_groups` `kag`							ON
							`kag`.`id` = `kaga`.`group_id`'
				),
//			'hearaboutus' => array(
//				'orderby' => ', `tc_r`.`position`',
//				'part' => " LEFT JOIN
//					`tc_referrers` `tc_r` ON
//						`tc_r`.`id` = `ts_i`.`referer_id` "
//				),
			'accommodation' => array(
				'orderby' => ', `ts_ija`.`id`',
				'part' => ' LEFT JOIN
					`ts_inquiries_journeys_accommodations` AS `ts_ija`		ON
						`ts_ija`.`journey_id`		= `ts_ij`.`id`	AND
						`ts_ija`.`active`			= 1				AND
						`ts_ija`.`visible`			= 1
						{ACC_JOIN_ADDON} '
				),
			'accommodation_allocation' => array(
				'orderby' => ', `kaa`.`id`, `kr`.`id`',
				'part' => ' LEFT JOIN
						(
							`kolumbus_accommodations_allocations` AS `kaa`					INNER JOIN
							`kolumbus_rooms` AS `kr`
						) ON
							`ts_ija`.`id` =
								`kaa`.`inquiry_accommodation_id`		AND
							`kaa`.`active`				= 1				AND
							`kaa`.`status`				= 0				AND
							`kaa`.`active_storno`		= 1				AND
							`kaa`.`room_id`				= `kr`.`id`		AND
							`kr`.`active`				= 1	'
				),
			'tuition' => array(
				'orderby' => ', `ts_ijc`.`id`, `ktb`.`id`, `kt`.`id`, `ktc`.`id`',
				'part' => ' LEFT JOIN
					`ts_inquiries_journeys_courses` AS `ts_ijc`				ON
						`ts_ijc`.`journey_id`		= `ts_ij`.`id`	AND
						`ts_ijc`.`active`			= 1				AND
						`ts_ijc`.`visible`			= 1
						{COURSES_JOIN_ADDON} LEFT JOIN (
							`kolumbus_tuition_blocks_inquiries_courses` AS  `ktbic` INNER JOIN
							`kolumbus_tuition_blocks` AS  `ktb` FORCE INDEX (PRIMARY) ON
								`ktbic`.`block_id` =  `ktb`.`id` AND
								`ktb`.`active` =1 JOIN
							`ts_teachers` AS  `kt` ON
								`ktb`.`teacher_id` =  `kt`.`id` AND
								`kt`.`active` = 1
						) ON
							`ktbic`.`inquiry_course_id` = `ts_ijc`.`id` AND
							`ktbic`.`active` = 1
							{BLOCKS_JOIN_ADDON} LEFT JOIN
						`kolumbus_tuition_courses` AS `ktc` ON
							`ts_ijc`.`course_id`= `ktc`.`id`
							'
				),
			'tuition_progress' => array(
				'orderby' => ', `ktp`.`id`, `ktl_internal`.`id`',
				'part' => " LEFT JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON
						`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
						`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
						`ts_tcps`.`active` = 1 LEFT JOIN
					`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
						`ts_ijclc`.`journey_course_id` = `ts_ijc`.`id` AND
						`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` LEFT JOIN
					`kolumbus_tuition_courses` `ktc_combination_courses` ON
						`ktc_combination_courses`.`active` = 1 AND
						`ktc_combination_courses`.`id` = `ts_tcps`.`type_id` LEFT JOIN
					`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
						`ts_tctc`.`course_id` = `ktc`.`id` LEFT JOIN
					/* TMO-Logik 3: Progress, der in diese Woche reinfällt */
					/* Anmerkung: TMOs arbeiten ohne inquiry_course_id, nur inquiry_id und levelgroup_id */
					`kolumbus_tuition_progress` `ktp` ON
						`ktp`.`inquiry_course_id` = `ts_ijc`.`id` AND
						`ktp`.`program_service_id` = `ts_tcps`.`id` AND
						`ktp`.`active` = 1 AND
						`ktp`.`courselanguage_id` = `ts_tctc`.`courselanguage_id` AND
						`ktp`.`active` = 1 AND (
							`ktp`.`week` <= :TILL AND
							(`ktp`.`week` + INTERVAL 6 DAY) >= :FROM
						) LEFT JOIN
					/* TMO-Logik 2: Nicht jede Woche hat einen Progress */
					/* Anmerkung: TMOs arbeiten ohne inquiry_course_id, nur inquiry_id und levelgroup_id */
					`kolumbus_tuition_progress` `ktp2` ON
						`ktp2`.`inquiry_id` = `ts_i`.`id` AND
						`ktp2`.`courselanguage_id` = `ts_tctc`.`courselanguage_id` AND
						`ktp2`.`active` = 1 AND
						`ktp2`.`week` = (
							SELECT
								MAX(`ktp_sub`.`week`)
							FROM
								`kolumbus_tuition_progress` `ktp_sub`
							WHERE
								`ktp_sub`.`inquiry_id` = `ts_i`.`id` AND
								`ktp_sub`.`courselanguage_id` = `ts_tctc`.`courselanguage_id` AND
								`ktp_sub`.`week` <= :TILL AND 
								`ktp_sub`.`active` = 1
						) LEFT JOIN
					/* TMO-Logik 3 nicht implementiert (Level über Block) */
					`ts_tuition_levels` `ktl_internal` ON
						`ktl_internal`.`id` = IFNULL(`ktp`.`level`, `ktp2`.`level`)
				"
			),
			'transfer' => array(
				'orderby' => ', `ts_ijt`.`id`',
				'part' => "LEFT JOIN
					`ts_inquiries_journeys_transfers` AS `ts_ijt` FORCE INDEX (`journey_id`)			ON
						`ts_ijt`.`journey_id`		= `ts_ij`.`id`	AND
						`ts_ijt`.`active`			= 1				AND
						`ts_ijt`.`booked`			= 1				AND
						`ts_ij`.`transfer_mode` != ".Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE."
						{TRANSFER_JOIN_ADDON} "
				),
			'insurance' => array(
				'orderby' => ', `ts_iji`.`id`',
				'part' => 'LEFT JOIN
					`ts_inquiries_journeys_insurances` AS `ts_iji`			ON
						`ts_iji`.`journey_id`		= `ts_ij`.`id`	AND
						`ts_iji`.`active`			= 1				AND
						`ts_iji`.`visible`			= 1
						{INSURANCES_JOIN_ADDON}	'
				),
			'address' => array(
				'orderby' => ', `tc_c`.`id`',
				'part' => "INNER JOIN
					`ts_inquiries_to_contacts` AS `ts_i_to_c`				ON
						`ts_i_to_c`.`inquiry_id`	= `ts_i`.`id`		INNER JOIN
					`tc_contacts` AS `tc_c`									ON
						`ts_i_to_c`.`contact_id`	= `tc_c`.`id`	AND
						`ts_i_to_c`.`type`			= 'traveller'	AND
						`tc_c`.`active`				= 1					LEFT JOIN
					(
						`tc_contacts_to_addresses` AS `tc_c_to_a`						INNER JOIN
						`tc_addresses` AS `tc_a`										INNER JOIN
						`tc_addresslabels` AS `tc_al`
					) ON
						`tc_c_to_a`.`contact_id`	= `tc_c`.`id`	AND
						`tc_c_to_a`.`address_id`	= `tc_a`.`id`	AND
						`tc_a`.`active`				= 1				AND
						`tc_a`.`label_id`			= `tc_al`.`id`	AND
						`tc_al`.`active`			= 1				AND
						`tc_al`.`type`				= 'contact_address' "
			),
			'document' => array(
				'orderby' => ',
					`kid`.`id`,
					`kidvi`.`id`',
				'part' => " LEFT JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
						`kid`.`entity_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						/* Credits immer ignorieren */
						`kid`.`is_credit` = 0
						{DOCUMENTS_JOIN_ADDON}							LEFT JOIN
					`ts_documents_to_documents` `ts_dtd_creditnotes` ON
						`ts_dtd_creditnotes`.`parent_document_id` = `kid`.`id` AND
						`ts_dtd_creditnotes`.`type` = 'creditnote' LEFT JOIN
					/* Credit-Creditnotes haben weder is_credit = 1 noch werden die mit der Ursprungs-CN verknüpft */
					/* Scheinbar wird eine Creditnote aber immer mit credit und creditnote mit der Ursprungsrechnung verknüpft */
					`ts_documents_to_documents` `ts_dtd_creditnotes2` ON
						`kid`.`type` = 'creditnote' AND
						`ts_dtd_creditnotes2`.`child_document_id` = `kid`.`id` AND
						`ts_dtd_creditnotes2`.`type` = 'creditnote' LEFT JOIN
					`ts_documents_to_documents` `ts_dtd_credit` ON
						`ts_dtd_credit`.`parent_document_id` = IFNULL(`ts_dtd_creditnotes2`.`parent_document_id`, `kid`.`id`) AND
						`ts_dtd_credit`.`type` = 'credit' LEFT JOIN
					`kolumbus_inquiries_documents_versions` AS `kidv`		ON
						`kidv`.`id`					= `kid`.`latest_version`	AND
						`kidv`.`active`				= 1 LEFT JOIN
					`kolumbus_inquiries_documents_versions_items` AS `kidvi` ON
						`kidvi`.`version_id`		= `kidv`.`id`	AND
						`kidvi`.`active`			= 1				AND
						`kidvi`.`onPdf`				= 1 AND
						/* Rechnungen, die eine Credit haben, immer rauswerfen */
						`ts_dtd_credit`.`child_document_id` IS NULL
						{ITEMS_JOIN_ADDON}	"
			),
			'inbox' => array(
				'orderby' => ',
					`k_inb`.`id`',
				'part' => " LEFT JOIN
					`kolumbus_inboxlist` `k_inb` ON
						`k_inb`.`short` = `ts_i`.`inbox` AND
						`k_inb`.`active` = 1"
			)
		),
		'enquiry' => array(
			'agency' => array(
				'orderby' => ', `ka`.`id`, `kaga`.`group_id`, `kag`.`id`',
				'part' => 'LEFT JOIN
					`ts_companies` AS `ka`								ON
						`ts_e`.`agency_id`			= `ka`.`id`		AND
						`ka`.`active`				= 1					LEFT JOIN
					`kolumbus_agency_groups_assignments` AS `kaga`			ON
						`kaga`.`agency_id`			= `ka`.`id` 		LEFT JOIN
					`kolumbus_agency_groups` `kag`							ON
						`kag`.`id` = `kaga`.`group_id`'
				),
//			'hearaboutus' => array(
//				'orderby' => ', `kh`.`id`',
//				'part' => 'LEFT JOIN
//					`kolumbus_hearaboutus` AS `kh`							ON
//						`ts_e`.`referer_id`			= `kh`.`id`		AND
//						`kh`.`active`				= 1	'
//				),
			'address' => array(
				'orderby' => ', `tc_c`.`id`',
				'part' => "INNER JOIN
					`ts_enquiries_to_contacts` AS `ts_e_to_c`				ON
						`ts_e_to_c`.`enquiry_id`	= `ts_e`.`id` 		INNER JOIN
					`tc_contacts` AS `tc_c`									ON
						`ts_e_to_c`.`contact_id`	= `tc_c`.`id`	AND
						`ts_e_to_c`.`type`			= 'traveller'	AND
						`tc_c`.`active`				= 1
						{CONTACT_JOIN_ADDON}							LEFT JOIN
					(
						`tc_contacts_to_addresses` AS `tc_c_to_a`						INNER JOIN
						`tc_addresses` AS `tc_a`										INNER JOIN
						`tc_addresslabels` AS `tc_al`
					) ON
						`tc_c_to_a`.`contact_id`	= `tc_c`.`id`	AND
						`tc_c_to_a`.`address_id`	= `tc_a`.`id`	AND
						`tc_a`.`active`				= 1				AND
						`tc_a`.`label_id`			= `tc_al`.`id`	AND
						`tc_al`.`active`			= 1				AND
						`tc_al`.`type`				= 'contact_address' "
			)
		)

	);
	
	/**
	 * Hier wird die Info für das Key-Feld gespeichert
	 * @var type 
	 */
	protected $_aKeyAlias = array();
	protected $_aKeyField = array();

	/**
	 * Debug array
	 * 
	 * @var array
	 */
	public static $_aDebug;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 * 
	 * @param mixed $mStatistic
	 * @param int $iFrom
	 * @param int $iTill
	 * @param bool $bExport
	 */
	public function __construct($mStatistic, $iFrom, $iTill, $bExport = false) {
		$this->_bExport = $bExport;

		if($mStatistic instanceof Ext_Thebing_Management_Statistic) {
			$this->_oStatistic = $mStatistic;
		} elseif($mStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract) {
			$this->_oStatistic = $mStatistic->getFakeStatisticObject();
		} else {
			$this->_oStatistic = new Ext_Thebing_Management_Statistic($mStatistic);
		}

		$this->_oSmarty = new SmartyWrapper();
		$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare dates

		try
		{
			if($iFrom <= 0)
			{
				$iFrom = time();
			}

			$this->_oFrom = new WDDate($iFrom);
		}
		catch(Exception $e)
		{
			$this->_oFrom = new WDDate();
		}

		try
		{
			if($iTill <= 0)
			{
				$iTill = time();
			}

			$this->_oTill = new WDDate($iTill);
		}
		catch(Exception $e)
		{
			$this->_oTill = new WDDate();
		}

		$this->_oFrom->set('00:00:00', WDDate::TIMES);
		$this->_oTill->set('23:59:59', WDDate::TIMES);

		$this->_iMinTime = $this->_oFrom->get(WDDate::TIMESTAMP);
		$this->_iMaxTime = $this->_oTill->get(WDDate::TIMESTAMP);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			SET
				SESSION group_concat_max_len = 1048576
		";
		DB::executeQuery($sSQL);
	}

	/* ==================================================================================================== */

	/**
	 * Get the filter data defined under statistic creation
	 *
	 * @param Ext_Thebing_Management_Statistic|Ext_Thebing_Management_Statistic_Static_Abstract $oStatistic
	 * @param Ext_Thebing_Management_PageBlock $oPageblock
	 * @return array
	 */
	public static function getFilterData($oStatistic, $oPageblock=null) {

		$aFieldOptions = array();
		$aFieldOptions['statistic'] = $oStatistic;

		if($oStatistic instanceof Ext_Thebing_Management_Statistic_Static_Abstract) {
			$aFieldOptions['hide_currency_field'] = !$oStatistic::canConvertCurrency();
			$aFieldOptions['static_statistic'] = true;
			$oStatistic = $oStatistic->getFakeStatisticObject();
		}

		$oGui = new Ext_Thebing_Gui2('', 'Ext_Thebing_Management_Statistic_Gui2');
		$oGui->gui_description = Ext_Thebing_Management_Statistic::$_sDescription;

		$oDialog = new Ext_Gui2_Dialog();
		$oTabData = $oDialog->createTab('');

		Ext_Thebing_Management_Statistic_Gui2::addAdditionalFieldsByRef($oGui, $oDialog, $oTabData, $aFieldOptions);

		$oDialog->setElement($oTabData);

		$aData = $oDialog->generateAjaxData((array)$oStatistic->id, $oGui->hash);

//		$sSRC = '/admin/extensions/thebing/images/tuition_teacher_replace.png';
//		$sClick = 'onclick="refreshBlock(\'' . $oStatistic->id . '\');"';
//		$aData['tabs'][0]['html'] .= '<div class="GUIDialogRow">';
//			$aData['tabs'][0]['html'] .= '<div class="GUIDialogRowLabelDiv">';
//				$aData['tabs'][0]['html'] .= '<div>' . L10N::t('Aktualisieren', Ext_Thebing_Management_Statistic::$_sDescription) . '</div>';
//			$aData['tabs'][0]['html'] .= '</div>';
//			$aData['tabs'][0]['html'] .= '<div class="GUIDialogRowInputDiv">';
//				$aData['tabs'][0]['html'] .= '<img class="guiInplaceEditorImg" style="cursor:pointer;" src="' . $sSRC . '" alt="" ' . $sClick . ' />';
//			$aData['tabs'][0]['html'] .= '</div>';
//			$aData['tabs'][0]['html'] .= '<div class="divCleaner"></div>';
//		$aData['tabs'][0]['html'] .= '</div>';

		$aValues = array(
			'id' => 'ID_' . $oStatistic->id,
			'values' => $oDialog->aSaveData
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach($aValues['values'] as $iKey => $aValue)
		{
			switch($aValue['db_column'])
			{
				case 'schools':
				case 'agencies':
				case 'agency_categories':
				case 'agency_countries':
				case 'agency_groups':
				case 'countries':
				case 'agency':
				case 'direct_customer':
				case 'customer_invoice_filter':
				case 'inquiry_group_filter':
				{
					$aValues['values'][$iKey]['value'] = $oStatistic->{$aValue['db_column']};

					break;
				}
				case 'group_by':
				case 'currency_id':
				{
					$aValues['values'][$iKey]['value'] = $oStatistic->{$aValue['db_column']};

					$aValues['values'][$iKey]['select_options'] = $oStatistic->currencies;

					break;
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aFilter = array(
			'hash' => $oGui->hash,
			'html' => $aData['tabs'][0]['html'],
			'data' => $aValues,
			'show_dates_error' => false
		);

		if($oStatistic instanceof Ext_Thebing_Management_Statistic) {
			$aFilter['global']['from'] = $oPageblock->_oFrom->get(WDDate::TIMESTAMP);
			$aFilter['global']['till'] = $oPageblock->_oTill->get(WDDate::TIMESTAMP);
		} else {
			// Werte von statischer Statistik (stdClass)
			$aFilter['global']['from'] = $oStatistic->from->getTimestamp();
			$aFilter['global']['till'] = $oStatistic->until->getTimestamp();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if($oStatistic->type == 2) {
			$aFilter['show_dates_error'] = true;
		}

		return $aFilter;
	}

	/**
	 * Get results
	 *
	 * @param array $aFilter
	 * @param bool $bReturnObject
	 * @return Ext_Thebing_Management_PageBlock_Result|string
	 * @throws RuntimeException
	 */
	public function getResults($aFilter = array(), $bReturnObject=false) {

		$iTotalTime = microtime(true);

		if (
			$this->_oStatistic instanceof Ext_Thebing_Management_Statistic &&
			$this->_oStatistic->exist()
		) {
			$this->_oStatistic->last_use = time();
			$this->_oStatistic->save();
		}

		$this->_aFilter = $aFilter['save'];
		$this->_aUsedParts = array();

		$this->_setGloballyNeededParts();

		if(empty($this->_aFilter)) {
			$this->_aFilter = self::createFilterData($this->_oStatistic); // Get prepared main filter
		}
		$aSQL = array();
		$sSQL = $this->_createQuery($this->_aFilter, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check language errors

		$sLang = $this->_oSchool->getLanguage();

		foreach($this->_aFilterSchools as $iSchoolID)
		{
			$oSchool = Ext_Thebing_School::getInstance($iSchoolID);

			$aList = $oSchool->getLanguageList();

			if(!isset($aList[$sLang]))
			{
				return 'school_error';
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oResult = new Ext_Thebing_Management_PageBlock_Result($this->_oStatistic, $this->_aFilterDates, $this->_aColumnsSettings);
		$oResult->bFormat = $this->bFormat;

		$aOriginalNeededParts = $this->_aNeededParts;
		$aOriginalUsedParts = $this->_aUsedParts;

		foreach($this->_aFilterDates as $iKey => $aDate)
		{
			$sCopySQL = $sSQL;

			$aSQL['FROM'] = $aDate['from']->get(WDDate::DB_DATE);
			$aSQL['TILL'] = $aDate['till']->get(WDDate::DB_DATE);

			$aSQL['FROM_WITH_TIME'] = $aDate['from']->get(WDDate::DB_TIMESTAMP);
			$aSQL['TILL_WITH_TIME'] = $aDate['till']->get(WDDate::DB_TIMESTAMP);

			$aSQL['FROM_DATETIME'] = $aDate['from']->get(WDDate::DB_TIMESTAMP);
			$aSQL['TILL_DATETIME'] = $aDate['till']->get(WDDate::DB_TIMESTAMP);

			$sCopySQL = str_replace('{PERIOD}', $iKey + 1, $sCopySQL);

			foreach((array)$this->_oStatistic->columns['cols'] as $iColumnID)
			{

				$this->_aUsedParts = $aOriginalUsedParts;
				$this->_aNeededParts = $aOriginalNeededParts;

				$this->_setNeededParts($iColumnID);

				$sSubSQL = $sCopySQL;

				$this->_addNeededParts($sSubSQL);

				if($this->_oStatistic->list_type == 1) // Summe
				{
					$this->_createSelect($iColumnID, $sSubSQL, $aSQL);
				}
				else if($this->_oStatistic->list_type == 2) // Detail
				{
					$this->_createSimpleSelect($iColumnID, $sSubSQL, $aSQL);
				}

				$this->_addNeededParts($sSubSQL);

				// Kill other placeholder
				$sSubSQL = str_replace($this->_aQueryPlaceholder, '', $sSubSQL);

				if(!empty($this->_sUseAsFrom))
				{
					$sSubSQL = str_replace('{USAGE}', $sSubSQL, $this->_sUseAsFrom);

					$this->_sUseAsFrom = '';
				}

				$iMicroTime = microtime(true);

				try {
					$aTemp = DB::getPreparedQueryData($sSubSQL, $aSQL);
				} catch(DB_QueryFailedException $e) {
					$sColumn = "Statistic-ID: ".$this->_oStatistic->id."\nColumn-ID: ".$iColumnID."\n\n";
					throw new RuntimeException($sColumn.$e->getMessage());
				}

				self::$_aDebug['Queries_Total_Time'] += microtime(true) - $iMicroTime;
				self::$_aDebug['Queries_Counter']++;
				self::$_aDebug['Queries_Single_Times'][$iColumnID][] = microtime(true) - $iMicroTime;

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // DEBUG: &stats_debug=1&col_id=36

				if(isset($_REQUEST['stats_debug'])) {
					
					__pout(DB::getDefaultConnection()->getLastQuery());
					__pout($aTemp);
					
					if($iColumnID == $_REQUEST['col_id']) {
						__pout(microtime(true) - $iMicroTime);
						__pout(DB::getDefaultConnection()->getLastQuery());
						__pout($aTemp);
					}

				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

				$oResult->addResult($aTemp);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Export

		if($this->_bExport)
		{
			$oExport = new Ext_Thebing_Management_PageBlock_Export($this->_oStatistic, $oResult);
			$oExport->export();

			exit();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare and return result code

		$iTime = microtime(true);

		$oResult->format();

		self::$_aDebug['Result_Formating'] = microtime(true) - $iTime;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare and return result code

		self::$_aDebug['Total_Execution_Time'] = microtime(true) - $iTotalTime;

		if(!$bReturnObject) {
			$mReturn = $this->_writeResultsTable($oResult->getData(), $oResult->getLabels());
		} else {
			$mReturn = $oResult;
		}

		self::$_aDebug['Total_Execution_Time'] = microtime(true) - $iTotalTime;

		if(isset($_REQUEST['stats_debug']))
		{
			__pout(self::$_aDebug);
		}

		$usageLogging = new \Update('fidelo.com');
		$usageLogging->requestUpdateServer('/fidelo/api/stats-usage?statistic_title='.urlencode($this->_oStatistic->title).'&statistic_id='.urlencode($this->_oStatistic->id));
		
		return $mReturn;
	}

	/**
	 * Setzt benötigte Query-Teile
	 * @param $sSql
	 * @param bool $bReadonly Kein Zurücksetzen
	 */
	protected function _addNeededParts(&$sSql, $bReadonly=false) {

		// Based on enquiries
		if($this->_oStatistic->period == 5) {
			$sKey = 'enquiry';
		} else {
			$sKey = 'inquiry';
		}

		foreach($this->_aParts[$sKey] as $sPart=>$aPart) {

			if(
				isset($this->_aNeededParts[$sPart]) &&
				!isset($this->_aUsedParts[$sPart])
			) {

				$sSql = str_replace('{JOINS}', $aPart['part'].' {JOINS}', $sSql);
				$sSql = str_replace('{ORDER_BY}', $aPart['orderby'].' {ORDER_BY}', $sSql);

				if(!$bReadonly) {
					$this->_aUsedParts[$sPart] = 1;
				}
			}
			
		}

		if(!$bReadonly) {
			$this->_aNeededParts = array();
			$this->_setGloballyNeededParts();
		}
	}

	/**
	 * Durch WHERE sind durch manche Spalten alle von bestimmten Joins abhängig
	 */
	protected function _setGloballyNeededParts() {

		$aCols = array_merge((array)$this->_oStatistic->columns['cols'], (array)$this->_oStatistic->columns['groups']);

		foreach($aCols as $iColumnID) {
			switch($iColumnID) {
				case 21: // Agenturen
				case 23: // Agenturkategorien
				case 25: // Agenturgruppen
				case 158: // Agenturland
					$this->_aNeededParts['agency'] = 1;
					break;
			}
		}
	}

	/**
	 * Get template path
	 * 
	 * @return string
	 */
	public static function getTemplatePath()
	{
		return Util::getDocumentRoot() . 'system/legacy/admin/extensions/thebing/management/smarty/';
	}


	/**
	 * Get the block title
	 * 
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_oStatistic->title;
	}

	/**
	 * Get default sum query part
	 *
	 * @param bool $bGross
	 * @param bool $bCheckCreditnote
	 * @param int $iTaxOption 0 = keine Steuer, 1 = Steuer addieren, 2 = nur Steuerbetrag
	 * @param bool $bTransferSplit
	 * @return string
	 */
	protected function _addDefaultSumPart($bGross=false, $bCheckCreditnote=false, $iTaxOption=0, $bTransferSplit=true) {

		/*
			RECHNUNG: 1000$ (Entspricht am Tag der Stellung 850€)

			Fall 1: Nichts bezahlt
				-> Statistik zeigt 850€ bis mindestens eine (Teil)Zahlung gemacht wurde

			Fall 2: Teilzahlung (200$ entsprechen am Tag der Zahlung 150€)
				-> Statistik zeigt 750€ bis mindestens eine weitere (Teil)Zahlung folgt oder komplett bezahlt wurde
				 1000$ - 200$ = 800$ ~ 600€
				 600€ + 150€ = 750€

			Fall 3: Komplett bezahlt (800$ ensprechen heute 650€)
				-> Statistik zeigt ab jetzt IMMER 800€
				650€ + 150€ = 800€
		*/

		// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
		// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

		$this->_aNeededParts['transfer'] = 1;

		// Für explizite Bruttospalten die entsprechende Spalte benutzen
		if(!$bGross) {
			$sAmountColumn = '`kidvi`.`amount_net`';
			$sSpecialAmountColumn = '`kidvi`.`index_special_amount_net`';
			$sSpecialAmountColumnVat = '`kidvi`.`index_special_amount_net_vat`';
		} else {
			$sAmountColumn = '`kidvi`.`amount`';
			$sSpecialAmountColumn = '`kidvi`.`index_special_amount_gross`';
			$sSpecialAmountColumnVat = '`kidvi`.`index_special_amount_gross_vat`';
		}

		// Wenn true, dann Rechnungspositionen von Bruttorechnungen ignorieren, die bereits eine Creditnote haben
		$sCreditNoteCondition = '';
		if($bCheckCreditnote) {
			$sCreditNoteCondition = " OR `ts_dtd_creditnotes`.`type` = 'creditnote' ";
		}

		// Bei basierend auf Leistungszeitraum müssen Transferpakete gesplittet werden, wenn diese nicht komplett in den Zeitraum fallen
		if(
			// An mehreren Stellen ist das Konstrukt nochmal eingebaut, das darf aber nicht doppelt passieren!
			$bTransferSplit &&
			$this->_oStatistic->period == 3
		) {

			// TODO Das scheint gar nicht mehr aktiv benötigt zu werden (steht immer im Teil `type` != 'transfer')
			// Falls das doch noch benutzt wird, utopische Division durchführen, damit das sichtbar wird
			// Ansonsten fehlt hier der Teil mit der Multiplikation mit 0 (#12077)
			$sDivisionQueryPart = "
				IF(
					`kidvi`.`type` = 'transfer',
					0.0000001,
					1
				)
			";

//			$sDivisionQueryPart = "
//				IF(
//					`kidvi`.`type` = 'transfer',
//					IF(
//						`kidvi`.`type_id` = `ts_ijt`.`id`,
//						1,
//						IF(
//							`kidvi`.`index_from` BETWEEN :FROM AND :TILL AND
//							`kidvi`.`index_until` BETWEEN :FROM AND :TILL,
//							1,
//							2
//						)
//					),
//					1
//				)
//			";

		} else {
			$sDivisionQueryPart = "1";
		}

		/*
		 * Steuer abziehen oder addieren
		 *
		 * Hier folgt ein unschöner Teil der Struktur der Items:
		 * Item-Beträge sollten alle Steuer-Nettobeträge sein, aber natürlich ist das nicht der Fall,
		 * sondern bei Steuern inklusive stehen die Steuern mit in den Beträgen, sind also Steuer-Bruttobeträge.
		 * Daher muss, je nach Fall, die Steuer abgezogen werden bei inklusive und addiert werden bei exklusive.
		 * Eigentlich sollte der Betrag immer addiert werden, was korrekt wäre und bei den Special-Index-Spalten auch der Fall ist.
		 *
		 * Außerdem müssen bei einer vorhandenen CN die Steuerbeträge IMMER von den Bruttobeträgen berechnet werden,
		 * da es in diesem Fall eigentlich keine Netto-Steuerbeträge gibt.
		 */

		// Query-Part zum Ausrechnen des Steuerbetrages bei Steuer inklusive (Beträge sind Steuer-Bruttobeträge)
		// Da Discount keine eigene Position ist, sondern flach abgezogen werden muss, sieht das hier unschön aus
		$sTaxInclusiveQueryPart = "
			IF(
				`kidv`.`tax` = 1,
				IF(
					`kid`.`type` = 'creditnote',
					(
						(`kidvi`.`amount` - (`kidvi`.`amount` / 100 * `kidvi`.`amount_discount`)) -
						((`kidvi`.`amount` - (`kidvi`.`amount` / 100 * `kidvi`.`amount_discount`)) / (`kidvi`.`tax` / 100 + 1))
					),
					(
						(".$sAmountColumn." - (".$sAmountColumn." / 100 * `kidvi`.`amount_discount`)) -
						((".$sAmountColumn." - (".$sAmountColumn." / 100 * `kidvi`.`amount_discount`)) / (`kidvi`.`tax` / 100 + 1))
					)
				),
				0
			)
		";

		// Query-Part zum Ausrechnen des Steuerbetrags bei Steuer exklusive (Beträge sind Steuer-Nettobeträge)
		$sTaxExclusiveQueryPart = "
			IF(
				`kidv`.`tax` = 2,
				IF(
					`kid`.`type` = 'creditnote',
					(`kidvi`.`amount` - (`kidvi`.`amount` / 100 * `kidvi`.`amount_discount`)) * (`kidvi`.`tax` / 100),
					(".$sAmountColumn." - (".$sAmountColumn." / 100 * `kidvi`.`amount_discount`)) * (`kidvi`.`tax` / 100)
				),
				0
			)
		";

		if($iTaxOption === 0) {

			// Bei Steuern inklusive steht der Steuerbetrag mit in amount/amount_net, muss also abgezogen werden
			$sTaxQueryPart = "
				-
				$sTaxInclusiveQueryPart
			";

			// Die Special-Index-Spalte ist bereits der Steuer-Nettobetrag, daher muss nichts (mehr) abgezogen werden
			$sSpecialTaxQueryPart = "0";

		} else { // $iTaxOption === 1 || $iTaxOption === 2

			// Nur bei »nur Steuerbetrag« muss »Steuern inklusive« explizit mit ausgerechnet werden (sonst 0), ansonsten würde die Steuer verdoppelt
			if($iTaxOption !== 2) { // $iTaxOption === 1
				$sTaxInclusiveQueryPart = "0";
			}

			// Steuer muss berechnet werden mit dem Betrag NACH Abzug des Rabatts!
			// Bei Steuern exklusive steht der Steuerbetrag nicht in amount, daher addieren
			$sTaxQueryPart = "
				+ (
					".$sTaxExclusiveQueryPart."
					+
					".$sTaxInclusiveQueryPart."
				)
			";

			// Wenn Steuern gewünscht sind, muss diese bei inklusive UND exklusive addiert werden (da Steuer-Nettobetrag)
			$sSpecialTaxQueryPart = $sSpecialAmountColumnVat;

		}

		// Normaler Endbetrag (Amount - Discount) oder nur Steuerbetrag
		if($iTaxOption !== 2) {
			$sAmountQueryPart = "
				".$sAmountColumn." -
				(".$sAmountColumn." / 100 * `kidvi`.`amount_discount`)
				".$sTaxQueryPart."
			";
		} else { // $iTaxOption === 2
			// Nur Steuerbetrag
			$sAmountQueryPart = "
				".$sTaxQueryPart."
			";
			// Specials benötigen wieder einmal Sonderbehandlung: Betrag darf auch nicht addiert werden
			$sSpecialAmountColumn = "0";
		}

		/**
		 * Special-Items werden direkt über die entsprechende Index-Spalte 
		 * abgezogen und dürfen hier daher nicht eingerechnet werden.
		 */
		$sPart = "
				(
					COALESCE(
						IF(
							(
								`kid`.`type` IN(
									'proforma_brutto',
									'proforma_netto',
									'group_proforma',
									'group_proforma_netto'
								) AND
								`ts_i`.`has_invoice` = 1
								
							) OR
							`kidvi`.`type` = 'special'
							".$sCreditNoteCondition.",
							0,
							(
								".$sAmountQueryPart."
							) +
							COALESCE(
								".$sSpecialAmountColumn." +
								".$sSpecialTaxQueryPart.",
								0
							)
						), 0
					)
				) /
				".$sDivisionQueryPart."
		";

		return $sPart;

	}


	/**
	 * Add periods times to a copy of WHERE condition
	 * 
	 * @param array &$aSQL
	 * @return string
	 */
	protected function _addPeriodsTimes(&$aSQL)
	{
		$sWhere = "";

		$aParts = array(WDDate::DB_TIMESTAMP);

		$aSecondFields = array();

		switch($this->_oStatistic->period)
		{
			case 1: // Buchungsdatum
			{
				$aAliases		= array('ts_i');
				$aFields		= array('created');

				break;
			}
			case 3: // Leistungszeitraum
			{
				$aAliases		= array('ts_i');
				$aFields		= array('service_from');
				$aSecondFields	= array('service_until');
				$aParts			= array(WDDate::DB_DATE);

				break;
			}
			case 5: // Anfrage
			{
				$aAliases		= array('ts_e');
				$aFields		= array('created');

				break;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sWhere .= " AND
			(
		";
			$aIFs = array();

			foreach($this->_aFilterDates as $iKey => $aDate)
			{
				foreach($aAliases as $i => $m)
				{
					unset($m);

					$aIFs[$iKey][$i] = "";

					$aIFs[$iKey][$i] .=  "
						(
					";

					if(empty($aSecondFields[$i]))
					{
						$aIFs[$iKey][$i] .= "
							`" . $aAliases[$i] . "`.`" . $aFields[$i] . "` >= :date_field_" . count($aSQL) . " AND ";
						$aSQL['date_field_' . count($aSQL)] = $aDate['from']->get($aParts[$i]);

						$aIFs[$iKey][$i] .= "
							`" . $aAliases[$i] . "`.`" . $aFields[$i] . "` <= :date_field_" . count($aSQL);
						$aSQL['date_field_' . count($aSQL)] = $aDate['till']->get($aParts[$i]);
					}
					else
					{
						$aIFs[$iKey][$i] .= "
							`" . $aAliases[$i] . "`.`" . $aFields[$i] . "` <= :date_field_" . count($aSQL) . " AND ";
						$aSQL['date_field_' . count($aSQL)] = $aDate['till']->get($aParts[$i]);

						$aIFs[$iKey][$i] .= "
							`" . $aAliases[$i] . "`.`" . $aSecondFields[$i] . "` >= :date_field_" . count($aSQL);
						$aSQL['date_field_' . count($aSQL)] = $aDate['from']->get($aParts[$i]);
					}

					$aIFs[$iKey][$i] .=  "
						)
					";
				}

				$aIFs[$iKey] = implode(' OR ', $aIFs[$iKey]);
			}

			$sWhere .= implode(' OR ', $aIFs);

		$sWhere .= "
			)
		";

		return $sWhere;
	}

	/**
	 * Create filter data for results request
	 *
	 * @param Ext_Thebing_Management_Statistic|stdClass $oStatistic
	 * @return array
	 */
	public static function createFilterData($oStatistic) {
		$aFilter = array();

		$aFilter['schools'] = $oStatistic->schools;
		$aFilter['agency'] = $oStatistic->agency;
		$aFilter['group_by'] = $oStatistic->group_by;
		$aFilter['direct_customer'] = $oStatistic->direct_customer;
		$aFilter['nationalities'] = $oStatistic->nationalities;
		$aFilter['currency_id'] = $oStatistic->currency_id;
		$aFilter['customer_invoice_filter'] = $oStatistic->customer_invoice_filter;
		$aFilter['inquiry_group_filter'] = $oStatistic->inquiry_group_filter;

		switch($aFilter['group_by']) {
			case 1:
				$aFilter['agencies'] = $oStatistic->agencies;
				break;
			case 2:
				$aFilter['agency_groups'] = $oStatistic->agency_groups;
				break;
			case 3:
				$aFilter['agency_categories'] = $oStatistic->agency_categories;
				break;
			case 4:
				$aFilter['agency_countries'] = $oStatistic->agency_countries;
				break;
		}

		return $aFilter;
	}

	/**
	 * Hier wird festgelegt, welche Query-Parts für welche Spalte benötigt werden
	 * @param int $iColumnId
	 */
	protected function _setNeededParts($iColumnId) {

		$aAllocation = array(
			1 => array(
				'accommodation' => 1,
				'address' => 1
			),
			2 => array(
				'accommodation' => 1,
				'document' => 1
			),
			3 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'address' => 1,
			),
			4 => array(
				'address' => 1,
				'accommodation' => 1,
				'accommodation_allocation' => 1,
			),
			5 => array(
				'address' => 1
			),
			6 => array(
				'address' => 1
			),
			7 => array(
				'address' => 1
			),
			8 => array(
				'address' => 1
			),
			9 => array(
				'address' => 1
			),
			10 => array(
				'address' => 1
			),
			11 => array(
				'address' => 1
			),
			12 => array(
				'address' => 1,
			),
			13 => array(
				'address' => 1,
			),
			14 => array(
				'address' => 1
			),
			15 => array(
				'address' => 1
			),
			16 => array(
				'address' => 1
			),
			17 => array(
				'agency' => 1,
				'address' => 1,
				'document' => 1,
				'tuition' => 1
			),
			19 => array(
				'hearaboutus' => 1
			),
			21 => array(
				'address' => 1,
				'tuition' => 1
			),
			25 => array(
				'agency' => 1
			),
			27 => array(
				'address' => 1
			),
			28 => array(
				'address' => 1
			),
			29 => array(
				'address' => 1
			),
			30 => array(
				'address' => 1
			),
			31 => array(
				'address' => 1
			),
			36 => array(
				'accommodation' => 1,
				'document' => 1
			),
			37 => array(
				'address' => 1,
				'document' => 1,
				'tuition' => 1
			),
			38 => array(
				'tuition' => 1
			),
			39 => array(
				'accommodation'=>1,
				'accommodation_allocation'=>1
			),
			40 => array(
				'accommodation' => 1,
				'document' => 1
			),
			41 => array(
				'document' => 1
			),
			42 => array(
				'document' => 1,
				'tuition' => 1
			),
			43 => array(
				'document' => 1
			),
			44 => array(
				'document' => 1
			),
			45 => array(
				'document' => 1
			),
			46 => array(
				'tuition' => 1,
				'document' => 1
			),
			47 => array(
				'address' => 1,
				'document' => 1
			),
			48 => array(
				'tuition' => 1
			),
			49 => array(
				'tuition' => 1
			),
			50 => array(
				'document' => 1,
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			51 => array(
				'accommodation'	=> 1
			),
			52 => array(
				'document' => 1
			),
			53 => array(
				'document' => 1
			),
			54 => array(
				'document' => 1
			),
			55 => array(
				'document' => 1
			),
			56 => array(
				'document' => 1
			),
			57 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'document' => 1,
				'transfer' => 1,
				'tuition' => 1
			),
			58 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'document' => 1,
				'transfer' => 1,
				'tuition' => 1
			),
			60 => array(
				'document' => 1
			),
			61 => array(
				'document' => 1
			),
			62 => array(
				'document' => 1
			),
			63 => array(
				'address' => 1,
				'document' => 1
			),
			64 => array(
				'document' => 1
			),
			66 => array(
				'document' => 1
			),
			67 => array(
				'document' => 1
			),
			68 => array(
				'address' => 1,
				'tuition' => 1
			),
			69 => array(
				'tuition' => 1,
			),
			70 => array(
				'address' => 1,
				'tuition' => 1
			),
			71 => array(
				'accommodation' => 1
			),
			72 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			73 => array(
				'transfer' => 1
			),
			74 => array(
				'transfer' => 1
			),
			75 => array(
				'transfer' => 1
			),
			76 => array(
				'transfer' => 1
			),
			77 => array(
				'transfer' => 1
			),
			78 => array(
				'tuition' => 1
			),
			79 => array(
				'tuition' => 1
			),
			80 => array(
				'accommodation'	=> 1,
				'accommodation_allocation' => 1
			),
			81 => array(
				'accommodation' => 1
			),
			82 => array(
				'tuition' => 1
			),
			83 => array(
				'tuition' => 1
			),
			84 => array(
				'address' => 1,
				'tuition' => 1
			),
			86 => array(
				'address' => 1,
				'tuition' => 1
			),
			87 => array(
				'address' => 1,
				'accommodation' => 1
			),
			88 => array(
				'insurance' => 1
			),
			89 => array(
				'insurance' => 1
			),
			90 => array(
				'document' => 1
			),
			91 => array(
				'document' => 1,
				'insurance' => 1
			),
			92 => array(
				'tuition' => 1
			),
			93 => array(
				'tuition' => 1
			),
			94 => array(
				'tuition' => 1
			),
			95 => array(
				'tuition' => 1,
				'document' => 1,
			),
			96 => array(
				'tuition' => 1,
				'document' => 1,
			),
			97 => array(
				'tuition' => 1,
				'document' => 1,
			),
			98 => array(
				'tuition' => 1,
				'document' => 1,
			),
			100 => array(
				'accommodation' => 1,
				'document' => 1
			),
			101 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'document' => 1
			),
			102 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'document' => 1
			),
			103 => array(
				'document' => 1,
				'transfer' => 1
			),
			104 => array(
				'document' => 1,
				'transfer' => 1
			),
			105 => array(
				'document' => 1,
				'transfer' => 1
			),
			106 => array(
				'document' => 1,
				'transfer' => 1
			),
			107 => array(
				'document' => 1,
				'transfer' => 1
			),
			108 => array(
				'document' => 1,
				'transfer' => 1
			),
			109 => array(
				'document' => 1,
				'transfer' => 1
			),
			113 => array(
				'tuition' => 1
			),
			114 => array(
				'tuition' => 1
			),
			115 => array(
				'tuition' => 1
			),
			116 => array(
				'accommodation' => 1
			),
			118 => array(
				'accommodation' => 1
			),
			119 => array(
				'transfer' => 1
			),
			120 => array(
				'transfer' => 1
			),
			121 => array(
				'transfer' => 1
			),
			122 => array(
				'transfer' => 1
			),
			123 => array(
				'transfer' => 1
			),
			124 => array(
				'transfer' => 1
			),
			125 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			126 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			128 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			129 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			130 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			131 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			132 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			135 => array(
				'accommodation' => 1,
				'accommodation_allocation' => 1
			),
			137 => array(
				'accommodation' => 1,
				'document' => 1,
				'tuition' => 1
			),
			138 => array(
				'tuition' => 1
			),
			139 => array(
				'tuition' => 1
			),
			140 => array(
				'address' => 1,
				'document' => 1,
				'tuition' => 1
			),
			141 => array(
				'document' => 1,
				'accommodation' => 1
			),
			142 => array(
				'document' => 1
			),
			144 => array(
				'accommodation' => 1,
				'document' => 1,
				'transfer' => 1,
				'tuition' => 1
			),
			145 => array(
				'document' => 1,
				'tuition' => 1
			),
			146 => array(
				'document' => 1,
				'tuition' => 1
			),
			147 => array(
				'tuition'=>1
			),
			148 => array(
				'tuition'=>1
			),
			149 => array(
				'tuition'=>1
			),
			150 => array(
				'tuition'=>1
			),
			151 => array(
				'tuition'=>1
			),
			152 => array(
				'tuition'=>1
			),
			154 => array(
				'address' => 1,
				'accommodation' => 1,
				'accommodation_allocation' => 1,
				'document' => 1
			),
			155 => array(
				'accommodation'=>1,
				'accommodation_allocation' => 1,
				'document' => 1
			),
			156 => array(
				'tuition' => 1,
				'address' => 1
			),
			157 => array(
				'accommodation' => 1,
				'address' => 1
			),
			158 => array(
				'agency' => 1
			),
			159 => array(
				'address' => 1,
				'tuition' => 1
			),
			160 => array(
				'address' => 1,
				'tuition' => 1
			),
			161 => array(
				'accommodation' => 1,
				'document' => 1,
				'tuition' => 1,
				'inbox' => 1
			),
			162 => array(
				'tuition' => 1
			),
			163 => array(
				'tuition' => 1
			),
			164 => array(
				'tuition' => 1
			),
			165 => array(
				'tuition' => 1
			),
			166 => array(
				'tuition' => 1
			),
			167 => array(
				'tuition' => 1
			),
			168 => array(
				'tuition' => 1
			),
			170 => array(
				'document' => 1,
				'tuition' => 1
			),
			171 => array(
				'document' => 1,
				'tuition' => 1
			),
			172 => array(
				'document' => 1
			),
			173 => array(
				'document' => 1
			),
			174 => array(
				'address' => 1
			),
			176 => array(
				'document' => 1
			),
			178 => array(
				'document' => 1,
				'accommodation' => 1
			),
			180 => array(
				'document' => 1
			),
			182 => array(
				'document' => 1
			),
			184 => array(
				'document' => 1
			),
			186 => array(
				'document' => 1
			),
			188 => array(
				'document' => 1
			),
			190 => array(
				'document' => 1
			),
			191 => array(
				'tuition' => 1,
				'tuition_progress' => 1
			),
			192 => array(
				'tuition' => 1,
				'tuition_progress' => 1,
			),
			194 => array(
				'tuition' => 1
			),
			195 => array(
				'tuition' => 1,
				'tuition_progress' => 1
			),
			204 => [
				'agency' => 1,
				'inbox' => 1
			],
			205 => [
				'tuition' => 1
			],
			206 => [
				'tuition' => 1
			],
			208 => [
				'inbox' => 1
			],
		);

		$this->_aNeededParts += (array)$aAllocation[$iColumnId];
		
		if(
			isset($this->_oStatistic->columns['max_by'][$iColumnId]) &&
			(
				$this->_oStatistic->columns['max_by'][$iColumnId] == 1 ||
				$this->_oStatistic->columns['max_by'][$iColumnId] == 2	
			)
		)
		{
			$this->_aNeededParts['document'] = 1;
		}
		
	}

	/**
	 * Create the SELECT statement for the simple query
	 * 
	 * Folgende Felder fehlen:
	 *  - 32:	// Grund der Stornierung
	 *  - 133:	// ø Bewertung gesamt (seit letztem Gespräch/Besuch)
	 *  - 134:	// Anzahl der Besuche
	 *  - 143:	// Stunden je Stundensatz
	 * 
	 * @param int $iColumnID
	 * @param array &$sSQL
	 * @param array &$aSQL
	 * @return string
	 */
	protected function _createSimpleSelect($iColumnID, &$sSQL, &$aSQL)
	{
		$aGroups = $this->_aQueryGroups;

		switch($iColumnID)
		{
			case 1: // Kundennummer
			{
				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`tc_contacts_numbers` AS `tc_cn` ON
								`tc_c`.`id` = `tc_cn`.`contact_id`
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(DISTINCT `x`.`result` SEPARATOR '{_}') AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 2: // Rechnungsnummer
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(DISTINCT `x`.`result` SEPARATOR '{_}') AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				$aGroups["`kid`.`id`"] = "`kid`.`id`";

				break;
			}
			case 21: // Agenturen
			case 23: // Agenturkategorien
			case 25: // Agenturgruppen
			case 158: // Agenturland
			
			case 3: // Vorname
			case 4: // Nachname
			case 5: // Alter
			case 14: // Geschlecht
			case 15: // Land
			case 16: // Muttersprache
			case 17: // Nationalität
			case 18: // Status des Schülers
			case 19: // Wie sind Sie auf uns aufmerksam geworden
			case 20: // Währung
			case 33: // Anfrage (Y/N)
			case 92: // Name, Vorname (Lehrer)
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(DISTINCT `x`.`result` SEPARATOR '{_}') AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 36: // Umsätze (inkl. Storno) gesamt
			case 41: // Umsätze je generelle Kosten
			case 42: // Umsätze je kursbezogene Kosten
			case 54: // Zahlungseingänge (Summe)
			case 55: // Zahlungseingänge (tatsächlig, einzeln)
			case 56: // Umsätze je unterkunftsbezogene Kosten
			case 60: // Zahlungsmethode
			case 61: // Zahlung je Rechnungsposition
			case 62: // Zahlungskommentar
			case 63: // Provision gesamt
			case 67: // Summe je angelegtem Steuersatz
			case 90: // Versicherungsumsatz
			case 140: // Kursumsatz
			case 141: // Unterkunftumsatz
			case 142: // Stornierungsumsatz
			case 154: // Umsatz je Unterkunftsanbieter
			case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
			case 170: // Kursumsatz (brutto)
			case 171: // Kursumsatz (netto)
			case 172: // Umsätze gesamt (brutto, inkl. Storno)
			case 173: // Umsätze gesamt (netto, inkl. Storno)
			case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
			case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
			case 180: // Umsatz - Transfer (netto, exkl. Steuern)
			case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
			case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
			case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
			case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
			case 190: // Totale Steuern (netto)
			{
				$aGroups["`kidvi`.`id`"] = "`kidvi`.`id`";

				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 41: // Umsätze je generelle Kosten
					case 42: // Umsätze je kursbezogene Kosten
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 90: // Versicherungsumsatz
					case 140: // Kursumsatz
					case 141: // Unterkunftumsatz
					case 142: // Stornierungsumsatz
					case 154: // Umsatz je Unterkunftsanbieter
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					case 170: // Kursumsatz (brutto)
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
						$sSQL = str_replace(
							'{DOCUMENTS_JOIN_ADDON}',
							" AND `kid`.`type` IN(:aDocumentTypes) {DOCUMENTS_JOIN_ADDON} ",
							$sSQL
						);
						break;
					case 171: // Kursumsatz (netto)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					case 190: // Totale Steuern (netto)
					default:
						$sSQL = str_replace(
							'{DOCUMENTS_JOIN_ADDON}',
							" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
							$sSQL
						);
				}

				if($iColumnID == 142)
				{
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_i`.`canceled` > 0 {WHERE} ",
						$sSQL
					);
				}

				else if(!in_array($iColumnID, array(36, 63, 172, 173, 176)))
				{
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_i`.`canceled` = 0 {WHERE} ",
						$sSQL
					);
				}

				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 190: // Totale Steuern (netto)
						break;
					case 41: // Umsätze je generelle Kosten
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_general' ";
						if($iColumnID == 41) {
							$aGroups["`key`"] = "`key`";
						}
						break;
					case 42: // Umsätze je kursbezogene Kosten
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_course' ";
						if($iColumnID == 42) {
							$aGroups["`key`"] = "`key`";
						}
						break;
					case 54: // Zahlungseingänge (Summe)
					case 55: // Zahlungseingänge (tatsächlig, einzeln)
					case 60: // Zahlungsmethode
					case 61: // Zahlung je Rechnungsposition
					case 62: // Zahlungskommentar

						$sSQL = str_replace(
							'{ITEMS_JOIN_ADDON}',
							"
								INNER JOIN
									`kolumbus_inquiries_payments_items` AS `kipi` ON
										`kidvi`.`id` = `kipi`.`item_id` AND
										`kipi`.`active` = 1
								INNER JOIN
									`kolumbus_inquiries_payments` AS `kip` ON
										`kipi`.`payment_id` = `kip`.`id` AND
										`kip`.`active` = 1
								{ITEMS_JOIN_ADDON}
							",
							$sSQL
						);
						$aGroups["`kipi`.`id`"] = "`kipi`.`id`";
						break;
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_accommodation' ";
						if($iColumnID == 56) {
							$aGroups["`key`"] = "`key`";
						}
						break;
					case 63: // Provision gesamt
						$sType = " AND `ts_i`.`agency_id` > 0 ";
						break;
					case 67: // Summe je angelegtem Steuersatz
						$sType = " AND `kidvi`.`tax_category` > 0 ";
						$aGroups["`key`"] = "`key`";
						break;
					case 90: // Versicherungsumsatz
						$sType = " AND `kidvi`.`type` = 'insurance' ";
						break;
					case 140: // Kursumsatz
					case 170: // Kursumsatz (brutto)
					case 171: // Kursumsatz (netto)

						// Nur aktive Kursleistungen! R-#5070
						$sSQL = str_replace(
							'{WHERE}',
							" AND `ts_ijc`.`visible` = 1 ",
							$sSQL
						);

						$sType = " AND `kidvi`.`type` = 'course' ";
						break;
					case 141: // Unterkunftumsatz
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";

						$sSQL = str_replace(
							'{WHERE}',
							" AND `ts_ija`.`visible` = 1 ",
							$sSQL
						);

						break;
					case 142: // Stornierungsumsatz
						$sType = " AND `kidvi`.`parent_type` = 'cancellation' ";
						break;
					case 154: // Umsatz je Unterkunftsanbieter
						$sType = " AND `kidvi`.`type` IN('accommodation', 'additional_accommodation', 'extra_nights', 'extra_weeks') ";
						$aGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";
						break;
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						$sType = " AND `kidvi`.`type` IN('accommodation', 'additional_accommodation', 'extra_nights', 'extra_weeks') ";
						$aGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";
						$aGroups["`kaa`.`room_id`"] = "`kaa`.`room_id`";
						break;
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'transfer' ";
						break;
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'extraPosition' ";
						break;
					default:
						$sType = "";
						break;
				}

				$sSQL = str_replace(
					'{ITEMS_JOIN_ADDON}',
					$sType . " {ITEMS_JOIN_ADDON} ",
					$sSQL
				);

				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 54: // Zahlungseingänge (Summe)
					case 63: // Provision gesamt
					case 90: // Versicherungsumsatz
					case 140: // Kursumsatz
					case 170: // Kursumsatz (brutto)
					case 171: // Kursumsatz (netto)
					case 141: // Unterkunftumsatz
					case 142: // Stornierungsumsatz
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					case 190: // Totale Steuern (netto)
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`
						";

						break;
					}
					case 41: // Umsätze je generelle Kosten
					case 42: // Umsätze je kursbezogene Kosten
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 67: // Summe je angelegtem Steuersatz
					case 154: // Umsatz je Unterkunftsanbieter
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`key`
						";

						break;
					}
					case 60: // Zahlungsmethode
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`result`
						";

						break;
					}
					case 61: // Zahlung je Rechnungsposition
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								SUM(`y`.`result`) AS `result`
							FROM
							(
								SELECT
									`x`.*
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`unique`
							) AS `y`
							GROUP BY
								`y`.`unique_row_key`,
								`y`.`query_group_result`
						";

						break;
					}
					case 62: // Zahlungskommentar
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*
							FROM
							(
								{USAGE}
							) AS `x`
							WHERE
								`x`.`result` IS NOT NULL
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`result`
						";

						break;
					}
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`sub_key`,
								`x`.`key`
						";

						break;
					}
				}

				break;
			}
			case 57: // Zahlungsausgänge (Summe)
			case 58: // Zahlungsausgänge (tatsächlig, einzeln)
			{
				$aGroups["`kidvi`.`id`"] = "`kidvi`.`id`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`kolumbus_transfers_payments` AS `kTRp` ON
								`ts_ijt`.`id` = `kTRp`.`inquiry_transfer_id` AND
								`kTRp`.`active` = 1 AND
								`kTRp`.`date` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accommodations_payments` AS `kACp` ON
								`ts_ija`.`id` = `kACp`.`inquiry_accommodation_id` AND
								`kACp`.`active` = 1 AND
								`kACp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accounting_manual_transactions` AS `kINp` ON
								`ts_ij`.`school_id` = `kINp`.`school_id` AND
								`kINp`.`active` = 1 AND
								`kINp`.`type` = 'outcome' AND
								`kINp`.`date` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`,
						`x`.`query_kTRp_group`,
						`x`.`query_kTEp_group`,
						`x`.`query_kACp_group`,
						`x`.`query_kINp_group`
				";

				break;
			}
			case 68: // Kurswochen je Kurs
			case 69: // Kurswochen je Kurskategorie
			case 70: // Kurswochen gesamt
			{
				$aGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";

				$sSQL = str_replace('{WHERE}', " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				switch($iColumnID)
				{
					case 68: // Kurswochen je Kurs
					case 69: // Kurswochen je Kurskategorie
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`key`
						";

						break;
					}
					case 70: // Kurswochen gesamt
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`
						";

						break;
					}
				}

				break;
			}
			case 71: // Unterkunftswochen je Unterkunftskategorie
			case 72: // Unterkunftswochen je Unterkunft
			{
				$aGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";

				$sSQL = str_replace('{WHERE}', " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`,
						`x`.`key`
				";

				break;
			}
			case 93: // Geleistete Stunden gesamt
			{
				$aGroups["`key`"] = "`key`";

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 94: // Geleistete Stunden je Niveau
			{
				$aGroups["`unique`"] = "`unique`";
				$aGroups["`key`"] = "`key`";

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						SELECT
							`y`.*
						FROM
						(
							{USAGE}
						) AS `y`
						GROUP BY
							`y`.`unique`
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`,
						`x`.`key`
				";

				break;
			}
			case 126: // Aufgenommene Schüler gesamt
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(DISTINCT `x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 88: // Versicherung
			case 125: // Name des Anbieters
			case 137: // Schule
			case 193: // Gruppen
			case 208: // Inbox
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(DISTINCT `x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 144: // Verdienst gesamt
			{
				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{DOCUMENTS_JOIN_ADDON}',
					" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
					$sSQL
				);

				switch($iColumnID)
				{
					case 145: // Verdienst je Kurskategorie
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					case 146: // Verdienst je Kurs
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					default:
						$sType = "";
				}

				$sSQL = str_replace('{ITEMS_JOIN_ADDON}', $sType . " {ITEMS_JOIN_ADDON} ", $sSQL);

				$aGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$aGroups["`query_kTRp_group`"] = "`query_kTRp_group`";
				$aGroups["`query_kTEp_group`"] = "`query_kTEp_group`";
				$aGroups["`query_kACp_group`"] = "`query_kACp_group`";
				$aGroups["`query_kINp_group`"] = "`query_kINp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`kolumbus_transfers_payments` AS `kTRp` ON
								`ts_ijt`.`id` = `kTRp`.`inquiry_transfer_id` AND
								`kTRp`.`active` = 1 AND
								`kTRp`.`date` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accommodations_payments` AS `kACp` ON
								`ts_ija`.`id` = `kACp`.`inquiry_accommodation_id` AND
								`kACp`.`active` = 1 AND
								`kACp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accounting_manual_transactions` AS `kINp` ON
								`ts_ij`.`school_id` = `kINp`.`school_id` AND
								`kINp`.`active` = 1 AND
								`kINp`.`type` = 'outcome' AND
								`kINp`.`date` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`result` != 0, CONCAT(`x`.`unique`, '_', `x`.`result`), NULL)
						) AS `result`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`transfer_pay` != 0, CONCAT(`x`.`query_kTRp_group`, '_', `x`.`transfer_pay`), NULL)
						) AS `transfer_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`teacher_pay` != 0, CONCAT(`x`.`query_kTEp_group`, '_', `x`.`teacher_pay`), NULL)
						) AS `teacher_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`acc_pay` != 0, CONCAT(`x`.`query_kACp_group`, '_', `x`.`acc_pay`), NULL)
						) AS `acc_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`individual_pay` != 0, CONCAT(`x`.`query_kINp_group`, '_', `x`.`individual_pay`), NULL)
						) AS `individual_pay`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 145: // Verdienst je Kurskategorie
			case 146: // Verdienst je Kurs
			{
				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{DOCUMENTS_JOIN_ADDON}',
					" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
					$sSQL
				);

				switch($iColumnID)
				{
					case 145: // Verdienst je Kurskategorie
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					case 146: // Verdienst je Kurs
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					default:
						$sType = "";
				}

				$sSQL = str_replace('{ITEMS_JOIN_ADDON}', $sType . " {ITEMS_JOIN_ADDON} ", $sSQL);

				$aGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$aGroups["`query_kTEp_group`"] = "`query_kTEp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`result` != 0, CONCAT(`x`.`unique`, '_', `x`.`result`), NULL)
						) AS `result`,
						GROUP_CONCAT(
							DISTINCT CONCAT(`x`.`query_kTEp_group`, '_', `x`.`teacher_pay`)
						) AS `teacher_pay`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`unique_row_key`
				";

				break;
			}
			case 128: // Anzahl der Bewertungen
			case 129: // Niedrigste Bewertung (Note)
			case 130: // Höchste Bewertung (Note)
			case 131: // Häufigste Bewertung
			case 132: // ø Bewertung
			case 135: // Bewertungen Details (Unterkunft)
			case 147: // Anzahl der Bewertungen
			case 148: // Niedrigste Bewertungen
			case 149: // Höchste Bewertungen
			case 150: // Häufigste Bewertungen
			case 151: // ø Bewertung gesamt
			case 152: // Bewertungen Details (Lehrer)
			{
				// Hier wurde zuvor IMMER nur ts_ijc benutzt…
				$sAlias = 'ts_ijc';
				if(
					$this->_oStatistic->start_with == 5 ||
					$this->_oStatistic->start_with == 6
				) {
					// Bei »Ausgehend von« Unterkunftsanbieter/-kategorie
					$sAlias = 'ts_ija';
				}

				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`kolumbus_feedback_customer` AS `kfc` ON
								`kfc`.`inquiry_id` = `ts_i`.`id` AND
								`kfc`.`active` = 1 AND
								`" . $sAlias . "`.`id` IS NOT NULL
						INNER JOIN
							`kolumbus_feedback_customer_answer` AS `kfca` ON
								`kfc`.`id` = `kfca`.`customer_feedback_id` AND
								`kfca`.`active` = 1
						INNER JOIN
							`kolumbus_feedback_question` AS `kfq` ON
								`kfca`.`question_id` = `kfq`.`id` AND
								`kfq`.`active` = 1 AND
								`kfq`.`type` = 'teacher' AND
								`kfq`.`answer_type` = 'answers'
						INNER JOIN
							`ts_teachers` AS `kfca_kt` ON
								`kfca`.`parent_id` = `kfca_kt`.`id` AND
								`kfca_kt`.`active` = 1
						LEFT JOIN
							`kolumbus_feedback_answer` AS `kfa` ON
								`kfca`.`answer` = `kfa`.`id` AND
								`kfa`.`active` = 1
						LEFT JOIN
							`kolumbus_feedback_note` AS `kfn` ON
								`kfa`.`note_id` = `kfn`.`id` AND
								`kfn`.`active` = 1
						{JOINS}
					",
					$sSQL
				);

				switch($iColumnID)
				{
					case 147: // Anzahl der Bewertungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								COUNT(DISTINCT `x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`
						";

						break;
					}
					case 148: // Niedrigste Bewertungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								MIN(`x`.`result`) AS `result`
							FROM
							(
								SELECT
									`y`.*
								FROM
								(
									{USAGE}
								) AS `y`
								GROUP BY
									`y`.`unique_row_key`,
									`y`.`key`
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`
						";

						break;
					}
					case 149: // Höchste Bewertungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								MAX(`x`.`result`) AS `result`
							FROM
							(
								SELECT
									`y`.*
								FROM
								(
									{USAGE}
								) AS `y`
								GROUP BY
									`y`.`unique_row_key`,
									`y`.`key`
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`
						";

						break;
					}
					case 150: // Häufigste Bewertungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								COUNT(*) AS `result`
							FROM
							(
								SELECT
									`x`.*
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`unique_row_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`unique_row_key`,
								`y`.`result`
							ORDER BY
								`result` DESC
						";

						break;
					}
					case 151: // ø Bewertung gesamt
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								AVG(`y`.`result`) AS `result`
							FROM
							(
								SELECT
									`x`.*
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`unique_row_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`unique_row_key`
						";

						break;
					}
					case 152: // Bewertungen Details (Lehrer)
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`unique_row_key`,
								`x`.`key`
						";

						break;
					}
				}

				break;
			}
		}

		/* ==================================================================================================== */

		switch($iColumnID)
		{
			case 1: // Kundennummer
			{
				$sSelect = "CONCAT(`tc_cn`.`contact_id`, '{::}', `tc_cn`.`number`)";

				break;
			}
			case 2: // Rechnungsnummer
			{
				$oDocSearch = new Ext_Thebing_Inquiry_Document_Type_Search();
				$aInvoiceTypes = $oDocSearch->getSectionTypes('invoice_without_proforma');

				$sSelect = "
					CONCAT(`ts_i`.`id`, '{::}',
						IF(
							`kid`.`type` IN (
								'".join("', '", $aInvoiceTypes)."'
							),
							`kid`.`document_number`,
							NULL
						)
					)
				";

				break;
			}
			case 3: // Vorname
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', `tc_c`.`firstname`)";

				break;
			}
			case 4: // Nachname
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', `tc_c`.`lastname`)";

				break;
			}
			case 5: // Alter
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', getAge(`tc_c`.`birthday`))";

				break;
			}
			case 14: // Geschlecht
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', `tc_c`.`gender`)";

				break;
			}
			case 15: // Land
			{
				$sSelect = "CONCAT(`tc_a`.`id`, '{::}', `tc_a`.`country_iso`)";

				break;
			}
			case 16: // Muttersprache
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', `tc_c`.`language`)";

				break;
			}
			case 17: // Nationalität
			{
				$sSelect = "CONCAT(`tc_c`.`id`, '{::}', `tc_c`.`nationality`)";

				break;
			}
			case 18: // Status des Schülers
			{
				$sSelect = "CONCAT(`ts_i`.`id`, '{::}', `ts_i`.`status_id`)";

				break;
			}
			case 19: // Wie sind Sie auf uns aufmerksam geworden
			{
				$sSelect = "CONCAT(`ts_i`.`id`, '{::}', `ts_i`.`referer_id`)";

				break;
			}
			case 20: // Währung
			{
				$sSelect = "CONCAT(`ts_i`.`id`, '{::}', `ts_i`.`currency_id`)";

				break;
			}
			case 21: // Agenturen
			{
				$sSelect = "CONCAT(`ka`.`id`, '{::}', `ka`.`id`)";

				break;
			}
			case 23: // Agenturkategorien
			{
				$sSelect = "CONCAT(`ka`.`id`, '{::}', `ka`.`ext_39`)";

				break;
			}
			case 25: // Agenturgruppen
			{
				$sSelect = "CONCAT(`ka`.`id`, '{::}', `kaga`.`group_id`)";

				break;
			}
			case 33: // Anfrage (Y/N)
			{
				$sSelect = "CONCAT(`ts_e`.`id`, '{::}', `ts_e`.`id`)";

				break;
			}
			case 36: // Umsätze (inkl. Storno) gesamt
			case 41: // Umsätze je generelle Kosten
			case 42: // Umsätze je kursbezogene Kosten
			case 56: // Umsätze je unterkunftsbezogene Kosten
			case 67: // Summe je angelegtem Steuersatz
			case 90: // Versicherungsumsatz
			case 140: // Kursumsatz
			case 141: // Unterkunftumsatz
			case 142: // Stornierungsumsatz
			case 154: // Umsatz je Unterkunftsanbieter
			case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
			case 170: // Kursumsatz (brutto)
			case 171: // Kursumsatz (netto)
			case 172: // Umsätze gesamt (brutto, inkl. Storno)
			case 173: // Umsätze gesamt (netto, inkl. Storno)
			case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
			case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
			case 180: // Umsatz - Transfer (netto, exkl. Steuern)
			case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
			case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
			case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
			case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
			case 190: // Totale Steuern (netto)
			{
				$sKey = '';

				// Beträge nach Leistungszeitraum splitten
				// @TODO Wenn das für noch mehr Felder gesetzt werden muss, dann Abfrage gleich entfernen?
				$bCalcSubAmountByDates = true;

				// Explizit Brutto-Betrag nutzen
				$bUseGrossColumn = false;

				// Wenn Ursprungsrechnung CN hat, dann Positionen der Ursprungsrechnung ignorieren
				$bCheckCreditnote = false;

				$iSumTaxOption = 0;

				switch($iColumnID)
				{
					case 41: // Umsätze je generelle Kosten
						$sKey = " `kidvi`.`type_id` ";
						$bCalcSubAmountByDates = false;
						break;
					case 42: // Umsätze je kursbezogene Kosten
						$sKey = " `kidvi`.`type_id` ";
						$bCalcSubAmountByDates = false;
						break;
					case 56: // Umsätze je unterkunftsbezogene Kosten
						$sKey = " `kidvi`.`type_id` ";
						$bCalcSubAmountByDates = false;
						break;
					case 67: // Summe je angelegtem Steuersatz
						$sKey = " `kidvi`.`tax_category` ";
						break;
					case 154: // Umsatz je Unterkunftsanbieter
						$sKey = " `kr`.`accommodation_id` ";
						break;
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						$sKey = " `kr`.`id` AS `sub_key`, `kr`.`accommodation_id` ";
						break;
					case 170: // Kursumsatz (brutto)
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
						$bUseGrossColumn = true;
						break;
					case 171: // Kursumsatz (netto)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						$bCheckCreditnote = true;
						break;
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 190: // Totale Steuern (netto)
						$bCheckCreditnote = true;
						$iSumTaxOption = 1;
						if($iColumnID == 190) {
							$iSumTaxOption = 2;
						}
						break;
				}

				if(!empty($sKey)) {
					$sKey .= " AS `key`, ";
				}

				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				) {
					$sSelect = $sKey . " (" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iSumTaxOption) . ")";
				} else {

					if($bCalcSubAmountByDates) {

						$sIndexFromAndUntil = "
							`kidvi`.`index_from` <= :TILL AND
							`kidvi`.`index_until` >= :FROM
						";

						$sSubAmountByDates = "
							getSubAmountByDates(
								(" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iSumTaxOption) . "),
								:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
							)
						";
					} else {

						$sIndexFromAndUntil = "
							`kidvi`.`index_from` BETWEEN :FROM AND :TILL
						";

						$sSubAmountByDates = "
							(" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iSumTaxOption) . ")
						";
					}

					$this->_aNeededParts['transfer'] = 1;

					// @TODO Ist das generelle Splitten vom Transfer hier eventuell falsch? Dürfte nur bei Leistungszeitraum passieren
					$sSelect = "
						" . $sKey . "
						(
							
							IF(
								(
									$sIndexFromAndUntil
								),
								(
									IF(
										(
											`kidvi`.`type` = 'transfer'
										),
										(
											(
												" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, 0, false) . "
											) /
											IF(
												(
													`kidvi`.`type_id` = `ts_ijt`.`id`
												),
												1,
												(
													IF(
														(
															`kidvi`.`index_from` BETWEEN :FROM AND :TILL AND
															`kidvi`.`index_until` BETWEEN :FROM AND :TILL
														),
														1,
														2
													)
												)
											) *
											IF(
												/* Transferpaket: Geht der Transfer mehr als 2 Monate/Zeiträume, darf dazwischen kein Wert auftauchen */
												`kidvi`.`type_id` = 0 AND
												`kidvi`.`index_from` NOT BETWEEN :FROM AND :TILL AND
												`kidvi`.`index_until` NOT BETWEEN :FROM AND :TILL,
												0,
												1
											)
										),
										0
									) +
									IF(
										(
											`kidvi`.`type` != 'transfer' AND
											`kidvi`.`type` != 'special'
										),
										(
											$sSubAmountByDates
										),
										0
									)
								),
								0
							)
						)
					";
				}

				break;
			}
			case 54: // Zahlungseingänge (Summe)
			{
				$sSelect = "
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry`,
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					)
				";

				break;
			}
			case 55: // Zahlungseingänge (tatsächlig, einzeln)
			{
				$sSelect = "
					`kipi`.`id` AS `unique`,
					`kip`.`date` AS `name`,
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry`,
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						 " . $this->_iCurrencyID . ",
						 `kip`.`date`
					)
				";

				break;
			}
			case 57: // Zahlungsausgänge (Summe)
			case 58: // Zahlungsausgänge (tatsächlig, einzeln)
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					`kTRp`.`date` AS `query_kTRp_date`,
					`kTRp`.`id` AS `query_kTRp_group`,
					calcAmountByCurrencyFactors(
						`kTRp`.`amount`,
						`kTRp`.`payment_currency_id`,
						`kTRp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTRp`.`date`
					) AS `transfer_pay`,
					`kTEp`.`timepoint` AS `query_kTEp_date`,
					`kTEp`.`id` AS `query_kTEp_group`,
					calcAmountByCurrencyFactors(
						`kTEp`.`amount`,
						`kTEp`.`payment_currency_id`,
						`kTEp`.`timepoint`,
						" . $this->_iCurrencyID . ",
						`kTEp`.`timepoint`
					) AS `teacher_pay`,
					`kACp`.`timepoint` AS `query_kACp_date`,
					`kACp`.`id` AS `query_kACp_group`,
					calcAmountByCurrencyFactors(
						`kACp`.`amount`,
						`kACp`.`payment_currency_id`,
						`kACp`.`timepoint`,
						" . $this->_iCurrencyID . ",
						`kACp`.`timepoint`
					) AS `acc_pay`,
					`kINp`.`date` AS `query_kINp_date`,
					`kINp`.`id` AS `query_kINp_group`,
					calcAmountByCurrencyFactors(
						`kINp`.`amount_school`,
						`kINp`.`currency_school`,
						`kINp`.`date`,
						" . $this->_iCurrencyID . ",
						`kINp`.`date`
					) AS `individual_pay`,
					1
				";

				break;
			}
			case 60: // Zahlungsmethode
			{
				$sSelect = "GROUP_CONCAT(DISTINCT `kip`.`method_id`, '{::}', `kip`.`method_id` SEPARATOR '{_}')";

				break;
			}
			case 61: // Zahlung je Rechnungsposition
			{
				$sSelect = "
					`kipi`.`id` AS `unique`,
					`kidvi`.`id` AS `query_group_result`,
					`kidvi`.`description` AS `name`,
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry`,
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					)
				";

				break;
			}
			case 62: // Zahlungskommentar
			{
				$sSelect = "GROUP_CONCAT(DISTINCT `kip`.`id`, '{::}', IF(`kip`.`comment` = '', NULL, `kip`.`comment`) SEPARATOR '{_}')";

				break;
			}
			case 63: // Provision gesamt
			{
				$sAmountPart = "
					IF(
						`kidv`.`tax` = 1,
						(`kidvi`.`amount_provision` - (`kidvi`.`amount_provision` / 100 * `kidvi`.`amount_discount`)) / (`kidvi`.`tax` / 100 + 1),
						`kidvi`.`amount_provision` - `kidvi`.`amount_provision` / 100 * `kidvi`.`amount_discount`
					)
				";

				$sSelect = "
					`ts_i`.`id` AS `key`,
				";

				if($this->_oStatistic->period == 3) {
					$sSelect .= "
						IF(
							`kidvi`.`index_from` <= :TILL AND
							`kidvi`.`index_until` >= :FROM,
					";

					$sAmountPart = "
						getSubAmountByDates(
							$sAmountPart,
							:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
						)
					";
				}

				$sSelect .= "
						IF(
							(
								`kid`.`type` IN(
									'proforma_brutto',
									'proforma_netto',
									'group_proforma',
									'group_proforma_netto'
								) AND
								`ts_i`.`has_invoice` = 1
							),
							0,
							$sAmountPart
						)
				";

				if($this->_oStatistic->period == 3) {
					$sSelect .= "
						, 0
						)
					";
				}

				break;
			}
			case 68: // Kurswochen je Kurs
			{
				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = "`ktc`.`id` AS `key`, `ts_ijc`.`weeks`";
				}
				else
				{
					$sSelect = "`ktc`.`id` AS `key`, calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`)";
				}

				break;
			}
			case 69: // Kurswochen je Kurskategorie
			{
				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = "`ktc`.`category_id` AS `key`, `ts_ijc`.`weeks`";
				}
				else
				{
					$sSelect = "`ktc`.`category_id` AS `key`, calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`)";
				}

				break;
			}
			case 70: // Kurswochen gesamt
			{
				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = "`ts_ijc`.`weeks`";
				}
				else
				{
					$sSelect = "calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`)";
				}

				break;
			}
			case 71: // Unterkunftswochen je Unterkunftskategorie
			{
				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = "`ts_ija`.`accommodation_id` AS `key`, `ts_ija`.`weeks`";
				}
				else
				{
					$sSelect = "
						`ts_ija`.`accommodation_id` AS `key`,
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ija`.`from`, `ts_ija`.`until`)
					";
				}

				break;
			}
			case 72: // Unterkunftswochen je Unterkunft
			{
				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = "
						`kr`.`accommodation_id` AS `key`,
						`ts_ija`.`weeks`
					";
				}
				else
				{
					$sSelect = "
						`kr`.`accommodation_id` AS `key`,
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ija`.`from`, `ts_ija`.`until`)
					";
				}

				break;
			}
			case 88: // Versicherung
			{
				$sSelect = "`ts_iji`.`insurance_id`";

				break;
			}
			case 92: // Name, Vorname (Lehrer)
			{
				$sSelect = "CONCAT(`kt`.`id`, '{::}', `kt`.`lastname`, ', ', `kt`.`firstname`)";

				break;
			}
			case 93: // Geleistete Stunden gesamt
			{
				$sSelect = "
					`ktb`.`id` AS `key`,
					(
						(
							SELECT
								COUNT(*)
							FROM
								`kolumbus_tuition_blocks_days`
							WHERE
								`block_id` = `ktb`.`id` AND
								(
									`ktb`.`week` <= :TILL AND
									(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
								)
						) *
						(
							SELECT
								`lessons`
							FROM
								`kolumbus_tuition_templates`
							WHERE
								`id` = `ktb`.`template_id` AND
								(
									`ktb`.`week` <= :TILL AND
									(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
								)
						)
					)
				";
				break;
			}
			case 94: // Geleistete Stunden je Niveau
			{
				$sSelect = "
					`ktb`.`id` AS `unique`,
					`ktb`.`level_id` AS `key`,
					(
						(
							SELECT
								COUNT(*)
							FROM
								`kolumbus_tuition_blocks_days`
							WHERE
								`block_id` = `ktb`.`id` AND
								(
									`ktb`.`week` <= :TILL AND
									(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
								)
						) *
						(
							SELECT
								`lessons`
							FROM
								`kolumbus_tuition_templates`
							WHERE
								`id` = `ktb`.`template_id` AND
								(
									`ktb`.`week` <= :TILL AND
									(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
								)
						)
					)
				";

				break;
			}
			case 125: // Name des Anbieters
			{
				$sSelect = "`kr`.`accommodation_id`";

				break;
			}
			case 126: // Aufgenommene Schüler gesamt
			{
				$sSelect = "`ts_ija`.`id`";

				break;
			}
			case 137: // Schule
			{
				$sSelect = "`cdb2`.`id`";

				break;
			}
			/*case 144: // Verdienst gesamt
			case 145: // Verdienst je Kurskategorie
			case 146: // Verdienst je Kurs
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					`kidvi`.`id` AS `unique`,
					`kTRp`.`id` AS `query_kTRp_group`,
					calcAmountByCurrencyFactors(
						`kTRp`.`amount`,
						`kTRp`.`payment_currency_id`,
						`kTRp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTRp`.`date`
					) AS `transfer_pay`,
					`kTEp`.`id` AS `query_kTEp_group`,
					calcAmountByCurrencyFactors(
						`kTEp`.`amount`,
						`kTEp`.`payment_currency_id`,
						`kTEp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTEp`.`date`
					) AS `teacher_pay`,
					`kACp`.`id` AS `query_kACp_group`,
					calcAmountByCurrencyFactors(
						`kACp`.`amount`,
						`kACp`.`payment_currency_id`,
						`kACp`.`date`,
						" . $this->_iCurrencyID . ",
						`kACp`.`date`
					) AS `acc_pay`,
					`kINp`.`id` AS `query_kINp_group`,
					calcAmountByCurrencyFactors(
						`kINp`.`amount_school`,
						`kINp`.`currency_school`,
						`kINp`.`date`,
						" . $this->_iCurrencyID . ",
						`kINp`.`date`
					) AS `individual_pay`,
				";

				switch($iColumnID)
				{
					case 145: // Verdienst je Kurskategorie
					{
						$sKey = "`ktc`.`category_id` AS `key`, ";

						// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
						// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

						$sSelect = "
							`kidvi`.`id` AS `unique`,
							`kTEp`.`id` AS `query_kTEp_group`,
							calcAmountByCurrencyFactors(
								`kTEp`.`amount`,
								`kTEp`.`payment_currency_id`,
								`kTEp`.`date`,
								" . $this->_iCurrencyID . ",
								`kTEp`.`date`
							) AS `teacher_pay`,
						";

						break;
					}
					case 146: // Verdienst je Kurs
					{
						$sKey = "`ktc`.`id` AS `key`, ";

						// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
						// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

						$sSelect = "
							`kidvi`.`id` AS `unique`,
							`kTEp`.`id` AS `query_kTEp_group`,
							calcAmountByCurrencyFactors(
								`kTEp`.`amount`,
								`kTEp`.`payment_currency_id`,
								`kTEp`.`date`,
								" . $this->_iCurrencyID . ",
								`kTEp`.`date`
							) AS `teacher_pay`,
						";

						break;
					}
					default:
						$sKey = "";
				}

				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect .= $sKey . " (" . $this->_addDefaultSumPart() . ")";
				}
				else
				{
					$this->_aNeededParts['transfer'] = 1;
					
					$sSelect .= $sKey . "
						(
							
							IF(
								(
									`kidvi`.`index_from` BETWEEN :FROM AND :TILL OR
									`kidvi`.`index_until` BETWEEN :FROM AND :TILL
								),
								(
									IF(
										(
											`kidvi`.`type` = 'transfer'
										),
										(
											(
												" . $this->_addDefaultSumPart(false, false, 0, false) . "
											) /
											IF(
												(
													`kidvi`.`type_id` = `ts_ijt`.`id`
												),
												1,
												(
													IF(
														(
															`kidvi`.`index_from` BETWEEN :FROM AND :TILL AND
															`kidvi`.`index_until` BETWEEN :FROM AND :TILL
														),
														1,
														2
													)
												)
											)
										),
										0
									) +
									IF(
										(
											`kidvi`.`type` != 'transfer' AND
											`kidvi`.`type` != 'special'
										),
										(
											getSubAmountByDates(
												(" . $this->_addDefaultSumPart() . "),
												:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
											)
										),
										0
									)
								),
								0
							)
						)
					";
				}

				break;
			}*/
			case 128: // Anzahl der Bewertungen
			case 147: // Anzahl der Bewertungen
			{
				$sSelect = "`kfc`.`inquiry_id`";

				break;
			}
			case 129: // Niedrigste Bewertung (Note)
			case 148: // Niedrigste Bewertungen
			{
				$sSelect = "`kfn`.`id` AS `key`, MIN(`kfn`.`name`)";

				break;
			}
			case 130: // Höchste Bewertung (Note)
			case 149: // Höchste Bewertungen
			{
				$sSelect = "`kfn`.`id` AS `key`, MAX(`kfn`.`name`)";

				break;
			}
			case 131: // Häufigste Bewertung (Note, bei mehreren CVS)
			case 132: // ø Bewertung gesamt
			case 135: // Bewertungen Details (Unterkunft)
			case 150: // Häufigste Bewertungen
			case 151: // ø Bewertung gesamt
			case 152: // Bewertungen Details (Lehrer)
			{
				$sSelect = "
					`kfca`.`id` AS `key`,
					`kfn`.`name`
				";

				break;
			}
			case 158: // Agenturland
			{
				$sSelect = "CONCAT(`ka`.`id`, '{::}', `ka`.`ext_6`)";

				break;
			}
			case 193: // Gruppen
				$sSelect = "`ts_i`.`group_id`";
				break;
			case 203: // Vertriebsmitarbeiter
				$sSelect = "`ts_i`.`sales_person_id`";
				break;
			case 208: // Inbox
				$sSelect = "`ts_i`.`inbox`";
				break;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSelect .= " AS `result`, '" . $iColumnID . "' AS `column_id`";

		$sSQL = str_replace('{SELECT}', $sSelect . " {SELECT} ", $sSQL);

		$sSQL = str_replace('{GROUP_BY}', implode(',', $aGroups) . " {GROUP_BY} ", $sSQL);

		return $sSQL;
	}


	/**
	 * Create the SELECT statement for the sum query
	 * 
	 * Folgende Felder fehlen:
	 *  - 32:	// Grund der Stornierung
	 *  - 143:	// Stunden je Stundensatz
	 * 
	 * @param int $iColumnID
	 * @param array &$sSQL
	 * @param array &$aSQL
	 * @return string
	 */
	protected function _createSelect($iColumnID, &$sSQL, &$aSQL)
	{
		$this->_aQueryGroups = array("NULL" => "NULL");
		$sWhere = '';

		switch($iColumnID) { // JOINS / WHERE / GROUPS
			case 6: // Schüler gesamt
			case 201: // Anzahl der Online-Anmeldungen (Buchungen)
			{
				$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				if($iColumnID == 201) {
					$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`frontend_log_id` IS NOT NULL {WHERE} ", $sSQL);
				}

				break;
			}
			case 7: // Erwachsene schüler
			case 8: // Minderjährige schüler
			case 9: // Weibliche Schüler
			case 10: // Männliche Schüler
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				break;
			}
			case 11: // ø Alter gesamt
			case 12: // ø Alter männliche Schüler
			case 13: // ø Alter weibliche Schüler
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						ROUND(AVG(`x`.`result`)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				break;
			}
			case 15: // Land
			case 16: // Muttersprache
			case 17: // Nationalität
			case 18: // Status des Schülers
			case 19: // Wie sind Sie auf uns aufmerksam geworden
			case 21: // Agenturen
			case 23: // Agenturkategorien
			case 25: // Agenturgruppen
			case 137: // Schulen
			case 161: // Schulen / Inboxen
			case 193: // Gruppen
			case 203: // Vertriebsmitarbeiter
			case 207: // Vertriebsmitarbeiter (Anfragen)
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				switch($iColumnID) {
					case 15: // Land
						$this->_aQueryGroups["`tc_a`.`id`"] = "`tc_a`.`id`";
						break;
					case 16: // Muttersprache
					case 17: // Nationalität
						$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);
						$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";
						break;
					case 18: // Status des Schülers
					case 19: // Wie sind Sie auf uns aufmerksam geworden
					case 21: // Agenturen
					case 23: // Agenturkategorien
					case 25: // Agenturgruppen
						$this->_aQueryGroups["`root_id`"] = "`root_id`";
						break;
					case 137: // Schulen
					case 161: // Schulen / Inboxen
						$this->_aQueryGroups["`cdb2`.`id`"] = "`cdb2`.`id`";
						if($iColumnID == 161) {
							$this->_aQueryGroups["`k_inb`.`id`"] = "`k_inb`.`id`";
						}
						break;
					case 193: // Gruppen
						$this->_aQueryGroups["`ts_i`.`group_id`"] = "`ts_i`.`group_id`";
						break;
					case 203: // Vertriesmitarbeiter
					case 207: // Vertriebsmitarbeiter
						$this->_aQueryGroups["`root_id`"] = "`root_id`";
						break;
					case 204: // Agenturen / Inboxen
						$this->_aQueryGroups["`ka`.`id`"] = "`ka`.`id`";
						$this->_aQueryGroups["`k_inb`.`id`"] = "`k_inb`.`id`";
					case 208: // Inbox
						$this->_aQueryGroups["`k_inb`.`id`"] = "`k_inb`.`id`";
				}

				break;
			}
			case 27: // Stornierungen gesamt
			case 28: // Stornierungen Minderjähriger
			case 29: // Stornierungen Erwachsener
			case 30: // Stornierungen männlich
			case 31: // Stornierungen weiblich
			{
				$sSQL = str_replace(
					'{WHERE}',
					" AND `ts_i`.`canceled` > 0 {WHERE} ",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				break;
			}
			case 34: // Anfragen (Anzahl)
			case 35: // Umwandlung (Anzahl)
			{

				// War damals wichtig für »Basierend auf Buchung«, um auf die Enquiry zu kommen
				/*$sSQL = str_replace(
					'{ENQUIRIES_JOIN_ADDON}',
					" LEFT JOIN
					(
						`ts_enquiries_to_inquiries` AS `ts_e_to_i`						INNER JOIN
						`ts_enquiries` AS `ts_e`
					) ON
						`ts_e_to_i`.`inquiry_id`	= `ts_i`.`id`	AND
						`ts_e_to_i`.`enquiry_id`	= `ts_e`.`id`	AND
						`ts_e`.`active`				= 1
					{DOCUMENTS_JOIN_ADDON} ",
					$sSQL
				);*/
				
				break; // DO NOTHING
			}
			case 36: // Umsätze (inkl. Storno) gesamt
			case 37: // Umsätze je Kurskategorie
			case 38: // Umsätze je Kurs
			case 39: // Umsätze je Unterkunftskategorie
			case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
			case 41: // Umsätze je generelle Kosten
			case 42: // Umsätze je kursbezogene Kosten
			case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
			case 44: // Umsätze Direktkunden (inkl. Storno)
			case 45: // ø Reisepreis (alles, inkl. Storno)
			case 46: // ø Kurspreis je Kurs
			case 47: // ø Kurspreis je Kunde
			case 48: // ø Kurspreis je Kurskategorie (Auflistung)
			case 49: // ø Kurspreis je Kurs (Auflistung)
			case 50: // ø Unterkunftspreis
			case 51: // ø Unterkunftspreis je Unterkunftskategorie
			case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
			case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
			case 56: // Umsätze je unterkunftsbezogene Kosten
			case 63: // Provision gesamt
			case 64: // ø Provision absolut pro Kunde bei Agenturbuchungen
			case 65: // ø Provisionssatz je Kunde bei Agenturbuchungen
			case 66: // Stornierungsumsätze
			case 67: // Summe je angelegtem Steuersatz
			case 90: // Versicherungsumsatz
			case 91: // Versicherungssumme je Versicherung
			case 154: // Umsatz je Unterkunftsanbieter
			case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
			case 171: // Kursumsatz (netto)
			case 172: // Umsätze gesamt (brutto, inkl. Storno)
			case 173: // Umsätze gesamt (netto, inkl. Storno)
			case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
			case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
			case 180: // Umsatz - Transfer (netto, exkl. Steuern)
			case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
			case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
			case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
			case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
			case 190: // Totale Steuern (netto)
			{ // UMSÄTZE
				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";

				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 38: // Umsätze je Kurs
					case 39: // Umsätze je Unterkunftskategorie
					case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
					case 41: // Umsätze je generelle Kosten
					case 42: // Umsätze je kursbezogene Kosten
					case 44: // Umsätze Direktkunden (inkl. Storno)
					case 45: // ø Reisepreis (alles, inkl. Storno)
					case 46: // ø Kurspreis je Kurs
					case 47: // ø Kurspreis je Kunde
					case 48: // ø Kurspreis je Kurskategorie (Auflistung)
					case 49: // ø Kurspreis je Kurs (Auflistung)
					case 50: // ø Unterkunftspreis
					case 51: // ø Unterkunftspreis je Unterkunftskategorie
					case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
					case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 66: // Stornierungsumsätze
					case 90: // Versicherungsumsatz
					case 91: // Versicherungssumme je Versicherung
					case 154: // Umsatz je Unterkunftsanbieter
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					case 172: // Umsätze gesamt (brutto, inkl. Storno)

						$sSQL = str_replace(
							'{DOCUMENTS_JOIN_ADDON}',
							" AND `kid`.`type` IN(:aDocumentTypes) {DOCUMENTS_JOIN_ADDON} ",
							$sSQL
						);
						break;
					case 37: // Umsätze je Kurskategorie
					case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
					case 171: // Kursumsatz (netto)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					case 190: // Totale Steuern (netto)
					default:
						$sSQL = str_replace(
							'{DOCUMENTS_JOIN_ADDON}',
							" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
							$sSQL
						);
				}

				if($iColumnID == 66)
				{
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_i`.`canceled` > 0 {WHERE} ",
						$sSQL
					);
				}
				else if(!in_array($iColumnID, array(36, 43, 63, 64, 65, 44, 172, 173, 176)))
				{
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_i`.`canceled` = 0 {WHERE} ",
						$sSQL
					);
				}

				// Version-Item mit dem Feld (das eine Untergruppierung hat) verknüpfen
				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 190: // Totale Steuern (netto)
						break;
					case 37: // Umsätze je Kurskategorie, ohne Zusatzkosten
						$sType = " AND `kidvi`.`type` = 'course' AND `kidvi`.`type_object_id` = `ktc`.`id` ";
						$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";
						break;
					case 38: // Umsätze je Kurs, ohne Zusatzkosten
						// Hier fehlt die Item-Verknüpfung (Spalte ist auskommentiert)
						$sType = " AND `kidvi`.`type` IN('course') ";

						// Ticket R-#5070 #8
						if($this->_oStatistic->period != 3) {
							$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";
						}

						break;
					case 39: // Umsätze je Unterkunftskategorie
						// Hier fehlt die Item-Verknüpfung (Spalte ist auskommentiert)
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";
						$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";
						break;
					case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') AND `kidvi`.`type_object_id` = `ts_ija`.`accommodation_id` ";
						$this->_aQueryGroups["`key`"] = "`key`";
						break;
					case 41: // Umsätze je generelle Kosten
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_general' ";
						if($iColumnID == 41) {
							$this->_aQueryGroups["`key`"] = "`key`";
						}
						break;
					case 42: // Umsätze je kursbezogene Kosten
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_course' ";
						if($iColumnID == 42) {
							$this->_aQueryGroups["`key`"] = "`key`";
						}
						break;
					case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
						$sAdd = " AND `ts_i`.`agency_id` > 0 ";
						break;
					case 44: // Umsätze Direktkunden (inkl. Storno)
						$sAdd = " AND `ts_i`.`agency_id` = 0 ";
						break;
					case 45: // ø Reisepreis (alles, inkl. Storno)
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 46: // ø Kurspreis je Kurs
						$sType = " AND `kidvi`.`type` IN('course') ";
						$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";
						break;
					case 47: // ø Kurspreis je Kunde
						$sType = " AND `kidvi`.`type` IN('course') ";
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 48: // ø Kurspreis je Kurskategorie (Auflistung)
						$sType = " AND `kidvi`.`type` IN('course') ";
						$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";
						break;
					case 49: // ø Kurspreis je Kurs (Auflistung)
						$sType = " AND `kidvi`.`type` IN('course') ";
						$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";
						break;
					case 50: // ø Unterkunftspreis
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";
						$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";
						break;
					case 51: // ø Unterkunftspreis je Unterkunftskategorie
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";
						$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";
						break;
					case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
						$sType = " AND `kidvi`.`parent_type` != 'cancellation' ";
						$sAdd = " AND `ts_i`.`agency_id` > 0 ";
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
						$sType = " AND `kidvi`.`parent_type` != 'cancellation' ";
						$sAdd = " AND `ts_i`.`agency_id` = 0 ";
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'additional_accommodation' ";
						if($iColumnID == 56) {
							$this->_aQueryGroups["`key`"] = "`key`";
						}
						break;
					case 63: // Provision gesamt
						$sAdd = " AND `ts_i`.`agency_id` > 0 ";
						break;
					case 64: // ø Provision absolut pro Kunde bei Agenturbuchungen
						$sAdd = " AND `ts_i`.`agency_id` > 0 ";
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 65: // ø Provisionssatz je Kunde bei Agenturbuchungen
						$sAdd = " AND `ts_i`.`agency_id` > 0 ";
						$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";
						break;
					case 66: // Stornierungsumsätze
						$sType = " AND `kidvi`.`parent_type` = 'cancellation' ";
						break;
					case 67: // Summe je angelegtem Steuersatz
						$sType = " AND `kidvi`.`tax_category` > 0 ";
						$this->_aQueryGroups["`kidvi`.`tax_category`"] = "`kidvi`.`tax_category`";
						break;
					case 90: // Versicherungsumsatz
						$sType = " AND `kidvi`.`type` = 'insurance' ";
						break;
					case 91: // Versicherungssumme je Versicherung
						$sType = " AND `kidvi`.`type` = 'insurance' ";
						$this->_aQueryGroups["`ts_iji`.`insurance_id`"] = "`ts_iji`.`insurance_id`";
						break;
					case 154: // Umsatz je Unterkunftsanbieter
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') AND `kidvi`.`type_object_id` = `ts_ija`.`accommodation_id` ";
						$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";
						break;
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') AND `kidvi`.`type_object_id` = `ts_ija`.`accommodation_id` ";
						$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";
						$this->_aQueryGroups["`kaa`.`room_id`"] = "`kaa`.`room_id`";
						break;
					case 171: // Kursumsatz (netto)

						// Nur aktive Kursleistungen
						$sSQL = str_replace(
							'{WHERE}',
							" AND `ts_ijc`.`visible` = 1 ",
							$sSQL
						);

						$sType = " AND `kidvi`.`type` = 'course' ";
						break;
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";

						// Nur aktive Kursleistungen
						$sSQL = str_replace(
							'{WHERE}',
							" AND `ts_ija`.`visible` = 1 ",
							$sSQL
						);

						break;
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'transfer' ";
						break;
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
						$sType = " AND `kidvi`.`type` = 'extraPosition' ";
						break;
					default:
						$sAdd = $sType = "";
						break;
				}

				$sSQL = str_replace(
					array('{ITEMS_JOIN_ADDON}', '{WHERE}'),
					array($sType . " {ITEMS_JOIN_ADDON} ", $sAdd . " {WHERE} "),
					$sSQL
				);

				switch($iColumnID)
				{
					case 36: // Umsätze (inkl. Storno) gesamt
					case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
					case 44: // Umsätze Direktkunden (inkl. Storno)
					case 63: // Provision gesamt
					case 66: // Stornierungsumsätze
					case 90: // Versicherungsumsatz
					case 171: // Kursumsatz (netto)
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					case 190: // Totale Steuern (netto)
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`query_group_id`,
								`x`.`query_group_key`
						";

						break;
					}
					case 37: // Umsätze je Kurskategorie
					case 38: // Umsätze je Kurs
					case 39: // Umsätze je Unterkunftskategorie
					case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
					case 41: // Umsätze je generelle Kosten
					case 42: // Umsätze je kursbezogene Kosten
					case 56: // Umsätze je unterkunftsbezogene Kosten
					case 67: // Summe je angelegtem Steuersatz
					case 91: // Versicherungssumme je Versicherung
					case 154: // Umsatz je Unterkunftsanbieter
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`query_group_id`,
								`x`.`query_group_key`,
								`x`.`key`
						";

						break;
					}
					case 45: // ø Reisepreis (alles, inkl. Storno)
					case 50: // ø Unterkunftspreis
					case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
					case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
					{
						// Damals war das hier AVG(SUM()), was aber nicht funktionierte
						// Der Subquery ist momentan nur noch drin, falls das doch nicht so ganz funktioniert!
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								AVG(`y`.`result_x`) AS `result`
							FROM
							(
								SELECT
									`x`.*,
									AVG(`x`.`result`) AS `result_x`
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`query_group_id`,
									`x`.`query_group_key`
							) AS `y`
							GROUP BY
								`y`.`query_group_id`,
								`y`.`query_group_key`
						";

						break;
					}
					case 46: // ø Kurspreis je Kurs
					case 47: // ø Kurspreis je Kunde
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								AVG(`y`.`result_x`) AS `result`
							FROM
							(
								SELECT
									`x`.*,
									SUM(`x`.`result`) AS `result_x`
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`query_group_id`,
									`x`.`query_group_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`query_group_id`,
								`y`.`query_group_key`
						";

						break;
					}
					case 48: // ø Kurspreis je Kurskategorie (Auflistung)
					case 49: // ø Kurspreis je Kurs (Auflistung)
					case 51: // ø Unterkunftspreis je Unterkunftskategorie
					{
						// Damals war das hier AVG(SUM()), was aber nicht funktionierte
						// Der Subquery ist momentan nur noch drin, falls das doch nicht so ganz funktioniert!
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								AVG(`y`.`result_x`) AS `result`
							FROM
							(
								SELECT
									`x`.*,
									AVG(`x`.`result`) AS `result_x`
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`query_group_id`,
									`x`.`query_group_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`query_group_id`,
								`y`.`query_group_key`,
								`y`.`key`
						";

						break;
					}
					case 64: // ø Provision absolut pro Kunde bei Agenturbuchungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								AVG(`y`.`result_x`) AS `result`
							FROM
							(
								SELECT
									`x`.*,
									SUM(`x`.`result`) AS `result_x`
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`query_group_id`,
									`x`.`query_group_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`query_group_id`,
								`y`.`query_group_key`
						";

						break;
					}
					case 65: // ø Provisionssatz je Kunde bei Agenturbuchungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`y`.*,
								IF(SUM(`y`.`res` > 0), (SUM(`y`.`pro`) / SUM(`y`.`res`)), 0) * 100 AS `result`
							FROM
							(
								SELECT
									`x`.*,
									SUM(`x`.`result`) AS `res`,
									SUM(`x`.`provision`) AS `pro`
								FROM
								(
									{USAGE}
								) AS `x`
								GROUP BY
									`x`.`query_group_id`,
									`x`.`query_group_key`,
									`x`.`key`
							) AS `y`
							GROUP BY
								`y`.`query_group_id`,
								`y`.`query_group_key`
						";

						break;
					}
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								SUM(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`query_group_id`,
								`x`.`query_group_key`,
								`x`.`sub_key`,
								`x`.`key`
						";

						break;
					}
				}

				break;
			}
			case 54: // Zahlungseingänge (Summe)
			{
				$this->_aQueryGroups["`kipi`.`id`"] = "`kipi`.`id`";

				$sSQL = str_replace(
					'{ITEMS_JOIN_ADDON}',
					"
							AND `ts_i`.`canceled` = 0
						INNER JOIN
							`kolumbus_inquiries_payments_items` AS `kipi` ON
								`kidvi`.`id` = `kipi`.`item_id` AND
								`kipi`.`active` = 1
						INNER JOIN
							`kolumbus_inquiries_payments` AS `kip` ON
								`kipi`.`payment_id` = `kip`.`id` AND
								`kip`.`active` = 1 AND
								`kip`.`date` BETWEEN :FROM AND :TILL
						
						{ITEMS_JOIN_ADDON}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				break;
			}
			case 57: // Zahlungsausgänge (Summe)
			{
				$this->_aQueryGroups["`query_kTRp_group`"] = "`query_kTRp_group`";
				$this->_aQueryGroups["`query_kTEp_group`"] = "`query_kTEp_group`";
				$this->_aQueryGroups["`query_kACp_group`"] = "`query_kACp_group`";
				$this->_aQueryGroups["`query_kINp_group`"] = "`query_kINp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`kolumbus_transfers_payments` AS `kTRp` ON
								`ts_ijt`.`id` = `kTRp`.`inquiry_transfer_id` AND
								`kTRp`.`active` = 1 AND
								`kTRp`.`date` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accommodations_payments` AS `kACp` ON
								`ts_ija`.`id` = `kACp`.`inquiry_accommodation_id` AND
								`kACp`.`active` = 1 AND
								`kACp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accounting_manual_transactions` AS `kINp` ON
								`ts_ij`.`school_id` = `kINp`.`school_id` AND
								`kINp`.`active` = 1 AND
								`kINp`.`type` = 'outcome' AND
								`kINp`.`date` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`query_kTRp_group`,
						`x`.`query_kTEp_group`,
						`x`.`query_kACp_group`,
						`x`.`query_kINp_group`
				";

				break;
			}
			case 68: // Kurswochen je Kurs
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(IF(`x`.`key` IS NOT NULL, `x`.`result`, 0)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";

				break;
			}
			case 69: // Kurswochen je Kurskategorie
			case 205: // Kurswochen je Kurskategorie (Erwachsene)
			case 206: // Kurswochen je Kurskategorie (Minderjährige)
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(IF(`x`.`key` IS NOT NULL, `x`.`result`, 0)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";

				break;
			}
			case 70: // Kurswochen gesamt
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";

				break;
			}
			case 71: // Unterkunftswochen je Unterkunftskategorie
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(IF(`x`.`key` IS NOT NULL, `x`.`result`, 0)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";
				$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";

				break;
			}
			case 72: // Unterkunftswochen je Unterkunft
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(IF(`x`.`key` IS NOT NULL, `x`.`result`, 0)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";
				$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";

				break;
			}
			case 73: // Anzahl Transfers (Anreise, Abreise, An- und Abreise)
			{
				// DO NOTHING

				break;
			}
			case 74: // Anreise je Flughafen
			case 75: // Abreise je Flughafen
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(*) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijt`.`id`"] = "`ts_ijt`.`id`";

				break;
			}
			case 76: // Anreise je Flughafen im Stundenrhytmus
			case 77: // Abreise je Flughafen im Stundenrhytmus
			{
				$this->_sUseAsFrom = "
					SELECT
						`y`.*,
						GROUP_CONCAT(CONCAT(`y`.`counter`, '_', `y`.`result`)) AS `result`
					FROM
					(
						SELECT
							`x`.*,
							COUNT(*) AS `counter`
						FROM
						(
							{USAGE}
						) AS `x`
						WHERE
							`x`.`key` IS NOT NULL
						GROUP BY
							`x`.`query_group_id`,
							`x`.`query_group_key`,
							`x`.`result`,
							`x`.`key`
					) AS `y`
					GROUP BY
						`y`.`query_group_id`,
						`y`.`query_group_key`,
						`y`.`key`
				";

				$this->_aQueryGroups["`ts_ijt`.`id`"] = "`ts_ijt`.`id`";

				break;
			}
			case 78: // ø Kursdauer je Kurs in Wochen
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL, `x`.`result`, NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";

				break;
			}
			case 79: // ø Kursdauer je Kurskategorie in Wochen
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL, `x`.`result`, NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";

				break;
			}
			case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL, `x`.`result`, NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";
				$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";

				break;
			}
			case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL, `x`.`result`, NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";
				$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";

				break;
			}
			case 82: // ø Anzahl Schüler pro Lektion
			{
				$sSQL = str_replace(
					'{BLOCKS_JOIN_ADDON}',
					"
						AND
						(
							`ktb`.`week` <= :TILL AND
							(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
						)
						{BLOCKS_JOIN_ADDON}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						IF(
							SUM(`x`.`lessons` * `x`.`days`) > 0,
							SUM(`x`.`lessons` * `x`.`days` * `x`.`total`) / SUM(`x`.`lessons` * `x`.`days`),
							0
						) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`ktb`.`id`"] = "`ktb`.`id`";

				break;
			}
			case 83: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (gesamt)
			case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
			case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
			{
				$sSQL = str_replace(
					'{BLOCKS_JOIN_ADDON}',
					"
						AND
						(
							`ktb`.`week` <= :TILL AND
							(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
						)
						{BLOCKS_JOIN_ADDON}
					",
					$sSQL
				);

				$this->_aQueryGroups["`ktb`.`id`"] = "`ktb`.`id`";

				$sKey = $sWhere = "";

				switch($iColumnID)
				{
					case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
					{
						$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";

						$sKey = ", `x`.`key`";

						$sWhere = "WHERE `x`.`key` IS NOT NULL";

						break;
					}
					case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
					{
						$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";

						$sKey = ", `x`.`key`";

						$sWhere = "WHERE `x`.`key` IS NOT NULL";

						break;
					}
				}

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						IF(
							SUM(`x`.`lessons` * `x`.`days`) > 0 AND `x`.`max` > 0,
							(
								SUM((`x`.`total` / `x`.`max`) * `x`.`lessons` * `x`.`days`) / SUM(`x`.`lessons` * `x`.`days`)
							) * 100,
							0
						) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					" . $sWhere . "
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
						" . $sKey . "
				";

				break;
			}
			case 84: // ø Alter Kunde je Kurs
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL AND `x`.`result` > 0, getAge(`x`.`result`), NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";

				break;
			}
			case 86: // ø Alter Kunde je Kurskategorie
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL AND `x`.`result` > 0, getAge(`x`.`result`), NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";

				break;
			}
			case 87: // ø Alter Kunde je Unterkunftskategorie
			{
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(IF(`x`.`key` IS NOT NULL AND `x`.`result` > 0, getAge(`x`.`result`), NULL)) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ts_ija`.`id`"] = "`ts_ija`.`id`";
				$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";

				break;
			}
			case 89: // Versicherungen (Anzahl)
			{
				// DO NOTHING

				break;
			}
			case 93: // Geleistete Stunden gesamt
			{
				$sSQL = str_replace(
					'{BLOCKS_JOIN_ADDON}',
					"
						AND
						(
							`ktb`.`week` <= :TILL AND
							(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
						)
						{BLOCKS_JOIN_ADDON}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`ktb`.`id`"] = "`ktb`.`id`";

				break;
			}
			case 94: // Geleistete Stunden je Niveau
			{
				$sSQL = str_replace(
					'{BLOCKS_JOIN_ADDON}',
					"
						AND
						(
							`ktb`.`week` <= :TILL AND
							(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
						)
						{BLOCKS_JOIN_ADDON}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					WHERE
						`x`.`key` IS NOT NULL
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$this->_aQueryGroups["`ktb`.`id`"] = "`ktb`.`id`";
				$this->_aQueryGroups["`ktb`.`level_id`"] = "`ktb`.`level_id`";

				break;
			}
			case 95: // Margen Kurse (gesamt)
			case 96: // Margen je Klasse (entsprechend Klassenplanung)
			case 97: // Margen je Kurs
			case 98: // Margen je Kurskategorie
			{
				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$this->_aQueryGroups["`kipi`.`id`"] = "`kipi`.`id`";
				$this->_aQueryGroups["`ktp`.`id`"] = "`ktp`.`id`";

				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{ITEMS_JOIN_ADDON}',
					"
							AND `kid`.`type` IN(:aDocumentTypes, 'creditnote')
							AND `kidvi`.`type` IN('course', 'additional_course')
							AND `ts_i`.`canceled` = 0
							AND `kidvi`.`index_from` <= :TILL
							AND `kidvi`.`index_until` >= :FROM
						LEFT JOIN
							`kolumbus_inquiries_payments_items` AS `kipi` ON
								`kidvi`.`id` = `kipi`.`item_id` AND
								`kipi`.`active` = 1
						LEFT JOIN
							`kolumbus_inquiries_payments` AS `kip` ON
								`kipi`.`payment_id` = `kip`.`id` AND
								`kip`.`active` = 1
						{ITEMS_JOIN_ADDON}
					",
					$sSQL
				);

				$sSQL = str_replace(
					'{BLOCKS_JOIN_ADDON}',
					"
							AND
							(
								`ktb`.`week` <= :TILL AND
								(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
							)
						LEFT JOIN
							`kolumbus_tuition_classes` AS `ktcl` ON
								`ktb`.`class_id` = `ktcl`.`id` AND
								`ktcl`.`active` = 1
						LEFT JOIN
							`ts_teachers_payments` AS `ktp` ON
								`ktb`.`teacher_id` = `ktp`.`teacher_id` AND
								`ktp`.`active` = 1 AND
								`ktb`.`id` = `ktp`.`block_id` AND
								`ktp`.`timepoint` BETWEEN :FROM AND :TILL
						{BLOCKS_JOIN_ADDON}
					",
					$sSQL
				);

				if($iColumnID == 96)
				{
					$sSQL = str_replace(
						'{BLOCKS_JOIN_ADDON}',
						"
							INNER JOIN
								`kolumbus_tuition_templates` AS `ktt` ON
									`ktb`.`template_id` = `ktt`.`id` AND
									`ktt`.`active` = 1
							{BLOCKS_JOIN_ADDON}
						",
						$sSQL
					);
				}

				break;
			}
			case 100: // Margen Unterkunftsbezogen (gesamt)
			case 101: // Margen je Unterkunftsanbieter
			case 102: // Margen je Unterkunftskategorie
			{
				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$this->_aQueryGroups["`kipi`.`id`"] = "`kipi`.`id`";
				$this->_aQueryGroups["`kap`.`id`"] = "`kap`.`id`";

				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{ITEMS_JOIN_ADDON}',
					"
							AND `kid`.`type` IN(:aDocumentTypes, 'creditnote')
							AND `kidvi`.`type` IN('accommodation', 'additional_accommodation', 'extra_nights', 'extra_weeks')
							AND `ts_i`.`canceled` = 0
							AND `kidvi`.`index_from` <= :TILL
							AND `kidvi`.`index_until` >= :FROM
						LEFT JOIN
							`kolumbus_inquiries_payments_items` AS `kipi` ON
								`kidvi`.`id` = `kipi`.`item_id` AND
								`kipi`.`active` = 1
								
						LEFT JOIN
							`kolumbus_inquiries_payments` AS `kip` ON
								`kipi`.`payment_id` = `kip`.`id` AND
								`kip`.`active` = 1
						LEFT JOIN
							`kolumbus_accommodations_payments` AS `kap` ON
								`ts_ija`.`id` = `kap`.`inquiry_accommodation_id` AND
								`kap`.`active` = 1 AND
								`kap`.`timepoint` BETWEEN :FROM AND :TILL
						{ITEMS_JOIN_ADDON}
					",
					$sSQL
				);

				break;
			}
			case 103: // Margen transferbezogen (gesamt)
			case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
			case 105: // Margen je Transferabreise (bei An- und Abreise: Preis/2)
			case 106: // Margen je Transferanreise (bei An- und Abreise: Preis/2)
			case 107: // Margen Transfer gesamt je Flughafen
			case 108: // Margen Transfer - Abreise je Flughafen
			case 109: // Margen Transfer - Anreise je Flughafen
			{
				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$this->_aQueryGroups["`kipi`.`id`"] = "`kipi`.`id`";
				$this->_aQueryGroups["`ktp`.`id`"] = "`ktp`.`id`";

				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				switch($iColumnID)
				{
					case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
						$sAdd = " AND `ts_ijt`.`provider_type` IN('provider') ";
						break;
					case 105: // Margen je Transferabreise (bei An- und Abreise: Preis/2)
						$sAdd = " AND `ts_i`.`tsp_transfer` IN('arr_dep', 'departure') AND `ts_ijt`.`transfer_type` = 2 ";
						break;
					case 106: // Margen je Transferanreise (bei An- und Abreise: Preis/2)
						$sAdd = " AND `ts_i`.`tsp_transfer` IN('arr_dep', 'arrival') AND `ts_ijt`.`transfer_type` = 1 ";
						break;
					case 107: // Margen Transfer gesamt je Flughafen
						$sAdd = "
							AND `ts_i`.`tsp_transfer` IN('arr_dep', 'arrival', 'departure')
							AND `ts_ijt`.`start` > 0
							AND `ts_ijt`.`start_type` = 'location'
						";
						break;
					case 108: // Margen Transfer - Abreise je Flughafen
						$sAdd = "
							AND `ts_i`.`tsp_transfer` IN('arr_dep', 'departure')
							AND `ts_ijt`.`transfer_type` = 2
							AND `ts_ijt`.`start` > 0
							AND `ts_ijt`.`start_type` = 'location'
						";
						break;
					case 109: // Margen Transfer - Anreise je Flughafen
						$sAdd = "
							AND `ts_i`.`tsp_transfer` IN('arr_dep', 'arrival')
							AND `ts_ijt`.`transfer_type` = 1
							AND `ts_ijt`.`start` > 0
							AND `ts_ijt`.`start_type` = 'location'
						";
						break;
					default:
						$sAdd = "";
						break;
				}

				$sSQL = str_replace(
					'{ITEMS_JOIN_ADDON}',
					"
							AND `kid`.`type` IN(:aDocumentTypes, 'creditnote')
							AND `kidvi`.`type` IN('transfer')
							AND `ts_i`.`canceled` = 0
							AND `kidvi`.`index_from` BETWEEN :FROM AND :TILL
						" . $sAdd . "
						LEFT JOIN
							`kolumbus_inquiries_payments_items` AS `kipi` ON
								`kidvi`.`id` = `kipi`.`item_id` AND
								`kipi`.`active` = 1
						LEFT JOIN
							`kolumbus_inquiries_payments` AS `kip` ON
								`kipi`.`payment_id` = `kip`.`id` AND
								`kip`.`active` = 1
						LEFT JOIN
							`kolumbus_transfers_payments` AS `ktp` ON
								`ts_ijt`.`id` = `ktp`.`inquiry_transfer_id` AND
								`ktp`.`active` = 1
						{ITEMS_JOIN_ADDON}
					",
					$sSQL
				);

				break;
			}
			case 113: // Kosten Lehrer
			case 114: // Kosten je Kurs
			case 115: // Kosten je Kurskategorie
			{
				$this->_aQueryGroups["`query_kTEp_group`"] = "`query_kTEp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$sKey = "";

				switch($iColumnID)
				{
					case 114: // Kosten je Kurs
					case 115: // Kosten je Kurskategorie
					{
						$sKey = ", `y`.`key`";

						break;
					}
				}

				$this->_sUseAsFrom = "
					SELECT
						`y`.*,
						SUM(`teacher_pay`) AS `result`
					FROM
					(
						SELECT
							`x`.*
						FROM
						(
							{USAGE}
						) AS `x`
						GROUP BY
							`x`.`query_group_id`,
							`x`.`query_group_key`,
							`x`.`query_kTEp_group`
					) AS `y`
					GROUP BY
						`y`.`query_group_id`,
						`y`.`query_group_key`
						" . $sKey . "
				";

				break;
			}
			case 116: // Unterkunftskosten
			case 118: // Kosten je Unterkunftskategorie
			{
				$this->_aQueryGroups["`query_kACp_group`"] = "`query_kACp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`kolumbus_accommodations_payments` AS `kACp` ON
								`ts_ija`.`id` = `kACp`.`inquiry_accommodation_id` AND
								`kACp`.`active` = 1 AND
								`kACp`.`timepoint` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$sKey = "";

				if($iColumnID == 118)
				{
					$sKey = ", `y`.`key`";
				}

				$this->_sUseAsFrom = "
					SELECT
						`y`.*,
						SUM(`y`.`acc_pay`) AS `result`
					FROM
					(
						SELECT
							`x`.*
						FROM
						(
							{USAGE}
						) AS `x`
						GROUP BY
							`x`.`query_group_id`,
							`x`.`query_group_key`,
							`x`.`query_kACp_group`
					) AS `y`
					GROUP BY
						`y`.`query_group_id`,
						`y`.`query_group_key`
						" . $sKey . "
				";

				break;
			}
			case 119: // Kosten Transfer gesamt
			case 120: // Kosten Transfer - Abreise
			case 121: // Kosten Transfer - Anreise
			case 122: // Kosten Transfer gesamt je Flughafen
			case 123: // Kosten Transfer - Abreise je Flughafen
			case 124: // Kosten Transfer - Anreise je Flughafen
			{
				switch($iColumnID)
				{
					case 120: // Kosten Transfer - Abreise
					case 123: // Kosten Transfer - Abreise je Flughafen
						$sAdd = " AND `ts_ijt`.`transfer_type` = 2 ";
						break;
					case 121: // Kosten Transfer - Abreise
					case 124: // Kosten Transfer - Anreise je Flughafen
						$sAdd = " AND `ts_ijt`.`transfer_type` = 1 ";
						break;
					default:
						$sAdd = "";
						break;
				}

				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`kolumbus_transfers_payments` AS `ktp` ON
								`ts_ijt`.`id` = `ktp`.`inquiry_transfer_id` AND
								`ktp`.`active` = 1
								" . $sAdd . "
						{JOINS}
					",
					$sSQL
				);

				$sKey = "";

				switch($iColumnID)
				{
					case 122: // Kosten Transfer gesamt je Flughafen
					case 123: // Kosten Transfer - Abreise je Flughafen
					case 124: // Kosten Transfer - Anreise je Flughafen
					{
						$sKey = ", `y`.`key`";

						break;
					}
				}

				$this->_sUseAsFrom = "
					SELECT
						`y`.*,
						SUM(`y`.`result`) AS `result`
					FROM
					(
						SELECT
							`x`.*
						FROM
						(
							{USAGE}
						) AS `x`
						GROUP BY
							`x`.`query_group_id`,
							`x`.`query_group_key`,
							`x`.`unique`
					) AS `y`
					GROUP BY
						`y`.`query_group_id`,
						`y`.`query_group_key`
						" . $sKey . "
				";

				$this->_aQueryGroups["`ktp`.`id`"] = "`ktp`.`id`";

				break;
			}
			case 144: // Verdienst gesamt
			{
				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{DOCUMENTS_JOIN_ADDON}',
					" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
					$sSQL
				);

				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$this->_aQueryGroups["`query_kTRp_group`"] = "`query_kTRp_group`";
				$this->_aQueryGroups["`query_kTEp_group`"] = "`query_kTEp_group`";
				$this->_aQueryGroups["`query_kACp_group`"] = "`query_kACp_group`";
				$this->_aQueryGroups["`query_kINp_group`"] = "`query_kINp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`kolumbus_transfers_payments` AS `kTRp` ON
								`ts_ijt`.`id` = `kTRp`.`inquiry_transfer_id` AND
								`kTRp`.`active` = 1 AND
								`kTRp`.`date` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accommodations_payments` AS `kACp` ON
								`ts_ija`.`id` = `kACp`.`inquiry_accommodation_id` AND
								`kACp`.`active` = 1 AND
								`kACp`.`timepoint` BETWEEN :FROM AND :TILL
						LEFT JOIN
							`kolumbus_accounting_manual_transactions` AS `kINp` ON
								`ts_ij`.`school_id` = `kINp`.`school_id` AND
								`kINp`.`active` = 1 AND
								`kINp`.`type` = 'outcome' AND
								`kINp`.`date` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`result` != 0, CONCAT(`x`.`unique`, '_', `x`.`result`), NULL)
						) AS `result`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`transfer_pay` != 0, CONCAT(`x`.`query_kTRp_group`, '_', `x`.`transfer_pay`), NULL)
						) AS `transfer_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`teacher_pay` != 0, CONCAT(`x`.`query_kTEp_group`, '_', `x`.`teacher_pay`), NULL)
						) AS `teacher_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`acc_pay` != 0, CONCAT(`x`.`query_kACp_group`, '_', `x`.`acc_pay`), NULL)
						) AS `acc_pay`,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`individual_pay` != 0, CONCAT(`x`.`query_kINp_group`, '_', `x`.`individual_pay`), NULL)
						) AS `individual_pay`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				break;
			}
			case 145: // Verdienst je Kurskategorie
			case 146: // Verdienst je Kurs
			{
				$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

				$sSQL = str_replace(
					'{DOCUMENTS_JOIN_ADDON}',
					" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
					$sSQL
				);

				switch($iColumnID)
				{
					case 145: // Verdienst je Kurskategorie
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					case 146: // Verdienst je Kurs
						$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
						break;
					default:
						$sType = "";
				}

				$sSQL = str_replace('{ITEMS_JOIN_ADDON}', $sType . " {ITEMS_JOIN_ADDON} ", $sSQL);

				$this->_aQueryGroups["`kidvi`.`id`"] = "`kidvi`.`id`";
				$this->_aQueryGroups["`query_kTEp_group`"] = "`query_kTEp_group`";

				$sSQL = str_replace(
					'{JOINS}',
					"
						LEFT JOIN
							`ts_teachers_payments` AS `kTEp` ON
								`kt`.`id` = `kTEp`.`teacher_id` AND
								`kTEp`.`active` = 1 AND
								`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
						{JOINS}
					",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						GROUP_CONCAT(
							DISTINCT
							IF(`x`.`result` != 0, CONCAT(`x`.`unique`, '_', `x`.`result`), NULL)
						) AS `result`,
						GROUP_CONCAT(
							DISTINCT CONCAT(`x`.`query_kTEp_group`, '_', `x`.`teacher_pay`)
						) AS `teacher_pay`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				break;
			}
			case 147: // Anzahl der Bewertungen
			case 150: // Häufigste Bewertungen
			case 151: // ø Bewertung gesamt
			{
				$sSQL = str_replace(
					'{JOINS}',
					"
						INNER JOIN
							`kolumbus_feedback_customer` AS `kfc` ON
								`kfc`.`inquiry_id` = `ts_i`.`id` AND
								`kfc`.`active` = 1 AND
								`ts_ijc`.`id` IS NOT NULL
						INNER JOIN
							`kolumbus_feedback_customer_answer` AS `kfca` ON
								`kfc`.`id` = `kfca`.`customer_feedback_id` AND
								`kfca`.`active` = 1
						INNER JOIN
							`kolumbus_feedback_question` AS `kfq` ON
								`kfca`.`question_id` = `kfq`.`id` AND
								`kfq`.`active` = 1 AND
								`kfq`.`type` = 'teacher' AND
								`kfq`.`answer_type` = 'answers'
						INNER JOIN
							`ts_teachers` AS `kfca_kt` ON
								`kfca`.`parent_id` = `kfca_kt`.`id` AND
								`kfca_kt`.`active` = 1
						LEFT JOIN
							`kolumbus_feedback_answer` AS `kfa` ON
								`kfca`.`answer` = `kfa`.`id` AND
								`kfa`.`active` = 1
						LEFT JOIN
							`kolumbus_feedback_note` AS `kfn` ON
								`kfa`.`note_id` = `kfn`.`id` AND
								`kfn`.`active` = 1
						{JOINS}
					",
					$sSQL
				);

				switch($iColumnID)
				{
					case 150: // Häufigste Bewertungen
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								COUNT(*) AS `counter`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`query_group_id`,
								`x`.`query_group_key`,
								`x`.`key`
							ORDER BY
								`counter` DESC
							LIMIT
								1
						";

						$this->_aQueryGroups["`kfn`.`name`"] = "`kfn`.`name`";

						break;
					}
					case 151: // ø Bewertung gesamt
					{
						$this->_sUseAsFrom = "
							SELECT
								`x`.*,
								AVG(`x`.`result`) AS `result`
							FROM
							(
								{USAGE}
							) AS `x`
							GROUP BY
								`x`.`query_group_id`,
								`x`.`query_group_key`
						";

						$this->_aQueryGroups["`kfca`.`id`"] = "`kfca`.`id`";

						break;
					}
				}

				break;
			}
			case 156: // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
			case 159: // Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
			case 160: // Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
			case 168: // Anzahl der Schüler (nur kursbezogen)
			case 192: // Anzahl der Schüler ohne internes Level (exkl. Storno)
			case 194: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
			case 195: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
			{
				$sSQL = str_replace(
					'{COURSES_JOIN_ADDON}',
					"
						AND `ts_i`.`canceled` = 0
						AND `ts_ijc`.`from` <= :TILL
						AND `ts_ijc`.`until` >= :FROM
						{COURSES_JOIN_ADDON}
					",
					$sSQL
				);

				$sWhere_ = ' AND `ts_ijc`.`id` IS NOT NULL ';
				// Das war vorher != 168 (#6010), aber mittlerweile gibt es den Filter und nur bei 156 steht Rechnung explizit drin
				if($iColumnID == 156) {
					$sWhere_ .= ' AND `ts_i`.`has_invoice` = 1 ';
				}

				switch($iColumnID) {
					case 192: // Anzahl der Schüler ohne internes Level (exkl. Storno)
						$sWhere_ .= ' AND `ktl_internal`.`id` IS NULL ';
						break;
					case 194: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
						$sSQL = str_replace(
							'{JOINS}',
							" INNER JOIN
								`ts_inquiries_tuition_index` `ts_iti` ON
									`ts_iti`.`state` & 1 AND
									`ts_iti`.`inquiry_id` = `ts_i`.`id` AND (
										`ts_iti`.`week` <= :TILL AND
										`ts_iti`.`week` + INTERVAL 6 DAY >= :FROM
									)
								{JOINS}
							",
							$sSQL
						);
						break;
					case 195: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
						$sSQL = str_replace(
							'{JOINS}',
							" INNER JOIN
								`ts_inquiries_journeys_courses_tuition_index` `ts_ijcti` ON
									`ts_ijcti`.`state` & 1 AND
									`ts_ijcti`.`journey_course_id` = `ts_ijc`.`id` AND (
										`ts_ijcti`.`week` <= :TILL AND
										`ts_ijcti`.`week` + INTERVAL 6 DAY >= :FROM
									)
								{JOINS}
							",
							$sSQL
						);
						break;
				}

				$sSQL = str_replace(
					'{WHERE}',
					$sWhere_.' {WHERE} ',
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

//				if($this->_bHasAnySubGroup) {
//					$this->_sUseAsFrom .= " , `query_sub_group_id` ";
//				}

				switch($iColumnID) {
					case 192: // Anzahl der Schüler ohne internes Level (exkl. Storno)
					case 195: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
						// Summierung auf Basis der Klassenplanung: Gruppierung nach Kurs und Unterkurs (Kombinationskurse)
//						$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
//						$this->_aQueryGroups["`ktc_combination_courses`.`id`"] = "`ktc_combination_courses`.`id`";
						$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";
						$this->_aQueryGroups["`ktl_internal`.`id`"] = "`ktl_internal`.`id`";
						break;
					default:
						$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";
				}

				break;
			}
			case 162: // Anzahl der Schüler (Einzelreisende)
			case 163: // Anzahl der Schüler (Gruppenreisende)
			{
				$sSQL = str_replace(
					'{COURSES_JOIN_ADDON}',
					"
						AND `ts_i`.`canceled` = 0
						AND `ts_ijc`.`from` <= :TILL
						AND `ts_ijc`.`until` >= :FROM
						{COURSES_JOIN_ADDON}
					",
					$sSQL
				);

				$sSQL = str_replace(
					'{WHERE}',
					" AND `ts_ijc`.`id` IS NOT NULL {WHERE} ",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

//				if($this->_bHasAnySubGroup) {
//					$this->_sUseAsFrom .= " , `query_sub_group_id` ";
//				}

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				break;
			}
			case 164: // Lektionen (Gruppenreisende)
			case 165: // Lektionen (Einzelreisende)
			{
				
				$sSQL = str_replace(
					'{COURSES_JOIN_ADDON}',
					"
						AND `ts_i`.`canceled` = 0
						AND `ts_ijc`.`from` <= :TILL
						AND `ts_ijc`.`until` >= :FROM
						{COURSES_JOIN_ADDON}
					",
					$sSQL
				);
				
				if($iColumnID == 164) {
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_ijc`.`id` IS NOT NULL AND ts_i.group_id > 0 {WHERE} ",
						$sSQL
					);
				} else {
					$sSQL = str_replace(
						'{WHERE}',
						" AND `ts_ijc`.`id` IS NOT NULL AND ts_i.group_id = 0 {WHERE} ",
						$sSQL
					);
				}
				
				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

//				if($this->_bHasAnySubGroup) {
//					$this->_sUseAsFrom .= " , `query_sub_group_id` ";
//				}
				
				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
				
				break;
			}
			case 157: // Anzahl der Schüler (nur unterkunftsbezogen)
			{
				$sSQL = str_replace(
					'{ACC_JOIN_ADDON}',
					"
						AND `ts_i`.`canceled` = 0
						AND `ts_ija`.`from` <= :TILL
						AND `ts_ija`.`until` >= :FROM
						{ACC_JOIN_ADDON}
					",
					$sSQL
				);

				$sSQL = str_replace(
					'{WHERE}',
					" AND `ts_ija`.`id` IS NOT NULL {WHERE} ",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				break;
			}
			case 166: // Anzahl der Schüler je Kurs
			case 167: // Anzahl der Schüler je Kurskategorie
			{

				$sSQL = str_replace(
					'{COURSES_JOIN_ADDON}',
					"
						AND `ts_i`.`canceled` = 0
						AND `ts_ijc`.`from` <= :TILL
						AND `ts_ijc`.`until` >= :FROM
						{COURSES_JOIN_ADDON}
					",
					$sSQL
				);

				$sSQL = str_replace(
					'{WHERE}',
					" AND `ts_ijc`.`id` IS NOT NULL {WHERE} ",
					$sSQL
				);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						SUM(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

//				if($this->_bHasAnySubGroup) {
//					$this->_sUseAsFrom .= " , `query_sub_group_id` ";
//				}

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";
				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";

				if($iColumnID === 166) {
					$this->_aQueryGroups["`ktc`.`id`"] = "`ktc`.`id`";
				} else {
					$this->_aQueryGroups["`ktc`.`category_id`"] = "`ktc`.`category_id`";
				}

				break;
			}
			case 174: // Nationalität in %

				$sSubSql = $this->getColumnQuery(6, $aSQL);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) / `students_count`.`result` * 100 AS `result`
					FROM
					(
						{USAGE}
					) AS `x`,
					(
						".$sSubSql."
					) `students_count`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";

				break;
			case 191: // Schüler pro internem Niveau (exkl. Storno)

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						COUNT(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`,
						`x`.`key`
				";

				$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`canceled` = 0 {WHERE} ", $sSQL);

				// Summierung auf Basis der Klassenplanung: Gruppierung nach Kurs und Unterkurs (Kombinationskurse)
//				$this->_aQueryGroups["`ts_ijc`.`id`"] = "`ts_ijc`.`id`";
//				$this->_aQueryGroups["`ktc_combination_courses`.`id`"] = "`ktc_combination_courses`.`id`";
				$this->_aQueryGroups["`tc_c`.`id`"] = "`tc_c`.`id`";
				$this->_aQueryGroups["`ktl_internal`.`id`"] = "`ktl_internal`.`id`";

				break;
			case 196: // Anzahl der Angebote
			case 197: // Anzahl der Anfragen ohne Angebot

				// Nur Angebote mit Dokument sind relevant
				$sSQL = str_replace(
					'{JOINS}',
					" LEFT JOIN (
						`ts_enquiries_offers` `ts_eo` INNER JOIN
						`ts_enquiries_offers_to_documents` `ts_eotd`
					) ON
						`ts_eo`.`enquiry_id` = `ts_e`.`id` AND
						`ts_eo`.`active` = 1 AND
						`ts_eotd`.`enquiry_offer_id` = `ts_eo`.`id` {JOINS} ",
					$sSQL
				);

				if($iColumnID == 197) {
					$sSQL = str_replace(" {WHERE} ", " AND `ts_eo`.`id` IS NULL {WHERE} ", $sSQL);
				}

				break;
			case 198: // Anzahl fälliger nachzuhakender Anfragen

				$sSQL = str_replace(
					"{WHERE}",
					" AND
					`ts_i`.`id` IS NULL AND
					`ts_e`.`follow_up` != '0000-00-00' AND
					`ts_e`.`follow_up` <= :TILL
					{WHERE} ",$sSQL);

				break;
			case 199: // Anzahl umgewandelter Anfragen in %

				$sSubSql = $this->getColumnQuery(35, $aSQL);

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						`converted_enquiries`.`result` / COUNT(`x`.`result`) * 100 AS `result`
					FROM
					(
						{USAGE}
					) AS `x`,
					(
						".$sSubSql."
					) `converted_enquiries`
					/*GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`*/
				";

				// Nötig, da {USAGE} keine Aggregatfunktion (COUNT) hat
				$this->_aQueryGroups["`ts_e`.`id`"] = "`ts_e`.`id`";

				break;
			case 200: // Durchschnittliche Dauer bis zur Umwandlung (Tage)

				$this->_sUseAsFrom = "
					SELECT
						`x`.*,
						AVG(`x`.`result`) AS `result`
					FROM
					(
						{USAGE}
					) AS `x`
					GROUP BY
						`x`.`query_group_id`,
						`x`.`query_group_key`
				";

				// Lediglich Optimierung (andere Spalten wären NULL)
				$sSQL = str_replace(" {WHERE} ", " AND `ts_i`.`id` IS NOT NULL {WHERE} ", $sSQL);

				$this->_aQueryGroups["`ts_e`.`id`"] = "`ts_e`.`id`";

				break;
			case 202: // Anzahl der Online-Anmeldungen (Anfragen)

				$sSQL = str_replace(" {WHERE} ", " AND `ts_e`.`frontend_log_id` IS NOT NULL {WHERE} ", $sSQL);

				break;
		}

		/* ==================================================================================================== */
		switch($iColumnID) { // SELECT
			case 6: // Schüler gesamt
			case 201: // Anzahl der Online-Anmeldungen (Buchungen)
				$sSelect = "COUNT(DISTINCT `tc_c`.`id`)";
				break;
			case 7: // Erwachsene schüler
			case 159: // Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
				$iAge = $this->_oSchool->getGrownAge();
				$sSelect = "(getAge(`tc_c`.`birthday`) >= " . $iAge . " OR NULL)";
				break;
			case 8: // Minderjährige schüler
			case 160: // Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
				$iAge = $this->_oSchool->getGrownAge();
				$sSelect = "(getAge(`tc_c`.`birthday`) < " . $iAge . " OR NULL)";
				break;
			case 162: // Anzahl der Schüler (Einzelreisende)
				$sSelect = "(`ts_i`.`group_id` = 0)";
				break;
			case 163: // Anzahl der Schüler (Gruppenreisende)
				$sSelect = "(`ts_i`.`group_id` > 0)";
				break;
			case 164: // Lektionen (Gruppenreisende)
			case 165: // Lektionen (Einzelreisende)

				$sSelect = "
					getSubAmountByDates(
						`ts_ijclc`.`absolute`,
						:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`
					)";

				break;
			case 9: // Weibliche Schüler
				$sSelect = "(`tc_c`.`gender` = 2 OR NULL)";
				break;
			case 10: // Männliche Schüler
				$sSelect = "(`tc_c`.`gender` = 1 OR NULL)";
				break;
			case 11: // ø Alter gesamt
				$sSelect = "IF(`tc_c`.`birthday` > 0, getAge(`tc_c`.`birthday`), NULL)";
				break;
			case 12: // ø Alter männliche Schüler
				$sSelect = "IF(`tc_c`.`birthday` > 0 AND `tc_c`.`gender` = 1, getAge(`tc_c`.`birthday`), NULL)";
				break;
			case 13: // ø Alter weibliche Schüler
				$sSelect = "IF(`tc_c`.`birthday` > 0 AND `tc_c`.`gender` = 2, getAge(`tc_c`.`birthday`), NULL)";
				break;
			case 15: // Land
				$sSelect = "`tc_a`.`country_iso` AS `key`, (`tc_a`.`country_iso` != '' OR NULL)";
				break;
			case 16: // Muttersprache
				$sSelect = "`tc_c`.`language` AS `key`, (`tc_c`.`language` != '' OR NULL)";
				break;
			case 17: // Nationalität
			case 174: // Nationalität in %
				$sSelect = "`tc_c`.`nationality` AS `key`, (`tc_c`.`nationality` != '' OR NULL)";
				break;
			case 18: // Status des Schülers
				$sSelect = "`ts_i`.`status_id` AS `key`, (`ts_i`.`status_id` > 0 OR NULL)";
				break;
			case 19: // Wie sind Sie auf uns aufmerksam geworden
				$sSelect = "`ts_i`.`referer_id` AS `key`, (`ts_i`.`referer_id` > 0 OR NULL)";
				break;
			case 21: // Agenturen
			case 204: // Agenturen / Inboxen (macht bei Obergruppierung keinen Sinn, muss aber irgendwas gesetzt sein)
				$this->_aNeededParts['agency'] = 1;
				$sSelect = "`ka`.`id` AS `key`, 1";
				break;
			case 23: // Agenturkategorien
				$sSelect = "`ka`.`ext_39` AS `key`, 1";
				break;
			case 25: // Agenturgruppen
				$sSelect = "`kaga`.`group_id` AS `key`, 1";
				break;
			case 27: // Stornierungen gesamt
				$sSelect = "1";
				break;
			case 28: // Stornierungen Minderjähriger
				$iAge = $this->_oSchool->getGrownAge();
				$sSelect = "(getAge(`tc_c`.`birthday`) < " . $iAge . " OR NULL)";
				break;
			case 29: // Stornierungen Erwachsener
				$iAge = $this->_oSchool->getGrownAge();
				$sSelect = "(getAge(`tc_c`.`birthday`) >= " . $iAge . " OR NULL)";
				break;
			case 30: // Stornierungen männlich
				$sSelect = "(`tc_c`.`gender` = 1 OR NULL)";
				break;
			case 31: // Stornierungen weiblich
				$sSelect = "(`tc_c`.`gender` = 2 OR NULL)";
				break;
			case 34: // Anzahl der Anfragen
			case 197: // Anzahl der Anfragen ohne Angebot
			case 198: // Anzahl fälliger nachzuhakender Anfragen
			case 202: // Anzahl der Online-Anmeldungen (Anfragen)
				$sSelect = "COUNT(DISTINCT `ts_e`.`id`)";
				break;
			case 35: // Anzahl umgewandelter Anfragen
				$sSelect = "COUNT(DISTINCT `ts_e_to_i`.`enquiry_id`)";
				break;
			case 36: // Umsätze (inkl. Storno) gesamt
			case 37: // Umsätze je Kurskategorie
			case 38: // Umsätze je Kurs
			case 39: // Umsätze je Unterkunftskategorie
			case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
			case 41: // Umsätze je generelle Kosten
			case 42: // Umsätze je kursbezogene Kosten
			case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
			case 44: // Umsätze Direktkunden (inkl. Storno)
			case 45: // ø Reisepreis (alles, inkl. Storno)
			case 46: // ø Kurspreis je Kurs
			case 47: // ø Kurspreis je Kunde
			case 48: // ø Kurspreis je Kurskategorie (Auflistung)
			case 49: // ø Kurspreis je Kurs (Auflistung)
			case 50: // ø Unterkunftspreis
			case 51: // ø Unterkunftspreis je Unterkunftskategorie
			case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
			case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
			case 56: // Umsätze je unterkunftsbezogene Kosten
			case 66: // Stornierungsumsätze
			case 67: // Summe je angelegtem Steuersatz
			case 90: // Versicherungsumsatz
			case 91: // Versicherungssumme je Versicherung
			case 154: // Umsatz je Unterkunftsanbieter
			case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
			case 171: // Kursumsatz (netto)
			case 172: // Umsätze gesamt (brutto, inkl. Storno)
			case 173: // Umsätze gesamt (netto, inkl. Storno)
			case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
			case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
			case 180: // Umsatz - Transfer (netto, exkl. Steuern)
			case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
			case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
			case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
			case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
			case 190: // Totale Steuern (netto)
			{
				$bCalcSubAmountByDates = true;
				$bUseGrossColumn = false;
				$bCheckCreditnote = false;
				$iTaxOption = 0;

				switch($iColumnID)
				{
					case 37: // Umsätze je Kurskategorie
						$sKey = " `ktc`.`category_id` ";
						$bCheckCreditnote = true;
						break;
					case 38: // Umsätze je Kurs
						$sKey = " `ts_ijc`.`course_id` ";
						break;
					case 39: // Umsätze je Unterkunftskategorie
						$sKey = " `ts_ija`.`accommodation_id` ";
						break;
					case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
						$sKey = " CONCAT(`ts_ija`.`accommodation_id`, '_', `ts_ija`.`roomtype_id`, '_', `ts_ija`.`meal_id`) ";
						break;
					case 41: // Umsätze je generelle Kosten
						$sKey = " `kidvi`.`type_id` ";
						$bCalcSubAmountByDates = false;
						break;
					case 42: // Umsätze je kursbezogene Kosten
						$sKey = " `kidvi`.`type_id` ";
						break;
					case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
						$bCheckCreditnote = true;
						 break;
					case 46: // ø Kurspreis je Kurs
						$sKey = " `ktc`.`id` ";
						break;
					case 47: // ø Kurspreis je Kunde
						$sKey = " `tc_c`.`id` ";
						break;
					case 48: // ø Kurspreis je Kurskategorie (Auflistung)
						$sKey = " `ktc`.`category_id` ";
						break;
					case 49: // ø Kurspreis je Kurs (Auflistung)
						$sKey = " `ts_ijc`.`course_id` ";
						break;
					case 51: // ø Unterkunftspreis je Unterkunftskategorie
						$sKey = " `ts_ija`.`accommodation_id` ";
						break;
					case 56: // Umsätze je unterkunftsbezogene Kosten
						$sKey = " `kidvi`.`type_id` ";
						break;
					case 67: // Summe je angelegtem Steuersatz
						$sKey = " `kidvi`.`tax_category` ";
						break;
					case 91: // Versicherungssumme je Versicherung
						$sKey = " `ts_iji`.`insurance_id` ";
						break;
					case 154: // Umsatz je Unterkunftsanbieter
						$sKey = " `kr`.`accommodation_id` ";
						break;
					case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						$sKey = " `kr`.`id` AS `sub_key`, `kr`.`accommodation_id` ";
						break;
					case 172: // Umsätze gesamt (brutto, inkl. Storno)
						$bUseGrossColumn = true;
						break;
					case 171: // Kursumsatz (netto)
					case 173: // Umsätze gesamt (netto, inkl. Storno)
					case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
					case 180: // Umsatz - Transfer (netto, exkl. Steuern)
					case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						$bCheckCreditnote = true;
						break;
					case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					case 190: // Totale Steuern (netto)
						$bCheckCreditnote = true;
						$iTaxOption = 1;
						if($iColumnID == 190) {
							$iTaxOption = 2;
						}
						break;
					default:
						$sKey = "";
				}

				if(!empty($sKey))
				{
					$sKey .= " AS `key`, ";
				}

				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect = $sKey . " (" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iTaxOption) . ")";
				}
				else
				{
					
					$this->_aNeededParts['transfer'] = 1;

					// Beträge nach Leistungszeitraum und Zeile splitten
					// @TODO Wenn das für noch mehr Felder gesetzt werden muss, dann Abfrage gleich entfernen?
					if($bCalcSubAmountByDates) {

						$sIndexFromAndUntil = "
							`kidvi`.`index_from` <= :TILL AND
							`kidvi`.`index_until` >= :FROM
						";

						$sSubAmountByDates = "
							getSubAmountByDates(
								(" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iTaxOption) . "),
								:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
							)
						";
					} else {

						$sIndexFromAndUntil = "
							`kidvi`.`index_from` BETWEEN :FROM AND :TILL
						";

						$sSubAmountByDates = "
							(" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iTaxOption) . ")
						";
					}

					$sSelect = "
						" . $sKey . "
						(
							
							IF(
								(
									$sIndexFromAndUntil
								),
								(
									IF(
										(
											`kidvi`.`type` = 'transfer'
										),
										(
											(
												" . $this->_addDefaultSumPart($bUseGrossColumn, $bCheckCreditnote, $iTaxOption, false) . "
											) /
											IF(
												(
													`kidvi`.`type_id` = `ts_ijt`.`id`
												),
												1,
												(
													IF(
														(
															`kidvi`.`index_from` BETWEEN :FROM AND :TILL AND
															`kidvi`.`index_until` BETWEEN :FROM AND :TILL
														),
														1,
														2
													)
												)
											) *
											IF(
												/* Transferpaket: Geht der Transfer mehr als 2 Monate/Zeiträume, darf dazwischen kein Wert auftauchen */
												`kidvi`.`type_id` = 0 AND
												`kidvi`.`index_from` NOT BETWEEN :FROM AND :TILL AND
												`kidvi`.`index_until` NOT BETWEEN :FROM AND :TILL,
												0,
												1
											)
										),
										0
									) +
									IF(
										(
											`kidvi`.`type` != 'transfer' AND
											`kidvi`.`type` != 'special'
										),
										(
											$sSubAmountByDates
										),
										0
									)
								),
								0
							)
						)
					";
				}

				break;
			}
			case 54: // Zahlungseingänge (Summe)
			{
				$sSelect = "
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry`,
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					)
				";

				break;
			}
			case 57: // Zahlungsausgänge (Summe)
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					`kTRp`.`id` AS `query_kTRp_group`,
					calcAmountByCurrencyFactors(
						`kTRp`.`amount`,
						`kTRp`.`payment_currency_id`,
						`kTRp`.`date`,
						" . $this->_iCurrencyID . ",
					 	`kTRp`.`date`
					) AS `transfer_pay`,
					`kTEp`.`id` AS `query_kTEp_group`,
					calcAmountByCurrencyFactors(
						`kTEp`.`amount`,
						`kTEp`.`payment_currency_id`,
						`kTEp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTEp`.`date`
					) AS `teacher_pay`,
					`kACp`.`id` AS `query_kACp_group`,
					calcAmountByCurrencyFactors(
						`kACp`.`amount`,
						`kACp`.`payment_currency_id`,
						`kACp`.`date`,
						" . $this->_iCurrencyID . ",
						`kACp`.`date`
					) AS `acc_pay`,
					`kINp`.`id` AS `query_kINp_group`,
					calcAmountByCurrencyFactors(
						`kINp`.`amount_school`,
						`kINp`.`currency_school`,
						`kINp`.`date`,
						" . $this->_iCurrencyID . ",
						`kINp`.`date`
					) AS `individual_pay`,
					1
				";

				break;
			}
			case 63: // Provision gesamt
			case 64: // ø Provision absolut pro Kunde bei Agenturbuchungen
			{
				$sAmountPart = "
					IF(
						`kidv`.`tax` = 1,
						(`kidvi`.`amount_provision` - (`kidvi`.`amount_provision` / 100 * `kidvi`.`amount_discount`)) / (`kidvi`.`tax` / 100 + 1),
						`kidvi`.`amount_provision` - `kidvi`.`amount_provision` / 100 * `kidvi`.`amount_discount`
					)
				";

				$sSelect = "
					`ts_i`.`id` AS `key`,
				";

				if($this->_oStatistic->period == 3) {
					$sSelect .= "
						IF(
							`kidvi`.`index_from` <= :TILL AND
							`kidvi`.`index_until` >= :FROM,
					";

					$sAmountPart = "
						getSubAmountByDates(
							$sAmountPart,
							:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
						)
					";
				}

				$sSelect .= "
						IF(
							(
								`kid`.`type` IN(
									'proforma_brutto',
									'proforma_netto',
									'group_proforma',
									'group_proforma_netto'
								) AND
								`ts_i`.`has_invoice` = 1
							),
							0,
							$sAmountPart
						)
				";

				if($this->_oStatistic->period == 3) {
					$sSelect .= "
						, 0
						)
					";
				}

				break;
			}
			case 65: // ø Provisionssatz je Kunde bei Agenturbuchungen
			{
				$sSelect = "
					`ts_i`.`id` AS `key`,
					(
						calcAmountByCurrencyFactors(
							`kidvi`.`amount_provision` - `kidvi`.`amount_provision` / 100 * `kidvi`.`amount_discount`,
							`ts_i`.`currency_id`,
							`kidv`.`date`,
							" . $this->_iCurrencyID . ",
							`kidv`.`date`
						)
					) AS `provision`,
					(
						" . $this->_addDefaultSumPart() . "
					)
				";

				break;
			}
			case 68: // Kurswochen je Kurs
			case 78: // ø Kursdauer je Kurs in Wochen
			{
				if($this->_oStatistic->period == 3) // Leistungszeitraum
				{
					$sSelect = "
						`ktc`.`id` AS `key`,
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`)
					";
				}
				else
				{
					$sSelect = "`ktc`.`id` AS `key`, `ts_ijc`.`weeks`";
				}

				break;
			}
			case 69: // Kurswochen je Kurskategorie
			case 205: // Kurswochen je Kurskategorie (Erwachsene)
			case 206: // Kurswochen je Kurskategorie (Minderjährige)
			case 79: // ø Kursdauer je Kurskategorie in Wochen
			{
				$sSelectBefore = $sSelectAfter = "";
				if(
					$iColumnID == 205 ||
					$iColumnID == 206
				) {
					$sOperator = $iColumnID == 205 ? '>=' : '<';
					$iAge = $this->_oSchool->getGrownAge();
					$sSelectBefore = "IF(getAge(`tc_c`.`birthday`) {$sOperator} {$iAge}, ";
					$sSelectAfter = ", 0)";
				}

				if($this->_oStatistic->period == 3) { // Leistungszeitraum
					$sSelect = "
						`ktc`.`category_id` AS `key`,
						{$sSelectBefore}calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`){$sSelectAfter}
					";
				} else {
					$sSelect = "`ktc`.`category_id` AS `key`, {$sSelectBefore}`ts_ijc`.`weeks`".$sSelectAfter;
				}

				break;
			}
			case 70: // Kurswochen gesamt
			{
				if($this->_oStatistic->period == 3) // Leistungszeitraum
				{
					$sSelect = "
						(
							IF(
								`ts_ijc`.`id` IS NOT NULL,
								calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`),
								0
							)
						)
					";
				}
				else
				{
					$sSelect = "`ts_ijc`.`weeks`";
				}

				break;
			}
			case 71: // Unterkunftswochen je Unterkunftskategorie
			case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
			{
				if($this->_oStatistic->period == 3) // Leistungszeitraum
				{
					$sSelect = "
						`ts_ija`.`accommodation_id` AS `key`,
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ija`.`from`, `ts_ija`.`until`)
					";
				}
				else
				{
					$sSelect = "`ts_ija`.`accommodation_id` AS `key`, `ts_ija`.`weeks`";
				}

				break;
			}
			case 72: // Unterkunftswochen je Unterkunft
			case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
			{
				if($this->_oStatistic->period == 3) // Leistungszeitraum
				{
					$sSelect = "
						`kr`.`accommodation_id` AS `key`,
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ija`.`from`, `ts_ija`.`until`)
					";
				}
				else
				{
					$sSelect = "
						`kr`.`accommodation_id` AS `key`,
						`ts_ija`.`weeks`
					";
				}

				break;
			}
			case 73: // Anzahl Transfers (Anreise, Abreise, An- und Abreise)
			{
				$sSelect = "COUNT(DISTINCT `ts_ijt`.`id`)";

				break;
			}
			case 74: // Anreise je Flughafen
			{
				$sSelect = "
					IF(
						`ts_ijt`.`transfer_type` = 1 AND `ts_ijt`.`start_type` = 'location', `ts_ijt`.`start`, NULL
					) AS `key`,
					`ts_ijt`.`start`
				";

				break;
			}
			case 75: // Abreise je Flughafen
			{
				$sSelect = "
					IF(
						`ts_ijt`.`transfer_type` = 2 AND `ts_ijt`.`start_type` = 'location', `ts_ijt`.`start`, NULL
					) AS `key`,
					`ts_ijt`.`start`
				";

				break;
			}
			case 76: // Anreise je Flughafen im Stundenrhytmus
			{
				$sSelect = "
					IF(
						`ts_ijt`.`transfer_type` = 1 AND `ts_ijt`.`start_type` = 'location', `ts_ijt`.`start`, NULL
					) AS `key`,
					HOUR(`ts_ijt`.`transfer_time`)";

				break;
			}
			case 77: // Abreise je Flughafen im Stundenrhytmus
			{
				$sSelect = "
					IF(
						`ts_ijt`.`transfer_type` = 2 AND `ts_ijt`.`start_type` = 'location', `ts_ijt`.`start`, NULL
					) AS `key`,
					HOUR(`ts_ijt`.`transfer_time`)";

				break;
			}
			case 82: // ø Anzahl Schüler pro Lektion
			{
				$sSelect = "
					COUNT(DISTINCT `ts_i`.`id`) AS `total`,
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days`
						WHERE
							`block_id` = `ktb`.`id`
					) AS `days`,
					(
						SELECT
							`lessons`
						FROM
							`kolumbus_tuition_templates`
						WHERE
							`id` = `ktb`.`template_id`
					) AS `lessons`,
					1
				";

				break;
			}
			case 83: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (gesamt)
			case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
			case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
			{
				switch($iColumnID)
				{
					case 83: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (gesamt)
						$sKey = "`ktb`.`id`";
						break;
					case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
						$sKey = "`ktc`.`id`";
						break;
					case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
						$sKey = "`ktc`.`category_id`";
						break;
				}

				$sSelect = "
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_inquiries_courses`
						WHERE
							`active` = 1 AND
							`block_id` = `ktb`.`id`
					) AS `total`,
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days`
						WHERE
							`block_id` = `ktb`.`id`
					) AS `days`,
					(
						SELECT
							`lessons`
						FROM
							`kolumbus_tuition_templates`
						WHERE
							`id` = `ktb`.`template_id`
					) AS `lessons`,
					(
						SELECT
							MIN(`_ktc`.`maximum_students`)
						FROM 
							`kolumbus_tuition_classes_courses` AS `_ktbc` INNER JOIN
							`kolumbus_tuition_courses` AS `_ktc` ON
								`_ktbc`.`course_id` = `_ktc`.`id`
						WHERE
							`_ktc`.`maximum_students` > 0 AND
							`_ktbc`.`class_id` = `ktb`.`class_id`
					) AS `max`,
					" . $sKey . " AS `key`,
					1
				";

				break;
			}
			case 84: // ø Alter Kunde je Kurs
			{
				$sSelect = "`ktc`.`id` AS `key`, `tc_c`.`birthday`";

				break;
			}
			case 86: // ø Alter Kunde je Kurskategorie
			{
				$sSelect = "`ktc`.`category_id` AS `key`, `tc_c`.`birthday`";

				break;
			}
			case 87: // ø Alter Kunde je Unterkunftskategorie
			{
				$sSelect = "`ts_ija`.`accommodation_id` AS `key`, `tc_c`.`birthday`";

				break;
			}
			case 89: // Versicherungen (Anzahl)
			{
				$sSelect = "COUNT(DISTINCT `ts_iji`.`id`)";

				break;
			}
			case 93: // Geleistete Stunden gesamt
			case 94: // Geleistete Stunden je Niveau
			{
				$sSelect = "
					`ktb`.`level_id` AS `key`,
					(
						(
							SELECT
								COUNT(*)
							FROM
								`kolumbus_tuition_blocks_days`
							WHERE
								`block_id` = `ktb`.`id`
						) *
						(
							SELECT
								`lessons`
							FROM
								`kolumbus_tuition_templates`
							WHERE
								`id` = `ktb`.`template_id`
						)
					)
				";

				break;
			}
			/*
				MARGEN-BERECHNUNG:

				(1 - SUM(Ausgaben) / SUM(Einnahmen)) * 100 = Margen in %

				Die Möglichen Werte sind: +100 bis -unendlich %
			*/
			case 95: // Margen Kurse (gesamt)
			case 96: // Margen je Klasse (entsprechend Klassenplanung)
			case 97: // Margen je Kurs
			case 98: // Margen je Kurskategorie
			{
				switch($iColumnID)
				{
					case 95: // Margen Kurse (gesamt)
						$sKey = "";
						break;
					case 96: // Margen je Klasse (entsprechend Klassenplanung)
						$sKey = " `ktcl`.`id` AS `key`, `ktt`.`lessons`, JSON_EXTRACT(`ktc`.`lessons_list` , '$[0]') AS `total`, ";
						break;
					case 97: // Margen je Kurs
						$sKey = " `ktc`.`id` AS `key`, ";
						break;
					case 98: // Margen je Kurskategorie
						$sKey = " `ktc`.`category_id` AS `key`, ";
						break;
				}

				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					" . $sKey . "
					`ktb`.`id` AS `ktb_id`,
					`ktp`.`id` AS `ktp_id`,
					`kipi`.`id` AS `kipi_id`,
					`ts_ijc`.`id` AS `ijc_id`,
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry` /
						calcWeeksFromCourseDates(
							`ts_ijc`.`from`,
							`ts_ijc`.`until`,
							`ts_ijc`.`from`,
							`ts_ijc`.`until`
						) *
						calcWeeksFromCourseDates(
							:FROM,
							:TILL,
							`ts_ijc`.`from`,
							`ts_ijc`.`until`
						),
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					) AS `price`,
					calcAmountByCurrencyFactors(
						`ktp`.`amount`,
						`ktp`.`payment_currency_id`,
						`ktp`.`timepoint`,
						" . $this->_iCurrencyID . ",
						`ktp`.`timepoint`
					)
				";

				break;
			}
			case 100: // Margen Unterkunftsbezogen (gesamt)
			case 101: // Margen je Unterkunftsanbieter
			case 102: // Margen je Unterkunftskategorie
			{
				switch($iColumnID)
				{
					case 100: // Margen Unterkunftsbezogen (gesamt)
						$sKey = "";
						break;
					case 101: // Margen je Unterkunftsanbieter
						$sKey = " `kr`.`accommodation_id` AS `key`, ";
						break;
					case 102: // Margen je Unterkunftskategorie
						$sKey = " `ts_ija`.`accommodation_id` AS `key`, ";
						break;
				}

				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					" . $sKey . "
					`kap`.`id` AS `kap_id`,
					`kipi`.`id` AS `kipi_id`,
					calcAmountByCurrencyFactors(
						`kipi`.`amount_inquiry` /
						calcWeeksFromCourseDates(
							`ts_ija`.`from`,
							`ts_ija`.`until`,
							`ts_ija`.`from`,
							`ts_ija`.`until`
						) *
						calcWeeksFromCourseDates(
							:FROM,
							:TILL,
							`ts_ija`.`from`,
							`ts_ija`.`until`
						),
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					) AS `price`,
					calcAmountByCurrencyFactors(
						`kap`.`amount`,
						`kap`.`payment_currency_id`,
						`kap`.`timepoint`,
						" . $this->_iCurrencyID . ",
						`kap`.`timepoint`
					)
				";

				break;
			}
			case 103: // Margen transferbezogen (gesamt)
			case 105: // Margen je Transferabreise (bei An- und Abreise: Preis/2)
			case 106: // Margen je Transferanreise (bei An- und Abreise: Preis/2)
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				switch($iColumnID)
				{
					case 105: // Margen je Transferabreise (bei An- und Abreise: Preis/2)
					case 106: // Margen je Transferanreise (bei An- und Abreise: Preis/2)
					{
						$sSelect = "
							`kipi`.`id` AS `query_price_group`,
							calcAmountByCurrencyFactors(
								IF(`ts_i`.`tsp_transfer` = 'arr_dep', `kipi`.`amount_inquiry` / 2, `kipi`.`amount_inquiry`),
								`kipi`.`currency_inquiry`,
								`kip`.`date`,
								" . $this->_iCurrencyID . ",
								`kip`.`date`
							) AS `price`,
							`ktp`.`id` AS `query_result_group`,
							calcAmountByCurrencyFactors(
								`ktp`.`amount`,
								`payment_currency_id`,
								`ktp`.`date`,
								" . $this->_iCurrencyID . ",
								`ktp`.`date`
							)
						";

						break;
					}
					default:
					{
						$sSelect = "
							`kipi`.`id` AS `query_price_group`,
							calcAmountByCurrencyFactors(
								`kipi`.`amount_inquiry`,
								`kipi`.`currency_inquiry`,
								`kip`.`date`,
								" . $this->_iCurrencyID . ",
								`kip`.`date`
							) AS `price`,
							`ktp`.`id` AS `query_result_group`,
							calcAmountByCurrencyFactors(
								`ktp`.`amount`,
								`ktp`.`payment_currency_id`,
								`ktp`.`date`,
								" . $this->_iCurrencyID . ",
								`ktp`.`date`
							)
						";
					}
				}

				break;
			}
			case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
			case 107: // Margen Transfer gesamt je Flughafen
			case 108: // Margen Transfer - Abreise je Flughafen
			case 109: // Margen Transfer - Anreise je Flughafen
			{
				switch($iColumnID)
				{
					case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
					{
						$sSelectPart = "`ts_ijt`.`provider_id` AS `key`, ";

						break;
					}
					default:
						$sSelectPart = "`ts_ijt`.`start` AS `key`, ";
				}

				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = $sSelectPart . "
					`kipi`.`id` AS `query_price_group`,
					calcAmountByCurrencyFactors(
						IF(`ts_i`.`tsp_transfer` = 'arr_dep', `kipi`.`amount_inquiry` / 2, `kipi`.`amount_inquiry`),
						`kipi`.`currency_inquiry`,
						`kip`.`date`,
						" . $this->_iCurrencyID . ",
						`kip`.`date`
					) AS `price`,
					`ktp`.`id` AS `query_result_group`,
					calcAmountByCurrencyFactors(
						`ktp`.`amount`,
						`ktp`.`payment_currency_id`,
						`ktp`.`date`,
						" . $this->_iCurrencyID . ",
						`ktp`.`date`
					)
				";

				break;
			}
			case 113: // Kosten Lehrer
			case 114: // Kosten je Kurs
			case 115: // Kosten je Kurskategorie
			{
				switch($iColumnID)
				{
					case 115: // Kosten je Kurskategorie
						$sKey = "`ktc`.`category_id` AS `key`, ";
						break;
					case 114: // Kosten je Kurs
						$sKey = "`ktc`.`id` AS `key`, ";
						break;
					default:
						$sKey = "";
				}

				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = $sKey . "
					`kTEp`.`id` AS `query_kTEp_group`,
					GROUP_CONCAT(`ktc`.`id`) `key_grouped`, -- T-4131
					calcAmountByCurrencyFactors(
						`kTEp`.`amount`,
						`kTEp`.`payment_currency_id`,
						`kTEp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTEp`.`date`
					) AS `teacher_pay`,
					1
				";

				break;
			}
			case 116: // Unterkunftskosten
			case 118: // Kosten je Unterkunftskategorie
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					`ts_ija`.`accommodation_id` AS `key`,
					`kACp`.`id` AS `query_kACp_group`,
					IF(
						" . $this->_iCurrencyID . " = `kACp`.`school_currency_id`,
						`kACp`.`amount_school`,
						calcAmountByCurrencyFactors(
							`kACp`.`amount`,
							`kACp`.`payment_currency_id`,
							`kACp`.`date`,
							" . $this->_iCurrencyID . ",
							`kACp`.`date`
						)
					) AS `acc_pay`,
					1
				";

				break;
			}
			case 119: // Kosten Transfer gesamt
			case 120: // Kosten Transfer - Abreise
			case 121: // Kosten Transfer - Anreise
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					CONCAT(`ts_ijt`.`start`, '_', `ts_ijt`.`start_type`) AS `key`,
					`ktp`.`id` AS `unique`,
					(
						calcAmountByCurrencyFactors(
							`ktp`.`amount`,
							`ktp`.`payment_currency_id`,
							`ktp`.`date`,
							" . $this->_iCurrencyID . ",
							`ktp`.`date`
						)
					)
				";

				break;
			}
			case 122: // Kosten Transfer gesamt je Flughafen
			case 123: // Kosten Transfer - Abreise je Flughafen
			case 124: // Kosten Transfer - Anreise je Flughafen
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					IF(`ts_ijt`.`start_type` = 'location', `ts_ijt`.`start`, NULL) AS `key`,
					`ktp`.`id` AS `unique`,
					(
						calcAmountByCurrencyFactors(
							`ktp`.`amount`,
							`ktp`.`payment_currency_id`,
							`ktp`.`date`,
							" . $this->_iCurrencyID . ",
							`ktp`.`date`
						)
					)
				";

				break;
			}
			case 137: // Schulen
			case 161: // Schulen / Inboxen (macht bei Obergruppierung keinen Sinn, muss aber irgendwas gesetzt sein)
			{
				$sSelect = "`cdb2`.`id` AS `key`, `cdb2`.`id`";

				break;
			}
			/*case 144: // Verdienst gesamt
			case 145: // Verdienst je Kurskategorie
			case 146: // Verdienst je Kurs
			{
				// ACHTUNG: HIER SIND EXPERIMENTELL TEILE DER QUERY DURCH SCHLANKERE TEILE ERSETZT
				// MAL SCHAUEN, OB ES DAMIT SCHNELLER WIRD...

				$sSelect = "
					`kidvi`.`id` AS `unique`,
					`kTRp`.`id` AS `query_kTRp_group`,
					calcAmountByCurrencyFactors(
						`kTRp`.`amount`,
						`kTRp`.`payment_currency_id`,
						`kTRp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTRp`.`date`
					) AS `transfer_pay`,
					`kTEp`.`id` AS `query_kTEp_group`,
					calcAmountByCurrencyFactors(
						`kTEp`.`amount`,
						`kTEp`.`payment_currency_id`,
						`kTEp`.`date`,
						" . $this->_iCurrencyID . ",
						`kTEp`.`date`
					) AS `teacher_pay`,
					`kACp`.`id` AS `query_kACp_group`,
					calcAmountByCurrencyFactors(
						`kACp`.`amount`,
						`kACp`.`payment_currency_id`,
						`kACp`.`date`,
						" . $this->_iCurrencyID . ",
						`kACp`.`date`
					) AS `acc_pay`,
					`kINp`.`id` AS `query_kINp_group`,
					calcAmountByCurrencyFactors(
						`kINp`.`amount_school`,
						`kINp`.`currency_school`,
						`kINp`.`date`,
						" . $this->_iCurrencyID . ",
						`kINp`.`date`
					) AS `individual_pay`,
				";

				switch($iColumnID)
				{
					case 145: // Verdienst je Kurskategorie
					{
						$sKey = "`ktc`.`category_id` AS `key`, ";

						$sSelect = "
							`kidvi`.`id` AS `unique`,
							`kTEp`.`id` AS `query_kTEp_group`,
							calcAmountByCurrencyFactors(
								`kTEp`.`amount`,
								`kTEp`.`payment_currency_id`,
								`kTEp`.`date`,
								" . $this->_iCurrencyID . ",
								`kTEp`.`date`
							) AS `teacher_pay`,
						";

						break;
					}
					case 146: // Verdienst je Kurs
					{
						$sKey = "`ktc`.`id` AS `key`, ";

						$sSelect = "
							`kidvi`.`id` AS `unique`,
							`kTEp`.`id` AS `query_kTEp_group`,
							calcAmountByCurrencyFactors(
								`kTEp`.`amount`,
								`kTEp`.`payment_currency_id`,
								`kTEp`.`date`,
								" . $this->_iCurrencyID . ",
								`kTEp`.`date`
							) AS `teacher_pay`,
						";

						break;
					}
					default:
						$sKey = "";
				}

				if(
					$this->_oStatistic->period == 1 ||	// Buchungsdatum
					$this->_oStatistic->period == 5		// Anfrage
				)
				{
					$sSelect .= $sKey . " (" . $this->_addDefaultSumPart() . ")";
				}
				else
				{
					
					$this->_aNeededParts['transfer'] = 1;
					
					$sSelect .= $sKey . "
						(
							
							IF(
								(
									`kidvi`.`index_from` BETWEEN :FROM AND :TILL OR
									`kidvi`.`index_until` BETWEEN :FROM AND :TILL
								),
								(
									IF(
										(
											`kidvi`.`type` = 'transfer'
										),
										(
											(
												" . $this->_addDefaultSumPart() . "
											) /
											IF(
												(
													`kidvi`.`type_id` = `ts_ijt`.`id`
												),
												1,
												(
													IF(
														(
															`kidvi`.`index_from` BETWEEN :FROM AND :TILL AND
															`kidvi`.`index_until` BETWEEN :FROM AND :TILL
														),
														1,
														2
													)
												)
											)
										),
										0
									) +
									IF(
										(
											`kidvi`.`type` != 'transfer' AND
											`kidvi`.`type` != 'special'
										),
										(
											getSubAmountByDates(
												(" . $this->_addDefaultSumPart() . "),
												:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
											)
										),
										0
									)
								),
								0
							)
						)
					";
				}

				break;
			}*/
			case 147: // Anzahl der Bewertungen
			{
				$sSelect = "COUNT(DISTINCT `kfc`.`inquiry_id`)";

				break;
			}
			case 150: // Häufigste Bewertungen
			case 151: // ø Bewertung gesamt
			{
				$sSelect = "`kfn`.`name` AS `key`, `kfn`.`name`";

				break;
			}
			case 156: // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
			case 157: // Anzahl der Schüler (nur unterkunftsbezogen)
			case 168: // Anzahl der Schüler (nur kursbezogen)
			case 192: // Anzahl der Schüler ohne internes Level (exkl. Storno)
			case 194: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
			case 195: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
			{
				$sSelect = "1";

				break;
			}
			case 166: // Anzahl der Schüler je Kurs
			{
				$sSelect = "`ktc`.`id` AS `key`, 1";

				break;
			}
			case 167: // Anzahl der Schüler je Kurskategorie
			{
				$sSelect = "`ktc`.`category_id` AS `key`, 1";

				break;
			}
			case 193: // Gruppen
			{
				$sSelect = "`ts_i`.`group_id` AS `key`, `ts_i`.`group_id`";

				break;
			}
			case 191: // Schüler pro internem Niveau (exkl. Storno)
				$sSelect = "`ktl_internal`.`id` AS `key`, (`ktl_internal`.`name_short` != '' OR NULL)";
				break;
			case 196: // Anzahl der Angebote
				$sSelect = "COUNT(DISTINCT `ts_eo`.`id`)";
				break;
			case 199: // Anzahl umgewandelter Anfragen in %
				$sSelect = "1";
				break;
			case 200: // Durchschnittliche Dauer bis zur Umwandlung (Tage)
				$sSelect = " DATEDIFF(`ts_i`.`created`, `ts_e`.`created`) ";
				break;
			case 203:
				$sSelect = "`ts_i`.`sales_person_id` AS `key`, 1";
				break;
			case 208:
				$sSelect = "`ts_i`.`inbox`";
				break;
		}

		// Weiteres Switch
		switch($iColumnID) {
			case 68: // Kurswochen je Kurs
			case 69: // Kurswochen je Kurskategorie
			case 205: // Kurswochen je Kurskategorie (Erwachsene)
			case 206: // Kurswochen je Kurskategorie (Minderjährige)
			case 70: // Kurswochen gesamt
			case 71: // Unterkunftswochen je Unterkunftskategorie
			case 72: // Unterkunftswochen je Unterkunft

				// Stornierte Buchungen nicht hinzuzählen
				// R-#4045
				$sWhere = " AND `ts_i`.`canceled` = 0 ";

				break;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(isset($this->_aColumnsSettings[$iColumnID]))
		{ 
			$sSQL = str_replace(
				'{WHERE}',
				" AND #key_alias.#key_field IN(" . implode(',', $this->_aColumnsSettings[$iColumnID]) . ") {WHERE} ",
				$sSQL
			);
			$aSQL['key_alias'] = $this->_aKeyAlias[$iColumnID];
			$aSQL['key_field'] = $this->_aKeyField[$iColumnID];
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSelect .= " AS `result`, '" . $iColumnID . "' AS `column_id` ";

		$sSQL = str_replace('{SELECT}', $sSelect . " {SELECT} ", $sSQL);
		$sSQL = str_replace('{WHERE}', $sWhere . " {WHERE} ", $sSQL);
		$sSQL = str_replace('{GROUP_BY}', implode(',', $this->_aQueryGroups) . " {GROUP_BY} ", $sSQL);

	}


	/**
	 * Create the statistic query dummy
	 * 
	 * @param array $aFilter
	 * @param array &$aSQL
	 * @return string
	 */
	protected function _createQuery($aFilter, &$aSQL)
	{
		$this->_iCurrencyID = $aFilter['currency_id'];

//		if($this->_oStatistic->period == 5) // Based on enquiries
//		{
//			$sSQL = "
//				SELECT STRAIGHT_JOIN
//					`ts_e`.`id` AS `root_id`,
//					{PERIOD} AS `period`,
//					{SELECT}
//				FROM
//					`ts_enquiries` AS `ts_e`							LEFT JOIN
//					(
//						`ts_enquiries_to_inquiries` AS `ts_e_to_i`		INNER JOIN
//						`ts_inquiries` AS `ts_i`						INNER JOIN
//						`ts_inquiries_journeys` AS `ts_ij`
//					) ON
//						`ts_e_to_i`.`enquiry_id`	= `ts_e`.`id`	AND
//					 	`ts_e_to_i`.`inquiry_id`	= `ts_i`.`id`	AND
//						`ts_i`.`active`				= 1				AND
//						`ts_ij`.`inquiry_id`		= `ts_i`.`id`	AND
//						`ts_ij`.`active`			= 1						INNER JOIN
//					`customer_db_2` AS `cdb2`								ON
//						`ts_e`.`school_id`			= `cdb2`.`id`
//					{JOINS}
//				WHERE
//					`ts_e`.`active` = 1 AND
//					`ts_e`.`currency_id` = {$this->_iCurrencyID} AND
//					`cdb2`.`active` = 1
//					{WHERE}
//				GROUP BY
//					{GROUP_BY}
//				{HAVING}
//				ORDER BY
//					`ts_e`.`id`,
//					`ts_i`.`id`,
//					`ts_ij`.`id`,
//					`cdb2`.`id`
//					{ORDER_BY}
//				{LIMIT}
//			";
//		}
//		else // Other types are based on inquiries
//		{
			$sSQL = "
				SELECT 
					`ts_i`.`id` AS `root_id`,
					{PERIOD} AS `period`,
					{SELECT}
				FROM
					`ts_inquiries` AS `ts_i` INNER JOIN
					`ts_inquiries_journeys` AS `ts_ij`						ON
						`ts_ij`.`inquiry_id`		= `ts_i`.`id`	AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_ij`.`active`			= 1 INNER JOIN
					`customer_db_2` AS `cdb2` ON
						`ts_ij`.`school_id` = `cdb2`.`id` 
						{ENQUIRIES_JOIN_ADDON} 								
					{JOINS}
				WHERE
					`ts_i`.`active` = 1 AND
				    `ts_i`.`confirmed` > 0 AND
					`ts_i`.`currency_id` = {$this->_iCurrencyID} AND
					`cdb2`.`active` = 1
					{WHERE}
				GROUP BY
					{GROUP_BY}
				{HAVING}
				ORDER BY
					`ts_i`.`id`,
					`ts_ij`.`id`,
					`cdb2`.`id`
					{ORDER_BY}
				{LIMIT}
			";
//		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create WHERE

		$aValues = $this->createWhere($this->_oStatistic, $aFilter, $sSQL, $aSQL, $this);
		$this->_aFilterSchools = $aValues['schools'];
		$this->_aNeededParts = array_merge($this->_aNeededParts, $aValues['needed_parts']);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare column settings

		if($this->_oStatistic->list_type == 1) // Summe
		{
			$this->_prepareColumnSettings($sSQL, $aSQL);
		}
		else if($this->_oStatistic->list_type == 2) // Detail
		{
			$this->_prepareColumnsGroup($sSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $sSQL;
	}

	/**
	 * Create the WHERE contition
	 *
	 * @param Ext_Thebing_Management_Statistic|Ext_Thebing_Management_Statistic_Static_Abstract $oStatistic
	 * @param array $aFilter
	 * @param string $sSQL
	 * @param Ext_Thebing_Management_PageBlock $oPageblock
	 * @return array
	 */
	public static function createWhere($oStatistic, $aFilter, &$sSQL, &$aSql, $oPageblock=null) {
		$aValues = array('needed_parts' => array());
		$oClient = Ext_Thebing_Client::getInstance();

		$aValues['needed_parts']['address'] = 1;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter schools

		$aSchools = $oClient->getSchools(true);

		$aSchools = Ext_Thebing_Access_User::clearSchoolsListByAccessRight($aSchools);

		if(!isset($aFilter['schools']) || empty($aFilter['schools']))
		{
			$aFilter['schools'] = array_keys($aSchools);
		}

		if(empty($aFilter['schools']))
		{
			$aFilter['schools'] = array(0);
		}

		$aValues['schools'] = $aFilter['schools'];

		$sSQL = str_replace(
			'{WHERE}',
			" AND `cdb2`.`id` IN(" . implode(',', $aFilter['schools']) . ") {WHERE} ",
			$sSQL
		);
	
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sAgencyWhere = " `ts_i`.`agency_id` > 0 ";
		$sDirectWhere = " `ts_i`.`agency_id` = 0 ";

		switch($oStatistic->period) {

			case 1: // Buchungsdatum
			{
				$sSQL = str_replace(
					'{WHERE}',
					" AND (`ts_i`.`created` BETWEEN :FROM_DATETIME AND :TILL_DATETIME) {WHERE} ",
					$sSQL
				);

				break;
			}
			case 3: // Leistungszeitraum
			{
				$sSQL = str_replace(
					'{WHERE}',
					" AND (
						`ts_i`.`service_from` <= :TILL AND
						`ts_i`.`service_until` >= :FROM
					) {WHERE} ",
					$sSQL
				);

//				// Sollte das mal eingebaut werden, muss beachtet werden, dass auch nur wahre Leistungen berücksichtigt werden
				// Kursbuchung hat Kurs, Unterkunftsbuchung hat Unterkunft, Transfer des Typs ist im Select der Buchung ausgewählt…
//				$aValues['needed_parts']['tuition'] = 1;
//				$aValues['needed_parts']['accommodation'] = 1;
//
//				$sSQL = str_replace(
//					'{WHERE}',
//					" AND (
//						(
//							`ts_ijc`.`from` <= :TILL AND
//							`ts_ijc`.`until` >= :FROM
//						) OR (
//							`ts_ija`.`from` <= :TILL AND
//							`ts_ija`.`until` >= :FROM
//						)
//					) {WHERE} ",
//					$sSQL
//				);

				break;
			}
			case 5: // Anfrage
			{
				$sAgencyWhere = " `ts_e`.`agency_id` > 0 ";
				$sDirectWhere = " `ts_e`.`agency_id` = 0 ";

				$sSQL = str_replace(
					'{WHERE}',
					" AND (`ts_e`.`created` BETWEEN :FROM_DATETIME AND :TILL_DATETIME) {WHERE} ",
					$sSQL
				);

				break;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter customer

		if(($aFilter['agency'] && $aFilter['direct_customer'])) {
			$sSQL = str_replace('{WHERE}', " AND (({AGENCY}) OR ({DIRECT})) {WHERE} ", $sSQL);
		} else if($aFilter['agency'] && !$aFilter['direct_customer']) {
			$sSQL = str_replace('{WHERE}', " AND {AGENCY} {WHERE} ", $sSQL);
		} else if(!$aFilter['agency'] && $aFilter['direct_customer']) {
			$sSQL = str_replace('{WHERE}', " AND {DIRECT} {WHERE} ", $sSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter agency subvalues

		if($aFilter['agency']) {
			$aValues['needed_parts']['agency'] = 1;

			switch($aFilter['group_by']) {
				case 1: // Agenturen
				{
					if(!empty($aFilter['agencies'])) {
						$aAgencies = $aFilter['agencies'];
					} else {
						$aAgencies = array_keys(Ext_Thebing_Client::getFirstClient()->getAgencies(true));
					}

					if(empty($aAgencies)) {
						$aAgencies = array(0);
					}

					$sAgencyWhere .= " AND `ka`.`id` IN(" . implode(',', $aAgencies) . ") ";

					break;
				}
				case 2: // Agenturgruppen
				{
					if(empty($aFilter['agency_groups']))
					{
						$aAgencyGroups = Ext_Thebing_Agency::getGroupList(true);

						unset($aAgencyGroups[0]);

						$aAgencyGroups = array_keys($aAgencyGroups);
					}
					else
					{
						$aAgencyGroups = ($aFilter['agency_groups']);
					}

					$sAgencyWhere .= " AND `kag`.`id` IN(" . implode(',', $aAgencyGroups) . ") ";

					break;
				}
				case 3: // Agenturkategorien
				{
					if(empty($aFilter['agency_categories']))
					{
						$aAgencyCategories = array_keys($oClient->getAgenciesCategoriesList());
					}
					else
					{
						$aAgencyCategories = $aFilter['agency_categories'];
					}

					if(empty($aAgencyCategories))
					{
						$aAgencyCategories = array(0);
					}

					$sAgencyWhere .= " AND `ka`.`ext_39` IN(" . implode(',', $aAgencyCategories) . ") ";

					break;
				}
				case 4: // Agenturländer
				{
					if(empty($aFilter['agency_countries']))
					{
						$aAgencyCountries = array_keys($oClient->getAgenciesCountriesList());
					}
					else
					{
						$aAgencyCountries = $aFilter['agency_countries'];
					}

					if(empty($aAgencyCountries))
					{
						$aAgencyCountries = array(0);
					}

					$sTempSQL = "
						SELECT
							GROUP_CONCAT('\'', `cn_iso_2`, '\'')
						FROM
							`data_countries`
						WHERE
							`id` IN(" . implode(',', $aAgencyCountries) . ")
					";
					$sList = DB::getQueryOne($sTempSQL);

					$sAgencyWhere .= " AND `ka`.`ext_6` IN(" . $sList . ") ";

					break;
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Filter direct customer countries
		if(
			$aFilter['direct_customer'] &&
			!empty($aFilter['nationalities'])
		) {
			$aValues['needed_parts']['address'] = 1;

			$aSql['nationalities'] = $aFilter['nationalities'];
			$sDirectWhere .= " AND `tc_c`.`nationality` IN(:nationalities) ";
		}
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = str_replace(
			array('{AGENCY}', '{DIRECT}'),
			array($sAgencyWhere, $sDirectWhere),
			$sSQL
		);

		// Kunden in Liste anzeigen (ab Proforma/Rechnung)
		if($aFilter['customer_invoice_filter'] === 'proforma') {
			$sSQL = str_replace('{WHERE}', " AND (`ts_i`.`has_invoice` = 1 OR `ts_i`.`has_proforma` = 1) {WHERE} ", $sSQL);
		} elseif($aFilter['customer_invoice_filter'] === 'invoice') {
			$sSQL = str_replace('{WHERE}', " AND `ts_i`.`has_invoice` = 1 {WHERE} ", $sSQL);
		}

		// Erstellungsdatum (nur bei Leistungszeitraum)
		if(
			!empty($aFilter['created_from']) &&
			!empty($aFilter['created_until'])
		) {
			// Können direkt eingebaut werden, da diese durch die PHP-Datei konvertiert werden
			$sSQL = str_replace(
				'{WHERE}',
				" AND `ts_i`.`created` BETWEEN '".$aFilter['created_from']."' AND '".$aFilter['created_until']."'
				 {WHERE} ",
				$sSQL
			);
		}

		// Allgemeines Startdatum
		if(
			!empty($aFilter['service_from_start']) &&
			!empty($aFilter['service_from_end'])
		) {
			// Können direkt eingebaut werden, da diese durch die PHP-Datei konvertiert werden
			$sSQL = str_replace(
				'{WHERE}',
				" AND `ts_i`.`service_from` BETWEEN '".$aFilter['service_from_start']."' AND '".$aFilter['service_from_end']."'
				 {WHERE} ",
				$sSQL
			);
		}

		// Startdatum Kurs
		if(
			!empty($aFilter['course_from_start']) &&
			!empty($aFilter['course_from_end'])
		) {
			// Können direkt eingebaut werden, da diese durch die PHP-Datei konvertiert werden
			$sSQL = str_replace(
				'{WHERE}',
				" AND `ts_ijc`.`from` BETWEEN '".$aFilter['course_from_start']."' AND '".$aFilter['course_from_end']."'
				 {WHERE} ",
				$sSQL
			);
		}

		// Filter nach Gruppe- oder Individualbuchung
		if($aFilter['inquiry_group_filter'] === 'individual') {
			$sSQL = str_replace('{WHERE}', " AND `ts_i`.`group_id` = 0 {WHERE} ", $sSQL);
		} elseif($aFilter['inquiry_group_filter'] === 'group') {
			$sSQL = str_replace('{WHERE}', " AND `ts_i`.`group_id` > 0 {WHERE} ", $sSQL);
		}

		// Filter nach Status des Schülers
		if(
			!empty($aFilter['inquiry_student_status_filter']) &&
			$aFilter['inquiry_student_status_filter'] !== 'xNullx'
		) {
			$iStatusId = (int)$aFilter['inquiry_student_status_filter'];
			$sAlias = 'ts_i';

			if($oStatistic->period == 5) {
				$sAlias = 'ts_e'; // Enquiries
			}

			$sSQL = str_replace('{WHERE}', " AND `{$sAlias}`.`status_id` = {$iStatusId} {WHERE} ", $sSQL);
		}

		// Filter nach Kurskategorie
		if(
			!empty($aFilter['inquiry_course_category_id']) &&
			$aFilter['inquiry_course_category_id'] !== 'xNullx'
		) {
			$oPageblock->_aNeededParts['tuition'] = 1;
			$oPageblock->_aNeededParts['tuition_progress'] = 1; // Es zählen die Kategorien der Unterkurse bei Kombinationskurs
			$iCategoryId = (int)$aFilter['inquiry_course_category_id'];
			$sSQL = str_replace('{WHERE}', " AND `ktc_combination_courses`.`category_id` = {$iCategoryId} {WHERE} ", $sSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Dates filter

		// Nur bei normalen Statistiken ausführen, die auch mit Pageblock direkt arbeiten
		if($oStatistic instanceof Ext_Thebing_Management_Statistic) {

			$oPageblock->_aFilterDates = array();

			switch($oStatistic->interval)
			{
				case 1: $sPart = WDDate::YEAR;		$sMethod = 'getYearLimits';		break;
				case 2: $sPart = WDDate::QUARTER;	$sMethod = 'getQuarterLimits';	break;
				case 3: $sPart = WDDate::MONTH;		$sMethod = 'getMonthLimits';	break;
				case 4: $sPart = WDDate::WEEK;		$sMethod = 'getWeekLimits';		break;
				case 5: $sPart = WDDate::DAY;		$sMethod = 'getDayLimits';		break;
			}

			switch($oStatistic->type)
			{
				case 1: // Relativ: zu HEUTE
				{
					$aIntervals = (array)$oStatistic->intervals;

					foreach($aIntervals as $iInterval)
					{
						$oFrom = new WDDate();
						$oTill = new WDDate();

						$oFrom->add($iInterval, $sPart);
						$oTill->add($iInterval, $sPart);

						$aFrom = $oFrom->$sMethod();
						$aTill = $oTill->$sMethod();

						$oFrom = new WDDate($aFrom['start']);
						$oTill = new WDDate($aTill['end']);

						$oPageblock->_aFilterDates[] = array(
							'from'	=> $oFrom,
							'till'	=> $oTill
						);

						if((int)$oPageblock->_iMinTime > $oFrom->get(WDDate::TIMESTAMP))
						{
							$oPageblock->_iMinTime = $oFrom->get(WDDate::TIMESTAMP);
						}
						if((int)$oPageblock->_iMaxTime < $oTill->get(WDDate::TIMESTAMP))
						{
							$oPageblock->_iMaxTime = $oTill->get(WDDate::TIMESTAMP);
						}
					}

					break;
				}
				case 2: // Absolut: VON-BIS
				{
					if($oStatistic->interval <= 0)
					{
						$oPageblock->_aFilterDates[] = array(
							'from'	=> $oPageblock->_oFrom,
							'till'	=> $oPageblock->_oTill
						);
					}
					else
					{
						$oTill = new WDDate($oPageblock->_oFrom);

						$i = 0;

						while(1)
						{
							$oFrom = new WDDate($oPageblock->_oFrom);

							$oFrom->add($i, $sPart);

							$aLimits = $oFrom->$sMethod();

							if($i > 0)
							{
								$oFrom = new WDDate($aLimits['start']);
							}

							$oTill = new WDDate($aLimits['end']);

							if($oPageblock->_oTill->get(WDDate::TIMESTAMP) < $oTill->get(WDDate::TIMESTAMP))
							{
								$oTill->set($oPageblock->_oTill->get(WDDate::TIMESTAMP), WDDate::TIMESTAMP);
							}

							$oPageblock->_aFilterDates[] = array(
								'from'	=> $oFrom,
								'till'	=> $oTill
							);

							if($oPageblock->_oTill->get(WDDate::TIMESTAMP) <= $oTill->get(WDDate::TIMESTAMP))
							{
								break;
							}

							$i++;

							if($i >= 1000)
							{
								break;
							}
						}
					}

					$oPageblock->_iMinTime = $oPageblock->_oFrom->get(WDDate::TIMESTAMP);
					$oPageblock->_iMaxTime = $oPageblock->_oTill->get(WDDate::TIMESTAMP);

					break;
				}
			}
		}

		return $aValues;
	}


	/**
	 * Prepare the GROUP BY for the simple query (Ausgehend von)
	 * 
	 * @param string &$sSQL
	 */
	protected function _prepareColumnsGroup(&$sSQL)
	{
		switch($this->_oStatistic->start_with)
		{
			case 1: // Buchung
			{
				$sRowID = ' `ts_i`.`id` ';

				$this->_aQueryGroups["`ts_i`.`id`"] = "`ts_i`.`id`";

				break;
			}
			case 2: // Agentur
			{
				$sRowID = ' `ka`.`id` ';

				$this->_aQueryGroups["`ka`.`id`"] = "`ka`.`id`";

				$this->_aNeededParts['agency'] = 1;
				
				break;
			}
			case 3: // Kurs
			{
				$sRowID = ' `ts_ijc`.`course_id` ';

				$this->_aQueryGroups["`ts_ijc`.`course_id`"] = "`ts_ijc`.`course_id`";

				break;
			}
			case 4: // Lehrer
			{
				$sRowID = ' `kt`.`id` ';

				$this->_aQueryGroups["`kt`.`id`"] = "`kt`.`id`";

				break;
			}
			case 5: // Unterkunftskategorie
			{
				$sRowID = ' `ts_ija`.`accommodation_id` ';

				$this->_aQueryGroups["`ts_ija`.`accommodation_id`"] = "`ts_ija`.`accommodation_id`";

				break;
			}
			case 6: // Unterkunftsanbieter
			{
				$sRowID = ' `kr`.`accommodation_id` ';

				$this->_aQueryGroups["`kr`.`accommodation_id`"] = "`kr`.`accommodation_id`";

				break;
			}
			case 7: // Anfrage
			{
				$sRowID = ' `ts_e`.`id` ';

				$this->_aQueryGroups["`ts_e`.`id`"] = "`ts_e`.`id`";

				break;
			}
		}

		$sRowID .= ' AS `unique_row_key` ';

		$sSQL = str_replace('{SELECT}', $sRowID . ', {SELECT}', $sSQL);
	}


	/**
	 * Prepare columns settings, get und sort TOP values
	 * 
	 * @param string $sSQL
	 * @param array $aSQL
	 */
	protected function _prepareColumnSettings(&$sSQL, $aSQL)
	{
		$iTime = microtime(true);

		if($this->_oStatistic->list_type != 1)
		{
			return; // When not sum view
		}

		$aColumns = $this->_oStatistic->columns;

		if(in_array(40, (array)$aColumns['cols'])) // HOOK for "Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)"
		{
			$aColumns['settings'][40] = 0;
		}

		$bQueryHasGroupID = false;

		if(!isset($aColumns['groups']) || empty($aColumns['groups']))
		{
			$sSQL = str_replace(
				'{SELECT}',
				//" NULL AS `query_group_id`, NULL AS `query_sub_group_id`, NULL AS `query_group_key`, {SELECT} ",
				" NULL AS `query_group_id`, NULL AS `query_group_key`, {SELECT} ",
				$sSQL
			);

			$bQueryHasGroupID = true;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aCols = array_merge((array)$aColumns['groups'], (array)$aColumns['cols']);

		foreach($aCols as $iColumnID)
		{

			switch($iColumnID) // Only follow fields has limit (max) settings
			{
				case 15: // Land
					$this->_aKeyAlias[$iColumnID] = 'tc_a';
					$this->_aKeyField[$iColumnID] = 'country_iso';
					$sKey = "`tc_a`.`country_iso`";									break;
				case 16: // Muttersprache
					$this->_aKeyAlias[$iColumnID] = 'tc_c';
					$this->_aKeyField[$iColumnID] = 'language';
					$sKey = "`tc_c`.`language`";									break;
				case 17: // Nationalität
				case 174: // Nationalität in %
					$this->_aKeyAlias[$iColumnID] = 'tc_c';
					$this->_aKeyField[$iColumnID] = 'nationality';
					$sKey = "`tc_c`.`nationality`";									break;
				case 18: // Status des Schülers
					$this->_aKeyAlias[$iColumnID] = 'ts_i';
					$this->_aKeyField[$iColumnID] = 'status_id';
					$sKey = "`ts_i`.`status_id`";									break;
				case 19: // Wie sind Sie auf uns aufmerksam geworden
					$this->_aKeyAlias[$iColumnID] = 'ts_i';
					$this->_aKeyField[$iColumnID] = 'referer_id';
					$sKey = "`ts_i`.`referer_id`";									break;
				case 21: // Agenturen
					$this->_aKeyAlias[$iColumnID] = 'ka';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ka`.`id`";											break;
				case 23: // Agenturkategorien
					$this->_aKeyAlias[$iColumnID] = 'ka';
					$this->_aKeyField[$iColumnID] = 'ext_39';
					$sKey = "`ka`.`ext_39`";										break;
				case 25: // Agenturgruppen
					$this->_aKeyAlias[$iColumnID] = 'kaga';
					$this->_aKeyField[$iColumnID] = 'group_id';
					$sKey = "`kaga`.`group_id`";									break;
				case 37: // Umsätze je Kurskategorie
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 38: // Umsätze je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 39: // Umsätze je Unterkunftskategorie
					$this->_aKeyAlias[$iColumnID] = 'ts_ija';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`ts_ija`.`accommodation_id`";							break;
				case 41: // Umsätze je generelle Kosten
					$this->_aKeyAlias[$iColumnID] = 'kidvi';
					$this->_aKeyField[$iColumnID] = 'type_id';
					$sKey = "`kidvi`.`type_id`";									break;
				case 42: // Umsätze je kursbezogene Kosten
					$this->_aKeyAlias[$iColumnID] = 'kidvi';
					$this->_aKeyField[$iColumnID] = 'type_id';
					$sKey = "`kidvi`.`type_id`";									break;
				case 48: // ø Kurspreis je Kurskategorie (Auflistung)
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 49: // ø Kurspreis je Kurs (Auflistung)
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 51: // ø Unterkunftspreis je Unterkunftskategorie
					$this->_aKeyAlias[$iColumnID] = 'ts_ija';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`ts_ija`.`accommodation_id`";							break;
				case 56: // Umsätze je unterkunftsbezogene Kosten
					$this->_aKeyAlias[$iColumnID] = 'kidvi';
					$this->_aKeyField[$iColumnID] = 'type_id';
					$sKey = "`kidvi`.`type_id`";									break;
				case 67: // Summe je angelegtem Steuersatz
					$this->_aKeyAlias[$iColumnID] = 'kidvi';
					$this->_aKeyField[$iColumnID] = 'tax_category';
					$sKey = "`kidvi`.`tax_category`";								break;
				case 68: // Kurswochen je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 69: // Kurswochen je Kurskategorie
				case 205: // Kurswochen je Kurskategorie (Erwachsene)
				case 206: // Kurswochen je Kurskategorie (Minderjährige)
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 71: // Unterkunftswochen je Unterkunftskategorie
					$this->_aKeyAlias[$iColumnID] = 'ts_ija';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`ts_ija`.`accommodation_id`";							break;
				case 72: // Unterkunftswochen je Unterkunft
					$this->_aKeyAlias[$iColumnID] = 'kr';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`kr`.`accommodation_id`";								break;
				case 74: // Anreise je Flughafen
					$this->_aKeyAlias[$iColumnID] = 'ts_ijt';
					$this->_aKeyField[$iColumnID] = 'start';
					$sKey = "`ts_ijt`.`start`";										break;
				case 75: // Abreise je Flughafen
					$this->_aKeyAlias[$iColumnID] = 'ts_ijt';
					$this->_aKeyField[$iColumnID] = 'start';
					$sKey = "`ts_ijt`.`start`";										break;
				case 76: // Anreise je Flughafen im Stundenrhytmus
					$this->_aKeyAlias[$iColumnID] = 'ts_ijt';
					$this->_aKeyField[$iColumnID] = 'start';
					$sKey = "`ts_ijt`.`start`";										break;
				case 77: // Abreise je Flughafen im Stundenrhytmus
					$this->_aKeyAlias[$iColumnID] = 'ts_ijt';
					$this->_aKeyField[$iColumnID] = 'start';
					$sKey = "`ts_ijt`.`start`";										break;
				case 78: // ø Kursdauer je Kurs in Wochen
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 79: // ø Kursdauer je Kurskategorie in Wochen
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
					$this->_aKeyAlias[$iColumnID] = 'kr';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`kr`.`accommodation_id`";								break;
				case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
					$this->_aKeyAlias[$iColumnID] = 'ts_ija';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`ts_ija`.`accommodation_id`";							break;
				case 84: // ø Alter Kunde je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 86: // ø Alter Kunde je Kurskategorie
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 87: // ø Alter Kunde je Unterkunftskategorie
					$this->_aKeyAlias[$iColumnID] = 'ts_ija';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`ts_ija`.`accommodation_id`";							break;
				case 91: // Versicherungssumme je Versicherung
					$this->_aKeyAlias[$iColumnID] = 'ts_iji';
					$this->_aKeyField[$iColumnID] = 'insurance_id';
					$sKey = "`ts_iji`.`insurance_id`";								break;
				case 114: // Kosten je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 115: // Kosten je Kurskategorie
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 137: // Schulen
					$this->_aKeyAlias[$iColumnID] = 'cdb2';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`cdb2`.`id`";											break;
				case 145: // Verdienst je Kurskategorie
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 146: // Verdienst je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 154: // Umsatz je Unterkunftsanbieter
					$this->_aKeyAlias[$iColumnID] = 'kr';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`kr`.`accommodation_id`";								break;
				case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					$this->_aKeyAlias[$iColumnID] = 'kr';
					$this->_aKeyField[$iColumnID] = 'accommodation_id';
					$sKey = "`kr`.`accommodation_id`";								break;
				case 161: // Schulen / Inboxen
					// Ist eigentlich für untere Spalten da, muss aber trotzdem immer da sein…
					$this->_aKeyAlias[$iColumnID] = 'cdb2';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "CONCAT(`cdb2`.`id`, '_', `k_inb`.`id`)";
					break;
				case 166: // Kurswochen je Kurs
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktc`.`id`";											break;
				case 167: // Kurswochen je Kurskategorie
					$this->_aKeyAlias[$iColumnID] = 'ktc';
					$this->_aKeyField[$iColumnID] = 'category_id';
					$sKey = "`ktc`.`category_id`";									break;
				case 191: // Schüler pro internem Niveau (exkl. Storno)
					$this->_aKeyAlias[$iColumnID] = 'ktl_internal';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`ktl_internal`.`id`";
					break;
				case 193: // Gruppen
					$this->_aKeyAlias[$iColumnID] = 'ts_i';
					$this->_aKeyField[$iColumnID] = 'group_id';
					$sKey = "`ts_i`.`group_id`";
					break;
				case 203: // Vertriebsmitarbeiter
					$this->_aKeyAlias[$iColumnID] = 'ts_i';
					$this->_aKeyField[$iColumnID] = 'sales_person_id';
					$sKey = "`ts_i`.`sales_person_id`";
					break;
				case 204: // Agenturen / Inboxen
					$this->_aKeyAlias[$iColumnID] = 'ka';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "CONCAT(IFNULL(`ka`.`id`, 0), '_', `k_inb`.`id`)";
					break;
				case 207: // Vertriebsmitarbeiter (Anfragen)
					$this->_aKeyAlias[$iColumnID] = 'ts_e';
					$this->_aKeyField[$iColumnID] = 'sales_person_id';
					$sKey = "`ts_e`.`sales_person_id`";
					break;
				case 208: // Inbox
					$this->_aKeyAlias[$iColumnID] = 'k_inb';
					$this->_aKeyField[$iColumnID] = 'id';
					$sKey = "`k_inb`.`id`";
					break;
				default:
					continue 2;
			}

			$this->_setNeededParts($iColumnID);

			$aTempUsedParts = $this->_aUsedParts;

			$sQueryCopy = $sSQL;

			$sQueryCopy = str_replace(
				'{WHERE}',
				" AND " . $sKey . " IS NOT NULL AND " . $sKey . " != '' {WHERE} ",
				$sQueryCopy
			);

			$sKeyColumn = $sGroupBy = $sKey;

			$sKey .= " AS `key` ";

			// Standardmäßig wird nach Totale sortiert, was die manchmal wilde Sortierung erklärt
			$sOuterOrderBy = " `total` DESC ";

			switch($this->_oStatistic->columns['max_by'][$iColumnID])
			{
				/*case 1: // Gesamtumsatz
				{
					$sSelect = $this->_addDefaultSumPart() . "AS `total_count`, " . $sKey;

					$sGroupBy = "`kidvi`.`id`";

					break;
				}
				case 2: // Nettoumsatz
				{
					$sSelect = $this->_addDefaultSumPart() . "AS `total_count`, " . $sKey;

					$sGroupBy = "`kidvi`.`id`";

					break;
				}
				case 4: // Anzahl Kurswochen
				{
					$this->_aNeededParts['tuition'] = 1;

					$sSelect = "
						calcWeeksFromCourseDates(:FROM, :TILL, `ts_ijc`.`from`, `ts_ijc`.`until`)
						AS `total_count`, " . $sKey;

					$sGroupBy = "`ts_ijc`.`id`";

					break;
				}*/
				default:
				{
					if($this->_oStatistic->period == 5) // Based on enquiries
					{
						$sSelect = " COUNT(DISTINCT `ts_e`.`id`) AS `total_count`, " . $sKey;
					}
					else // Other types are based on inquiries
					{
						$sSelect = " COUNT(DISTINCT `ts_i`.`id`) AS `total_count`, " . $sKey;
					}
				}
			}

			switch($iColumnID)
			{
				case 17: // Nationalitäten
				case 174: // Nationalität in %
					$sGroupBy = $sKeyColumn; // Ticket R-#4906
					break;
				case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
				{
					$aCache40 = array();

					foreach($this->_aFilterSchools as $iSchoolID)
					{
						$oSchool	= Ext_Thebing_School::getInstance($iSchoolID);
						$oAcc		= new Ext_Thebing_Accommodation_Util($oSchool, $this->_oSchool->getLanguage());
						$aAccCats	= (array)$oAcc->getAccommodationCategorieList();

						foreach($aAccCats as $aAccCat)
						{
							$aRoomMealCombi = array();
							$oAcc->setAccommodationCategorie($aAccCat['id']);
							$aRooms = $oAcc->getRoomtypeList();

							foreach ((array)$aRooms as $aRoom)
							{
								$aMeals = explode(',', $aRoom['meal']);
								$oAcc->setRoomtype($aRoom);

								foreach ((array)$aMeals as $iMeal)
								{
									if($aRoomMealCombi[$aRoom['id']][$iMeal] == 1)
									{
										continue;
									}

									$aRoomMealCombi[$aRoom['id']][$iMeal] = 1;
									$oAcc->setMealById($iMeal);

									$sCacheKey = $aAccCat['id'] . '_' . $oAcc->getRoomtypeId() . '_' . $oAcc->getMealId();
									$sCacheValue = $oAcc->getCategoryName(true) . ' ' . $oAcc->getRoomtypeName() . '/' . $oAcc->getMealName();

									$aCache40[] = array('key'	=> $sCacheKey, 'value' => $sCacheValue);
								}
							}
						}
					}

					break;
				}
				case 41: // Umsätze je generelle Kosten
				case 42: // Umsätze je kursbezogene Kosten
				case 56: // Umsätze je unterkunftsbezogene Kosten
				case 67: // Summe je angelegtem Steuersatz
				{
					$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

					switch($iColumnID)
					{
						case 41: // Umsätze je generelle Kosten
						case 42: // Umsätze je kursbezogene Kosten
						case 56: // Umsätze je unterkunftsbezogene Kosten
							$sQueryCopy = str_replace(
								'{DOCUMENTS_JOIN_ADDON}',
								" AND `ts_i`.`canceled` = 0 AND `kid`.`type` IN(:aDocumentTypes) {DOCUMENTS_JOIN_ADDON} ",
								$sQueryCopy
							);
							break;
						default:
							$sQueryCopy = str_replace(
								'{DOCUMENTS_JOIN_ADDON}',
								" AND `ts_i`.`canceled` = 0 AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
								$sQueryCopy
							);
					}

					$sGroupBy = "`kidvi`.`id`, " . $sGroupBy;

					switch($iColumnID)
					{
						case 41: // Umsätze je generelle Kosten
							$sAdd = " AND `kidvi`.`type` IN('additional_general') {WHERE} ";			break;
						case 42: // Umsätze je kursbezogene Kosten
							$sAdd = " AND `kidvi`.`type` IN('additional_course') {WHERE} ";			break;
						case 56: // Umsätze je unterkunftsbezogene Kosten
							$sAdd = " AND `kidvi`.`type` IN('additional_accommodation') {WHERE} ";	break;
						case 67: // Summe je angelegtem Steuersatz
							$sAdd = " AND `kidvi`.`tax_category` > 0 {WHERE} ";						break;
					}

					$sQueryCopy = str_replace('{WHERE}', $sAdd, $sQueryCopy);

					$sSelect = $this->_addDefaultSumPart() . "AS `total_count`, " . $sKey;

					break;
				}
				case 38: // Umsätze je Kurs
				case 49: // ø Kurspreis je Kurs (Auflistung)
					$sGroupBy = "`kidvi`.`id`, " . $sKeyColumn;
					break;
				case 68: // Kurswochen je Kurs
				case 78: // ø Kursdauer je Kurs in Wochen
				case 84: // ø Alter Kunde je Kurs
				case 166: // Anzahl der Schüler je Kurs
					$sGroupBy = "`ts_ijc`.`id`, " . $sKeyColumn;
					break;
				case 37: // Umsätze je Kurskategorie
				case 48: // ø Kurspreis je Kurskategorie (Auflistung)
					$sGroupBy = "`kidvi`.`id`, " . $sKeyColumn;
					break;
				case 69: // Kurswochen je Kurskategorie
				case 79: // ø Kursdauer je Kurskategorie in Wochen
				case 86: // ø Alter Kunde je Kurskategorie
				case 167: // Anzahl der Schüler je Kurskategorie
				case 191: // Schüler pro internem Niveau (exkl. Storno)
				case 205: // Kurswochen je Kurskategorie (Erwachsene)
				case 206: // Kurswochen je Kurskategorie (Minderjährige)
					$sGroupBy = "`ts_ijc`.`id`, " . $sKeyColumn;

					// Niveaus nach Kürzel sortieren
					if($iColumnID == 191) {
						$sSelect .= ", `ktl_internal`.`name_short` ";
						$sOuterOrderBy = " `x`.`name_short` ASC ";
					}

					break;
				case 39: // Umsätze je Unterkunftskategorie
				case 51: // ø Unterkunftspreis je Unterkunftskategorie
					$sGroupBy = "`kidvi`.`id`, " . $sKeyColumn;
					break;
				case 71: // Unterkunftswochen je Unterkunftskategorie
				case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
				case 87: // ø Alter Kunde je Unterkunftskategorie
					$sGroupBy = "`ts_ija`.`id`, " . $sKeyColumn;
					break;
				case 72: // Unterkunftswochen je Unterkunft
				case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
					$sGroupBy = "`ts_ija`.`id`, " . $sKeyColumn;
					break;
				case 74: // Anreise je Flughafen
				case 76: // Anreise je Flughafen im Stundenrhytmus
				{
					$sTempWhere = "
						AND `ts_ijt`.`transfer_type` = 1
						AND `ts_ijt`.`start_type` = 'location'	
					";

					$sQueryCopy = str_replace('{WHERE}', $sTempWhere . " {WHERE} ", $sQueryCopy);

					$sGroupBy = "`ts_ijt`.`id`, " . $sKeyColumn;

					break;
				}
				case 75: // Abreise je Flughafen
				case 77: // Abreise je Flughafen im Stundenrhytmus
				{
					$sTempWhere = "
						AND `ts_ijt`.`transfer_type` = 2
						AND `ts_ijt`.`start_type` = 'location'	
					";

					$sQueryCopy = str_replace('{WHERE}', $sTempWhere . " {WHERE} ", $sQueryCopy);

					$sGroupBy = "`ts_ijt`.`id`, " . $sKeyColumn;

					break;
				}
				case 91: // Versicherungssumme je Versicherung
					$sGroupBy = "`ts_iji`.`id`, " . $sKeyColumn;
					break;
				case 114: // Kosten je Kurs
				case 115: // Kosten je Kurskategorie
				{
					$sJoin = "
							INNER JOIN
						`ts_teachers_payments` AS `ktp` ON
							(
								`ktb`.`week` <= :TILL AND
								(`ktb`.`week` + INTERVAL 6 DAY) >= :FROM
							) AND
							`ktb`.`id` = `ktp`.`block_id` AND `ktp`.`active` = 1 AND
							`ktp`.`timepoint` <= :TILL AND
							IF(
								`ktp`.`payment_type` = 'week' OR `ktp`.`payment_type` = 'fix_week',
								(`ktp`.`timepoint` + INTERVAL 6 DAY) >= :FROM,
								(`ktp`.`timepoint` + INTERVAL 1 MONTH - INTERVAL 1 DAY) >= :FROM
							)
					";

					$sSelect = "
						(
							getSubAmountByDates(
								`ktp`.`amount`,
								:FROM,
								:TILL,
								`ktp`.`timepoint`,
								IF(
									`ktp`.`payment_type` = 'week' OR `ktp`.`payment_type` = 'fix_week',
									(`ktp`.`timepoint` + INTERVAL 6 DAY),
									(`ktp`.`timepoint` + INTERVAL 1 MONTH - INTERVAL 1 DAY)
								)
							)
						) AS `total_count`, " . $sKey;

					$sGroupBy = "`ktp`.`id`, " . $sKeyColumn;

					$sQueryCopy = str_replace('{JOINS}', " {JOINS} " . $sJoin, $sQueryCopy);

					break;
				}
				case 137: // Schulen
				case 193: // Gruppen
				case 203: // Vertriebsmitarbeiter
				case 207: // Vertriebsmitarbeiter (Anfragen)
					$sGroupBy .= ", " . $sKeyColumn;
					break;
				case 145: // Verdienst je Kurskategorie
				case 146: // Verdienst je Kurs
				{
					$aSQL['aDocumentTypes'] = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

					$sQueryCopy = str_replace(
						'{DOCUMENTS_JOIN_ADDON}',
						" AND `kid`.`type` IN(:aDocumentTypes, 'creditnote') {DOCUMENTS_JOIN_ADDON} ",
						$sQueryCopy
					);

					switch($iColumnID)
					{
						case 145: // Verdienst je Kurskategorie
							$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
							$sTempKey = "`ktc`.`category_id` AS `key`, ";
							break;
						case 146: // Verdienst je Kurs
							$sType = " AND `kidvi`.`type` IN('course', 'additional_course') ";
							$sTempKey = "`ktc`.`id` AS `key`, ";
							break;
					}

					$sQueryCopy = str_replace('{ITEMS_JOIN_ADDON}', $sType . " {ITEMS_JOIN_ADDON} ", $sQueryCopy);

					$sGroupBy = "`kidvi`.`id`, `query_kTEp_group`, " . $sKeyColumn;

					$sQueryCopy = str_replace(
						'{JOINS}',
						"
							LEFT JOIN
								`ts_teachers_payments` AS `kTEp` ON
									`kt`.`id` = `kTEp`.`teacher_id` AND
									`kTEp`.`active` = 1 AND
									`kTEp`.`timepoint` BETWEEN :FROM AND :TILL
							{JOINS}
						",
						$sQueryCopy
					);

					$sSelect = "
						`kidvi`.`id` AS `unique`,
						`kTEp`.`id` AS `query_kTEp_group`,
						calcAmountByCurrencyFactors(
							`kTEp`.`amount`,
							`kTEp`.`payment_currency_id`,
							`kTEp`.`date`,
							" . $this->_iCurrencyID . ",
							`kTEp`.`date`
						) AS `teacher_pay`,
					";

					if(
						$this->_oStatistic->period == 1 ||	// Buchungsdatum
						$this->_oStatistic->period == 5		// Anfrage
					)
					{
						$sSelect .= $sTempKey . " (" . $this->_addDefaultSumPart() . ")";
					}
					else
					{
						$sSelect .= $sTempKey . "
							(
								
								IF(
									(
										`kidvi`.`index_from` BETWEEN :FROM AND :TILL OR
										`kidvi`.`index_until` BETWEEN :FROM AND :TILL
									),
									(
										getSubAmountByDates(
											(" . $this->_addDefaultSumPart() . "),
											:FROM, :TILL, `kidvi`.`index_from`, `kidvi`.`index_until`
										)
									),
									0
								)
							) AS `result`
						";
					}

					break;
				}
				case 154: // Umsatz je Unterkunftsanbieter
				case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
					$sGroupBy = "`kidvi`.`id`, " . $sKeyColumn;
					break;
				case 161: // Schulen / Inboxen
					// Sortieren nach Schulname / Inbox-Name
					$sSelect .= ", `cdb2`.`ext_1` `school_name`, `k_inb`.`name` `inbox_name`";
					$sOuterOrderBy = " `x`.`school_name`, `x`.`inbox_name` ";
					break;
				case 204: // Agenturen / Inboxen
					// Sortieren nach Agenturname / Inbox-Name
					$sSelect .= ", `ka`.`ext_1` `agency_name`, `k_inb`.`name` `inbox_name`";
					$sOuterOrderBy = " `x`.`agency_name`, `x`.`inbox_name` ";
					break;
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			if(isset($aColumns['settings'][$iColumnID]) && $aColumns['settings'][$iColumnID] > 0)
			{
				$iLimit = (int)$aColumns['settings'][$iColumnID];
			}
			else
			{
				$iLimit = 9999999;
			}

			$sLimit = " LIMIT " . $iLimit;

			$sWhere = "";

			if($this->_oStatistic->type == 1) // If relative: Add periods times
			{
				$sWhere = $this->_addPeriodsTimes($aSQL);
			}

			$aSearch	= array('{SELECT}', '{WHERE}', '{GROUP_BY}', '{PERIOD}');
			$aReplace	= array($sSelect, $sWhere, $sGroupBy, 0);

			$sQueryCopy = str_replace($aSearch, $aReplace, $sQueryCopy);

			$this->_addNeededParts($sQueryCopy, true);

			// Kill other placeholder
			$sQueryCopy = str_replace($this->_aQueryPlaceholder, '', $sQueryCopy);

			$oFrom = new WDDate($this->_iMinTime);
			$oTill = new WDDate($this->_iMaxTime);

			$oFrom->set('00:00:00', WDDate::TIMES);
			$oTill->set('23:59:59', WDDate::TIMES);

			$aSQL['FROM_DATETIME'] = $oFrom->get(WDDate::DB_DATETIME);
			$aSQL['TILL_DATETIME'] = $oTill->get(WDDate::DB_DATETIME);

			$aSQL['FROM'] = $oFrom->get(WDDate::DB_DATE);
			$aSQL['TILL'] = $oTill->get(WDDate::DB_DATE);

			if($iColumnID == 40) // HOOK for "Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)"
			{
				$aCounts = $aCache40;
			}
			else
			{
				$sQuery = "
					SELECT
						$iColumnID AS `XXX`,
						`x`.*,
						SUM(`x`.`total_count`) AS `total`
					FROM
						(
							" . $sQueryCopy . "
						) AS `x`
					GROUP BY
						`x`.`key`
					ORDER BY
						{$sOuterOrderBy}
					" . $sLimit . "
				";

//				if($iColumnID == 145 || $iColumnID == 146)
//				{
//					$sQuery = "
//						SELECT
//							`x`.*,
//							GROUP_CONCAT(
//								DISTINCT
//								IF(`x`.`result` != 0, CONCAT(`x`.`unique`, '_', `x`.`result`), NULL)
//							) AS `result`,
//							GROUP_CONCAT(
//								DISTINCT CONCAT(`x`.`query_kTEp_group`, '_', `x`.`teacher_pay`)
//							) AS `teacher_pay`
//						FROM
//						(
//							" . $sQueryCopy . "
//						) AS `x`
//						GROUP BY
//							`x`.`query_group_id`,
//							`x`.`query_group_key`,
//							`x`.`key`
//					";
//				}

				$iMicroTime = microtime(true);

				try {
					$aCounts = DB::getPreparedQueryData($sQuery, $aSQL);
				} catch(DB_QueryFailedException $e) {
					$sColumn = "Statistic-ID: ".$this->_oStatistic->id."\nColumn-ID: ".$iColumnID."\n\n";
					throw new RuntimeException($sColumn.$e->getMessage());
				}

				$this->_aUsedParts = $aTempUsedParts;
				
				if(isset($_REQUEST['stats_debug'])) {
					self::$_aDebug['Prepare_Queries_Total_Time'] += microtime(true) - $iMicroTime;
					self::$_aDebug['Prepare_Queries_Counter']++;
					self::$_aDebug['Prepare_Queries_Single_Times'][$iColumnID] = microtime(true) - $iMicroTime;
					self::$_aDebug['Prepare_Queries_Query'][$iColumnID] = DB::getDefaultConnection()->getLastQuery();
				}

				if(
					$iColumnID == 145 || 
					$iColumnID == 146
				) {

					$aResults = $aCounts;

					$aIn = $aOut = $aCounts = $aData = array();

					foreach($aResults as $aResult)
					{
						$aTemp = explode(',', $aResult['teacher_pay']);

						foreach($aTemp as $sTempOut)
						{
							$aTempOut = explode('_', $sTempOut);

							$aOut[$aResult['key']][$aTempOut[0]] = $aTempOut[1];
						}

						$aTemp = explode(',', $aResult['result']);

						foreach($aTemp as $sTempIn)
						{
							$aTempIn = explode('_', $sTempIn);

							$aIn[$aResult['key']][$aTempIn[0]] = $aTempIn[1];
						}
					}

					foreach($aIn as $iKey => $aIns)
					{
						$aData[$iKey] += array_sum($aIns);
						$aData[$iKey] -= array_sum($aOut[$iKey]);
					}

					arsort($aData);

					foreach($aData as $iKey => $iValue)
					{
						if($iValue)
						{
							$aCounts[$iKey] = array(
								'key' => $iKey
							);
						}

						if(count($aCounts) >= $iLimit)
						{
							break;
						}
					}
				}
			}

			if(empty($aCounts))
			{
				$aCounts = array(array('key' => 0));
			}

			foreach($aCounts as $aCount)
			{
				$this->_aColumnsSettings[$iColumnID][$aCount['key']] = $aCount['key'];

				if(!is_numeric($aCount['key']))
				{
					$this->_aColumnsSettings[$iColumnID][$aCount['key']] = "'" . $aCount['key'] . "'";
				}
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			if(in_array($iColumnID, (array)$aColumns['groups']))
			{
				$aAlias = array(
					15	=> '`tc_a`.`country_iso`',		// Land
					16	=> '`tc_c`.`language`',			// Muttersprache
					17	=> '`tc_c`.`nationality`',		// Nationalität
					18	=> '`ts_i`.`status_id`',		// Status des Schülers
					19	=> '`ts_i`.`referer_id`',		// Wie sind Sie auf uns aufmerksam geworden
					21	=> '`ka`.`id`',					// Agenturen
					23	=> '`ka`.`ext_39`',				// Agenturkategorien
					25	=> '`kaga`.`group_id`',			// Agenturgruppen
					137	=> '`cdb2`.`id`',				// Schulen
					161 => "CONCAT(`cdb2`.`id`, '_', `k_inb`.`id`)", // Schulen / Inboxen
					174 => '`tc_c`.`nationality`',		// Nationalität in %
					191 => '`ktl_internal`.`id`', 		// Schüler pro internem Niveau (exkl. Storno)
					193 => '`ts_i`.`group_id`',			// Gruppen
					203 => '`ts_i`.`sales_person_id`',	// Vertriebsmitarbeiter
					204 => "CONCAT(IFNULL(`ka`.`id`, 0), '_', `k_inb`.`id`)", // Agenturen / Inboxen
					207 => '`ts_e`.`sales_person_id`',	// Vertriebsmitarbeiter (Anfragen)
				);

				// Zweite Gruppierungsebene
				/*
				 * Achtung: Es gibt Spalten (z.B. Schulen), die haben nochmals eine eigene Untergruppierung!
				 * Die Statistik bietet momentan keine 5. Ebene dafür und somit MÜSSEN beim Einbau einer neuen zweiten
				 * Gruppierungsebene bei den Abhängigkeiten der Spalten zueinander alle Spalten exkludiert werden,
				 * die selber settings haben!
				 *
				 * Alternativ können alle Checkboxen ausgewählt werden und dann kann dieser Query ausgeführt werden:

					-- Achtung, muss auch andersrum ausgeführt werden!
					DELETE FROM
						`kolumbus_statistic_cols_definitions_access`
					WHERE `x_id` = 161 AND `y_id` IN (
							SELECT
								`id`
							FROM
								`kolumbus_statistic_cols_definitions`
							WHERE settings = 1
						)
				*/
//				$aSubGroups = array(
//					161 => '`k_inb`.`id`'				// Schulen / Inboxen
//				);

				$sWhere = "";

				foreach((array)$this->_oStatistic->columns['groups'] as $iColumnID)
				{
					if(isset($this->_aColumnsSettings[$iColumnID]) && !empty($this->_aColumnsSettings[$iColumnID]))
					{

						$this->_setNeededParts($iColumnID);

						$sWhere .= "
							AND " . $aAlias[$iColumnID] . "
								IN (" . implode(',', $this->_aColumnsSettings[$iColumnID]) . ")
						";

						$sSQL = str_replace(
							'{GROUP_BY}',
							$aAlias[$iColumnID] . ", {GROUP_BY} ",
							$sSQL
						);

						$sSQL = str_replace(
							'{SELECT}',
							" " . $aAlias[$iColumnID] . " AS `query_group_id`, " . $iColumnID . " AS `query_group_key`, {SELECT} ",
							$sSQL
						);

						// Zweite Gruppierungsebene
//						if(isset($aSubGroups[$iColumnID])) {
//							$sSQL = str_replace(
//								'{SELECT}',
//								$aSubGroups[$iColumnID] . " AS `query_sub_group_id`, {SELECT}  ",
//								$sSQL
//							);
//
//							$sSQL = str_replace(
//								'{GROUP_BY}',
//								$aSubGroups[$iColumnID] . ", {GROUP_BY} ",
//								$sSQL
//							);
//
//							// Markierung für das redundante $this->_sUseAsFrom
//							$this->_bHasAnySubGroup = true;
//						}

					}
				}

				$this->_addNeededParts($sSQL);
				
				$sSQL = str_replace('{WHERE}', $sWhere . " {WHERE}", $sSQL);

				$bQueryHasGroupID = true;
			}

			if(!$bQueryHasGroupID)
			{
				$sSQL = str_replace(
					'{SELECT}',
					" NULL AS `query_group_id`, NULL AS `query_group_key`, {SELECT} ",
					$sSQL
				);

				$bQueryHasGroupID = true;
			}
		}

		self::$_aDebug['Prepare_Total_Time'] = microtime(true) - $iTime;
	}

	/**
	 * Write results table
	 * 
	 * @param array $aData
	 * @param array $aLabels
	 * @return string
	 */
	protected function _writeResultsTable($aData, $aLabels) {

		$this->_oSmarty->assign('aData', $aData);
		$this->_oSmarty->assign('aLabels', $aLabels);
		$this->_oSmarty->assign('iListType', $this->_oStatistic->list_type);

		$this->_oSmarty->assign('aColors', Ext_Thebing_Management_Statistic_Gui2::getColumnColorsByID());

		if(!empty($this->_oStatistic->columns['groups']))
		{
			$this->_oSmarty->assign('bWithGroups', true);
		}
		else
		{
			$this->_oSmarty->assign('bWithGroups', false);
		}

		$sCode = $this->_oSmarty->fetch($this->getTemplatePath() . 'result.tpl');

		// Clear code for javascript
		$sCode = str_replace(array("\t", "\n", "\r"), '', $sCode);

		return $sCode;
	}

	/**
	 * Da der »QueryBuilder« dieser Klasse so wundervoll prozedural arbeitet:
	 *	Versuchen, einen Query für eine Spalte unabhängig von dieser Schleife zu erzeugen
	 *
	 * @param $iColumnId
	 * @param $aSql
	 * @return string
	 */
	protected function getColumnQuery($iColumnId, $aSql) {

		$aOriginalUsedParts = $this->_aUsedParts;
		$this->_aUsedParts = array();

		$sSubSql = $this->_createQuery($this->_aFilter, $aSql);
		$sSubSql = str_replace('{PERIOD}', 0, $sSubSql);
		$this->_createSelect($iColumnId, $sSubSql, $aSql);
		$this->_setNeededParts($iColumnId);
		$this->_addNeededParts($sSubSql);
		$sSubSql = str_replace($this->_aQueryPlaceholder, '', $sSubSql);

		$this->_aUsedParts = $aOriginalUsedParts;

		return $sSubSql;

	}
}
