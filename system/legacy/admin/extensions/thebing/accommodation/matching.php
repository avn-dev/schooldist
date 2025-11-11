<?php

$oSchool = Ext_Thebing_School::getSchoolFromSession();
$sLang = $oSchool->getInterfaceLanguage();

if($sView == 'parking') {
    $iType = 2;
} else if($sView == 'matching_hostfamily') {
	$iType = 1;
} else {
	$iType = 0;
}

// Zeitfilter
$oDate = new WDDate();
$oDate->add(1, WDDate::MONTH);
$iUntil = (int)$oDate->get(WDDate::TIMESTAMP);
$oDate->sub(2, WDDate::MONTH);
$iFrom = (int)$oDate->get(WDDate::TIMESTAMP);

$oGuiMatching = new Ext_Thebing_Gui2(md5($sGuiUnique), 'Ext_Thebing_Accommodation_Matching_Gui2');
$oGuiMatching->setWDBasic('Ext_Thebing_Inquiry_Matching_Accommodation');
$oGuiMatching->setTableData('orderby', ['kia.from' => 'ASC']);

$oGuiMatching->gui_description = 'Thebing » Accommodation » Matching';
$oGuiMatching->gui_title = $sTitle;
$oGuiMatching->column_sortable = 1; // es geht nur eine sortierart!
$oGuiMatching->row_sortable = 0; // es geht nur eine sortierart! ( hat prioritär )
$oGuiMatching->multiple_selection = 0;
$oGuiMatching->access = $sAccess;
$oGuiMatching->query_id_alias = 'kia';
$oGuiMatching->calendar_format = new Ext_Thebing_Gui2_Format_Date();
$oGuiMatching->class_js = 'MatchingGui';
$oGuiMatching->sView = $sView;
$oGuiMatching->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Matching();
$oGuiMatching->row_style = new Ext_Thebing_Gui2_Style_Matching_Row();
$oGuiMatching->include_jquery = true;

$oGuiMatching->additional_sections = [
	[
		'section' => 'student_record_accommodation',
		'primary_key' => 'inquiry_id'
	],
	[
		'section' => 'student_record_matching',
		'primary_key' => 'inquiry_id'
	]
];

$aAccommodationCategories = $oSchool->getAccommodationList(true, $iType);
$aAccommodationCategories = Ext_Gui2_Util::addLabelItem($aAccommodationCategories, $oGuiMatching->t('Unterkunftskategorie'));

$aRoomtypes = Ext_Thebing_Accommodation_Roomtype::getListForSchools([$oSchool->id]);//$oSchool->getRoomtypeList(true);
$aRoomtypes = Ext_Gui2_Util::addLabelItem($aRoomtypes, $oGuiMatching->t('Raumart'));

$aBoards = $oSchool->getMealList(true);
$aBoards = Ext_Gui2_Util::addLabelItem($aBoards, $oGuiMatching->t('Verpflegung'));

$aAgeOptions = Ext_Thebing_Matching::getAgeOptions();
$aAgeOptions = Ext_Gui2_Util::addLabelItem($aAgeOptions, $oGuiMatching->t('Alter'));

$oBar = $oGuiMatching->createBar();
$oBar->width = '100%'; 

// Suchfilter
$aColumnSearch = [];
$aColumnSearch[] = 'lastname';
$aColumnSearch[] = 'firstname';
$aColumnSearch[] = 'number';
$aColumnSearch[] = 'name';
$aColumnSearch[] = 'short';
$aColumnSearch[] = 'number';
$aColumnSearch[] = 'number';
$aAliasSearch = [];
$aAliasSearch[] = 'tc_c';
$aAliasSearch[] = 'tc_c';
$aAliasSearch[] = 'tc_c_n';
$aAliasSearch[] = 'kg';
$aAliasSearch[] = 'kg';
$aAliasSearch[] = 'ts_i';
$aAliasSearch[] = 'kg';

$oFilter = $oBar->createFilter();
$oFilter->db_column = $aColumnSearch;
$oFilter->db_alias = $aAliasSearch;
$oFilter->id = 'search';
$oFilter->placeholder = $oGuiMatching->t('Suche').'…';
$oBar->setElement($oFilter);

$oBar->setElement($oBar->createSeperator());

// zeitfilter -------------------------------------------------------------------------------
$oFilter = $oBar->createTimeFilter(new Ext_Thebing_Gui2_Format_Date());
$oFilter->db_from_column = 'from'; // Spalten ( von/bis müssen die gleiche anzahl haben )
$oFilter->db_from_alias = 'kia';
$oFilter->db_until_column = 'until'; // Spalten ( von/bis müssen die gleiche anzahl haben )
$oFilter->db_until_alias = 'kia';
$oFilter->default_from = Ext_Thebing_Format::LocalDate($iFrom); // Standart Wert
$oFilter->default_until = Ext_Thebing_Format::LocalDate($iUntil); // Standart Wert
$oFilter->search_type = 'contact'; 
//$oFilter->query_value_key = 'filter_student_period';
$oFilter->label = $oGuiMatching->t('Von');
$oFilter->label_between = $oGuiMatching->t('bis');
$oBar->setElement($oFilter);

$oBar->setElement($oBar->createSeperator());

