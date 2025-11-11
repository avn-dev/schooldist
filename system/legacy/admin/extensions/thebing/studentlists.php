<?php

if(empty($sAccess)){
	$sAccess = 'thebing_invoice_inbox';
}

$sDescription					= 'Thebing » Invoice » Inbox';

## Objekte ##
$oSchool						= Ext_Thebing_Client::getFirstSchool($sAccess);
$aSchoolList					= Ext_Thebing_Client::getSchoolList(true);



if(!$oSchool){
	__pout('no School found');
	die();
}

$oCurrency 						= new Ext_Thebing_Currency_Util($oSchool->id);
$sInterfaceLanguage				= $oSchool->getInterfaceLanguage();
$sNameField						= 'name_'.$sInterfaceLanguage;

$sSchoolFileDir					= $oSchool->getSchoolFileDir(false, true);  

## Select Arrays
$aLangs							= Ext_Thebing_Data::getLanguageSkills(true, \System::getInterfaceLanguage());
$sDefaultLang					= $oSchool->getLanguage();

$aCountries						= Ext_Thebing_Data::getCountryList(true, true);
$aNationalities					= Ext_Thebing_Nationality::getNationalities(true, \System::getInterfaceLanguage(), 0);
$aNationalities					= Ext_Thebing_Util::addEmptyItem($aNationalities, Ext_Thebing_L10N::getEmptySelectLabel('nationalities'));

$aCorrespondenceLanguages		= Ext_Thebing_Data::getCorrespondenceLanguages(true, \System::getInterfaceLanguage());

$aAgencies						= Ext_Thebing_Client::getFirstClient()->getAgencies(true);
$aAgencies						= Ext_Thebing_Util::addEmptyItem($aAgencies, Ext_Thebing_L10N::getEmptySelectLabel('agency'));

$aPaymentMethods				= Ext_Thebing_Inquiry_Amount::getPaymentMethods();

$aCurrencys						= $oCurrency->getCurrencyList(2);

$aCustomerStatus				= $oSchool->getCustomerStatusList();
$aCustomerStatus = Ext_Thebing_Util::addEmptyItem($aCustomerStatus);
$aTransferCosts 				= array();
$aTransferCosts[0]				= $oGui->t('Schule');
$aTransferCosts[1]				= $oGui->t('Schüler');

$aReferer 						= $oSchool->getRefererList();
$aReferer						= Ext_Thebing_Util::addEmptyItem($aReferer);

$aDistance 						= Ext_Thebing_Data::getDistance();
$aFamilyAge 					= Ext_Thebing_Data::getFamilyAge();

$aTransfer						= Ext_Thebing_Data::getTransferList();


$aGender						= Ext_Thebing_Util::getGenders(true, $oGui->t('kein Geschlecht'));

$aRoomTypes						= $oSchool->getRoomtypeList(true);
$aVisumStatus 					= $oSchool->getVisumList($sDescription);
$aGroups						= $oSchool->getAllGroups(true); 

$aGroups						= Ext_Thebing_Util::addEmptyItem($aGroups);

## ENDE ##
$sFormat						= Ext_Thebing_Format::getFormat();
$sFormat						= str_replace(array('%d', '%m', '%Y'), array('DD','MM','YYYY'), $sFormat);

$iPassportDue					= (int)$oSchool->passport_due;
$iVisumDue						= (int)$oSchool->visum_due;

$oStyleUntilPass				= new Ext_Thebing_Gui2_Style_Until();
$oStyleUntilPass->iCount		= $iPassportDue;
$oStyleUntilPass->sDiffPart		= WDDate::DAY;
$oStyleUntilPass->sColor		= Ext_Thebing_Util::getColor('red');

$oStyleUntilVisum				= new Ext_Thebing_Gui2_Style_Until();
$oStyleUntilVisum->iCount		= $iVisumDue;
$oStyleUntilVisum->sDiffPart	= WDDate::DAY;
$oStyleUntilVisum->sColor		= Ext_Thebing_Util::getColor('red');

## ENDE ##

$sTitleAddon = '';
if($_VARS['inbox_id'] > 0) {

	$oClient = Ext_Thebing_Client::getInstance();
	$aInboxList = $oClient->getInboxList();
	$aInbox = $aInboxList[$_VARS['inbox_id']];

	if(!empty($aInbox['name'])) {
		$sTitleAddon = " - ".$aInbox['name'];
	}

}

## SPALTENGRUPPEN ##
$oColGroupPersonalDetails = $oGui->createColumnGroup('general');
$oColGroupPersonalDetails->title = $oGui->t('Persönliche Daten');

$oColGroupCourse = $oGui->createColumnGroup('course');
$oColGroupCourse->title = $oGui->t('Kurs');

$oColGroupAccommodation = $oGui->createColumnGroup('accommodation');
$oColGroupAccommodation->title = $oGui->t('Unterkunft');

$oColGroupMatching = $oGui->createColumnGroup('matching');
$oColGroupMatching->title = $oGui->t('Unterkunftszuweisung');

$oColGroupVisa = $oGui->createColumnGroup('visa');
$oColGroupVisa->title = $oGui->t('Visa');

$oColGroupIndividual = $oGui->createColumnGroup('flex');
$oColGroupIndividual->title = $oGui->t('Individuelle Felder');

$oColGroupAccounting = $oGui->createColumnGroup('payment');
$oColGroupAccounting->title = $oGui->t('Bezahlung');

$oColGroupTransfer = $oGui->createColumnGroup('transfer');
$oColGroupTransfer->title = $oGui->t('Transfer');

$oColGroupTransferProvider = $oGui->createColumnGroup('transfer_provider');
$oColGroupTransferProvider->title = $oGui->t('Transferanbieter');

## SPALTEN ##

$oClient = Ext_Thebing_System::getClient();
$aInboxes = $oClient->getInboxList(true, true);

$oColInbox							= new Ext_Gui2_Head();
$oColInbox->db_column				= 'inbox';
$oColInbox->db_alias					= 'ki';
$oColInbox->select_column			= 'inbox';
$oColInbox->title					= $oGui->t('Inbox');
$oColInbox->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColInbox->width_resize				= false;
$oColInbox->format					= new Ext_Thebing_Gui2_Format_Select($aInboxes);
$oColInbox->small					= true;
$oColInbox->default = false;

$oColSchool									= new Ext_Gui2_Head();
$oColSchool->db_column						= 'ext_1';
$oColSchool->db_alias						= 'cdb2';
$oColSchool->title							= $oGui->t('Schule');
$oColSchool->width							= Ext_Thebing_Util::getTableColumnWidth('name');
$oColSchool->width_resize					= false;
$oColSchool->sortable						= false;
$oColSchool->format							= new Ext_TC_GUI2_Format_List('Ext_Thebing_School', 'school_id', 'ext_1');

$oColInvoice								= new Ext_Gui2_Head();
$oColInvoice->db_column						= 'document_number';
$oColInvoice->db_type						= 'varchar';
$oColInvoice->select_column					= 'document_number';
$oColInvoice->title							= $oGui->t('Re.Nr.');
$oColInvoice->width							= Ext_Thebing_Util::getTableColumnWidth('document_number');
$oColInvoice->width_resize					= false;
$oColInvoice->format						= 'Text';

$oColCustomerNum							= new Ext_Gui2_Head();
$oColCustomerNum->db_column					= 'customerNumber';
$oColCustomerNum->db_alias					= '';
$oColCustomerNum->db_type					= 'varchar';
$oColCustomerNum->select_column				= 'customerNumber';
$oColCustomerNum->title						= $oGui->t('K.Nr.');
$oColCustomerNum->width						= Ext_Thebing_Util::getTableColumnWidth('customer_number');
$oColCustomerNum->width_resize				= false;
$oColCustomerNum->format					= 'Text';

$oColCustomerCheckin					    = new Ext_Gui2_Head();
$oColCustomerCheckin->db_column				= 'checkin';
$oColCustomerCheckin->db_alias			    = '';
$oColCustomerCheckin->title					= $oGui->t('Eingecheckt');
$oColCustomerCheckin->width					= Ext_Thebing_Util::getTableColumnWidth('datetime');
$oColCustomerCheckin->format				= new Ext_Thebing_Gui2_Format_Date_Time();
$oColCustomerCheckin->style					= new Ext_TS_Inquiry_Index_Gui2_Style_Checkin();
$oColCustomerCheckin->default				= false;

$oColCustomerAddress						= new Ext_Gui2_Head();
$oColCustomerAddress->db_column				= 'customer_address';
$oColCustomerAddress->db_alias				= '';
$oColCustomerAddress->db_type				= 'varchar';
$oColCustomerAddress->select_column			= 'customer_address';
$oColCustomerAddress->title					= $oGui->t('Adresse');
$oColCustomerAddress->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerAddress->width_resize			= false;
$oColCustomerAddress->format				= 'Text';
$oColCustomerAddress->group					= $oColGroupPersonalDetails;

$oColCustomerAddress2						= new Ext_Gui2_Head();
$oColCustomerAddress2->db_column			= 'customer_address2';
$oColCustomerAddress2->db_alias				= '';
$oColCustomerAddress2->db_type				= 'varchar';
$oColCustomerAddress2->select_column		= 'customer_address2';
$oColCustomerAddress2->title				= $oGui->t('Adresszusatz');
$oColCustomerAddress2->width				= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerAddress2->width_resize			= false;
$oColCustomerAddress2->format				= 'Text';
$oColCustomerAddress2->group				= $oColGroupPersonalDetails;

$oColCustomerZip							= new Ext_Gui2_Head();
$oColCustomerZip->db_column					= 'customer_zip';
$oColCustomerZip->db_alias					= '';
$oColCustomerZip->db_type					= 'varchar';
$oColCustomerZip->select_column				= 'customer_zip';
$oColCustomerZip->title						= $oGui->t('PLZ');
$oColCustomerZip->width						= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerZip->width_resize				= false;
$oColCustomerZip->format					= 'Text';
$oColCustomerZip->group						= $oColGroupPersonalDetails;

