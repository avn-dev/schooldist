<?php

$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

if($iSessionSchoolId <= 0) {

	$oSchool = Ext_Thebing_Client::getFirstSchool($sAccess);

	if($oSchool){
		$aSchoolIds = array($oSchool->id);
	} else {
		die('No Schools');
	}

} else {
	$oSchool = Ext_Thebing_School::getSchoolFromSession();
}

// Nicht immer vorhanden daher neu holen
global $user_data;
$oClient = Ext_Thebing_System::getClient();

if(
	$sView == 'inbox' ||
	$sView == 'client_payment' ||
	$sView == 'proforma'
) {
	$oDate = new WDDate();
	$oDate->add(1, WDDate::DAY);
	$iNow = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate->sub(12, WDDate::MONTH);
	$iLastYear = (int)$oDate->get(WDDate::TIMESTAMP);
} elseif($sView=='visum_list') {
	$oDate = new WDDate();
	$oDate->add(6, WDDate::MONTH);
	$iNow = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate->sub(8, WDDate::MONTH);
	$iLastYear = (int)$oDate->get(WDDate::TIMESTAMP);
} else if($sView == 'agency_payment') {
	$oDate = new WDDate();
	$oDate->add(1, WDDate::YEAR);
	$iNow = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate = new WDDate();
	$oDate->sub(2, WDDate::MONTH);
	$iLastYear = (int)$oDate->get(WDDate::TIMESTAMP);
} elseif($sView == 'transfer') {
	$oDate = new WDDate();
	$oDate->sub((abs($oDate->get(WDDate::DAY_OF_WEEK)-6) * -1 + 28), WDDate::DAY);
	$iFilterStart = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate->add(8 * 7, WDDate::DAY);
	$iFilterEnd = (int)$oDate->get(WDDate::TIMESTAMP);
} elseif($sView=='progress_report') {
	$oDate = new WDDate();
	$oDate->add(2, WDDate::MONTH);
	$iNow = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate->sub(4, WDDate::MONTH);
	$iLastYear = (int)$oDate->get(WDDate::TIMESTAMP);
} else {
	$oDate = new WDDate();
	$oDate->sub((abs($oDate->get(WDDate::DAY_OF_WEEK)-6) * -1 + 7), WDDate::DAY);
	$iFilterStart = (int)$oDate->get(WDDate::TIMESTAMP);
	$oDate->add(13, WDDate::DAY);
	$iFilterEnd = (int)$oDate->get(WDDate::TIMESTAMP);
}

if(!isset($oGui)) {

	if(!isset($sHash)) {
		$sHash = md5($sAccess);
	}

	$sDataClass = 'Ext_Thebing_Inquiry_Gui2';
	if($sView == 'transfer') {
		$sDataClass = 'Ext_TS_Pickup_Gui2_Data';
	}
	
	// @TODO Entfernen, wenn Inquiry vollständig auf Gui2-Config basiert
	if($sView === 'inbox') {
		$oGui = new Ext_TS_Inquiry_Gui2($sHash, $sDataClass);
	} else {
		$oGui = new Ext_Thebing_Gui2($sHash, $sDataClass);
	}

}

if(!isset($bAddIconAdditionalFiles)) {
	$bAddIconAdditionalFiles	= false;
}

if(
	!isset($bWDSearch)
){
	$bWDSearch					= true;
}

$sGuiDescription = 'Thebing » Invoice » Inbox';

$oGui->setWDBasic('Ext_TS_Inquiry');
$oGui->setTableData('limit', 30);

switch($sOrderByOption){
	
	case 'created':
		$oGui->setTableData('orderby', array('id' => 'DESC'));
		break;
	case 'departure':
		$oGui->setTableData('orderby', array('departure_day' => 'ASC'));
		break;
	case 'visum':
		$oGui->setTableData('orderby', array('visum_date_until' => 'DESC'));
		break;
	default:
		
		$oGui->setTableData('orderby', array(
			'inquiry_course_from'			=> 'ASC',
			'inquiry_accommodation_from'	=> 'ASC',
			'created'						=> 'DESC'
		));
		break;
}

// Listen Optionen

$oGui->column_sortable				= 1; // es geht nur eine sortierart!
$oGui->row_sortable					= 0; // es geht nur eine sortierart! ( hat prioritär )
$oGui->row_style					= new Ext_Thebing_Gui2_Style_InboxRow(); // Klasse zum formatieren der Zeilen
$oGui->multiple_selection			= 0;
$oGui->access						= $sAccess;

if($bAddIconAdditionalFiles) {
	$oGui->multiple_selection		= 1;
}
$oGui->query_id_column				= 'id';

if($sView == 'transfer') {
	$oGui->query_id_alias			= 'kit';
} else {
	$oGui->query_id_alias			= 'ki';
}

if($sView == 'transfer') {
	$oGui->row_icon_status_visible = new Ext_TS_Pickup_Gui2_Icon_Visible();
	$oGui->row_icon_status_active = new Ext_TS_Pickup_Gui2_Icon_Active('transfer_inquiry_id');
} else {
	$oGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Inbox(false);
}

$oGui->include_jquery				= true;
$oGui->include_jquery_multiselect	= true;

$oGui->calendar_format				= new Ext_Thebing_Gui2_Format_Date();
$oGui->class_js						= 'StudentlistGui';
$oGui->sSection						= 'student_record';
$oGui->sView						= (string)$sView;

if($sView == 'inbox') {
	$oGui->showLeftFrame = false;
}

if($sView == 'student_cards') {
	$oGui->multiple_pdf_class = new Ext_Thebing_Gui2_Pdf_Cards('document_student_cards');
}

include_once(Util::getDocumentRoot()."system/legacy/admin/extensions/thebing/studentlists.php");

## START DIALOG TAB ##
$oDialog = Ext_TS_Inquiry_Index_Gui2_Data::getDialog($sView, $oGui);
## ENDE DIALOG TAB ##