$aStatusFilter = array(
	'not_matched' => $oGuiMatching->t('noch nicht zugewiesen'),
	'matched' => $oGuiMatching->t('zugewiesen')
);
$aStatusFilter = Ext_Gui2_Util::addLabelItem($aStatusFilter, $oGuiMatching->t('Status'));

$oFilter = $oBar->createFilter('select');
$oFilter->id = 'matched_status';
$oFilter->value = 'not_matched';
$oFilter->filter_part = 'having';
$oFilter->select_options = $aStatusFilter;
$oFilter->filter_query = array(
	'not_matched' => "
		(
			`allocated_room_ids` IS NULL OR
			`not_allocated_room_ids` IS NOT NULL
		)
	",
	'matched' => "
		(
			`allocated_room_ids` IS NOT NULL AND
			`not_allocated_room_ids` IS NULL
		)
	"
);
$oBar->setElement($oFilter);

$oFilter = $oBar->createFilter('select');
$oFilter->id = 'matched_category';
$oFilter->value = '';
$oFilter->db_column = 'accommodation_id';
$oFilter->db_alias = 'kia';
$oFilter->db_operator = '=';
$oFilter->select_options = $aAccommodationCategories;
$oBar->setElement($oFilter);

// Schüler Status
$aCustomerStatus = Ext_TS_Inquiry_Index_Gui2_Data::getCustomerStatusOptions();
$oFilter = $oBar->createFilter('select');
$oFilter->id = 'student_status';
$oFilter->db_column = 'status_id';
$oFilter->db_alias = '';
$oFilter->value = '';
$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aCustomerStatus, $oGuiMatching->t('Schülerstatus'));
$oBar->setElement($oFilter);

// Index-Filter
$oClient = Ext_Thebing_System::getClient();
$aInboxes = $oClient->getInboxList(true, true);
$oFilter = $oBar->createFilter('select');
$oFilter->id = 'inbox_filter';
$oFilter->db_column = 'inbox';
$oFilter->db_alias = '';
$oFilter->value = '';
$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aInboxes, $oGuiMatching->t('Inbox'));
$oBar->setElement($oFilter);

$aYesNo = Ext_Thebing_Util::getYesNoArray(false);
$oFilter = $oBar->createFilter('select');
$oFilter->id = 'group_filter';
$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aYesNo, $oGuiMatching->t('Gruppe'), 'xNullx');
$oFilter->filter_query = array(
	'yes' => '
		`ts_i`.`group_id` != 0 ',
	'no' => '
		`ts_i`.`group_id` = 0 '
);
$oBar->setElement($oFilter);

/*
 * Der Dokumentenfilter darf nur angezeigt werden, wenn in den generellen Einstellungen
 * nicht "Kunden ohne Rechnung in Matching und Planung anzeigen" als Antwort "ab Rechnung"
 * gespeichert wurde.
 */
if ((int)$oClient->show_customer_without_invoice !== 2) {

	// Dokumentenfilter
	$oFilter = $oBar->createFilter('select');
	$oFilter->id = 'document_type';
	$oFilter->select_options = [
		''			=>	$oGuiMatching->t('Alle Dokumente'),
		'invoice'	=>	$oGuiMatching->t('nur Rechnungen'),
		'proforma'	=>	$oGuiMatching->t('nur Proforma')
	];
	$oFilter->filter_query = [
		'invoice' => '
			`ts_i`.`has_invoice` = 1
		',
		'proforma' => '
			(
				`ts_i`.`has_proforma` = 1 AND
				`ts_i`.`has_invoice` = 0
			)
		',
	];

	$oBar->setElement($oFilter);

}
$genders = Ext_TC_Util::getGenders();

$oFilter = $oBar->createFilter('select');
$oFilter->id = 'gender';
$oFilter->db_column = 'gender';
$oFilter->select_options = Ext_Gui2_Util::addLabelItem($genders, $oGuiMatching->t('Geschlecht'));
$oBar->setElement($oFilter);

$oFilter = $oBar->createFilter('select');
$oFilter->id = 'roomtype';
$oFilter->db_column = 'roomtype_id';
$oFilter->db_alias = 'kia';
$oFilter->select_options = Ext_Gui2_Util::addLabelItem($aRoomtypes, $oGuiMatching->t('Raumart'));
$oBar->setElement($oFilter);

$oBar->setElement($oBar->createSeperator());

// Kategorie  ignorieren
$oFilter = $oBar->createFilter('checkbox');
$oFilter->id = 'ignore_roomtype';
$oFilter->value = '1';
$oFilter->db_column = 'test';
$oFilter->db_alias = '';
$oFilter->db_operator = '=';
if($sView === 'parking') {
    $oFilter->label = $oGuiMatching->t('Parkplatzbuchung ignorieren');
} else {
    $oFilter->label = $oGuiMatching->t('Raumbuchung ignorieren');
}
$oFilter->skip_query = true;
$oBar->setElement($oFilter);

if($sView !== 'parking') {
    // Kategorie  ignorieren
    $oFilter = $oBar->createFilter('checkbox');
    $oFilter->id = 'ignore_category';
    $oFilter->value = '1';
    $oFilter->db_column = 'test';
    $oFilter->db_alias = '';
    $oFilter->db_operator = '=';
    $oFilter->label = $oGuiMatching->t('Gebuchte Kategorie ignorieren');
    $oFilter->skip_query = true;
    $oBar->setElement($oFilter);
}

$oGuiMatching->setBar($oBar);
	
$oBar = $oGuiMatching->createBar();