$oColCustomerCity							= new Ext_Gui2_Head();
$oColCustomerCity->db_column				= 'customer_city';
$oColCustomerCity->db_alias					= '';
$oColCustomerCity->db_type					= 'varchar';
$oColCustomerCity->select_column			= 'customer_city';
$oColCustomerCity->title					= $oGui->t('Stadt');
$oColCustomerCity->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerCity->width_resize				= false;
$oColCustomerCity->format					= 'Text';
$oColCustomerCity->group					= $oColGroupPersonalDetails;

$oColCustomerState							= new Ext_Gui2_Head();
$oColCustomerState->db_column				= 'customer_state';
$oColCustomerState->db_alias				= '';
$oColCustomerState->db_type					= 'varchar';
$oColCustomerState->select_column			= 'customer_state';
$oColCustomerState->title					= $oGui->t('Bundesland');
$oColCustomerState->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerState->width_resize			= false;
$oColCustomerState->format					= 'Text';
$oColCustomerState->group					= $oColGroupPersonalDetails;

$oColCustomerCountry						= new Ext_Gui2_Head();
$oColCustomerCountry->db_column				= 'customer_country';
$oColCustomerCountry->db_alias				= '';
$oColCustomerCountry->db_type				= 'varchar';
$oColCustomerCountry->title					= $oGui->t('Land');
$oColCustomerCountry->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerCountry->width_resize			= false;
$oColCustomerCountry->group					= $oColGroupPersonalDetails;

$oColCustomerName							= new Ext_Gui2_Head();
$oColCustomerName->db_column				= 'customer_name';
$oColCustomerName->db_alias					= '';
$oColCustomerName->db_type					= 'varchar';
$oColCustomerName->select_column			= 'customer_name';
$oColCustomerName->title					= $oGui->t('Name'); // Von MK entfernt: .', '.$oGui->t('Vorname');
$oColCustomerName->width					= Ext_Thebing_Util::getTableColumnWidth('customer_name');
$oColCustomerName->width_resize				= true;
$oColCustomerName->format					= new Ext_Thebing_Gui2_Format_CustomerName();
$oColCustomerName->group					= $oColGroupPersonalDetails;

$oColGroup									= new Ext_Gui2_Head();
$oColGroup->db_column						= 'short';
$oColGroup->db_alias						= 'kg';
$oColGroup->db_type							= 'varchar';
$oColGroup->select_column					= 'group_short';
$oColGroup->title							= Ext_Thebing_Gui2_Data::getGroupColumnTitle();
$oColGroup->width							= Ext_Thebing_Util::getTableColumnWidth('group_short');
$oColGroup->width_resize					= false;
$oColGroup->format							= new Ext_Thebing_Gui2_Format_ColumnTitle('group_name');
$oColGroup->group							= $oColGroupPersonalDetails;

$oColGender									= new Ext_Gui2_Head();
$oColGender->db_column						= 'customer_gender';
$oColGender->db_alias						= '';
$oColGender->db_type						= 'varchar';
$oColGender->select_column					= 'customer_gender';
$oColGender->title							= $oGui->t('Geschlecht');
$oColGender->width							= Ext_Thebing_Util::getTableColumnWidth('gender');
$oColGender->width_resize					= false;
$oColGender->format							= new Ext_Thebing_Gui2_Format_Gender();
$oColGender->group							= $oColGroupPersonalDetails;

$oColNationality							= new Ext_Gui2_Head();
$oColNationality->db_column					= 'customer_nationality_full';
$oColNationality->db_alias					= '';
$oColNationality->db_type					= 'varchar';
$oColNationality->title						= $oGui->t('Nationalität');
$oColNationality->width						= Ext_Thebing_Util::getTableColumnWidth('nationality');
$oColNationality->width_resize				= false;
$oColNationality->group						= $oColGroupPersonalDetails;

$oColMothertongue							= new Ext_Gui2_Head();
$oColMothertongue->db_column				= 'customer_mother_tongue';
$oColMothertongue->db_alias					= '';
$oColMothertongue->db_type					= 'varchar';
$oColMothertongue->title					= $oGui->t('Muttersprache');
$oColMothertongue->width					= Ext_Thebing_Util::getTableColumnWidth('language');
$oColMothertongue->width_resize				= false;
$oColMothertongue->group					= $oColGroupPersonalDetails;

$oColCorrespondingLang						= new Ext_Gui2_Head();
$oColCorrespondingLang->db_column			= 'corresponding_language';
$oColCorrespondingLang->db_alias			= '';
$oColCorrespondingLang->db_type				= 'varchar';
$oColCorrespondingLang->title				= $oGui->t('Korrespondenzsprache');
$oColCorrespondingLang->width				= Ext_Thebing_Util::getTableColumnWidth('language');
$oColCorrespondingLang->width_resize		= false;
//$oColCorrespondingLang->format				= new Ext_Thebing_Gui2_Format_Language(\System::getInterfaceLanguage());
$oColCorrespondingLang->small				= true;
$oColCorrespondingLang->group				= $oColGroupPersonalDetails;

$oColAge									= new Ext_Gui2_Head();
$oColAge->db_column							= 'birthday';
$oColAge->db_alias							= 'cdb1';
$oColAge->select_column						= 'customer_age';
$oColAge->title								= $oGui->t('Alter');
$oColAge->width								= Ext_Thebing_Util::getTableColumnWidth('age');
$oColAge->width_resize						= false;
$oColAge->format							= new Ext_TS_Gui2_Format_Age();
$oColAge->group								= $oColGroupPersonalDetails;

$oColBirthday								= new Ext_Gui2_Head();
$oColBirthday->db_column					= 'customer_birthday';
$oColBirthday->db_alias						= '';
$oColBirthday->db_type						= 'timestamp';
$oColBirthday->select_column				= 'customer_birthday';
$oColBirthday->title						= $oGui->t('Geburtsdatum');
$oColBirthday->width						= Ext_Thebing_Util::getTableColumnWidth('date');
$oColBirthday->width_resize					= false;
$oColBirthday->format						= new Ext_Thebing_Gui2_Format_Date();
$oColBirthday->group						= $oColGroupPersonalDetails;

$oColReferer								= new Ext_Gui2_Head();
$oColReferer->db_column						= 'referer';
$oColReferer->db_alias						= '';
$oColReferer->title							= $oGui->t('Referenz');
$oColReferer->width							= Ext_Thebing_Util::getTableColumnWidth('name');
$oColReferer->width_resize					= false;
$oColReferer->sortable						= false;
//$oColReferer->format						= new Ext_TC_GUI2_Format_List('Ext_Thebing_Advertency', 'referer', $sNameField);
$oColReferer->format = new Ext_Thebing_Gui2_Format_Select(\Ext_TS_Referrer::getReferrers(true));
$oColReferer->group							= $oColGroupPersonalDetails;

$oColAccRoomtype							= new Ext_Gui2_Head();
$oColAccRoomtype->db_column					= 'roomtype_id';
$oColAccRoomtype->db_alias					= 'kia';
$oColAccRoomtype->title						= $oGui->t('Unterkunftsart');
$oColAccRoomtype->width						= Ext_Thebing_Util::getTableColumnWidth('name');
$oColAccRoomtype->width_resize				= false;
$oColAccRoomtype->format					= new Ext_Thebing_Gui2_Format_Select($aRoomTypes);
$oColAccRoomtype->small						= true;
$oColAccRoomtype->group						= $oColGroupAccommodation;

$oColVisumRequired							= new Ext_Gui2_Head();
$oColVisumRequired->select_column			= 'visum_required';
$oColVisumRequired->db_column				= 'required';
$oColVisumRequired->db_alias				= 'ts_j_t_v_d';
$oColVisumRequired->title					= $oGui->t('Visum wird benötigt');
$oColVisumRequired->width					= Ext_Thebing_Util::getTableColumnWidth('yes_no');
$oColVisumRequired->width_resize			= false;
$oColVisumRequired->format					= new Ext_Thebing_Gui2_Format_YesNo();
$oColVisumRequired->group					= $oColGroupVisa;

$oColVisumId								= new Ext_Gui2_Head();
$oColVisumId->select_column					= 'visum_servis_id';
$oColVisumId->db_column						= 'servis_id';
$oColVisumId->db_alias						= 'ts_j_t_v_d';
$oColVisumId->title							= $oGui->t('Visum-ID');
$oColVisumId->width							= Ext_Thebing_Util::getTableColumnWidth('number');
$oColVisumId->width_resize					= false;
$oColVisumId->group							= $oColGroupVisa;

$oColVisumTrackingNumber					= new Ext_Gui2_Head();
$oColVisumTrackingNumber->select_column		= 'visum_tracking_number';
$oColVisumTrackingNumber->db_column			= 'tracking_number';
$oColVisumTrackingNumber->db_alias			= 'ts_j_t_v_d';
$oColVisumTrackingNumber->title				= $oGui->t('Mail Tracking Nummer');
$oColVisumTrackingNumber->width				= Ext_Thebing_Util::getTableColumnWidth('number');
$oColVisumTrackingNumber->width_resize		= false;
$oColVisumTrackingNumber->group				= $oColGroupVisa;

$oColVisumPassNumber						= new Ext_Gui2_Head();
$oColVisumPassNumber->select_column			= 'visum_passport_number';
$oColVisumPassNumber->db_column				= 'passport_number';
$oColVisumPassNumber->db_alias				= 'ts_j_t_v_d';
$oColVisumPassNumber->title					= $oGui->t('Passnummer');
$oColVisumPassNumber->width					= Ext_Thebing_Util::getTableColumnWidth('number');
$oColVisumPassNumber->width_resize			= false;
$oColVisumPassNumber->group					= $oColGroupVisa;