# START - Leiste 1 #
$oBar = $oGui->createBar();
$oBar->width = '100%';

	#$oBar->addWDSearch();

	// Suchfilter
	$aColumnSearch = array('lastname', 'firstname', 'number', 'document_number', 'short', 'name', 'ext_1', 'ext_2', 'email', 'number');
	$aAliasSearch = array('cdb1', 'cdb1', 'tc_c_n', 'kid_filter', 'kg', 'kg', 'ka', 'ka', 'tc_e', 'ka_n');

	if($sView == 'transfer'){
		$aColumnSearch = array_merge($aColumnSearch, array('airline', 'flightnumber'));
		$aAliasSearch = array_merge($aAliasSearch, array('kit', 'kit'));
	}

	$oFilter = $oBar->createFilter();
	$oFilter->db_column = $aColumnSearch;
	$oFilter->db_alias = $aAliasSearch;
	$oFilter->id = 'search';
	$oFilter->placeholder = $oGui->t('Suche').'…';
	$oBar ->setElement($oFilter);

	if(
		$sView == 'inbox' ||
		$sView == 'progress_report' || 
		$sView == 'agency_payment' ||
		$sView == 'proforma' ||
		$sView == 'visum_list'
	){
		$oBar->setElement($oBar->createSeperator());

		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('created'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('ki');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iLastYear); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iNow); // Standart Wert
		$oFilter->search_type		= 'between';
		// Query wird übersprungen, da das DD die Filterung übernimmt
		$oFilter->skip_query		= true;
		$oFilter->query_value_key	= 'filter_student_period';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

	} else if(
		$sView == 'agency_payment'
	){

		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('from', 'until', 'from', 'until'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('kic', 'kic', 'kia', 'kia');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iLastYear); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iNow); // Standart Wert
		$oFilter->search_type		= 'between';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

		$oBar->setElement($oBar->createSeperator());

	} else if($sView == 'client_payment') {

		$oBar->setElement($oBar->createSeperator());

		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('amount_finalpay_due'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('ki');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iLastYear); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iNow); // Standart Wert
		$oFilter->search_type		= 'between';
		$oFilter->filter_part		= 'where';
		#$oFilter->data_function		= 'FROM_UNIXTIME';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

		$oBar->setElement($oBar->createSeperator());

	} else if($sView == 'departure_list') {

		$oBar->setElement($oBar->createSeperator());

		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('last_course_end');
		$oFilter->filter_part		= 'having';

		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->search_type		= 'between';
		// Query wird übersprungen, da das DD die Filterung übernimmt
		$oFilter->skip_query		= true;
		$oFilter->query_value_key	= 'filter_student_period';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

	} else if($sView == 'arrival_list') {

		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('first_course_start');
		$oFilter->filter_part		= 'having';
		 
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->search_type		= 'between'; //contact
		// Query wird übersprungen, da das DD die Filterung übernimmt
		$oFilter->skip_query		= true;
		$oFilter->query_value_key	= 'filter_student_period';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

		

	} else if($sView == 'transfer'){

		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('transfer_date'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('kit');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->search_type		= 'between';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oBar ->setElement($oFilter);

		$sHtml = '<span>'.$oGui->t('basierend auf').' '.$oGui->t('Anreisedatum').'</span>';
		$oHtml = $oBar->createHtml($sHtml);
		$oBar->setElement($oHtml);

		$oBar->setElement($oBar->createSeperator());

	} else if(
		$sView == 'student_cards'
	){
		// Filter auf dern Service Start und nicht den gesammten Leistungszeitraum
		// #2298
		$oBar->setElement($oBar->createSeperator());

		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('from', 'from', 'service_from'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('kic', 'kia', 'ki');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->search_type		= 'between';
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oFilter->query_value_key = 'filter_student_period';
		$oFilter->skip_query = true;
		$oBar ->setElement($oFilter);

		$aFilterTypes = array(
			'service_start' => $oGui->t('Leistungsstart'),
			'absolute_service_start' => $oGui->t('Absoluter Leistungsstart')
		);

		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'time_interval_filter';
		$oFilter->value = 'service_start';
		$oFilter->label	= $oGui->t('basierend auf');
		$oFilter->select_options = $aFilterTypes;
		$oFilter->filter_query = array(
			'service_start' => "
				(
					DATE(COALESCE(`kic`.`from`, 0000-00-00)) >= :filter_student_period_from  AND
					DATE(COALESCE(`kic`.`from`, 0000-00-00)) <= :filter_student_period_from
				) OR (
					DATE(COALESCE(`kia`.`from`, 0000-00-00)) >= :filter_student_period_from AND
					DATE(COALESCE(`kia`.`from`, 0000-00-00)) <= :filter_student_period_from
				) OR (
					DATE(COALESCE(`ki`.`service_from`, 0000-00-00)) >= :filter_student_period_from
				)
			",
			'absolute_service_start' => "
				 DATE(COALESCE(`ki`.`service_from`, 0000-00-00)) >= :filter_student_period_from AND
				 DATE(COALESCE(`ki`.`service_from`, 0000-00-00)) <= :filter_student_period_until
			 "
		);

		$oBar->setElement($oFilter);

		$oBar->setElement($oBar->createSeperator());

	}  else {

		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
		$oFilter->db_from_column	= array('from'	,	'from'); // Spalten ( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_from_alias		= array('kic'	,	'kia');
		$oFilter->db_until_column	= array('until'	,	'until'); // Spalten( von/bis müssen die gleiche anzahl haben )
		$oFilter->db_until_alias	= array('kic'	,	'kia');
		$oFilter->default_from		= Ext_Thebing_Format::LocalDate($iFilterStart); // Standart Wert
		$oFilter->default_until		= Ext_Thebing_Format::LocalDate($iFilterEnd); // Standart Wert
		$oFilter->label				= $oGui->t('Von');
		$oFilter->label_between		= $oGui->t('bis');
		$oFilter->search_type		= 'contact';
		$oBar ->setElement($oFilter);

		$oBar->setElement($oBar->createHtml('<span>'.$oGui->t('basierend auf').' '.$oGui->t('Leistungszeitraum').'</span>'));

		if($sView!=='student_cards') {
			$oBar->setElement($oBar->createSeperator());
		}
	}

	// DD Filter für Zeitinterval-Filter
	if(
		$sView == 'inbox' ||
		$sView == 'departure_list' ||
		$sView == 'progress_report' ||
		$sView == 'arrival_list' || 
		$sView == 'agency_payment' ||
		$sView == 'proforma' ||
		$sView == 'visum_list'
	){
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'time_interval_filter';
		$oFilter->value = 'course_start';
		$oFilter->filter_part = 'having';
		$oFilter->label	= L10N::t('basierend auf', $oGui->gui_description);

		// Default values 
		switch($sView){
			case 'arrival_list':
					$oFilter->value = 'course_start'; break;
			case 'departure_list':
					$oFilter->value = 'course_end'; break;
			case 'progress_report':
					$oFilter->value = 'course_contact'; break;
			case 'visum_list':
					$oFilter->value = 'visum_contact'; break;
			case 'inbox':
			default:
					$oFilter->value = 'booking'; break;

		}

		$aFilterTypes = array(
			'course_start'			=> L10N::t('Kursstart', $oGui->gui_description),
			'accommodation_start'	=> L10N::t('Unterkunftsstart', $oGui->gui_description),
			'all_start'				=> L10N::t('Alles Start', $oGui->gui_description),
			'booking'				=> L10N::t('Buchungsdatum', $oGui->gui_description),
			'course_end'			=> L10N::t('Kursende', $oGui->gui_description),
			'accommodation_end'		=> L10N::t('Unterkunftsende', $oGui->gui_description),
			'all_end'				=> L10N::t('Alles Ende', $oGui->gui_description),
			'course_contact'		=> L10N::t('Leistungszeitraum', $oGui->gui_description),
			'visum_contact'			=> L10N::t('Visum', $oGui->gui_description),
		);

		$oFilter->select_options = $aFilterTypes;

		$oFilter->filter_query = array(
			'course_start' => " `first_course_start` BETWEEN :filter_student_period_from AND :filter_student_period_until ",
			'accommodation_start' => " `first_accommodation_start` BETWEEN :filter_student_period_from AND :filter_student_period_until ",
			'all_start' => " ( `first_course_start` BETWEEN :filter_student_period_from AND :filter_student_period_until ) OR
							 ( `first_accommodation_start` BETWEEN :filter_student_period_from AND :filter_student_period_until ) /*OR
							 ( `arrival_date` BETWEEN :filter_student_period_from AND :filter_student_period_until)*/
							",
			'booking' => array(
				'query' => " DATE(`ki`.`created`) BETWEEN :filter_student_period_from AND :filter_student_period_until ",
				'part'	=> 'where'
			),
			'course_end' => " `last_course_end` BETWEEN :filter_student_period_from AND :filter_student_period_until ",
			'accommodation_end' => " `last_accommodation_end` BETWEEN :filter_student_period_from AND :filter_student_period_until ",
			'all_end' => "  ( `last_course_end` BETWEEN :filter_student_period_from AND :filter_student_period_until ) OR
							( `last_accommodation_end` BETWEEN :filter_student_period_from AND :filter_student_period_until ) /*OR
							( `departure_date` BETWEEN :filter_student_period_from AND :filter_student_period_until)*/
							",
			'course_contact' => array(
				'query' => " `kic`.`until` >= :filter_student_period_from AND `kic`.`from` <= :filter_student_period_until ",
				'part'	=> 'where'
			),
			'visum_contact' => array(
				'query' => " `ts_j_t_v_d`.`date_until` >= :filter_student_period_from AND `ts_j_t_v_d`.`date_from` <= :filter_student_period_until ",
				'part'	=> 'where'
			),
		);

		$oBar ->setElement($oFilter);

		$oBar->setElement($oBar->createSeperator());

	}

	if(
		$sView == 'inbox' ||
		$sView == 'client_payment' ||
		$sView == 'departure_list' ||
		$sView == 'arrival_list' ||
		$sView == 'simple_view' ||
		$sView == 'visum_list'
	) {
		// Agentur/Direktbucher
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'students_or_agencys';
		$oFilter->value = '';

		$aBookingType = array(
			'customer'	=>	L10N::t('Direktbuchungen', $oGui->gui_description),
			'agency'	=>	L10N::t('Agenturbuchungen', $oGui->gui_description)
		);

		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aBookingType, L10N::t('Buchungstyp', $oGui->gui_description));

		$oFilter->filter_query = array(
							'customer' => '
								`ki`.`agency_id` <= 0',
							'agency' => '
								`ki`.`agency_id` > 0'
							);
		$oBar ->setElement($oFilter);
	}
	
	if(
		$sView == 'departure_list' ||
		$sView == 'arrival_list' ||
		$sView == 'simple_view' ||
		$sView=='visum_list'
	) {

		// Visum state
		$aVisumStatus = $oSchool->getVisumList();
		$aVisumStatus = Ext_Thebing_Util::addEmptyItem($aVisumStatus, $oGui->t('Kein Visum'), '-1');
		$aVisumStatus = Ext_Gui2_Util::addLabelItem($aVisumStatus, $oGui->t('Visum'), '');

		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'visa_info_select';
		$oFilter->value = '';
		$oFilter->select_options = $aVisumStatus;

		$aVisumQueryPart = array();
		foreach((array)$aVisumStatus as $iStatus => $sStatus) {

			if($iStatus == '-1') {
				$aVisumQueryPart[$iStatus] = ' `ts_j_t_v_d`.`status` = 0 ';
			} else if($iStatus == '0') {
				$aVisumQueryPart[$iStatus] = ' 1 = 1 ';
			} else {
				$aVisumQueryPart[$iStatus] = ' `ts_j_t_v_d`.`status` = '.(int)$iStatus.' ';
			}

		}

		$oFilter->filter_query = $aVisumQueryPart;
		$oBar->setElement($oFilter);

		if($sView!='visum_list')
		{
			// Booked course category
			$aCategories = $oSchool->getCourseCategoriesList('select');
			$aCategories = Ext_Gui2_Util::addLabelItem($aCategories, L10N::t('Kurskategorie', $oGui->gui_description) , '');
			$oFilter = $oBar->createFilter('select');
			$oFilter->id = 'course_category_info_select';
			$oFilter->value = '';
			$oFilter->select_options = $aCategories;
			$oFilter->db_alias = 'ktc';
			$oFilter->db_column = 'category_id';
			
			$oBar->setElement($oFilter);
		}

		// Customer state
		$aCustomerStatus = $oSchool->getCustomerStatusList();

		$aCustomerStatus = Ext_Gui2_Util::addLabelItem($aCustomerStatus, L10N::t('Schülerstatus', $oGui->gui_description), '');
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'customer_state_info_select';
		$oFilter->value = '';
		$oFilter->select_options = $aCustomerStatus;
		$aFilterQueryArray = array();

		foreach((array)$aCustomerStatus as $iKey => $sValue)
		{
			if(empty($iKey))
			{
				continue;
			}

			$aFilterQueryArray[(int)$iKey] = ' `ki`.`status_id` = ' . (int)$iKey;
		}

		$oFilter->filter_query = $aFilterQueryArray;
		$oBar->setElement($oFilter);
	}
	
	if(
		$sView == 'departure_list'
	){
		$aAgencies					= $oClient->getAgencies(true);
		$aAgenciesFilter			= Ext_Gui2_Util::addLabelItem($aAgencies,$oGui->t('Agentur'));

		$oFilter = $oBar->createFilter('select');
		$oFilter->select_options	= $aAgenciesFilter;
		$oFilter->db_column			= 'agency_id';
		$oFilter->db_alias			= 'ki';
		$oFilter->db_operator		= '=';
		$oBar ->setElement($oFilter);

		$aCountriesFilter			= Ext_Thebing_Data::getCountryList(true);
		$aCountriesFilter			= Ext_Gui2_Util::addLabelItem($aCountriesFilter,$oGui->t('Land'));

		$oFilter = $oBar->createFilter('select');
		$oFilter->select_options	= $aCountriesFilter;
		$oFilter->db_column			= 'country_iso';
		$oFilter->db_alias			= 'tc_a';
		$oFilter->db_operator		= '=';
		$oBar ->setElement($oFilter);
	}
	
	if(
		$sView == 'inbox'
	){

		$oBreak						= new Ext_Gui2_Bar_Break();
		$oBar->setElement($oBreak);

		// Stornierte Kunden -------------------------------------------------------------------
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'cancelation_info_select';
		$oFilter->value = '';

		$aCancel = array(
			'canceled'	=>	L10N::t('Storniert', $oGui->gui_description),
			'not_canceled'	=>	L10N::t('Nicht storniert', $oGui->gui_description),
		);

		$aCancel = Ext_Gui2_Util::addLabelItem($aCancel, L10N::t('Stornierung', $oGui->gui_description));
		$oFilter->select_options = $aCancel;
		
		$oFilter->filter_query = array(
							'canceled' => '
								`ki`.`canceled` > 0',
							'not_canceled' => '
								`ki`.`canceled` <= 0',
							);
		$oBar ->setElement($oFilter);

		$aTempCurrencies = $oSchool->getSchoolCurrencyList();

		// Währungen -----------------------------------------------------------------------------
		if(count($aTempCurrencies) > 1)
		{
			//$aTempCurrencies = Ext_Thebing_Util::addEmptyItem($aTempCurrencies, '', '');
			$aTempCurrencies = Ext_Gui2_Util::addLabelItem($aTempCurrencies, L10N::t('Währung', $oGui->gui_description));

			$oFilter = $oBar->createFilter('select');
			$oFilter->id = 'currencies_info_select';
			$oFilter->value = '';
			$oFilter->select_options = $aTempCurrencies;

			$aFilterQueryArray = array();

			foreach((array)$aTempCurrencies as $iKey => $sValue)
			{
				if(empty($iKey))
				{
					continue;
				}

				$aFilterQueryArray[(int)$iKey] = ' `ki`.`currency_id` = ' . (int)$iKey;
			}

			$oFilter->filter_query = $aFilterQueryArray;

			$oBar->setElement($oFilter);
		}

		// Default filter ----------------------------------------------------------------------
//		$oGui->addDefaultFilters($oBar);
	}
	
	// Agentur und Suche nach Herkunftsland
	if(
		$sView == 'inbox' ||
		$sView == 'simple_view' ||
		$sView == 'arrival_list' ||
		$sView == 'visum_list'
	) {
		
		// Special Filter -------------------------------------------------------------------------

		if($sView!='visum_list') {

			$oFilter = $oBar->createFilter('select');
			$oFilter->id = 'special_filter';

			$aSpecialOptions = $oSchool->getSpecialFilterOptions();
			$aSpecialOptions = Ext_Gui2_Util::addLabelItem($aSpecialOptions, L10N::t('Sonderangebot', $oGui->gui_description));

			$oFilter->select_options = $aSpecialOptions;
			$oFilter->filter_query = $oSchool->getSpecialFilterOptions(true);
			$oFilter->access = 'thebing_marketing_special';

			$oBar ->setElement($oFilter);

		}

		// Agenturfilter -----------------------------------------------------------------------
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'agency_filter';

		$aAgencyOptions = $oClient->getAgencies(true);
		$aAgencyOptions = Ext_Gui2_Util::addLabelItem($aAgencyOptions, L10N::t('Agenturen', $oGui->gui_description));

		$oFilter->select_options = $aAgencyOptions;
		$oFilter->filter_query = " `ka`.`id` = {value} ";

		$oBar ->setElement($oFilter);
		
		// Länder Filter
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'country_filter';
		$oFilter->db_column = 'country_iso';
		$oFilter->db_alias = 'tc_a';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem(Ext_Thebing_Data::getCountryList(true, false), $oGui->t('Land'));
		
		$oBar ->setElement($oFilter);
		
		
		// Wie sind Sie auf uns aufmerksam geworden Filter
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'referer_filter';
		$oFilter->db_column = 'referer_id';
		$oFilter->db_alias = 'ki';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($oSchool->getRefererList(), $oGui->t('Referenz'));
		$oBar ->setElement($oFilter);
		
		// Filter nach Proforma/Rechnung
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'document_type';
		$oFilter->select_options = array(
											''			=>	$oGui->t('Alle Dokumente'),
											'invoice'	=>	$oGui->t('nur Rechnungen'),
											'proforma'	=>	$oGui->t('nur Proforma')
											);
		$oFilter->filter_query = array(
							'invoice' => '
								`ki`.`has_invoice` = 1 ',
							'proforma' => '
								(
									`ki`.`has_proforma` = 1 AND
									`ki`.`has_invoice` = 0
								)'
							);
		$oBar ->setElement($oFilter);
	}

	// TransferListe(n)
	if($sView == 'transfer') {

		// Transfer-Status
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'transfer_info_select';
		$oFilter->value = '';

		$aTransferInfo = array(
			//	'first'	=>	L10N::t('Fehlende Ankunftsdaten', $oGui->gui_description),
			'second'	=>	L10N::t('Kein Anbieter/Fahrer zugewiesen', $oGui->gui_description)
		);
		
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aTransferInfo, L10N::t('Transferinformation' , $oGui->gui_description));
		$oFilter->filter_query = array(
							'first' => "
								(

								)",
							'second' => "
								(
									`kit`.`provider_id` = 0 AND
									`kit`.`driver_id` = 0
								)"
							);
		$oBar ->setElement($oFilter);

		// An oder Abreisefilter
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'transfer_status_select';
		$oFilter->value = '';


		$aTransferType	= array(
			'arrival'		=>	L10N::t('Ankunftsliste', $oGui->gui_description),
			'departure'		=>	L10N::t('Abflugsliste', $oGui->gui_description),
			'additional'	=>	L10N::t('Zusatztransfer', $oGui->gui_description)
		);

		$aTransferType = Ext_Gui2_Util::addLabelItem($aTransferType, L10N::t('Transfertyp', $oGui->gui_description));

		$oFilter->select_options = $aTransferType;

		$oFilter->filter_query = array(
					'arrival' => "
						(
							`kit`.`transfer_type` = 1
						)",
					'departure' => "
						(
							`kit`.`transfer_type` = 2
						)",
					'additional' => "
						(
							`kit`.`transfer_type` = 0
						)"
					);

		$oBar ->setElement($oFilter);

		// Filter nach Transferort
		$aTransferLocations = $oSchool->getTransferLocationsForInquiry('arrival', null);
		$aTransferLocations = Ext_Gui2_Util::addLabelItem($aTransferLocations, $oGui->t('Transferort'));

		// Filter-Query muss wegen den zusammengesetzten Options zusammengebaut werden
		$aFilterQuery = [];
		foreach(array_keys($aTransferLocations) as $sKey) {
			list($sType, $sId) = explode('_', $sKey);
			if(
				strpos($sKey, 'school') !== false ||
				strpos($sKey, 'accommodation') !== false
			) {
				// accommodation: Da es keine konkreten Provider (ind. Transfers!) gibt, die 0 entfernen, damit die auch gefunden werden
				$aFilterQuery[$sKey] = " (`kit`.`start_type` = '{$sType}') OR (`kit`.`end_type` = '{$sType}') ";
			} else {
				$aFilterQuery[$sKey] = " (`kit`.`start_type` = '{$sType}' AND `kit`.`start` = {$sId}) OR (`kit`.`end_type` = '{$sType}' AND `kit`.`end` = {$sId}) ";
			}
		}

		$oFilter = $oBar->createFilter('select');
		$oFilter->select_options = $aTransferLocations;
		$oFilter->filter_query = $aFilterQuery;
		$oBar ->setElement($oFilter);

		/*
		 * Der Dokumenten-Type-Filter darf nur angezeigt werden, wenn in den Systemeinstellungen nicht "Ab Rechnung"
		 * ausgewählt wurde.
		 */
		if ((int)$oClient->show_customer_without_invoice !== 2) {

			// Filter nach Proforma/Rechnung
			$oFilter = $oBar->createFilter('select');
			$oFilter->id = 'document_type';
			$oFilter->select_options = array(
				'' => $oGui->t('Alle Dokumente'),
				'invoice' => $oGui->t('Nur Rechnungen'),
				'proforma' => $oGui->t('Nur Proforma')
			);
			$oFilter->filter_query = array(
				'invoice' => '
					`ki`.`has_invoice` = 1 ',
				'proforma' => '
					(
						`ki`.`has_proforma` = 1 AND
						`ki`.`has_invoice` = 0
					)'
			);
			$oBar ->setElement($oFilter);

		}
	}
	if(
		$sView == 'transfer' || 
		$sView == 'progress_report' ||
		$sView == 'simple_view'	||
		$sView == 'student_cards'
	) {
		
		$oClient = Ext_Thebing_System::getClient();
        $aInboxes = $oClient->getInboxList(true, true);

        $oFilter = $oBar->createFilter('select');
        $oFilter->id				= 'inbox_filter';
        $oFilter->db_column         = array('inbox');
        $oFilter->db_alias          = array('ki');
        $oFilter->value				= '';
        $oFilter->select_options	= Ext_Gui2_Util::addLabelItem($aInboxes, $oGui->t('Inbox'));
        $oBar->setElement($oFilter);
	}


	if(
		$sView === 'proforma' ||
		$sView === 'transfer' ||
		$sView === 'progress_report' ||
		$sView === 'student_cards'
	) {

		$aYesNo = Ext_Thebing_Util::getYesNoArray(false);
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'group_filter';
		$oFilter->value = '';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aYesNo, $oGui->t('Gruppe'), 'xNullx');
		$oFilter->filter_query = array(
			'yes' => '
				`ki`.`group_id` != 0 ',
			'no' => '
				`ki`.`group_id` = 0 '
		);
		$oBar->setElement($oFilter);
	}

	if(
		$sView === 'proforma' ||
		$sView === 'transfer' ||
		$sView === 'progress_report' ||
		$sView === 'student_cards'
	) {

		$aYesNo = Ext_Thebing_Util::getYesNoArray(false);
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'checkin_filter';
		$oFilter->value = '';
		$oFilter->db_column='checkin';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aYesNo, $oGui->t('Eingecheckt'));
		$oFilter->filter_query = array(
			'no' => '
					`checkin` IS NULL ',
			'yes' => '
					`checkin` IS NOT NULL '
		);
		$oBar->setElement($oFilter);
	}

	if(
		$sView === 'proforma' ||
		$sView === 'transfer' ||
		$sView === 'progress_report' ||
		$sView === 'student_cards'
	) {

		$aYesNo = Ext_Thebing_Util::getYesNoArray(false);
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'student_status_filter';
		$oFilter->db_column='status_id';
		$oFilter->db_alias='ki';
		$oFilter->select_options = Ext_Gui2_Util::addLabelItem(Ext_TS_Inquiry_Index_Gui2_Data::getCustomerStatusOptions(), $oGui->t('Schülerstatus'));
		$oFilter->db_operator = '=';
		$oBar->setElement($oFilter);
	}

	$oGui->setBar($oBar);