$oBar = $oGuiMatching->createBar();

$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('calendar'), 'request', $oGuiMatching->t('Übersicht'));
$oIcon->active = 1;
$oIcon->multipleId = 1;
$oIcon->action = 'overview';
$oIcon->label = $oGuiMatching->t('Übersicht');
$oBar->setElement($oIcon);

if($sView === 'parking') {
    $sIcon = 'fa-car';
} else {
    $sIcon = 'fa-bed';
}

$oIcon = $oBar->createIcon($sIcon, 'request', $oGuiMatching->t('Verfügbarkeit'));
$oIcon->active = 1;
$oIcon->multipleId = 1;
$oIcon->action = 'availability';
$oIcon->label = $oGuiMatching->t('Verfügbarkeit');
$oBar->setElement($oIcon);

$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('print'), 'method', $oGuiMatching->t('Drucken'));
$oIcon->active = 1;
$oIcon->multipleId = 1;
$oIcon->label = $oGuiMatching->t('Drucken');
$oBar->setElement($oIcon);
				

$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('shear'), 'openDialog', $oGuiMatching->t('Zerschneiden'));
$oIcon->action = 'matching_cut';
$oIcon->active = 1;
$oIcon->multipleId = 0;
$oIcon->label = $oGuiMatching->t('Zerschneiden');
$oBar->setElement($oIcon);
	

$oBar->setElement(
	$oBar->createLabelGroup($oGuiMatching->t('Export'))
);

if(
    $sView !== 'parking' &&
    (
        $sView === 'matching_other' ||
        System::d('debugmode') > 0
    )
) {
	$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('excel'), 'requestAsUrl', $oGuiMatching->t('Putzplan'));
	$oIcon->action = 'room_cleaning_schedule';
	$oIcon->additional = $sView;
	$oIcon->label = $oGuiMatching->t('Putzplan');
	$oIcon->active = 1;
	$oIcon->access = 'thebing_accommodation_other_roomcleaning';
	$oBar->setElement($oIcon);
}

$oIcon = $oBar->createCSVExport($oGuiMatching->t('Export CSV'));
$oIcon->label = $oGuiMatching->t('CSV');
$oBar->setElement($oIcon);

$oIcon = $oBar->createExcelExport();
$oBar->setElement($oIcon);

$oBar->setElement(
	$oBar->createLoadingIndicator()
);

$oGuiMatching->setBar($oBar);

$oColCustomerNum = new Ext_Gui2_Head();
$oColCustomerNum->db_column = 'customerNumber';
$oColCustomerNum->db_alias = '';
$oColCustomerNum->db_type = 'varchar';
$oColCustomerNum->select_column = 'customerNumber';
$oColCustomerNum->title = $oGuiMatching->t('K.Nr.');
$oColCustomerNum->width = Ext_Thebing_Util::getTableColumnWidth('customer_number');
$oColCustomerNum->width_resize = false;
$oColCustomerNum->format = 'Text';
$oGuiMatching->setColumn($oColCustomerNum);

$oColBookingNum = new Ext_Gui2_Head();
$oColBookingNum->db_column = 'number';
$oColBookingNum->db_alias = 'ts_i';
$oColBookingNum->select_column = 'booking_number';
$oColBookingNum->title = $oGuiMatching->t('Buchungsnummer');
$oColBookingNum->width = Ext_Thebing_Util::getTableColumnWidth('customer_number');
$oColBookingNum->width_resize = false;
$oColBookingNum->default = false;
$oColBookingNum->small = true;
$oGuiMatching->setColumn($oColBookingNum);

$oColCustomerName = new Ext_Gui2_Head();
$oColCustomerName->db_column = 'lastname';
$oColCustomerName->db_alias = 'tc_c';
$oColCustomerName->db_type = 'varchar';
$oColCustomerName->title = $oGuiMatching->t('Name');
$oColCustomerName->width = Ext_Thebing_Util::getTableColumnWidth('customer_name');
$oColCustomerName->width_resize = true;
$oColCustomerName->format = new Ext_Thebing_Gui2_Format_CustomerName();
$oGuiMatching->setColumn($oColCustomerName);

$oColGender = new Ext_Gui2_Head();
$oColGender->db_column = 'customer_gender';
$oColGender->db_alias = '';
$oColGender->db_type = 'varchar';
$oColGender->select_column = 'customer_gender';
$oColGender->title = $oGuiMatching->t('Geschlecht');
$oColGender->width = Ext_Thebing_Util::getTableColumnWidth('gender');
$oColGender->width_resize = false;
$oColGender->format = new Ext_Thebing_Gui2_Format_Gender();
$oGuiMatching->setColumn($oColGender);

$oColGroup = new Ext_Gui2_Head();
$oColGroup->db_column = 'short';
$oColGroup->db_alias = 'kg';
$oColGroup->db_type = 'varchar';
$oColGroup->select_column = 'group_short';
$oColGroup->title = Ext_Thebing_Gui2_Data::getGroupColumnTitle();
$oColGroup->width = Ext_Thebing_Util::getTableColumnWidth('group_short');
$oColGroup->width_resize = false;
$oColGroup->format = new Ext_Thebing_Gui2_Format_ColumnTitle('group_name');
$oGuiMatching->setColumn($oColGroup);