$oColVisumPassportDateOfIssue					= new Ext_Gui2_Head();
$oColVisumPassportDateOfIssue->select_column	= 'visum_passport_date_of_issue';
$oColVisumPassportDateOfIssue->db_alias			= 'ts_j_t_v_d';
$oColVisumPassportDateOfIssue->title			= $oGui->t('Pass Ausstellungsdatum');
$oColVisumPassportDateOfIssue->width			= Ext_Thebing_Util::getTableColumnWidth('date');
$oColVisumPassportDateOfIssue->width_resize		= false;
$oColVisumPassportDateOfIssue->format			= new Ext_Thebing_Gui2_Format_Date();
$oColVisumPassportDateOfIssue->group			= $oColGroupVisa;

$oColVisumDateFrom							= new Ext_Gui2_Head();
$oColVisumDateFrom->select_column			= 'visum_date_from';
$oColVisumDateFrom->db_column				= 'date_from';
$oColVisumDateFrom->db_alias				= 'ts_j_t_v_d';
$oColVisumDateFrom->title					= $oGui->t('Visum gültig von');
$oColVisumDateFrom->width					= Ext_Thebing_Util::getTableColumnWidth('date');
$oColVisumDateFrom->width_resize			= false;
$oColVisumDateFrom->format					= new Ext_Thebing_Gui2_Format_Date();
$oColVisumDateFrom->group					= $oColGroupVisa;

$oColVisumDateUntil							= new Ext_Gui2_Head();
$oColVisumDateUntil->select_column			= 'visum_date_until';
$oColVisumDateUntil->db_column				= 'date_until';
$oColVisumDateUntil->db_alias				= 'ts_j_t_v_d';
$oColVisumDateUntil->title					= $oGui->t('Visum gültig bis');
$oColVisumDateUntil->width					= Ext_Thebing_Util::getTableColumnWidth('date');
$oColVisumDateUntil->width_resize			= false;
$oColVisumDateUntil->format					= new Ext_Thebing_Gui2_Format_Date();
$oColVisumDateUntil->style					= $oStyleUntilVisum;
$oColVisumDateUntil->group					= $oColGroupVisa;

$oColCourseFulllist							= new Ext_Gui2_Head();
$oColCourseFulllist->db_column				= 'course_id';
$oColCourseFulllist->db_alias				= 'kic';
$oColCourseFulllist->db_type				= 'varchar';
$oColCourseFulllist->select_column			= 'course_fulllist';
$oColCourseFulllist->title					= $oGui->t('Kursliste');
$oColCourseFulllist->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCourseFulllist->width_resize			= true;
$oColCourseFulllist->format					= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('course_name');
$oColCourseFulllist->sortable				= false;
$oColCourseFulllist->group					= $oColGroupCourse;

$oColTotalCourseWeeksFulllist				= new Ext_Gui2_Head();
$oColTotalCourseWeeksFulllist->db_column	= 'weeks';
$oColTotalCourseWeeksFulllist->db_alias		= 'kic';
$oColTotalCourseWeeksFulllist->select_column= 'course_fulllist';
$oColTotalCourseWeeksFulllist->title		= L10N::t('Kurswochen', $sDescription);
$oColTotalCourseWeeksFulllist->width		= Ext_Thebing_Util::getTableColumnWidth('name');
$oColTotalCourseWeeksFulllist->width_resize	= false;
$oColTotalCourseWeeksFulllist->sortable 	= false;
$oColTotalCourseWeeksFulllist->format		= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('course_weeks');
$oColTotalCourseWeeksFulllist->group		= $oColGroupCourse; 

$oColCourseTimeFromFulllist					= new Ext_Gui2_Head();
$oColCourseTimeFromFulllist->db_column		= 'from';
$oColCourseTimeFromFulllist->db_alias		= 'kic';
$oColCourseTimeFromFulllist->db_type		= 'varchar';
$oColCourseTimeFromFulllist->select_column	= 'crs_time_from_fulllist';
$oColCourseTimeFromFulllist->title			= $oGui->t('Kursstart');
$oColCourseTimeFromFulllist->width			= Ext_Thebing_Util::getTableColumnWidth('date');
$oColCourseTimeFromFulllist->width_resize	= false;
$oColCourseTimeFromFulllist->format			= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('course_start');
$oColCourseTimeFromFulllist->group			= $oColGroupCourse;

$oColCourseTimeToFulllist					= new Ext_Gui2_Head();
$oColCourseTimeToFulllist->db_column		= 'until';
$oColCourseTimeToFulllist->db_alias			= 'kic';
$oColCourseTimeToFulllist->db_type			= 'varchar';
$oColCourseTimeToFulllist->select_column	= 'crs_time_to_fulllist';
$oColCourseTimeToFulllist->title			= $oGui->t('Kursende');
$oColCourseTimeToFulllist->width			= Ext_Thebing_Util::getTableColumnWidth('date');
$oColCourseTimeToFulllist->width_resize		= false;
$oColCourseTimeToFulllist->format			= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('course_end');
$oColCourseTimeToFulllist->group			= $oColGroupCourse;

//$oColCourseLevel							= new Ext_Gui2_Head();
//$oColCourseLevel->db_column					= 'level_id';
//$oColCourseLevel->db_alias					= 'kic';
//$oColCourseLevel->db_type					= 'varchar';
//$oColCourseLevel->select_column				= 'course_level_fulllist';
//$oColCourseLevel->title						= $oGui->t('Kursniveau');
//$oColCourseLevel->width						= Ext_Thebing_Util::getTableColumnWidth('name');
//$oColCourseLevel->width_resize				= true;
//$oColCourseLevel->format					= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('level_name');
//$oColCourseLevel->group						= $oColGroupCourse;

$oColFirstCourseFrom						= new Ext_Gui2_Head();
$oColFirstCourseFrom->db_column				= 'first_course_start';
$oColFirstCourseFrom->db_alias				= '';
$oColFirstCourseFrom->select_column			= 'first_course_start';
$oColFirstCourseFrom->title					= $oGui->t('Erster Kursbeginn');
$oColFirstCourseFrom->width					= Ext_Thebing_Util::getTableColumnWidth('date');
$oColFirstCourseFrom->format				= new Ext_Thebing_Gui2_Format_Date();
$oColFirstCourseFrom->small					= true;
$oColFirstCourseFrom->group					= $oColGroupCourse;

$oColLastCourseUntil = new Ext_Gui2_Head();
$oColLastCourseUntil->db_column	= 'last_course_end';
$oColLastCourseUntil->db_alias = '';
$oColLastCourseUntil->select_column	= 'last_course_end';
$oColLastCourseUntil->title	= $oGui->t('Letztes Kursende');
$oColLastCourseUntil->width	= Ext_Thebing_Util::getTableColumnWidth('date');
$oColLastCourseUntil->format = new Ext_Thebing_Gui2_Format_Date();
$oColLastCourseUntil->small	= true;
$oColLastCourseUntil->group	= $oColGroupCourse;

$oColAccFulllist							= new Ext_Gui2_Head();
$oColAccFulllist->db_column					= 'accommodation_id';
$oColAccFulllist->db_alias					= 'kia';
$oColAccFulllist->db_type					= 'varchar';
$oColAccFulllist->select_column				= 'accommodation_fulllist';
$oColAccFulllist->title						= $oGui->t('Unterkunft');
$oColAccFulllist->width						= Ext_Thebing_Util::getTableColumnWidth('name');
$oColAccFulllist->width_resize				= false;
$oColAccFulllist->format					= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationCategories();
$oColAccFulllist->group						= $oColGroupAccommodation;


$oColAccTimeFromFulllist					= new Ext_Gui2_Head();
$oColAccTimeFromFulllist->db_column			= 'from';
$oColAccTimeFromFulllist->db_alias			= 'kia';
$oColAccTimeFromFulllist->db_type			= 'varchar';
$oColAccTimeFromFulllist->select_column		= 'acc_time_from_fulllist';
$oColAccTimeFromFulllist->title				= $oGui->t('Startdatum');
$oColAccTimeFromFulllist->width				= Ext_Thebing_Util::getTableColumnWidth('date');
$oColAccTimeFromFulllist->width_resize		= false;
$oColAccTimeFromFulllist->format			= new Ext_Thebing_Gui2_Format_Date_List();
$oColAccTimeFromFulllist->small				= true;
$oColAccTimeFromFulllist->group				= $oColGroupAccommodation;

$oColAccTimeToFulllist						= new Ext_Gui2_Head();
$oColAccTimeToFulllist->db_column			= 'until';
$oColAccTimeToFulllist->db_alias			= 'kia';
$oColAccTimeToFulllist->db_type				= 'varchar';
$oColAccTimeToFulllist->select_column		= 'acc_time_to_fulllist';
$oColAccTimeToFulllist->title				= $oGui->t('Enddatum');
$oColAccTimeToFulllist->width				= Ext_Thebing_Util::getTableColumnWidth('date');
$oColAccTimeToFulllist->width_resize		= false;
$oColAccTimeToFulllist->format				= new Ext_Thebing_Gui2_Format_Date_List();
$oColAccTimeToFulllist->small				= true;
$oColAccTimeToFulllist->group				= $oColGroupAccommodation;