# ENDE - Leiste 1 #

# START - Leiste 2 #
$oBar = $oGui->createBar();
$oBar->width = '100%';

	if(
		$sView == 'simple_view' ||
		$sView == 'departure_list' ||
		$sView == 'arrival_list' ||
		$sView == 'progress_report'
	) {
		$oIcon = $oBar->createEditIcon(
								L10N::t('Editieren', $oGui->gui_description),
								$oDialog,
								L10N::t('Editieren', $oGui->gui_description)
						);
		$oIcon->label = $oGui->t('Editieren');
		$oBar ->setElement($oIcon);

		if($sView=='progress_report')
		{
			$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('table_go'), 'openDialog', $oGui->t('Fortschritt öffnen'));
			$oIcon->label = L10N::t('Fortschritt öffnen', $oGui->gui_description);
			$oIcon->active = 0;
			$oIcon->action = 'openProgressReport';
			$oBar ->setElement($oIcon);
		}

		$oLabelGroup	= $oBar->createLabelGroup($oGui->t('Kommunikation'));
		
		if(Ext_Thebing_Access::hasRight('thebing_tuition_progress_report_communication')) {
			$oBar->setElement($oLabelGroup);
		}
		$oIcon = $oBar->createCommunicationIcon($oGui->t('Kunde informieren'), $sCommunicationApplication);
		
		if(Ext_Thebing_Access::hasRight('thebing_tuition_progress_report_communication')) {
			$oBar->setElement($oIcon);
		}
		
	} else {

		if($sView == 'inbox'){
				$oIcon = $oBar->createNewIcon(
					
								L10N::t('Neuer Eintrag', $oGui->gui_description),
								$oDialog,
								L10N::t('Neuer Eintrag', $oGui->gui_description)
						);
				$oBar ->setElement($oIcon);
		}

		$oIcon = $oBar->createEditIcon(
								L10N::t('Editieren', $oGui->gui_description),
								$oDialog,
								L10N::t('Editieren', $oGui->gui_description)
						);

		if($sView === 'transfer') {
			$oIcon->access = 'thebing_pickup_confirmation_edit';
		}

		$oBar ->setElement($oIcon);

		if($sView == 'student_cards') {
			$oIcon = $oBar->createIcon('fa-camera', 'openDialog', $oGui->t('Kamera'));
			$oIcon->action = 'camera';
			$oIcon->label = $oGui->t('Kamera');
			$oIcon->access = 'thebing_student_cards_camera';
			$oBar->setElement($oIcon);
		}

		if(
			$sView == 'inbox'
		){
			$oIcon = $oBar->createDeleteIcon(L10N::t('Löschen', $oGui->gui_description), L10N::t('Löschen', $oGui->gui_description));
			$oIcon->access = 'thebing_invoice_delete_inquiry';
			$oBar ->setElement($oIcon);
		}

		if(
			(
				Ext_Thebing_Access::hasRight('thebing_invoice_enter_payments') ||
				Ext_Thebing_Access::hasRight('thebing_invoice_enter_payments_history')
			) && (
				$sView == 'client_payment' ||
				$sView == 'agency_payment' ||
				$sView == 'inbox'
			)
		) {
			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('payment'), 'openDialog', L10N::t('Bezahlen', $oGui->gui_description));
			$oIcon->action = 'payment';
			$oIcon->active = 0;
			$oIcon->label = L10N::t('Bezahlen', $oGui->gui_description);
			$oBar ->setElement($oIcon);
		}

		if(
			$sView != 'transfer' &&
			$sView != 'proforma' &&
			$sView != 'student_cards' &&
			$sView != 'progress_report'
		) {
			$oIcon = $oBar->createCommunicationIcon($oGui->t('Kommunikation'), $sCommunicationApplication);
			if($sView == 'transfer'){
				// In TransferListe soll es mehrfachkommunikation geben
				$oIcon->multipleId 	= 1;
			}
			$oBar->setElement($oIcon);
		}

		if($sView == 'proforma') {
			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('refresh'), 'request', L10N::t('Proforma umwandeln', $oGui->gui_description));
			$oIcon->request_data = '&action=convertProformaDocument';
			$oIcon->active = 0;
			$oIcon->multipleId 	= 1;
			$oIcon->action = 'convertProformaDocument'; // Wird NUR benötigt um Icon zu deaktivieren
			$oIcon->label = $oGui->t('Proforma umwandeln');
			$oBar ->setElement($oIcon);
		}

		if($sView == 'transfer') {

			// Provider zuweisen
			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('provider_confirm'), 'openDialog', L10N::t('Anbieter zuweisen', $oGui->gui_description));
			$oIcon->action		= 'transfer_provider';
			$oIcon->active		= 0;
			$oIcon->multipleId 	= 1;
			$oIcon->label = L10N::t('Anbieter zuweisen', $oGui->gui_description);
			$oBar ->setElement($oIcon);

			if(
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_button_request') ||
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_button_confirm_transfer') ||
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_button_confirm_accommodation')
			) {
				$oBar->setElement($oBar->createLabelGroup($oGui->t('Anbieter')));

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('accept'), 'request', L10N::t('Anfrage bestätigen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Anfrage bestätigen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_request';
				$oIcon->action	= 'confirmPickupRequest';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('delete'), 'request', L10N::t('Anfrage entfernen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Anfrage entfernen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_request';
				$oIcon->action	= 'deletePickupRequestConfirmation';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('accept'), 'request', L10N::t('Transfer bestätigen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Transfer bestätigen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_transfer';
				$oIcon->action	= 'confirmPickupProvider';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('delete'), 'request', L10N::t('Transfer entfernen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Transfer entfernen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_transfer';
				$oIcon->action	= 'deletePickupProviderConfirmation';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('accept'), 'request', L10N::t('Unterkunft bestätigen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Unterkunft bestätigen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_accommodation';
				$oIcon->action	= 'confirmPickupAccommodationProvider';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('delete'), 'request', L10N::t('Unterkunft entfernen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Unterkunft entfernen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_accommodation';
				$oIcon->action	= 'deletePickupAccommodationProviderConfirmation';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);
			}

			if(Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_button_confirm_student')) {
				$oBar->setElement($oBar->createLabelGroup($oGui->t('Schüler/Agentur')));

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('accept'), 'request', L10N::t('Transfer bestätigen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Transfer bestätigen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_student';
				$oIcon->action	= 'confirmPickupCustomer';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);

				$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('delete'), 'request', L10N::t('Transfer entfernen', $oGui->gui_description));
				$oIcon->label	= L10N::t('Transfer entfernen', $oGui->gui_description);
				$oIcon->access = 'thebing_pickup_confirmation_button_confirm_student';
				$oIcon->action	= 'deletePickupCustomerConfirmation';
				$oIcon->active	= 0;
				$oIcon->multipleId	= 1;
				$oBar ->setElement($oIcon);
			}
			
			// Kommunikation
			if(
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_request_transfer') ||
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_confirm_transfer') ||
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_confirm_student') ||
				Ext_Thebing_Access::hasRight('thebing_pickup_confirmation_confirm_accommodation')
			) {
				// Ansonsten ist obere Bar doppelt vorhanden
				$oGui->setBar($oBar);

				$oBar = $oGui->createBar();
				$oBar->setElement($oBar->createLabelGroup($oGui->t('Kommunikation')));

				// Transfer anfragen bei Provider/Unterkünften
				$oIcon = $oBar->createCommunicationIcon($oGui->t('Transfer anfragen'), 'transfer_provider_request');
				$oIcon->access = 'thebing_pickup_confirmation_request_transfer';
				$oIcon->multipleId 	= 1;
				$oBar->setElement($oIcon);

				// Transfer bestätigen Provider/Unterkünften
				$oIcon = $oBar->createCommunicationIcon($oGui->t('Transfer bestätigen'), 'transfer_provider_confirm');
				$oIcon->access = 'thebing_pickup_confirmation_confirm_transfer';
				$oIcon->multipleId 	= 1;
				$oBar->setElement($oIcon);

				// Transfer bestätigen Kunde/Agentur
				$oIcon = $oBar->createCommunicationIcon($oGui->t('Kunde/Agentur informieren'), 'transfer_customer_agency_information');
				$oIcon->access = 'thebing_pickup_confirmation_confirm_student';
				$oIcon->multipleId 	= 1;
				$oBar->setElement($oIcon);

				// Unterkunft informieren
				$oIcon = $oBar->createCommunicationIcon($oGui->t('Unterkunft informieren'), 'transfer_customer_accommodation_information');
				$oIcon->access = 'thebing_pickup_confirmation_confirm_accommodation';
				$oIcon->multipleId 	= 1;
				$oBar->setElement($oIcon);
			}

		}

		if(
			$sView == 'inbox'
		) {

			$oLabelgroup = $oBar->createLabelGroup(L10N::t('Rechnungen', $oGui->gui_description));
			$oBar ->setElement($oLabelgroup);

			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('page_edit'), 'openDialog', L10N::t('Rechnung editieren', $oGui->gui_description));
			$oIcon->action = 'invoice';
			$oIcon->active = 0;
			$oBar ->setElement($oIcon);

			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'request', L10N::t('Rechnungs PDF öffnen', $oGui->gui_description));
			$oIcon->info_text = 1;
			$oIcon->request_data = '&action=openInvoicePdf';
			$oIcon->active = 0;
			$oIcon->action = 'openInvoicePdf'; // Wird NUR benötigt um Icon zu deaktivieren
			$oBar ->setElement($oIcon);

			$oLabelgroup = $oBar->createLabelGroup(L10N::t('Dokumente', $oGui->gui_description));
			$oBar ->setElement($oLabelgroup);

			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('page_edit'), 'openDialog', L10N::t('Dokumente editieren', $oGui->gui_description));
			$oIcon->action = 'additional_document';
			$oIcon->active = 0;
			$oBar ->setElement($oIcon);

			$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'request', L10N::t('Dokument PDF öffnen', $oGui->gui_description));
			$oIcon->info_text = 1;
			$oIcon->request_data = '&action=openDocumentPdf';
			$oIcon->active = 0;
			$oIcon->action = 'openDocumentPdf'; // Wird NUR benötigt um Icon zu deaktivieren
			$oBar ->setElement($oIcon);
		}
	}

	if($bAddIconAdditionalFiles) {

		$oLabelGroup	= $oBar->createLabelGroup($oGui->t('Dokumente'));
		$oBar->setElement($oLabelGroup);

		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('page_edit'), 'openDialog', $oGui->t('Dokumente editieren'));
		$oIcon->action = 'additional_document';
		$oIcon->active = 0;

		$sTemplates = '';
		$aDefaultTemplates = array('document_loa', 'document_studentrecord_additional_pdf', 'document_studentrecord_visum_pdf');
		if(empty($aAdditionalTemplates)){
			$aAdditionalTemplates = $aDefaultTemplates;
		}

		foreach((array)$aAdditionalTemplates as $sTemplate){
			$sTemplates .= '&template_type[]='.$sTemplate;
		}

		$oIcon->request_data = $sTemplates;
		$oBar ->setElement($oIcon);

		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'request', $oGui->t('Dokument PDF öffnen'));
		$oIcon->info_text = 1;
		$oIcon->request_data = '&action=openDocumentPdf';
		$oIcon->active = 0;
		$oIcon->action = 'openDocumentPdf';
		$oBar ->setElement($oIcon);

	}
	
	// Massen PDF erzeugung
	if($sView == 'student_cards'){
		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'openMultiplePdf', $oGui->t('Massen PDF öffnen'));
		$oIcon->label = $oGui->t('Massen PDF öffnen');
		$oBar->setElement($oIcon);
	}

	$oGui->setBar($oBar);

	# START - Leiste 3 #
	$oBar = $oGui->createBar();
	$oBar->width = '100%';