$oColGroupNum = new Ext_Gui2_Head();
$oColGroupNum->db_column = 'group_number';
$oColGroupNum->title = $oGuiMatching->t('Gruppennummer');
$oColGroupNum->default = false;
$oColGroupNum->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oGuiMatching->setColumn($oColGroupNum);

$oColAgency = new Ext_Gui2_Head();
$oColAgency->db_alias = 'ka';
$oColAgency->db_column = 'ext_2';
$oColAgency->select_column = 'agency_short';
$oColAgency->title = $oGuiMatching->t('Agentur');
$oColAgency->width = Ext_Thebing_Util::getTableColumnWidth('short_name');
$oGuiMatching->setColumn($oColAgency);

$oColInbox = new Ext_Gui2_Head();
$oColInbox->db_column = 'inbox_name';
$oColInbox->title = $oGuiMatching->t('Inbox');
$oColInbox->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oColInbox->default = false;
$oGuiMatching->setColumn($oColInbox);

$oColAccommodationStudentStatus = new Ext_Gui2_Head();
$oColAccommodationStudentStatus->db_column = 'status_id';
$oColAccommodationStudentStatus->db_alias = 'ts_i';
$oColAccommodationStudentStatus->sortable = false;
$oColAccommodationStudentStatus->title = $oGuiMatching->t('Schülerstatus');
$oColAccommodationStudentStatus->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oColAccommodationStudentStatus->width_resize = false;
$oColAccommodationStudentStatus->format = new Ext_Thebing_Gui2_Format_Select($aCustomerStatus);
$oGuiMatching->setColumn($oColAccommodationStudentStatus);

$oColAccommodationRoomType = new Ext_Gui2_Head();
$oColAccommodationRoomType->db_column = 'roomtype';
$oColAccommodationRoomType->title = $oGuiMatching->t('Raumart');
$oColAccommodationRoomType->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oGuiMatching->setColumn($oColAccommodationRoomType);

$oColNationality = new Ext_Gui2_Head();
$oColNationality->db_column = 'nationality';
$oColNationality->title = $oGuiMatching->t('Nationalität');
$oColNationality->width = Ext_Thebing_Util::getTableColumnWidth('nationality');
$oGuiMatching->setColumn($oColNationality);

$oColMothertongue = new Ext_Gui2_Head();
$oColMothertongue->db_column = 'customer_mother_tongue';
$oColMothertongue->title = $oGuiMatching->t('Muttersprache');
$oColMothertongue->width = Ext_Thebing_Util::getTableColumnWidth('language');
$oGuiMatching->setColumn($oColMothertongue);

$oColAge = new Ext_Gui2_Head();
$oColAge->db_column = 'customer_age';
$oColAge->db_alias = '';
$oColAge->select_column = 'customer_age';
$oColAge->title = $oGuiMatching->t('Alter');
$oColAge->width = Ext_Thebing_Util::getTableColumnWidth('age');
$oColAge->width_resize = false;
$oColAge->format = new Ext_Thebing_Gui2_Format_Age();
$oGuiMatching->setColumn($oColAge);

$oColChange = new Ext_Gui2_Head();
$oColChange->db_column = 'family_change';
$oColChange->db_alias = '';
$oColChange->select_column = 'family_change';
$oColChange->title = $oGuiMatching->t('Änderung');
$oColChange->width = Ext_Thebing_Util::getTableColumnWidth('icon');
$oColChange->width_resize = false;
$oColChange->sortable = false;
$oColChange->format = new Ext_Thebing_Gui2_Format_Matching_FamilyChange();
$oGuiMatching->setColumn($oColChange);

$oColCategory = new Ext_Gui2_Head();
$oColCategory->db_column = 'short_' . $sLang;
$oColCategory->db_alias = 'kac';
$oColCategory->db_type = 'varchar';
$oColCategory->select_column = 'accommodation';
$oColCategory->title = $oGuiMatching->t('Unterkunft');
$oColCategory->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oColCategory->width_resize = false;
$oColCategory->format = 'Text';
$oGuiMatching->setColumn($oColCategory);

$oColAccommodationFrom = new Ext_Gui2_Head();
$oColAccommodationFrom->db_column = 'from';
$oColAccommodationFrom->db_alias = 'kia';
$oColAccommodationFrom->db_type = 'date';
$oColAccommodationFrom->select_column = 'from';
$oColAccommodationFrom->title = $oGuiMatching->t('Von');
$oColAccommodationFrom->width = Ext_Thebing_Util::getTableColumnWidth('date');
$oColAccommodationFrom->width_resize = false;
$oColAccommodationFrom->format = new Ext_Thebing_Gui2_Format_Date();
$oGuiMatching->setColumn($oColAccommodationFrom);

$oColAccommodationUntil = new Ext_Gui2_Head();
$oColAccommodationUntil->db_column = 'until';
$oColAccommodationUntil->db_alias = 'kia';
$oColAccommodationUntil->db_type = 'date';
$oColAccommodationUntil->select_column = 'until';
$oColAccommodationUntil->title = $oGuiMatching->t('Bis');
$oColAccommodationUntil->width = Ext_Thebing_Util::getTableColumnWidth('date');
$oColAccommodationUntil->width_resize = false;
$oColAccommodationUntil->format = new Ext_Thebing_Gui2_Format_Date();
$oGuiMatching->setColumn($oColAccommodationUntil);