// START Bezahl spalten
/*
	$oColAmountInitial						= new Ext_Gui2_Head();
	$oColAmountInitial->db_column			= 'amount_initial';
	$oColAmountInitial->db_alias			= '';
	$oColAmountInitial->db_type				= 'varchar';
	$oColAmountInitial->select_column		= 'amount_initial';
	$oColAmountInitial->title				= $oGui->t('Betrag vorort');
	$oColAmountInitial->width				= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmountInitial->width_resize		= false;
	$oColAmountInitial->format				= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmountInitial->style				= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmountInitial->small				= true;
	$oColAmountInitial->group					= $oColGroupAccounting;

	$oColPaymentsAtSchool					= new Ext_Gui2_Head();
	$oColPaymentsAtSchool->db_column		= 'payments_local';
	$oColPaymentsAtSchool->db_alias			= '';
	$oColPaymentsAtSchool->select_column	= 'payments_local';
	$oColPaymentsAtSchool->title			= $oGui->t('Vorort bezahlt');
	$oColPaymentsAtSchool->width			= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColPaymentsAtSchool->width_resize		= false;
	$oColPaymentsAtSchool->format			= new Ext_Thebing_Gui2_Format_Amount();
	$oColPaymentsAtSchool->style			= new Ext_Thebing_Gui2_Style_Amount();
	$oColPaymentsAtSchool->small			= true;
	$oColPaymentsAtSchool->group				= $oColGroupAccounting;

	$oColAmountDueAtSchool					= new Ext_Gui2_Head();
	$oColAmountDueAtSchool->db_column		= 'amount_due_at_school';
	$oColAmountDueAtSchool->db_alias		= '';
	$oColAmountDueAtSchool->db_type			= 'float';
	$oColAmountDueAtSchool->select_column	= 'amount_due_at_school';
	$oColAmountDueAtSchool->title			= $oGui->t('Offener Betrag vorort');
	$oColAmountDueAtSchool->width			= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmountDueAtSchool->width_resize	= false;
	//$oColAmountDueAtSchool->sortable		= false;
	$oColAmountDueAtSchool->format			= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmountDueAtSchool->style			= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmountDueAtSchool->small			= true;
	$oColAmountDueAtSchool->group				= $oColGroupAccounting;
	
	$oColAmount								= new Ext_Gui2_Head();
	$oColAmount->db_column					= 'amount';
	$oColAmount->db_alias					= 'ki';
	$oColAmount->db_type					= 'float';
	$oColAmount->select_column				= 'amount';
	$oColAmount->title						= $oGui->t('Betrag vor Anreise');
	$oColAmount->width						= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmount->width_resize				= false;
	$oColAmount->format						= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmount->style						= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmount->small						= true;
	$oColAmount->group						= $oColGroupAccounting;

	$oColPayments							= new Ext_Gui2_Head();
	$oColPayments->db_column				= 'payments';
	$oColPayments->db_alias					= '';
	$oColPayments->select_column			= 'payments';
	$oColPayments->title					= $oGui->t('vor Anreise bezahlt');
	$oColPayments->width					= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColPayments->width_resize				= false;
	$oColPayments->format					= new Ext_Thebing_Gui2_Format_Amount();
	$oColPayments->style					= new Ext_Thebing_Gui2_Style_Amount();
	$oColPayments->small					= true;
	$oColPayments->group					= $oColGroupAccounting;

	$oColAmountDueArrival					= new Ext_Gui2_Head();
	$oColAmountDueArrival->db_column		= 'amount_due_arrival';
	$oColAmountDueArrival->db_alias			= '';
	$oColAmountDueArrival->db_type			= 'float';
	$oColAmountDueArrival->select_column	= 'amount_due_arrival';
	$oColAmountDueArrival->title			= $oGui->t('Offener Betrag vor Anreise');
	$oColAmountDueArrival->width			= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmountDueArrival->width_resize		= false;
	//$oColAmountDueArrival->sortable			= false;
	$oColAmountDueArrival->format			= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmountDueArrival->style			= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmountDueArrival->small			= true;
	$oColAmountDueArrival->group				= $oColGroupAccounting;

	$oColAmountDueGeneral					= new Ext_Gui2_Head();
	$oColAmountDueGeneral->db_column		= 'amount_due_general';
	$oColAmountDueGeneral->db_alias			= '';
	$oColAmountDueGeneral->db_type			= 'float';
	$oColAmountDueGeneral->select_column	= 'amount_due_general'; 
	$oColAmountDueGeneral->title			= $oGui->t('Offener Betrag gesamt');
	$oColAmountDueGeneral->width			= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmountDueGeneral->width_resize		= false;
	//$oColAmountDueGeneral->sortable			= false;
	$oColAmountDueGeneral->format			= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmountDueGeneral->style			= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmountDueGeneral->small			= true;
	$oColAmountDueGeneral->group			= $oColGroupAccounting;

	$oColAmountRefund						= new Ext_Gui2_Head(); 
	$oColAmountRefund->db_column			= 'payments_refund';
	$oColAmountRefund->db_alias				= '';
	$oColAmountRefund->db_type				= 'float';
	$oColAmountRefund->select_column		= 'payments_refund'; 
	$oColAmountRefund->title				= $oGui->t('Refund gesamt');
	$oColAmountRefund->width				= Ext_Thebing_Util::getTableColumnWidth('amount');
	$oColAmountRefund->width_resize			= false;
	$oColAmountRefund->format				= new Ext_Thebing_Gui2_Format_Amount();
	$oColAmountRefund->style				= new Ext_Thebing_Gui2_Style_Amount();
	$oColAmountRefund->small				= true;
	$oColAmountRefund->group				= $oColGroupAccounting;

$oColAmountNetto							= new Ext_Gui2_Head(); 
$oColAmountNetto->db_column					= 'netto_amount';  
$oColAmountNetto->db_alias					= '';
$oColAmountNetto->select_column				= 'netto_amount';
$oColAmountNetto->title						= $oGui->t('Betrag Netto');
$oColAmountNetto->width						= Ext_Thebing_Util::getTableColumnWidth('amount');
$oColAmountNetto->width_resize				= false;
$oColAmountNetto->format					= new Ext_Thebing_Gui2_Format_Amount();
$oColAmountNetto->style						= new Ext_Thebing_Gui2_Style_Amount();
$oColAmountNetto->small						= true;
$oColAmountNetto->group						= $oColGroupAccounting;

$oColCredit									= new Ext_Gui2_Head();
$oColCredit->db_column						= 'amount_credit';
$oColCredit->db_alias						= '';
$oColCredit->select_column					= 'amount_credit';
$oColCredit->title							= $oGui->t('Schülergutschrift');
$oColCredit->width							= Ext_Thebing_Util::getTableColumnWidth('amount');
$oColCredit->width_resize					= false;
$oColCredit->format							= new Ext_Thebing_Gui2_Format_Credit();
$oColCredit->style							= new Ext_Thebing_Gui2_Style_Amount();
$oColCredit->small							= true;
$oColCredit->group							= $oColGroupAccounting;
*/
$oColVisumName								= new Ext_Gui2_Head();
$oColVisumName->db_column					= 'visum_name';
$oColVisumName->db_alias					= '';
$oColVisumName->db_type						= 'varchar';
$oColVisumName->select_column				= 'visum_name';
$oColVisumName->title						= $oGui->t('Visa Typ');
$oColVisumName->width						= Ext_Thebing_Util::getTableColumnWidth('name');
$oColVisumName->width_resize				= false;
$oColVisumName->sortable					= false;
$oColVisumName->format						= new Ext_TC_GUI2_Format_List('Ext_Thebing_Visum', 'visum_name', 'name');
$oColVisumName->group						= $oColGroupVisa;

$oColAgency									= new Ext_Gui2_Head();
$oColAgency->db_column						= 'ext_2';
$oColAgency->db_alias						= 'ka';
$oColAgency->db_type						= 'varchar';
$oColAgency->select_column					= 'agency_name';
$oColAgency->title							= $oGui->t('Agentur');
$oColAgency->width							= Ext_Thebing_Util::getTableColumnWidth('short_name');
$oColAgency->width_resize					= false;
$oColAgency->format							= new Ext_Thebing_Gui2_Format_ColumnTitle('agency_full_name', true);

$oColAgencyNumber							= new Ext_Gui2_Head();
$oColAgencyNumber->db_column				= 'number';
$oColAgencyNumber->db_alias					= 'ka_n';
$oColAgencyNumber->db_type					= 'varchar';
$oColAgencyNumber->select_column			= 'agency_number';
$oColAgencyNumber->title					= $oGui->t('Agenturnummer');
$oColAgencyNumber->width					= Ext_Thebing_Util::getTableColumnWidth('number');
$oColAgencyNumber->width_resize				= false;

$oColCustomerComment						= new Ext_Gui2_Head();
$oColCustomerComment->db_column				= 'customer_comment';
$oColCustomerComment->db_alias				= '';
$oColCustomerComment->db_type				= 'varchar';
$oColCustomerComment->select_column			= 'customer_comment';
$oColCustomerComment->title					= $oGui->t('Kommentar');
$oColCustomerComment->width					= Ext_Thebing_Util::getTableColumnWidth('comment');
$oColCustomerComment->width_resize			= true;
$oColCustomerComment->format				= new Ext_Gui2_View_Format_ToolTip('customer_comment',true,80);
$oColCustomerComment->group					= $oColGroupPersonalDetails;

$oColCustomerPhone							= new Ext_Gui2_Head();
$oColCustomerPhone->db_column				= 'customer_phone';
$oColCustomerPhone->db_alias				= '';
$oColCustomerPhone->db_type					= 'varchar';
$oColCustomerPhone->select_column			= 'customer_phone';
$oColCustomerPhone->title					= $oGui->t('Telefon');
$oColCustomerPhone->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerPhone->width_resize			= true;
$oColCustomerPhone->format					= new Tc\Gui2\Format\Contact\Detail('phone_private');
$oColCustomerPhone->group					= $oColGroupPersonalDetails;
$oColCustomerPhone->sortable = 0;

$oColCustomerMobile							= new Ext_Gui2_Head();
$oColCustomerMobile->db_column				= 'customer_mobile';
$oColCustomerMobile->db_alias				= '';
$oColCustomerMobile->db_type					= 'varchar';
$oColCustomerMobile->select_column			= 'customer_mobile';
$oColCustomerMobile->title					= $oGui->t('Mobiltelefon');
$oColCustomerMobile->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerMobile->width_resize			= true;
$oColCustomerMobile->format					= new Tc\Gui2\Format\Contact\Detail('phone_mobile');
$oColCustomerMobile->group					= $oColGroupPersonalDetails;
$oColCustomerMobile->sortable = 0;