$oFilter = $oBar->createPagination(false,true);
$oBar ->setElement($oFilter);

/*$oSeperator = $oBar->createSeperator();
$oBar ->setElement($oSeperator);*/

if(
	$sView == 'inbox'
){
	// NICHT in der All Ansicht
	if($iSessionSchoolId > 0){
		$oLabelgroup = $oBar->createLabelGroup(L10N::t('Gruppen', $oGui->gui_description));
		$oBar ->setElement($oLabelgroup);

		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('group'), 'openDialog', L10N::t('Gruppen editieren', $oGui->gui_description));
		$oIcon->action = 'editGroup';
		$oIcon->active = 1;
		$oIcon->label = L10N::t('Editieren', $oGui->gui_description);
		$oBar ->setElement($oIcon);
	}
}

/**
 * @todo: Beschriftung für diesen Bereich überlegen 13.12.2010
 * Mir fällt nichts ein 15.03.11
 */
//$oLabelgroup = $oBar->createLabelGroup(L10N::t('Weitere Aktionen', $oGui->gui_description));
//$oBar ->setElement($oLabelgroup);

$oLabelgroup = $oBar->createLabelGroup(L10N::t('Export', $oGui->gui_description));
$oBar ->setElement($oLabelgroup);

$oIcon = $oBar->createCSVExport();
$oBar ->setElement($oIcon);