$oColCustomerNum = new Ext_Gui2_Head();
$oColCustomerNum->db_column = 'weeks';
$oColCustomerNum->db_alias = 'kia';
$oColCustomerNum->db_type = 'int';
$oColCustomerNum->select_column = 'weeks';
$oColCustomerNum->title = $oGuiMatching->t('Wochen');
$oColCustomerNum->width = Ext_Thebing_Util::getTableColumnWidth('date');
$oColCustomerNum->width_resize = false;
$oColCustomerNum->format = new Ext_TC_Gui2_Format_Int;
$oGuiMatching->setColumn($oColCustomerNum);

$oColShareWith = new Ext_Gui2_Head();
$oColShareWith->db_column = 'share_with';
$oColShareWith->db_alias = '';
$oColShareWith->select_column = 'share_with';
$oColShareWith->title = $oGuiMatching->t('Teilen mit');
$oColShareWith->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oColShareWith->width_resize = false;
$oColShareWith->format = new Ext_Thebing_Gui2_Format_Matching_ShareName();
$oGuiMatching->setColumn($oColShareWith);

$oColAccommodationComment = new Ext_Gui2_Head();
$oColAccommodationComment->db_column = 'acc_comment';
$oColAccommodationComment->db_alias = 'ts_i_m_d';
$oColAccommodationComment->db_type = 'text';
$oColAccommodationComment->select_column = 'acc_comment';
$oColAccommodationComment->title = $oGuiMatching->t('Kommentar');
$oColAccommodationComment->width = Ext_Thebing_Util::getTableColumnWidth('long_description');
$oColAccommodationComment->width_resize = false;
$oColAccommodationComment->format = 'Text';
$oColAccommodationComment->format = new Ext_Thebing_Gui2_Format_Matching_Comment();
$oGuiMatching->setColumn($oColAccommodationComment);

$oColAccommodationComment1 = new Ext_Gui2_Head();
$oColAccommodationComment1->db_column = 'comment';
$oColAccommodationComment1->db_alias = 'kia';
$oColAccommodationComment1->db_type = 'text';
$oColAccommodationComment1->title = $oGuiMatching->t('Unterkunftskommentar');
$oColAccommodationComment1->width = Ext_Thebing_Util::getTableColumnWidth('long_description');
$oColAccommodationComment1->width_resize = false;
#$oColAccommodationComment->format = new Ext_Thebing_Gui2_Format_Matching_Comment();
$oGuiMatching->setColumn($oColAccommodationComment1);

$oColAccommodationComment2 = new Ext_Gui2_Head();
$oColAccommodationComment2->db_column = 'acc_comment2';
$oColAccommodationComment2->db_alias = 'ts_i_m_d';
$oColAccommodationComment2->db_type = 'text';
$oColAccommodationComment2->select_column = 'acc_comment2';
$oColAccommodationComment2->title = $oGuiMatching->t('Zusatzkommentar');
$oColAccommodationComment2->width = Ext_Thebing_Util::getTableColumnWidth('long_description');
$oColAccommodationComment2->format = 'Text';
$oColAccommodationComment2->format = new Ext_Thebing_Gui2_Format_Matching_Comment();
$oGuiMatching->setColumn($oColAccommodationComment2);

$oColAccommodationComment = new Ext_Gui2_Head();
$oColAccommodationComment->db_column = 'acc_allergies';
$oColAccommodationComment->db_alias = 'ts_i_m_d';
$oColAccommodationComment->db_type = 'text';
$oColAccommodationComment->select_column = 'acc_allergies';
$oColAccommodationComment->title = $oGuiMatching->t('Allergien');
$oColAccommodationComment->width = Ext_Thebing_Util::getTableColumnWidth('long_description');
$oColAccommodationComment->width_resize = false;
$oColAccommodationComment->format = 'Text';
$oColAccommodationComment->format = new Ext_Thebing_Gui2_Format_Matching_Allergy();
$oGuiMatching->setColumn($oColAccommodationComment);

$oColJourneyCourse = new Ext_Gui2_Head();
$oColJourneyCourse->db_column = '';
$oColJourneyCourse->db_alias = '';
$oColJourneyCourse->select_column = 'course_names';
$oColJourneyCourse->title = $oGuiMatching->t('Gebuchte Kurse');
$oColJourneyCourse->width = Ext_Thebing_Util::getTableColumnWidth('name');
$oColJourneyCourse->width_resize = false;
$oColJourneyCourse->sortable = false;
$oColJourneyCourse->format = new Ext_TC_Gui2_Format_Multiselect(array(), '<br/>', 'string', '{||}');
$oGuiMatching->setColumn($oColJourneyCourse);

$colSemiAutomaticAdditionalServices = new Ext_Gui2_Head();
$colSemiAutomaticAdditionalServices->db_column = 'additional_services';
$colSemiAutomaticAdditionalServices->title = $oGuiMatching->t('Gebuchte semi-automatische Unterkunftsgebühren');
$colSemiAutomaticAdditionalServices->width = Ext_Thebing_Util::getTableColumnWidth('name');
$colSemiAutomaticAdditionalServices->width_resize = false;
$colSemiAutomaticAdditionalServices->sortable = false;
$oGuiMatching->setColumn($colSemiAutomaticAdditionalServices);