$oColCustomerStatus							= new Ext_Gui2_Head();
$oColCustomerStatus->db_column				= 'status_id';
$oColCustomerStatus->db_alias				= 'ki';
$oColCustomerStatus->db_type				= 'int';
#$oColCustomerStatus->select_column			= 'status_id';
$oColCustomerStatus->title					= $oGui->t('Schülerstatus');
$oColCustomerStatus->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColCustomerStatus->width_resize			= true;
$oColCustomerStatus->format					= new Ext_Thebing_Gui2_Format_CustomerStatus();
$oColCustomerStatus->sortable				= 0;
$oColCustomerStatus->group					= $oColGroupPersonalDetails;

//$oColAccAllergies							= new Ext_Gui2_Head();
//$oColAccAllergies->db_column				= 'acc_allergies';
//$oColAccAllergies->db_alias					= '';
//$oColAccAllergies->db_type					= 'varchar';
//$oColAccAllergies->select_column			= 'acc_allergies';
//$oColAccAllergies->title					= $oGui->t('Allergien');
//$oColAccAllergies->width					= Ext_Thebing_Util::getTableColumnWidth('name');
//$oColAccAllergies->width_resize				= false;
//$oColAccAllergies->format					= new Ext_Gui2_View_Format_ToolTip('acc_allergies',true,80);
//$oColAccAllergies->group					= $oColGroupMatching;

$oColAccFullNameList						= new Ext_Gui2_Head();
$oColAccFullNameList->sortable				= 0;
$oColAccFullNameList->db_column				= 'accommodation_fullnamelist';
$oColAccFullNameList->select_column			= 'accommodation_fullnamelist';
$oColAccFullNameList->title					= $oGui->t('Accommodation Name');
$oColAccFullNameList->width					= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullNameList->width_resize			= true;
$oColAccFullNameList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullNameList->small					= true;
$oColAccFullNameList->group					= $oColGroupMatching;
$oColAccFullNameList->default = false;


//$oColAccRoomFullNameList					= new Ext_Gui2_Head();
//$oColAccRoomFullNameList->sortable			= 0;
//$oColAccRoomFullNameList->db_column			= 'accommodation_room_fullnamelist';
//$oColAccRoomFullNameList->select_column		= 'accommodation_room_fullnamelist';
//$oColAccRoomFullNameList->title				= $oGui->t('Familie Raum');
//$oColAccRoomFullNameList->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
//$oColAccRoomFullNameList->width_resize		= true;
//$oColAccRoomFullNameList->format			= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo();
//$oColAccRoomFullNameList->small				= true;
//$oColAccRoomFullNameList->group				= $oColGroupMatching;

$oColAccFullStreetList						= new Ext_Gui2_Head();
$oColAccFullStreetList->sortable			= 0;
$oColAccFullStreetList->db_column			= 'accommodation_fulllstreetlist';
$oColAccFullStreetList->select_column		= 'accommodation_fulllstreetlist';
$oColAccFullStreetList->title				= $oGui->t('Accommodation Streets');
$oColAccFullStreetList->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullStreetList->width_resize		= false;
$oColAccFullStreetList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullStreetList->small				= true;
$oColAccFullStreetList->group				= $oColGroupMatching;
$oColAccFullStreetList->default = false;

$oColAccFullAddressAddonList = new Ext_Gui2_Head();
$oColAccFullAddressAddonList->sortable = false;
$oColAccFullAddressAddonList->db_column = 'accommodation_fulladdressaddonlist';
$oColAccFullAddressAddonList->title = $oGui->t('Unterkunft Adresszusatz');
$oColAccFullAddressAddonList->width = Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullAddressAddonList->format = new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullAddressAddonList->small = true;
$oColAccFullAddressAddonList->default = false;
$oColAccFullAddressAddonList->group = $oColGroupMatching;

$oColAccFullZipList							= new Ext_Gui2_Head();
$oColAccFullZipList->sortable				= 0;
$oColAccFullZipList->db_column				= 'accommodation_fulllziplist';
$oColAccFullZipList->select_column			= 'accommodation_fulllziplist';
$oColAccFullZipList->title					= $oGui->t('Accommodation ZIP');
$oColAccFullZipList->width					= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullZipList->width_resize			= true;
$oColAccFullZipList->format					= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullZipList->small					= true;
$oColAccFullZipList->group					= $oColGroupMatching;
$oColAccFullZipList->default = false;

$oColAccFullCityList						= new Ext_Gui2_Head();
$oColAccFullCityList->sortable				= 0;
$oColAccFullCityList->db_column				= 'accommodation_fulllcitytlist';
$oColAccFullCityList->select_column			= 'accommodation_fulllcitytlist';
$oColAccFullCityList->title					= $oGui->t('Accommodation City');
$oColAccFullCityList->width					= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullCityList->width_resize			= true;
$oColAccFullCityList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullCityList->small					= true;
$oColAccFullCityList->group					= $oColGroupMatching;
$oColAccFullCityList->default = false;

$oColAccFullPhoneList						= new Ext_Gui2_Head();
$oColAccFullPhoneList->sortable				= 0;
$oColAccFullPhoneList->db_column			= 'accommodation_fulllphonelist';
$oColAccFullPhoneList->select_column		= 'accommodation_fulllphonelist';
$oColAccFullPhoneList->title				= $oGui->t('Unterkunft Telefon');
$oColAccFullPhoneList->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullPhoneList->width_resize			= true;
$oColAccFullPhoneList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullPhoneList->small				= true;
$oColAccFullPhoneList->group				= $oColGroupMatching;
$oColAccFullPhoneList->default = false;

$oColAccFullPhone2List						= new Ext_Gui2_Head();
$oColAccFullPhone2List->sortable			= 0;
$oColAccFullPhone2List->db_column			= 'accommodation_fulllphone2list';
$oColAccFullPhone2List->select_column		= 'accommodation_fulllphone2list';
$oColAccFullPhone2List->title				= $oGui->t('Unterkunft Telefon 2');
$oColAccFullPhone2List->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullPhone2List->width_resize		= false;
$oColAccFullPhone2List->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullPhone2List->small				= true;
$oColAccFullPhone2List->group				= $oColGroupMatching;
$oColAccFullPhone2List->default = false;

$oColAccFullMobileList						= new Ext_Gui2_Head();
$oColAccFullMobileList->sortable			= 0;
$oColAccFullMobileList->db_column			= 'accommodation_fulllmobilelist';
$oColAccFullMobileList->select_column		= 'accommodation_fulllmobilelist';
$oColAccFullMobileList->title				= $oGui->t('Accommodation Mobile');
$oColAccFullMobileList->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullMobileList->width_resize		= false;
$oColAccFullMobileList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullMobileList->small				= true;
$oColAccFullMobileList->group				= $oColGroupMatching;
$oColAccFullPhone2List->default = false;

$oColAccFullMailList						= new Ext_Gui2_Head();
$oColAccFullMailList->sortable				= 0;
$oColAccFullMailList->db_column				= 'accommodation_fulllmaillist';
$oColAccFullMailList->select_column			= 'accommodation_fulllmaillist';
$oColAccFullMailList->title					= $oGui->t('Accommodation Mail');
$oColAccFullMailList->width					= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
$oColAccFullMailList->width_resize			= true;
$oColAccFullMailList->format				= new Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2();
$oColAccFullMailList->small					= true;
$oColAccFullMailList->group					= $oColGroupMatching;
$oColAccFullMailList->default = false;

//$oColAccFullContactList						= new Ext_Gui2_Head();
//$oColAccFullContactList->sortable			= 0;
//$oColAccFullContactList->db_column			= 'accommodation_fulllcontactlist';
//$oColAccFullContactList->select_column		= 'accommodation_fulllcontactlist';
//$oColAccFullContactList->title				= $oGui->t('Accommodation Kontakt');
//$oColAccFullContactList->width				= Ext_Thebing_Util::getTableColumnWidth('familyinfo');
//$oColAccFullContactList->width_resize		= false;
//$oColAccFullContactList->format				= 'Text';
//$oColAccFullContactList->small				= true;
//$oColAccFullContactList->group				= $oColGroupMatching;

$oColTransfer								= new Ext_Gui2_Head();
$oColTransfer->db_column					= 'transfer_mode';
$oColTransfer->db_alias						= 'ki';
$oColTransfer->select_column				= 'transfer_mode';
$oColTransfer->title						= $oGui->t('Transfer');
$oColTransfer->width						= Ext_Thebing_Util::getTableColumnWidth('date_time');
$oColTransfer->width_resize					= false;
$oColTransfer->format						= new Ext_Thebing_Gui2_Format_Transfer();
$oColTransfer->group						= $oColGroupTransfer;

$oColAccComment								= new Ext_Gui2_Head();
$oColAccComment->db_column					= 'acc_comment';
$oColAccComment->db_alias					= '';
$oColAccComment->select_column				= 'acc_comment';
$oColAccComment->title						= $oGui->t('Unterkunft Kommentar');
$oColAccComment->width						= Ext_Thebing_Util::getTableColumnWidth('comment');
$oColAccComment->width_resize				= false;
$oColAccComment->format						= new Ext_Gui2_View_Format_ToolTip('acc_comment',true,80);
$oColAccComment->group						= $oColGroupAccommodation;

$oColAccComment2							= new Ext_Gui2_Head();
$oColAccComment2->db_column					= 'acc_comment2';
$oColAccComment2->db_alias					= '';
$oColAccComment2->select_column				= 'acc_comment2';
$oColAccComment2->title						= $oGui->t('Unterkunft Kommentar2');
$oColAccComment2->width						= Ext_Thebing_Util::getTableColumnWidth('comment');
$oColAccComment2->width_resize				= false;
$oColAccComment2->format					= new Ext_Gui2_View_Format_ToolTip('acc_comment2',true,80);
$oColAccComment2->group						= $oColGroupAccommodation;