$oIcon = $oBar->createExcelExport();
$oBar ->setElement($oIcon);

$oLoading = $oBar->createLoadingIndicator();
$oBar->setElement($oLoading);

$oGui->setBar($oBar);


# ENDE - Leiste 3 #

# START - Leiste 4 #
if($sView != 'proforma'){
	$oGui->setBar($oBarLegend);
}
# ENDE - Leiste 4 #

if($sView!='visum_list'){
	$oGui->setColumn($oColInvoice);
}
$oGui->setColumn($oColCustomerNum);
$oGui->setColumn($oColCustomerCheckin);
$oGui->setColumn($oColCustomerName);
$oGui->setColumn($oColCustomerAddress);
$oGui->setColumn($oColCustomerAddress2);
$oGui->setColumn($oColCustomerZip);
$oGui->setColumn($oColCustomerCity);
$oGui->setColumn($oColCustomerState);
$oGui->setColumn($oColCustomerCountry);



if($sView!='visum_list'){
	$oGui->setColumn($oColCustomerMail);
	$oGui->setColumn($oColGroup);
	$oGui->setColumn($oColGender);
	$oGui->setColumn($oColNationality);
	$oGui->setColumn($oColMothertongue);
	$oGui->setColumn($oColCorrespondingLang);
	$oGui->setColumn($oColAge);
	$oGui->setColumn($oColAccRoomtype);
}else{
	$oGui->setColumn($oColVisumName);
	$oGui->setColumn($oColVisumRequired);
	$oGui->setColumn($oColVisumId);
	$oGui->setColumn($oColVisumTrackingNumber);
	$oGui->setColumn($oColVisumPassNumber);
	$oGui->setColumn($oColVisumPassportDateOfIssue);
	$oGui->setColumn($oColPassportExpiration);
	$oGui->setColumn($oColVisumDateFrom);
	$oGui->setColumn($oColVisumDateUntil);
}