$colAmountOpen = new Ext_Gui2_Head();
// "_original" wegen Formatklasse
$colAmountOpen->db_column = 'amount_open_original';
$colAmountOpen->title = $oGuiMatching->t('Offener Betrag gesamt');
$colAmountOpen->width = Ext_Thebing_Util::getTableColumnWidth('date');
$colAmountOpen->format = new Ext_Thebing_Gui2_Format_Amount();
$colAmountOpen->style = new \Ts\Gui2\Style\Amount();
$oGuiMatching->setColumn($colAmountOpen);

$aOptionalInfo = [];

$aOptionalInfo['js'][] = '/admin/extensions/thebing/gui2/util.js';
$aOptionalInfo['js'][] = '/admin/extensions/thebing/accommodation/js/matching.js';

$aOptionalInfo['css'][] = '/assets/ts/css/gui2.css';
$aOptionalInfo['css'][] = '/admin/extensions/thebing/css/accommodation.css';
$aOptionalInfo['css'][] = '/assets/ts-accommodation/css/matching.css';

$oPage = new Ext_TS_Gui2_Page();

$oPage->setGui($oGuiMatching);

$sMatchingHtml = '';

$oBarContainer = new Ext_Gui2_Html_Div();
$oBarContainer->id = 'guiTableBars_matching_bottom';
$oBarContainer->class = 'matching_bar_container clearfix';

//Filter Bar ----------------------------
$oBar = new Ext_Gui2_Html_Div();
$oBar->class = 'divToolbar form-inline';

$oBar->id = 'matching_bar';
$oBar->style = 'height: 37px; width: 100%;';

/*$oLabelgroup = new Ext_Gui2_Html_Label();
$oLabelgroup->class = 'divToolbarLabelGroup';
$oLabelgroup->setElement($oGuiMatching->t('Filter'));
$oBar->setElement($oLabelgroup);*/

//Suchfeld ------------------------------
$oSearchDiv = new Ext_Gui2_Html_Div();
$oSearchDiv->class = 'guiBarFilter';

$oSearchField = new Ext_Gui2_Html_Input();
$oSearchField->class = 'form-control input-sm w200';
$oSearchField->id = 'matching_search_field';
$oSearchField->placeholder = $oGuiMatching->t('Name, Adresse, Bezirk, Ort, Raum, Ansprechpartner');

$oSearchDiv->setElement($oSearchField);
$oBar->setElement($oSearchDiv);

$oSeparatorDiv = new Ext_Gui2_Html_Div();
$oSeparatorDiv->class = 'divToolbarSeparator';
$oSeparatorDiv->setElement('<span class="hidden">::</span>');
$oBar->setElement($oSeparatorDiv);

//Checkbox Dokumentgültigkeit ignorieren ---------------------
$oCheckboxDiv = new Ext_Gui2_Html_Div();
$oCheckboxDiv->class = 'guiBarFilter';

$oLabel = new Ext_Gui2_Html_Div();
$oLabel->class = 'divToolbarLabel';
$oLabel->setElement($oGuiMatching->t('Dokumentgültigkeit ignorieren'));
$oCheckboxDiv->setElement($oLabel);

$oCheckbox = new Ext_Gui2_Html_Input();
$oCheckbox->type = 'checkbox';
$oCheckbox->id = 'matching_requirement_checkbox';

$oCheckboxDiv->setElement($oCheckbox);
$oBar->setElement($oCheckboxDiv);

//Checkbox optionales Ignorieren
$oCheckboxDiv = new Ext_Gui2_Html_Div();
$oCheckboxDiv->class = 'guiBarFilter';

$oLabel = new Ext_Gui2_Html_Div();
$oLabel->class = 'divToolbarLabel';
if($sView === 'parking') {
    $oLabel->setElement($oGuiMatching->t('Optionale Parkplätze anzeigen'));
} else {
    $oLabel->setElement($oGuiMatching->t('Optionale Betten anzeigen'));
}
$oCheckboxDiv->setElement($oLabel);

$oCheckbox = new Ext_Gui2_Html_Input();
$oCheckbox->type = 'checkbox';
$oCheckbox->id = 'matching_show_optional_beds';

$oCheckboxDiv->setElement($oCheckbox);
$oBar->setElement($oCheckboxDiv);

$oBarContainer->setElement($oBar);

/*
 * Verfügbarkeitsabfrage
 */
$oBar = new Ext_Gui2_Html_Div();
$oBar->class = 'divToolbar form-inline';
$oBar->id = 'matching_bar_availability';
$oBar->style = 'min-height: 37px; width: 100%;overflow:initial;';

$oDateFilter = new Ext_Gui2_Html_Div();
$oDateFilter->class = 'guiBarFilter';

$oLabelgroup = new Ext_Gui2_Html_Div();
$oLabelgroup->class = 'divToolbarLabel';
$oLabelgroup->setElement($oGuiMatching->t('Verfügbarkeit'));
$oDateFilter->setElement($oLabelgroup);

$oInputGroupFrom = new Ext_Gui2_Html_Div();
$oInputGroupFrom->class = 'input-group input-group-sm';

$oInputGroupAddon = new Ext_Gui2_Html_Div();
$oInputGroupAddon->class = 'input-group-addon';

$oCalendarIcon = new Ext_Gui2_Html_I();
$oCalendarIcon->class = 'fa fa-calendar';

$oInputGroupAddon->setElement($oCalendarIcon);
$oInputGroupFrom->setElement($oInputGroupAddon);