$oColFirstCourseName						= new Ext_Gui2_Head();
$oColFirstCourseName->db_column				= $sNameField;
$oColFirstCourseName->db_alias				= 'ktc';
$oColFirstCourseName->select_column			= 'course_name';
$oColFirstCourseName->title					= $oGui->t('Erster Kursname');
$oColFirstCourseName->width					= Ext_Thebing_Util::getTableColumnWidth('name');
$oColFirstCourseName->width_resize			= true;
$oColFirstCourseName->format				= new Ext_Thebing_Gui2_Format_Inquiry_Courselist('first_course_name');
$oColFirstCourseName->group					= $oColGroupCourse;
$oColFirstCourseName->sortable				= false;


//
//$oColDepartureDay							= new Ext_Gui2_Head();
//$oColDepartureDay->db_column				= 'departure_day';
//$oColDepartureDay->db_alias					= 'ki';
//$oColDepartureDay->select_column			= 'departure_day';
//$oColDepartureDay->title					= $oGui->t('Abreisetag nach Buchung');
//$oColDepartureDay->width					= Ext_Thebing_Util::getTableColumnWidth('date');
//$oColDepartureDay->width_resize				= false;
//$oColDepartureDay->format					= new Ext_Thebing_Gui2_Format_Date();
//$oColDepartureDay->small				= true;

// START Transfer

	$oColProvider								= new Ext_Gui2_Head();
	$oColProvider->db_column					= 'provider_id';
	$oColProvider->db_alias						= 'kit';
	$oColProvider->select_column				= 'provider_id';
	$oColProvider->title						= $oGui->t('Unternehmen');
	$oColProvider->width						= Ext_Thebing_Util::getTableColumnWidth('name');
	$oColProvider->format						= new Ext_Thebing_Gui2_Format_Transfer_ProviderName();
	$oColProvider->small						= true;
	$oColProvider->group						= $oColGroupTransfer;

	$oColDriver									= new Ext_Gui2_Head();
	$oColDriver->db_column						= 'driver_id';
	$oColDriver->db_alias						= 'kit';
	$oColDriver->select_column					= 'driver_id';
	$oColDriver->title							= $oGui->t('Fahrer');
	$oColDriver->width							= Ext_Thebing_Util::getTableColumnWidth('name');
	$oColDriver->format							= new Ext_Thebing_Gui2_Format_Transfer_Driver();
	$oColDriver->small							= true;
	$oColDriver->group							= $oColGroupTransfer;

	$oColTransferDate							= new Ext_Gui2_Head();
	$oColTransferDate->db_column				= 'transfer_date';
	$oColTransferDate->db_alias					= 'kit';
	$oColTransferDate->select_column			= 'transfer_date';
	$oColTransferDate->title					= $oGui->t('Datum');
	$oColTransferDate->width					= Ext_Thebing_Util::getTableColumnWidth('date');
	$oColTransferDate->width_resize				= false;
	$oColTransferDate->format					= new Ext_Thebing_Gui2_Format_Date_List();
	$oColTransferDate->small					= true;
	$oColTransferDate->group					= $oColGroupTransfer;

	$oColTransferTime							= new Ext_Gui2_Head();
	$oColTransferTime->db_column				= 'transfer_time';
	$oColTransferTime->db_alias					= 'kit';
	$oColTransferTime->select_column			= 'transfer_time';
	$oColTransferTime->title					= $oGui->t('Zeit');
	$oColTransferTime->width					= Ext_Thebing_Util::getTableColumnWidth('time');
	$oColTransferTime->width_resize				= false;
	$oColTransferTime->group					= $oColGroupTransfer;
	$oColTransferTime->format					= new Ext_Thebing_Gui2_Format_Time();
	$oColTransferTime->small					= true;

	$oColAirline								= new Ext_Gui2_Head();
	$oColAirline->db_column						= 'airline';
	$oColAirline->db_alias						= 'kit';
	$oColAirline->select_column					= 'airline';
	$oColAirline->title							= $oGui->t('Fluggesellschaft');
	$oColAirline->width							= Ext_Thebing_Util::getTableColumnWidth('name');
	$oColAirline->width_resize					= false;
	$oColAirline->format						= 'Text';
	$oColAirline->small							= true;
	$oColAirline->group							= $oColGroupTransfer;

	$oColFlynumber								= new Ext_Gui2_Head();
	$oColFlynumber->db_column					= 'flightnumber';
	$oColFlynumber->db_alias					= 'kit';
	$oColFlynumber->select_column				= 'flightnumber';
	$oColFlynumber->title						= $oGui->t('Flugnummer');
	$oColFlynumber->width_resize				= false;
	$oColFlynumber->width						= Ext_Thebing_Util::getTableColumnWidth('name');
	$oColFlynumber->format						= 'Text';
	$oColFlynumber->small						= true;
	$oColFlynumber->group						= $oColGroupTransfer;

	$oColTransferPickup							= new Ext_Gui2_Head();
	$oColTransferPickup->db_column				= 'pickup';
	$oColTransferPickup->db_alias				= 'kit';
	$oColTransferPickup->select_column			= 'pickup';
	$oColTransferPickup->title					= $oGui->t('Abholzeit');
	$oColTransferPickup->width					= Ext_Thebing_Util::getTableColumnWidth('time');
	$oColTransferPickup->width_resize			= false;
	$oColTransferPickup->small					= true;
	$oColTransferPickup->format					= new Ext_Thebing_Gui2_Format_Time();
	$oColTransferPickup->small					= true;
	$oColTransferPickup->group					= $oColGroupTransfer;

	$oColTransferType							= new Ext_Gui2_Head();
	$oColTransferType->db_column				= 'transfer_type';
	$oColTransferType->db_alias					= 'kit';
	$oColTransferType->select_column			= 'transfer_type';
	$oColTransferType->title					= $oGui->t('Art');
	$oColTransferType->width					= Ext_Thebing_Util::getTableColumnWidth('name');
	$oColTransferType->width_resize				= false;
	$oColTransferType->format					= new Ext_Thebing_Gui2_Format_Transfer_Type();
	$oColTransferType->small					= true;
	$oColTransferType->group					= $oColGroupTransfer;

	$oColTransferComment						= new Ext_Gui2_Head();
	$oColTransferComment->db_column				= 'value';
	$oColTransferComment->db_alias				= 'wa_journey';
	$oColTransferComment->select_column			= 'transfer_comment';
	$oColTransferComment->title					= $oGui->t('Transfer Kommentar');
	$oColTransferComment->width					= Ext_Thebing_Util::getTableColumnWidth('comment');
	$oColTransferComment->width_resize			= false;
	$oColTransferComment->format				= new Ext_Gui2_View_Format_ToolTip('transfer_comment',true,80);
	$oColTransferComment->group					= $oColGroupTransfer;

	if($sView == 'transfer') {
		$oColIndividualTransferComment							= new Ext_Gui2_Head();
		$oColIndividualTransferComment->db_column = 'comment';
		$oColIndividualTransferComment->db_alias = 'kit';
		$oColIndividualTransferComment->select_column = 'specific_transfer_comment';
		$oColIndividualTransferComment->title					= $oGui->t('Spezifischer Transfer Kommentar');
		$oColIndividualTransferComment->width					= Ext_Thebing_Util::getTableColumnWidth('comment');
		$oColIndividualTransferComment->width_resize			= false;
		$oColIndividualTransferComment->format					= new Ext_Gui2_View_Format_ToolTip('specific_transfer_comment',true,80);
		$oColIndividualTransferComment->group					= $oColGroupTransfer;
	}