$oGui->setColumn($oColTransferComment);
if ($oColIndividualTransferComment) {
	$oGui->setColumn($oColIndividualTransferComment);
}
//$oGui->setColumn($oColTransferArrComment);
//$oGui->setColumn($oColTransferDepComment);

$oGui->setColumn($oColCourseFulllist);
$oGui->setColumn($oColCourseTimeFromFulllist);
$oGui->setColumn($oColCourseTimeToFulllist);
$oGui->setColumn($oColTotalCourseWeeksFulllist);

if(
	$sView!='visum_list' &&
	$sView!='client_payments'
){
//	$oGui->setColumn($oColCourseLevel);
}

$oGui->setColumn($oColAccFulllist);
$oGui->setColumn($oColAccTimeFromFulllist);
$oGui->setColumn($oColAccTimeToFulllist);

$oSchool = \Ext_Thebing_School::getSchoolFromSession();

if(!$oSchool instanceof Ext_Thebing_School){
	$oGui->setColumn($oColSchool);
}

if($oGui->getOption('query_accommodation_customers')){
	$oGui->setColumn($oColAccommodationBookingShares);
	$oGui->setColumn($oColAccommodationAllocationShares);
	$oGui->setColumn($oColRoomName);
	$oGui->setColumn($oColAccommodationWeeks);
}