$oInputFrom = new Ext_Gui2_Html_Input();
$oInputFrom->type = 'text';
$oInputFrom->style = 'width:80px;';
$oInputFrom->class = 'form-control input-sm calendar_input';
$oInputFrom->id = 'availability_from';
$oInputFrom->name = 'availability_from';
$oInputGroupFrom->setElement($oInputFrom);

$oDateFilter->setElement($oInputGroupFrom);

$oLabelgroup = new Ext_Gui2_Html_Div();
$oLabelgroup->class = 'divToolbarLabel';
$oLabelgroup->setElement($oGuiMatching->t('bis'));
$oDateFilter->setElement($oLabelgroup);

$oInputGroupTo = new Ext_Gui2_Html_Div();
$oInputGroupTo->class = 'input-group input-group-sm';

$oInputGroupAddon = new Ext_Gui2_Html_Div();
$oInputGroupAddon->class = 'input-group-addon';

$oCalendarIcon = new Ext_Gui2_Html_I();
$oCalendarIcon->class = 'fa fa-calendar';

$oInputGroupAddon->setElement($oCalendarIcon);
$oInputGroupTo->setElement($oInputGroupAddon);

$oInputTo = new Ext_Gui2_Html_Input();
$oInputTo->type = 'text';
$oInputTo->style = 'width:80px;';
$oInputTo->class = 'form-control input-sm calendar_input';
$oInputTo->id = 'availability_to';
$oInputTo->name = 'availability_to';
$oInputGroupTo->setElement($oInputTo);

$oDateFilter->setElement($oInputGroupTo);

$oBar->setElement($oDateFilter);
		

$oCategorySelectContainer = new Ext_Gui2_Html_Div();
$oCategorySelectContainer->class = 'guiBarFilter';

$oCategorySelect = new Ext_Gui2_Html_Select();
$oCategorySelect->class = 'form-control input-sm';
$oCategorySelect->id = 'availability_category';
$oCategorySelect->name = 'availability_category';

foreach($aAccommodationCategories as $iAccommodationCategoryId=>$sAccommodationCategory) {
	$oCategorySelect->addOption($iAccommodationCategoryId, $sAccommodationCategory);
}

$oCategorySelectContainer->setElement($oCategorySelect);

$oBar->setElement($oCategorySelectContainer);

if($sView !== 'parking') {

    $oRoomtypeSelectContainer = new Ext_Gui2_Html_Div();
    $oRoomtypeSelectContainer->class = 'guiBarFilter';

    $oRoomtypeSelect = new Ext_Gui2_Html_Select();
    $oRoomtypeSelect->class = 'form-control input-sm';
    $oRoomtypeSelect->id = 'availability_roomtype';
    $oRoomtypeSelect->name = 'availability_roomtype';

    foreach($aRoomtypes as $iRoomtypesId=>$sRoomtypes) {
        $oRoomtypeSelect->addOption($iRoomtypesId, $sRoomtypes);
    }

    $oRoomtypeSelectContainer->setElement($oRoomtypeSelect);

    $oBar->setElement($oRoomtypeSelectContainer);

    $oBoardSelectContainer = new Ext_Gui2_Html_Div();
    $oBoardSelectContainer->class = 'guiBarFilter';

    $oBoardSelect = new Ext_Gui2_Html_Select();
    $oBoardSelect->class = 'form-control input-sm';
    $oBoardSelect->id = 'availability_board';
    $oBoardSelect->name = 'availability_board';

    foreach($aBoards as $iBoardId=>$sBoard) {
        $oBoardSelect->addOption($iBoardId, $sBoard);
    }

    $oBoardSelectContainer->setElement($oBoardSelect);

    $oBar->setElement($oBoardSelectContainer);

    // Erwachsen / Minderjährig
    $oAgeSelectContainer = new Ext_Gui2_Html_Div();
    $oAgeSelectContainer->class = 'guiBarFilter';

    $oAgeSelect = new Ext_Gui2_Html_Select();
    $oAgeSelect->class = 'form-control input-sm';
    $oAgeSelect->id = 'availability_age';
    $oAgeSelect->name = 'availability_age';
	foreach($aAgeOptions as $sAge => $sAgeDescription) {
		$oAgeSelect->addOption($sAge, $sAgeDescription);
	}
    $oAgeSelectContainer->setElement($oAgeSelect);

    $oBar->setElement($oAgeSelectContainer);

    // Geschlecht
    $oGenderSelectContainer = new Ext_Gui2_Html_Div();
    $oGenderSelectContainer->class = 'guiBarFilter';

    $oGenderSelect = new Ext_Gui2_Html_Select();
    $oGenderSelect->class = 'form-control input-sm';
    $oGenderSelect->id = 'availability_gender';
    $oGenderSelect->name = 'availability_gender';

    $aGenders = Ext_Thebing_Util::getGenders(true, '--- '.$oGuiMatching->t('Geschlecht').' ---');
    foreach($aGenders as $iGender=>$sGender) {
        $oGenderSelect->addOption($iGender, $sGender);
    }

    $oGenderSelectContainer->setElement($oGenderSelect);

    $oBar->setElement($oGenderSelectContainer);
}