//	$oColTransferArrComment						= new Ext_Gui2_Head();
//	$oColTransferArrComment->db_column			= 'arrival_comment';
//	$oColTransferArrComment->db_alias			= 'kit_arr';
//	$oColTransferArrComment->select_column		= 'arrival_comment';
//	$oColTransferArrComment->title				= $oGui->t('Anreise Kommentar');
//	$oColTransferArrComment->width				= Ext_Thebing_Util::getTableColumnWidth('comment');
//	$oColTransferArrComment->width_resize		= false;
//	$oColTransferArrComment->sortable			= false;
//	$oColTransferArrComment->format				= new Ext_Gui2_View_Format_ToolTip('arrival_comment',true,80);
//	$oColTransferArrComment->group				= $oColGroupTransfer;
//
//	$oColTransferDepComment						= new Ext_Gui2_Head();
//	$oColTransferDepComment->db_column			= 'departure_comment';
//	$oColTransferDepComment->db_alias			= 'kit_dep';
//	$oColTransferDepComment->select_column		= 'departure_comment';
//	$oColTransferDepComment->title				= $oGui->t('Abreise Kommentar');
//	$oColTransferDepComment->width				= Ext_Thebing_Util::getTableColumnWidth('comment');
//	$oColTransferDepComment->width_resize		= false;
//	$oColTransferDepComment->sortable			= false;
//	$oColTransferDepComment->format				= new Ext_Gui2_View_Format_ToolTip('departure_comment',true,80);
//	$oColTransferDepComment->group				= $oColGroupTransfer;

	$oColTransferStart							= new Ext_Gui2_Head();
	$oColTransferStart->db_column				= 'transfer_start';
	$oColTransferStart->db_alias				= 'kit';
	$oColTransferStart->select_column			= 'transfer_start';
	$oColTransferStart->title					= $oGui->t('Anreise');
	$oColTransferStart->width					= Ext_Thebing_Util::getTableColumnWidth('comment');
	$oColTransferStart->format					= new Ext_Thebing_Gui2_Format_Transfer_Locationname('start');
	$oColTransferStart->sortable				= false;
	$oColTransferStart->group					= $oColGroupTransfer;

	$oColTransferEnd							= new Ext_Gui2_Head();
	$oColTransferEnd->db_column					= 'transfer_end';
	$oColTransferEnd->db_alias					= 'kit';
	$oColTransferEnd->select_column				= 'transfer_end';
	$oColTransferEnd->title						= $oGui->t('Abreise');
	$oColTransferEnd->width						= Ext_Thebing_Util::getTableColumnWidth('comment');
	$oColTransferEnd->format					= new Ext_Thebing_Gui2_Format_Transfer_Locationname('end');
	$oColTransferEnd->sortable					= false;
	$oColTransferEnd->group						= $oColGroupTransfer;

	$oColTransferArr							= new Ext_Gui2_Head();
	$oColTransferArr->db_column					= 'transfer_date';
	$oColTransferArr->db_alias					= 'kit_arr';
	$oColTransferArr->select_column				= 'arrival_date';
	$oColTransferArr->sortable					= false;
	$oColTransferArr->title						= $oGui->t('Startdatum');
	$oColTransferArr->width						= Ext_Thebing_Util::getTableColumnWidth('date');
	$oColTransferArr->width_resize				= false;
	$oColTransferArr->small						= true;
	$oColTransferArr->format					= new Ext_Thebing_Gui2_Format_Date();
	$oColTransferArr->group						= $oColGroupTransfer;
	
	$oColTransferArrTime						= new Ext_Gui2_Head();
	$oColTransferArrTime->db_column				= 'arrival_time';
	$oColTransferArrTime->db_alias				= '';
	$oColTransferArrTime->select_column			= 'arrival_time';
	$oColTransferArrTime->sortable				= false;
	$oColTransferArrTime->title					= $oGui->t('Anreisezeit');
	$oColTransferArrTime->width					= Ext_Thebing_Util::getTableColumnWidth('time');
	$oColTransferArrTime->width_resize			= false;
	$oColTransferArrTime->small					= true;
	$oColTransferArrTime->format				= new Ext_Thebing_Gui2_Format_Time();
	$oColTransferArrTime->group					= $oColGroupTransfer;

	$oColTransferDep							= new Ext_Gui2_Head();
	$oColTransferDep->db_column					= 'transfer_date';
	$oColTransferDep->db_alias					= 'kit_dep';
	$oColTransferDep->select_column				= 'departure_date';
	$oColTransferDep->sortable					= false;
	$oColTransferDep->title						= $oGui->t('Enddatum');
	$oColTransferDep->width						= Ext_Thebing_Util::getTableColumnWidth('date');
	$oColTransferDep->width_resize				= false;
	$oColTransferDep->small						= true;
	$oColTransferDep->format					= new Ext_Thebing_Gui2_Format_Date();
	$oColTransferDep->group						= $oColGroupTransfer;
	
	$oColTransferDepTime						= new Ext_Gui2_Head();
	$oColTransferDepTime->db_column				= 'departure_time';
	$oColTransferDepTime->db_alias				= '';
	$oColTransferDepTime->select_column			= 'departure_time';
	$oColTransferDepTime->sortable				= false;
	$oColTransferDepTime->title					= $oGui->t('Abreisezeit');
	$oColTransferDepTime->width					= Ext_Thebing_Util::getTableColumnWidth('time');
	$oColTransferDepTime->width_resize			= false;
	$oColTransferDepTime->small					= true;
	$oColTransferDepTime->format				= new Ext_Thebing_Gui2_Format_Time();
	$oColTransferDepTime->group					= $oColGroupTransfer;
	
	
	// Transferliste
	$oColProviderRequested							= new Ext_Gui2_Head();
	$oColProviderRequested->db_column				= 'transfer_requested';
	$oColProviderRequested->select_column			= 'transfer_requested';
	$oColProviderRequested->title					= $oGui->t('Angefragt');
	$oColProviderRequested->width					= Ext_Thebing_Util::getTableColumnWidth('date_time');
	$oColProviderRequested->width_resize			= false;
	$oColProviderRequested->format					= new Ext_Thebing_Gui2_Format_Date_Time();
	$oColProviderRequested->style					= new Ext_Thebing_Gui2_Style_Transfer_Info();
	$oColProviderRequested->small					= true;
	$oColProviderRequested->group					= $oColGroupTransferProvider;

	$oColProviderAssigned							= new Ext_Gui2_Head();
	$oColProviderAssigned->db_column				= 'provider_updated';
	$oColProviderAssigned->db_alias					= 'kit';
	$oColProviderAssigned->select_column			= 'provider_updated';
	$oColProviderAssigned->title					= $oGui->t('Zugewiesen');
	$oColProviderAssigned->width					= Ext_Thebing_Util::getTableColumnWidth('date_time');
	$oColProviderAssigned->width_resize				= false;
	$oColProviderAssigned->format					= new Ext_Thebing_Gui2_Format_Date_Time();
	$oColProviderAssigned->style					= new Ext_Thebing_Gui2_Style_Transfer_Info();
	$oColProviderAssigned->small					= true;
	$oColProviderAssigned->group					= $oColGroupTransferProvider;

	$oColProviderConfirmed							= new Ext_Gui2_Head();
	$oColProviderConfirmed->db_column				= 'provider_confirmed';
	$oColProviderConfirmed->db_alias				= 'kit';
	$oColProviderConfirmed->select_column			= 'provider_confirmed';
	$oColProviderConfirmed->title					= $oGui->t('Bestätigt');
	$oColProviderConfirmed->width					= Ext_Thebing_Util::getTableColumnWidth('date_time');
	$oColProviderConfirmed->width_resize			= false;
	$oColProviderConfirmed->format					= new Ext_Thebing_Gui2_Format_Date_Time();
	$oColProviderConfirmed->style					= new Ext_Thebing_Gui2_Style_Transfer_Info();
	$oColProviderConfirmed->small					= true;
	$oColProviderConfirmed->group					= $oColGroupTransferProvider;

	$oColAccommodationConfirmed						= new Ext_Gui2_Head();
	$oColAccommodationConfirmed->db_column			= 'accommodation_confirmed';
	$oColAccommodationConfirmed->db_alias			= 'kit';
	$oColAccommodationConfirmed->select_column		= 'accommodation_confirmed';
	$oColAccommodationConfirmed->title				= $oGui->t('Unterkunft: Bestätigt');
	$oColAccommodationConfirmed->width				= Ext_Thebing_Util::getTableColumnWidth('date_time');
	$oColAccommodationConfirmed->width_resize		= false;
	$oColAccommodationConfirmed->format				= new Ext_Thebing_Gui2_Format_Date_Time();
	$oColAccommodationConfirmed->style				= new Ext_Thebing_Gui2_Style_Transfer_Info();
	$oColAccommodationConfirmed->small				= true;

	$oColCustomerAgencyConfirmed					= new Ext_Gui2_Head();
	$oColCustomerAgencyConfirmed->db_column			= 'customer_agency_confirmed';
	$oColCustomerAgencyConfirmed->db_alias			= 'kit';
	$oColCustomerAgencyConfirmed->select_column		= 'customer_agency_confirmed';
	$oColCustomerAgencyConfirmed->title				= $oGui->t('Kunde/Agentur: Bestätigt');
	$oColCustomerAgencyConfirmed->width				= Ext_Thebing_Util::getTableColumnWidth('date_time');
	$oColCustomerAgencyConfirmed->width_resize		= false;
	$oColCustomerAgencyConfirmed->format			= new Ext_Thebing_Gui2_Format_Date_Time();
	$oColCustomerAgencyConfirmed->style				= new Ext_Thebing_Gui2_Style_Transfer_Info();
	$oColCustomerAgencyConfirmed->small				= true;

//ENDE TRANSFER
/* Rausgenommen, da über die DefaultColums gesetzt
$oColInquiryCreated							= new Ext_Gui2_Head();
$oColInquiryCreated->db_column				= 'created';
$oColInquiryCreated->db_alias				= 'ki';
$oColInquiryCreated->select_column			= 'created';
$oColInquiryCreated->title					= $oGui->t('Buchungsdatum');
$oColInquiryCreated->width					= Ext_Thebing_Util::getTableColumnWidth('date');
$oColInquiryCreated->width_resize			= false;
$oColInquiryCreated->small					= true;
$oColInquiryCreated->format					= new Ext_Thebing_Gui2_Format_Date();
*/
$oColSendNettoPdf							= new Ext_Gui2_Head();
$oColSendNettoPdf->db_column				= 'ext_27';
$oColSendNettoPdf->db_alias					= 'ka';
$oColSendNettoPdf->select_column			= 'pdf_net';
$oColSendNettoPdf->title					= $oGui->t('Netto PDF');
$oColSendNettoPdf->width					= Ext_Thebing_Util::getTableColumnWidth('icon');
$oColSendNettoPdf->width_resize				= false; 
$oColSendNettoPdf->format					= new Ext_Thebing_Gui2_Format_NettoPdf();
$oColSendNettoPdf->style					= new Ext_Thebing_Gui2_Style_NettoPdf();
$oColSendNettoPdf->small					= true;
$oColSendNettoPdf->group					= $oColGroupAccounting;
$oColSendNettoPdf->sortable					= 0;

$oColSendGrossPdf							= new Ext_Gui2_Head();
$oColSendGrossPdf->db_column				= 'ext_28';
$oColSendGrossPdf->db_alias					= 'ka';
$oColSendGrossPdf->select_column			= 'pdf_gross';
$oColSendGrossPdf->title					= $oGui->t('Brutto PDF');
$oColSendGrossPdf->width					= Ext_Thebing_Util::getTableColumnWidth('icon');
$oColSendGrossPdf->width_resize				= false;
$oColSendGrossPdf->format					= new Ext_Thebing_Gui2_Format_GrossPdf();
$oColSendGrossPdf->style					= new Ext_Thebing_Gui2_Style_GrossPdf();
$oColSendGrossPdf->small					= true;
$oColSendGrossPdf->group					= $oColGroupAccounting;
$oColSendGrossPdf->sortable					= 0;