if($sView!='visum_list'){
	$oGui->setColumn($oColVisumName);
	$oGui->setColumn($oColAgency);
	$oGui->setColumn($oColAgencyNumber);
	$oGui->setColumn($oColCustomerComment);
	$oGui->setColumn($oColCustomerPhone);
	$oGui->setColumn($oColCustomerMobile);
	$oGui->setColumn($oColCustomerStatus);
//	$oGui->setColumn($oColAccAllergies);
	$oGui->setColumn($oColAccFullNameList);
//	$oGui->setColumn($oColAccRoomFullNameList);
	$oGui->setColumn($oColAccFullStreetList);
	$oGui->setColumn($oColAccFullAddressAddonList);
	$oGui->setColumn($oColAccFullZipList);
	$oGui->setColumn($oColAccFullCityList);
	$oGui->setColumn($oColAccFullPhoneList);
	$oGui->setColumn($oColAccFullPhone2List);
	$oGui->setColumn($oColAccFullMobileList);
	$oGui->setColumn($oColAccFullMailList);
//	$oGui->setColumn($oColAccFullContactList);
	$oGui->setColumn($oColBirthday);
	$oGui->setColumn($oColReferer);
	$oGui->setColumn($oColAccComment);
	$oGui->setColumn($oColAccComment2);
	$oGui->setColumn($oColFirstCourseName);
	$oGui->setColumn($oColFirstCourseFrom);
	$oGui->setColumn($oColLastCourseUntil);

	//$oGui->setColumn($oColDepartureDay);
	//$oGui->setColumn($oColInquiryCreated);
	$oGui->setColumn($oColPaymentReminder);
}