// Kriterien
if($sView == 'matching_hostfamily') {
	
	$oCriteriaSelectContainer = new Ext_Gui2_Html_Div();
	$oCriteriaSelectContainer->class = 'guiBarFilter';
	$oCriteriaSelectContainer->style = 'position:relative;';

	$oCriteriaSelect = new Ext_Gui2_Html_Div();
	$oCriteriaSelect->role="button";
	$oCriteriaSelect->setDataAttribute('toggle', "collapse");
	$oCriteriaSelect->href="#collapseCriteria";
	$oCriteriaSelect->addAttributeValue('aria-expanded', "false");
	$oCriteriaSelect->addAttributeValue('aria-controls', "collapseCriteria");

	$oCriteriaSelect->setElement($oGuiMatching->t('Kriterien').' <i class="fa fa-angle-down"></i>');

	$oCriteriaSelectContainer->setElement($oCriteriaSelect);

	$sHtml = '<div class="collapse form-horizontal" id="collapseCriteria" style="display: none;">
	  ';

	$oMatching = new Ext_Thebing_Matching();
	$aCriteria = $oMatching->getCriteria();


	foreach($aCriteria as $sCriteriaType=>$aCriteriaType) {

		$sHtml .= '<strong>'.(($sCriteriaType=='hard')?$oGuiMatching->t('Harte Kriterien'):$oGuiMatching->t('Weiche Kriterien')).'</strong><br>';

		foreach($aCriteriaType as $oCriterion) {
			$sHtml .= '<div class="form-group">
					  <label for="'.$oCriterion->getField().'" class="col-sm-7 control-label">'.$oCriterion->getLabel(true).'</label>

					  <div class="col-sm-5">
					  ';
					  if($oCriterion->getType() == 'select') {
						  $sHtml .= '<select name="availability_criteria['.$oCriterion->getField().']" class="form-control input-sm" id="'.$oCriterion->getField().'">';
						  foreach($oCriterion->getOptions() as $mKey=>$sLabel) {
							  $sHtml .= '<option value="'.$mKey.'">'.$sLabel.'</option>';
						  }
						  $sHtml .= '</select>';
					  } else {
						  $sHtml .= '<input name="availability_criteria['.$oCriterion->getField().']" type="email" class="form-control input-sm" id="'.$oCriterion->getField().'">';
					  }

			$sHtml .= '          </div>
					</div>';
		}
	}

	$sHtml .= '</div>';

	$oCriteriaSelectContainer->setElement($sHtml);

	$oBar->setElement($oCriteriaSelectContainer);
}
 
$oButtonContainer = new Ext_Gui2_Html_Div();
$oButtonContainer->class = 'guiBarElement guiBarLink';
$oButtonContainer->id = 'availability_button';

$oButtonIconContainer = new Ext_Gui2_Html_Div();
$oButtonIconContainer->class = 'divToolbarIcon';

$oButtonIcon = new Ext_Gui2_Html_I();
$oButtonIcon->class = 'fa fa-colored fa-refresh';

$oButtonIconContainer->setElement($oButtonIcon);

$oButtonContainer->setElement($oButtonIconContainer);

$oButtonLabel = new Ext_Gui2_Html_Div();
$oButtonLabel->class = 'divToolbarLabel';
$oButtonLabel->setElement($oGuiMatching->t('Anzeigen'));

$oButtonContainer->setElement($oButtonLabel);

$oBar->setElement($oButtonContainer);

$oBarContainer->setElement($oBar);

$sMatchingHtml .= $oBarContainer->generateHTML();


// Matching HTML
$oDiv = new Ext_Gui2_Html_Div();
$oDiv->id = 'matching_body';

$sMatchingHtml .= $oDiv->generateHTML();


// Legende ----------------------------------------------------------------

$oLegend = new Ext_Gui2_Bar_Legend($oGuiMatching);
$oLegend->class = 'matching_legend';
$oLegend->addTitle($oGuiMatching->t('Legende'));
$oLegend->addInfo($oGuiMatching->t('Zuweisung durch System verändert'), Ext_Thebing_Util::getColor('changed'));
$oLegend->addInfo($oGuiMatching->t('aktuelle Zuweisung'), Ext_Thebing_Util::getColor('orange'));
$oLegend->addInfo($oGuiMatching->t('verfügbares Zimmer'), Ext_Thebing_Util::getColor('yellow'));
$oLegend->addInfo($oGuiMatching->t('männliche Belegung'), Ext_Thebing_Util::getColor('matching_male'));
$oLegend->addInfo($oGuiMatching->t('weibliche Belegung'), Ext_Thebing_Util::getColor('matching_female'));
$oLegend->addInfo($oGuiMatching->t('diverse Belegung'), Ext_Thebing_Util::getColor('soft_purple', 40));
$oLegend->addInfo($oGuiMatching->t('Zusammenreisende Belegung'), Ext_Thebing_Util::getColor('matching_share'));
$oLegend->addInfo($oGuiMatching->t('Schüler aus anderer Schule'), Ext_Thebing_Util::getColor('matching_other_school')); // steht auch fix im matching.js
$oLegend->addInfo($oGuiMatching->t('Reservierung'), Ext_Thebing_Util::getColor('substitute_part'));

$sLegend = $oLegend->generateHTML();
$sMatchingHtml .= '<div class="divFooter clearfix">'.$sLegend.'</div>';

$aMatchingData = [
	'title' => $oGuiMatching->t('Zuweisungen'),
	'html' => $sMatchingHtml,
];
$oPage->setGui($aMatchingData);

$oPage->height = 35;

$oPage->display($aOptionalInfo);