$oColSendLoaPdf								= new Ext_Gui2_Head();
$oColSendLoaPdf->db_column					= 'ext_29';
$oColSendLoaPdf->db_alias					= 'ka';
$oColSendLoaPdf->select_column				= 'pdf_loa';
$oColSendLoaPdf->title						= $oGui->t('Loa PDF');
$oColSendLoaPdf->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
$oColSendLoaPdf->width_resize				= false;
$oColSendLoaPdf->format						= new Ext_Thebing_Gui2_Format_LoaPdf();
$oColSendLoaPdf->style						= new Ext_Thebing_Gui2_Style_LoaPdf();
$oColSendLoaPdf->small						= true;
$oColSendLoaPdf->group						= $oColGroupAccounting;
$oColSendLoaPdf->sortable					= 0;
 
$oColStudentCardPdf								= new Ext_Gui2_Head();
$oColStudentCardPdf->db_column					= 'pdf_student_card';
$oColStudentCardPdf->db_alias					= '';
$oColStudentCardPdf->select_column				= 'pdf_student_card';
$oColStudentCardPdf->title						= $oGui->t('Schülerausweis PDF');
$oColStudentCardPdf->width						= Ext_Thebing_Util::getTableColumnWidth('icon');
$oColStudentCardPdf->width_resize				= false;
$oColStudentCardPdf->format						= new Ext_Thebing_Gui2_Format_Pdf('pdf_student_card');
$oColStudentCardPdf->style						= new Ext_Thebing_Gui2_Style_Pdf('pdf_student_card');
$oColStudentCardPdf->small						= true;
$oColStudentCardPdf->group						= $oColGroupPersonalDetails;
 
$oColCustomerMail							= new Ext_Gui2_Head();
$oColCustomerMail->db_column				= 'customer_mail';
$oColCustomerMail->db_alias					= '';
$oColCustomerMail->select_column			= 'customer_mail';
$oColCustomerMail->title					= $oGui->t('E-Mail');
$oColCustomerMail->width					= Ext_Thebing_Util::getTableColumnWidth('email');
$oColCustomerMail->group					= $oColGroupPersonalDetails;

if($sView != 'transfer'){
	$oLinkFormat = new Ext_Thebing_Gui2_Format_Link();
	$oLinkFormat->link = '';
	$oLinkFormat->onClick = 'return false;';
	$oColCustomerMail->format				= $oLinkFormat;
	$oColCustomerMail->event				= new Ext_Thebing_Gui2_Event_Customermail($oGui);
}


$oColPaymentReminder						= new Ext_Gui2_Head();
$oColPaymentReminder->db_column				= 'payment_reminder';
$oColPaymentReminder->db_alias				= '';
$oColPaymentReminder->select_column			= 'payment_reminder';
$oColPaymentReminder->title					= $oGui->t('Zahlungserinnerung');
$oColPaymentReminder->width								= Ext_Thebing_Util::getTableColumnWidth('name');
$oColPaymentReminder->format				= new Ext_Thebing_Gui2_Format_Payment_Reminder();
$oColPaymentReminder->group								= $oColGroupAccounting;

$oColAccommodationBookingShares					= new Ext_Gui2_Head();
$oColAccommodationBookingShares->db_column		= 'acco_booking_shares';
$oColAccommodationBookingShares->db_alias		= '';
$oColAccommodationBookingShares->select_column	= 'acco_booking_shares';
$oColAccommodationBookingShares->title			= $oGui->t('Zusammenreisen mit');
$oColAccommodationBookingShares->width			= Ext_Thebing_Util::getTableColumnWidth('person_name');
$oColAccommodationBookingShares->group					= $oColGroupMatching;

$oColAccommodationAllocationShares				= new Ext_Gui2_Head();
$oColAccommodationAllocationShares->db_column	= 'acco_allocation_shares';
$oColAccommodationAllocationShares->db_alias		= '';
$oColAccommodationAllocationShares->select_column = 'acco_allocation_shares';
$oColAccommodationAllocationShares->title		= $oGui->t('Zimmer geteilt mit');
$oColAccommodationAllocationShares->width		= Ext_Thebing_Util::getTableColumnWidth('person_name');
$oColAccommodationAllocationShares->group				= $oColGroupMatching;

$oColRoomName				= new Ext_Gui2_Head();
$oColRoomName->db_column	= 'room_name';
$oColRoomName->db_alias		= '';
$oColRoomName->select_column = 'room_name';
$oColRoomName->title		= $oGui->t('Zugewiesenes Zimmer');
$oColRoomName->width		= Ext_Thebing_Util::getTableColumnWidth('person_name');
$oColRoomName->group									= $oColGroupMatching;

$oColAccommodationWeeks				= new Ext_Gui2_Head();
$oColAccommodationWeeks->db_column	= 'acco_weeks';
$oColAccommodationWeeks->db_alias	= '';
$oColAccommodationWeeks->select_column = 'acco_weeks';
$oColAccommodationWeeks->title		= $oGui->t('Dauer(in Wochen)');
$oColAccommodationWeeks->width		= Ext_Thebing_Util::getTableColumnWidth('date_short');
$oColAccommodationWeeks->group							= $oColGroupAccommodation;

$oColPassportExpiration									= new Ext_Gui2_Head();
$oColPassportExpiration->db_column						= 'visum_passport_due_date';
$oColPassportExpiration->db_alias						= '';
$oColPassportExpiration->select_column					= 'visum_passport_due_date';
$oColPassportExpiration->title							= $oGui->t('Ablaufdatum Pass');
$oColPassportExpiration->width							= Ext_Thebing_Util::getTableColumnWidth('date');
$oColPassportExpiration->format							= new Ext_Thebing_Gui2_Format_Date();
$oColPassportExpiration->style							= $oStyleUntilPass;
$oColPassportExpiration->group							= $oColGroupVisa;

// Transfer für Schülerlisten
$oColTransferInfoArrival								= new Ext_Gui2_Head();
$oColTransferInfoArrival->db_column						= 'arrival_inquiry_transfer_id';
$oColTransferInfoArrival->db_alias						= 'kit_arr';
$oColTransferInfoArrival->select_column					= 'arrival_inquiry_transfer_id';
$oColTransferInfoArrival->title							= $oGui->t('Anreiseinformation');
$oColTransferInfoArrival->width							= Ext_Thebing_Util::getTableColumnWidth('extra_long_description');
$oColTransferInfoArrival->format						= new Ext_Thebing_Gui2_Format_Transfer_Info('arrival');
$oColTransferInfoArrival->small							= true;
$oColTransferInfoArrival->sortable						= false;
$oColTransferInfoArrival->group							= $oColGroupTransfer;

$oColTransferInfoDeparture								= new Ext_Gui2_Head();
$oColTransferInfoDeparture->db_column					= 'departure_inquiry_transfer_id';
$oColTransferInfoDeparture->db_alias					= 'kit_dep';
$oColTransferInfoDeparture->select_column				= 'departure_inquiry_transfer_id';
$oColTransferInfoDeparture->title						= $oGui->t('Abreiseinformation');
$oColTransferInfoDeparture->width						= Ext_Thebing_Util::getTableColumnWidth('extra_long_description');
$oColTransferInfoDeparture->format						= new Ext_Thebing_Gui2_Format_Transfer_Info('departure');
$oColTransferInfoDeparture->small						= true;
$oColTransferInfoDeparture->sortable					= false;
$oColTransferInfoDeparture->group						= $oColGroupTransfer;


// Farblegende
$sHtmlLegend = '';
switch($sView){
	case 'transfer':
		$sHtmlLegend .= '<div style="float: left"><b>' . $oGui->t('Daten') . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('vorhanden') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('good').'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('verändert') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('neutral').'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('nicht vorhanden') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('bad').'" ></div>';
		break;
	case 'visum_list':
		$sHtmlLegend .= '<div style="float: left"><b>' . $oGui->t('Gültigkeit') . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('abgelaufen') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('red').'" ></div>';

		$sInfoPass = $oGui->t('Passablaufdatum in den nächsten %s Tagen');
		$sInfoPass = str_replace('%s', $iPassportDue, $sInfoPass);

		$sInfoVisum = $oGui->t('Visaablaufdatum in den nächsten %s Tagen');
		$sInfoVisum = str_replace('%s', $iVisumDue, $sInfoVisum);

		$sHtmlLegend .= '<div style="float: left;margin-right:5px;"> <label>' . $sInfoPass.', ' . $sInfoVisum . '</label></div>';

		break;
	default:
		$sHtmlLegend .= '<div style="float: left"><b>' . $oGui->t('Zahlung') . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('eingegangen') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('soft_green', 30).'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('vollständig') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('lightgreen').'" ></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('nicht erfolgt') . '</div> <div class="colorkey" style="background-color: '.Ext_Thebing_Util::getColor('red').'" ></div>';

		$sHtmlLegend .= '<div style="float: left"> | <b>' . $oGui->t('PDF') . ': </b>&nbsp;</div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('benötigt') . '</div> <div class="colorkey" style="background-color: #FFCCAA" ><img src="/media/page_white_acrobat.png" alt="" /></div>';
		$sHtmlLegend .= '<div style="float: left">' . $oGui->t('verschickt') . '</div> <div class="colorkey" style="background-color: #CCFFAA" ><img src="/media/page_white_acrobat.png" alt="" /></div>';

}

$sHtmlLegend .= '<div style="float: left"> | <b>' . $oGui->t('Buchung') . ': </b>&nbsp;</div>';
$sHtmlLegend .= '<div style="float: left">' . $oGui->t('Storniert') . '</div> <div class="colorkey" style="background-color: ' . Ext_Thebing_Util::getColor('storno') . '" ></div>';
$sHtmlLegend .= '<div style="float: left">' . $oGui->t('Verändert') . '</div> <div class="colorkey" style="background-color: ' . Ext_Thebing_Util::getColor('changed') . '" ></div>';


$oBarLegend = $oGui->createBar();
$oBarLegend->position = 'bottom';
$oHtml = $oBarLegend->createHtml($sHtmlLegend);
$oBarLegend ->setElement($oHtml);