// Transfer
if($sView === 'transfer') {
    $oGui->setColumn($oColInbox);
	$oGui->setColumn($oColTransferDate);
	$oGui->setColumn($oColTransferTime);
	$oGui->setColumn($oColTransferPickup);
	$oGui->setColumn($oColDriver);
	$oGui->setColumn($oColProvider);
	$oGui->setColumn($oColProviderRequested);
	$oGui->setColumn($oColProviderAssigned);
	$oGui->setColumn($oColProviderConfirmed);
	$oGui->setColumn($oColCustomerAgencyConfirmed);
	$oGui->setColumn($oColAccommodationConfirmed);
	$oGui->setColumn($oColFlynumber);
	$oGui->setColumn($oColAirline);
	$oGui->setColumn($oColTransferType);
	$oGui->setColumn($oColTransferStart);
	$oGui->setColumn($oColTransferEnd);
}
elseif(
	$sView === 'progress_report' ||
	$sView === 'student_cards'
) {
	$oGui->setColumn($oColInbox);
	$oGui->setColumn($oColStudentCardPdf);
}

elseif($sView !== 'visum_list') {
	// Spalten die NUR Inbox + Schülerlisten zur verfügung stehen sollen

	// Invoice Amount
//	$oGui->setColumn($oColAmount);
//	$oGui->setColumn($oColAmountInitial);
//	$oGui->setColumn($oColAmountDueArrival);
//	$oGui->setColumn($oColAmountDueAtSchool);
//	$oGui->setColumn($oColPayments);
//	$oGui->setColumn($oColPaymentsAtSchool);
//	$oGui->setColumn($oColCredit);
//	$oGui->setColumn($oColAmountDueGeneral);
//	$oGui->setColumn($oColAmountRefund);

	// Invoice PDF
	$oGui->setColumn($oColSendNettoPdf);
	$oGui->setColumn($oColSendGrossPdf);
	$oGui->setColumn($oColSendLoaPdf);

	// Transfer für Schülerlisten 
	$oGui->setColumn($oColTransferInfoArrival);
	$oGui->setColumn($oColTransferInfoDeparture);

}

$oGui->setColumn($oColTransfer);

if($sView != 'transfer'){ 
	$oGui->setColumn($oColTransferArr);
	$oGui->setColumn($oColTransferArrTime);
	$oGui->setColumn($oColTransferDep);
	$oGui->setColumn($oColTransferDepTime);
}

if($sView!='visum_list'){
	$oGui->setColumn($oColPassportExpiration);
}

$oDefaultColumn = $oGui->getDefaultColumn();
$oDefaultColumn->setAliasForAll('ki');
$oGui->setDefaultColumn($oDefaultColumn);

$oDefaultColumn = $oGui->getDefaultColumn();
$oDefaultColumn->changeEditorIdDbColumn('editor_id');
$oDefaultColumn->getSystemUsersById();
$oGui->setDefaultColumn($oDefaultColumn);

$oGui->addDefaultColumns();

$oGui->sum_row_columns = array(
	$oColAmountInitial->select_column,
	$oColAmount->select_column,
	$oColAmountDueArrival->select_column,
	$oColAmountDueAtSchool->select_column,
	$oColPaymentsAtSchool->select_column,
	$oColPayments->select_column,
	$oColAmountNetto->select_column,
	$oColAmountDueGeneral->select_column,
	$oColAmountRefund->select_column
); // Spalten welche in einer Summenspalte summiert werden sollen

$aOptionalInfo = array();
$aOptionalInfo['js'][] = '/admin/extensions/thebing/gui2/util.js';
$aOptionalInfo['js'][] = '/admin/extensions/thebing/gui2/payment.js';
$aOptionalInfo['js'][] = '/admin/extensions/thebing/gui2/studentlists.js';
$aOptionalInfo['js'][] = '/admin/extensions/tc/js/communication_gui.js';

$aOptionalInfo['css'][] = '/assets/ts/css/gui2.css';
$aOptionalInfo['css'][] = '/assets/ts-tuition/css/progress_report.css';
