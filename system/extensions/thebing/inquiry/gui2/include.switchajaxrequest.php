<?php

/** @var Ext_Thebing_Inquiry_Gui2 $this */

$aData = array();
  
$_VARS['id'] = (array)($_VARS['id'] ?? []);
$iInquiryId = reset($_VARS['id']);

if($iInquiryId == false){
	$iInquiryId = 0;
}
 
// ID der Schule aus dem Schul Select
// Wird für ALLE DATEN gebraucht NICHT zum FORMATIEREN
if(
	$_VARS['school_for_data'] &&
	$_VARS['school_for_data'] > 0
) {

	$oSchool = Ext_Thebing_School::getInstance($_VARS['school_for_data']);
	if($this->_oGui->query_id_alias == 'ki') {
		$oInquiry = new Ext_TS_Inquiry($iInquiryId);
	}

} else if($this->_oGui->query_id_alias == 'ki') {

	$oInquiry = new Ext_TS_Inquiry($iInquiryId);
	$oSchool = $oInquiry->getSchool();

} else if(
	$this->_oGui->query_id_alias == 'kia' ||
	$this->_oGui->query_id_alias == 'kit'
) {

	if($this->_oGui->query_id_alias == 'kia') {
		$sIdField = 'accommodation_inquiry_id';
	} else if($this->_oGui->query_id_alias == 'kit') {
		$sIdField = 'transfer_inquiry_id';
	}

	if(!$iGuiId) {
		$iGuiId = $iInquiryId;
	}
	
	$iEncodedInquiry = (int)$this->_oGui->decodeId($iGuiId, $sIdField);

	$oInquiry = Ext_TS_Inquiry::getInstance($iEncodedInquiry);
	$oSchool = $oInquiry->getSchool();

} else {
	$oSchool = Ext_Thebing_School::getSchoolFromSession();
}

// Schule aus der SESSION ODER die erste Schule mit zugriff
$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
$iSchoolForFormat = $oSchoolForFormat->id;
$iSchool = $oSchool->id;

// Loadbars wird abgeleitet da wird in der rückgabe etwas erweitern müssen
if($_VARS['task'] == 'loadBars'){
	$_VARS['task'] = 'loadBars2';
}

$aTransfer = $this->_switchAjaxRequest($_VARS);

# START : Leisten Daten Laden #calculateHolidayUntil
	if($_VARS['task'] == 'loadBars2'){
		$this->_oGui->load_table_bar_data = 1;
		// Blätterfunktion nicht neu laden da nur Leistendaten geholt werden
		$this->_oGui->load_table_pagination_data = 0;
		$aTable = $this->_oGui->getTableData($_VARS['filter'], $_VARS['orderby'], $_VARS['id']);
		$aTransfer['action'] 	= 'loadBars';
		$aTransfer['error'] 	= array();
		$aTransfer['data'] 		= $aTable;
		$aTransfer['number_format'] = $this->_oGui->getOneColumnValue('number_format');
	}
# ENDE #

// Aufbau der Rückgabe
// $aTransfer['action'] = XXX;
// $aTransfer['data'] = array();
// $aTransfer['error'][0]['message'] = XXX;
// $aTransfer['id'] = 'ID_'.implode('_', (array)$_VARS['id']);;
switch($_VARS['task']){
	case 'loadNewSchoolData':

		$oSchool = Ext_Thebing_School::getInstance((int)$_VARS['school_id']);

		$aData = [];
		$aData['id'] = 'ID_'.$iInquiryId;
		$aData['bSkipDynamicContent'] = 1;
		$aData['currency'] = $oSchool->getSchoolCurrencyList();
		$aData['status'] = $oSchool->getCustomerStatusList();
		$aData['status'] = Ext_Thebing_Util::addEmptyItem($aData['status']);
		$aData['visum'] = $oSchool->getVisumList($this->_oGui->gui_description);
		$aData['school_lang'] = $oSchool->getLanguageList(true);
		$aData['school_id'] = $oSchool->id;
		$aData['agencies'] = Ext_Thebing_Util::addEmptyItem($oSchool->getAgencies(true));
		$aData['agencies'] = array_map(
			function($sValue, $sText) {
				return array($sValue, $sText);
			},
			array_keys($aData['agencies']),
			$aData['agencies']
		);

		$aData['referer'] = $oSchool->getRefererList();
		$aData['referer'] = Ext_Thebing_Util::addEmptyItem($aData['referer']);
		$aData['referer'] = array_map(
			function($sValue, $sText) {
				return array($sValue, $sText);
			},
			array_keys($aData['referer']),
			$aData['referer']
		);
		$aData['referer'] = array_values($aData['referer']);

		if(
			!isset($_VARS['bSkipDynamicContent']) ||
			$_VARS['bSkipDynamicContent'] != 1
		) {

			$aData['bSkipDynamicContent'] = 0;

            $aData['course_categories'] = $oSchool->getCourseCategoriesList('select');
            $aData['course_categories'] = Ext_Thebing_Util::addEmptyItem($aData['course_categories']);
            $aData['course_categories'] = array_map(
                function($sValue, $sText) {
                    return array($sValue, $sText);
                },
                array_keys($aData['course_categories']),
                $aData['course_categories']
            );
            $aData['course_categories'] = array_values($aData['course_categories']);

			$aData['courses'] = $oSchool->getCourseList(true);
			$aData['courses'] = Ext_Thebing_Util::addEmptyItem($aData['courses'], L10N::t('Kein Kurs', Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription));
			$aData['courses'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['courses']),
				$aData['courses']
			);
			$aData['courses'] = array_values($aData['courses']);

			//Aktivitäten
			$oActivityRepository = \TsActivities\Entity\Activity::getRepository();
			$aActivityOptions = $oActivityRepository->getSelectOptions($oSchool);
			$aActivityOptions = Ext_Thebing_Util::addEmptyItem($aActivityOptions);
			$aData['activities'] = $aActivityOptions;

			$aData['activities'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['activities']),
				$aData['activities']
			);

			$aData['courses_levels'] = $oSchool->getCourseLevelList();
			$aData['courses_levels'] = Ext_Thebing_Util::addEmptyItem($aData['courses_levels']);
			$aData['courses_levels'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['courses_levels']),
				$aData['courses_levels']
			);
			$aData['courses_levels'] = array_values($aData['courses_levels']);

			$aData['accommodation'] = $oSchool->getAccommodationList(true);
			$aData['accommodation'] = Ext_Thebing_Util::addEmptyItem($aData['accommodation'], L10N::t('Keine Unterkunft', Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription));
			$aData['accommodation'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['accommodation']),
				$aData['accommodation']
			);
			$aData['accommodation'] = array_values($aData['accommodation']);

			$aData['roomtypes'] = $oSchool->getRoomtypeList(true);
			$aData['roomtypes'] = Ext_Thebing_Util::addEmptyItem($aData['roomtypes'], L10N::t('Kein Raumtyp', Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription));
			$aData['roomtypes'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['roomtypes']),
				$aData['roomtypes']
			);
			$aData['roomtypes'] = array_values($aData['roomtypes']);

			$aData['meals'] = $oSchool->getMealList(true);
			$aData['meals'] = Ext_Thebing_Util::addEmptyItem($aData['meals'], L10N::t('Keine Mahlzeit', Ext_Thebing_Inquiry_Gui2_Html::$sL10NDescription));
			$aData['meals'] = array_map(
				function($sValue, $sText) {
					return array($sValue, $sText);
				},
				array_keys($aData['meals']),
				$aData['meals']
			);
			$aData['meals'] = array_values($aData['meals']);

			//$aData['aAccTimes'] = $oSchool->getAccommodationTime();
			$aData['aAccRooms'] = $oSchool->getAccommodationRoomCombinations();
			$aData['aRoomMeals'] = $oSchool->getAccommodationMealCombinations();

			$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);

			if($oInquiry->group_id > 0){
				$aData['airports_arrival'] = $oSchool->getGroupTransferLocations();
				$aData['airports_departure'] = $aData['airports_arrival'];
				$aData['airports_individual'] = $aData['airports_arrival'];
			} else{
				$aData['airports_arrival'] = $oSchool->getTransferLocationsForInquiry('arrival', $oInquiry->id);
				$aData['airports_departure'] = $oSchool->getTransferLocationsForInquiry('departure', $oInquiry->id);
				$aData['airports_individual'] = $oSchool->getTransferLocationsForInquiry('', $oInquiry->id);
			}

		}

		$aTransfer['data'] = $aData;
		$aTransfer['action'] = 'loadNewSchoolDataCallback';
		break;

	case 'reloadMotherTongue':
		$sNationality = $_VARS['idNationality'];
		$sMothertongue = Ext_Thebing_Nationality::getMotherTonguebyNationality($sNationality);
		$aTransfer['data']['idMothertonge'] = $sMothertongue;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'writeMotherTongue';
		break;
//	case 'reloadKorrespondenceTongue':
//		$iMothertonge = $_VARS['idMothertonge'];
//		$sCorrespondence = Ext_Thebing_Nationality::getCorrespondenceTonguebyMotherTongue($iMothertonge, $oSchool->id);
//		$aTransfer['data']['idCorrespondence'] = $sCorrespondence;
//		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
//		$aTransfer['action'] = 'writeKorrespondenceTongue';
//		break;
	case 'calculateCourseUntil':

		if(empty($_VARS['from'])) {
			break;
		}

		// Kursende errechnen
		if(
			$_VARS['from'] > 0 &&
			$_VARS['weeks'] > 0
		){
			$sTo = Ext_Thebing_Util::getUntilDateOfCourse($_VARS['from'], $_VARS['weeks'], $iSchool, false, $oSchoolForFormat->id);
			// Wochentag ermitteln
			$iTo = Ext_Thebing_Util::getUntilDateOfCourse($_VARS['from'], $_VARS['weeks'], $iSchool, true, $oSchoolForFormat->id);
		}

		$aTransfer['data']['to'] = $sTo;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'writeCalculateCourseUntil';

		/*$aCourse = $_VARS['course'];
		$iKey = $_VARS['key'];
		if(is_array($_VARS['module'])){
			$aModulePost = reset($_VARS['module']);
		} else{
			$aModulePost = array();
		}

		$aCourseData = array();
		foreach((array) $aCourse as $iInquiryId => $aInquiryCourseData){
			#$iInquiryCourseId	= key($aInquiryCourseData);
			$aInquiryCourseData = reset($aInquiryCourseData);
			$aCourseData[$iKey] = array(
				'course_id' => $aInquiryCourseData['course_id'],
				'from' => Ext_Thebing_Format::ConvertDate($_VARS['from'], null, 'timestamp'),
				'until' => $iTo,
				'inquiry_course_id' => $aModulePost,
				'weeks' => $_VARS['weeks'],
			);
		}

		$aTransfer['data']['aCourseModules'] = Ext_Thebing_Inquiry_Gui2_Html::getCourseModulesHtml($aCourseData);*/

		break;
	case 'calculateAccUntil':

		if(empty($_VARS['from']))
		{
			break;
		}

		$category = \Ext_Thebing_Accommodation_Category::getInstance((int)$_VARS['category_id']);

		// Unterkunftsende errechnen
		$sTo = '';
		$_VARS['weeks'] = (int) $_VARS['weeks'];

		if(
			!empty($_VARS['from'])
		){
			$sTo = Ext_Thebing_Util::getUntilDate($_VARS['from'], $_VARS['weeks'], $iSchool, $category, false, $oSchoolForFormat->id);
			// Wochentag ermitteln
			$iTo = Ext_Thebing_Util::getUntilDate($_VARS['from'], $_VARS['weeks'], $iSchool, $category, true, $oSchoolForFormat->id);
		}
		$aTransfer['data']['to'] = $sTo;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'writeCalculateAccUntil';
		
		\System::wd()->executeHook('ts_inquiry_write_accommodation_until', $aTransfer, $_VARS);
		
		break;
	case 'calculateUntil':
		// Versicherungsende errechnen
		$sDate = Ext_Thebing_Format::ConvertDate($_VARS['from'], $oSchoolForFormat->id);

		if(!is_numeric($sDate)) // #2473 - Es soll nichts passieren, wenn kein Startdatum gegeben ist
		{
			break;
		}

		$oDate = new WDDate($sDate);

		// Logik existiert umgekehrt auch in \TsRegistrationForm\Service\InquiryBuilder::transformInsurances()
		$oDate->add((int) $_VARS['weeks'], WDDate::WEEK)->sub(1, WDDate::DAY);

		$iTo = $oDate->get(WDDate::TIMESTAMP);

		$sTo = Ext_Thebing_Format::LocalDate($iTo, $oSchoolForFormat->id);

		$aTransfer['data']['to'] = $sTo;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;

		$aTransfer['action'] = 'writeCalculateUntil';

		break;
	case 'calculateHolidayUntil':
		// Unterkunftsende errechnen

		$sTo = '';
		if(
			!empty($_VARS['from']) &&
			!empty($_VARS['weeks'])
		) {

			$dDate = Ext_Thebing_Format::ConvertDate($_VARS['from'], $oSchoolForFormat->id, 3);
			$dDate->add(new DateInterval('P'.(int)$_VARS['weeks'].'W'));

			// Immer bis zum Ende der Woche
			if($dDate->format('N') === '1') {
				$dDate->sub(new DateInterval('P1D'));
			} elseif($dDate->format('N') === '6') {
				$dDate->add(new DateInterval('P1D'));
			}

			$sTo = Ext_Thebing_Format::LocalDate($dDate, $oSchoolForFormat->id);
            
		}

		$aTransfer['data']['to'] = $sTo; 
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'writeCalculateHolidayUntil';

		break;


	case 'searchRoomSharingCustomers':

		$oCustomer = $oInquiry->getCustomer();

		$aSearchResult = array();

		$aFrom = json_decode($_VARS['sFrom']);
		$aUntil = json_decode($_VARS['sUntil']);
		#$aActive = array();

		//War das wirklich mal so geplant mit dem FirstLastDate? Siehe ticket #1538
		
		// Zeitraumfenster

		$aResult = Ext_Thebing_School::searchForRoomSharingInqiryJourneys($oInquiry->id, $_VARS['search'], $oSchoolForFormat->id, $aFrom, $aUntil);

		foreach((array) $aResult as $aData){
			
			$aTemp = array(
				'customerNumber'	=> $aData['customer_number'],
				'name'				=> $aData['info'],
				'id'				=> (int)$aData['inquiry_id']
			);
			$aSearchResult[] = $aTemp;

		}

		$aTransfer['error'] = [];
		
		if(empty($aSearchResult)) {
			$aTransfer['error'][] = L10N::t('Keine Schüler gefunden', 'Thebing » Invoice » Inbox');
		}
		
		$aTransfer['data']['searchResult'] = $aSearchResult;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'resultRoomSharingCustomers';
		
		break;

	case 'searchForSameUser':
		// Search Users when add NEW user
		$aData = array();
		$aData['lastname'] = $_VARS['lastname'];
		$aData['firstname'] = $_VARS['firstname'];
		$sBirthday = Ext_Thebing_Format::ConvertDate($_VARS['bday'], $oSchool->id, true);
		$aData['birthday'] = $sBirthday;
		
		$searchService = new Ext_Thebing_Customer_Search;
		
		if(!empty($_VARS['search'])) {
			$aSearchResult = $searchService->fulltextSearch($_VARS['search'], $oSchool->id, $_VARS['contact_id']);
		} else {
			$aSearchResult = $searchService->search($aData, $oSchool->id, $_VARS['contact_id']);
		}
		
		$aTransfer['data']['searchResultCount'] = $searchService->getFoundRows();
		$aTransfer['data']['container_id'] = $this->request->get('container_id');
		$aTransfer['data']['searchResult'] = $aSearchResult;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['action'] = 'resultSearchForSomeUser';
		$aTransfer['error'] = array();

		break;

	case 'getDatesForTransfer':
	// Unterkunfts von-bis ausrechnen (Leistungszeitraum) für Transfer
	case 'getCoursePeriod':

		// Coursdaten von-bis ausrechnen (Leistungszeitraum)
		$aFrom = json_decode($_VARS['sFrom']);
		$aUntil = json_decode($_VARS['sUntil']);
		$aActive = json_decode($_VARS['aActive']);

		$aTransfer['error'] = array();
		$aFirstLastDates = $oSchool->getFirstLastDate($aFrom, $aUntil, $aActive);

		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		if($_VARS['task'] == 'getDatesForTransfer'){
			$aTransfer['action'] = 'resultAccommodationTransferData';
		} elseif($_VARS['task'] == 'getCoursePeriod'){
			// Hier wird jetzt auch noch die Unterkunftszeit ausgerechnet
			$aInquiryAccommodationIDs = [];

			if (
				is_object($oInquiry) &&
				!empty($_VARS['accommodation'])
			) {
				krsort($_VARS['accommodation']);

				// Obwohl hier alle leeren Unterkünfte kommen erstmal nur die erste behandeln
				$accommodationIndex = \Illuminate\Support\Arr::first(array_keys($_VARS['accommodation']));
				$categoryId = (int)$_VARS['accommodation'][$accommodationIndex];

				$accommodationDates = $oSchoolForFormat->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], Ext_Thebing_Accommodation_Category::getInstance($categoryId));

				$aTransfer['data']['accommodationData'][] = ['index' => $accommodationIndex, 'dates' => $accommodationDates];

				// Inquiry Accommodations auch nochmal mitschicken da sie ggf. angepasst werden
				$aInquiryAccommodations = $oInquiry->getAccommodations(false);

				foreach((array)$aInquiryAccommodations as $aData){
					$aInquiryAccommodationIDs[] = $aData['id'];
				}
			}

			$aTransfer['action'] = 'resultCourseTransferData';
			$aTransfer['data']['inquiryAccommodationIds'] = $aInquiryAccommodationIDs;
		}
		$aTransfer['data']['returnData'] = $aFirstLastDates;
		// Ob Daten für Transfer übernommen werden können
		$aTransfer['data']['transfer_question'] = (int)$_VARS['transfer_question'];
		break;
	case 'getAccommodationPeriod':

		$iCategory = (int)$_VARS['category_id'];
		$aFrom = json_decode($_VARS['sFrom'], true);
		$aUntil = json_decode($_VARS['sUntil'], true);
		$aActive = json_decode($_VARS['aActive'], true);

		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		$aTransfer['error'] = array();
		if ($iCategory > 0) {
			$oCategory = \Ext_Thebing_Accommodation_Category::getInstance($iCategory);

			if (
				$oInquiry && empty($oInquiry->getAccommodations(false)) &&
				str_contains($_VARS['field'], '['.$iInquiryId.'][0]')
			) {
				// Nur bei der ersten Unterkunft ausführen
				$aFirstLastDates = $oSchool->getFirstLastDate($aFrom, $aUntil, $aActive);
				$aAccommodationDates = $oSchoolForFormat->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], $oCategory);
			}

			$aAccommodationDates['time_from'] = substr($oCategory->arrival_time, 0, 5);
			$aAccommodationDates['time_until'] = substr($oCategory->departure_time, 0, 5);

			$aTransfer['action'] = 'resultAccommodationData';
			$aTransfer['data']['field'] = $_VARS['field'];
			$aTransfer['data']['returnData'] = $aAccommodationDates;
			$aTransfer['data']['transfer_question'] = (int)$_VARS['transfer_question'];
		}

		break;
	case 'saveDialog':

		if($_VARS['action'] == 'transfer_provider') {
				// Speichert den Transfer-Provider Dialog
			$aTransfer = Ext_TS_Pickup_Gui2_Data::saveProviderDialog($_VARS['save']['transfer'], $_VARS['id'], $this->_oGui);
		}
		break;
	case 'loadInquiryHolidaySelectFields':
		$aTransfer['data']['vars'] = $_VARS;
		$aTransfer['data']['inquiry_courses'] = array();
		$aTransfer['data']['inquiry_accommodations'] = array();
		if($_VARS['idHoliday'] == 'new'){
			$aHoliday = array(
				'holidayFrom' => (int) Ext_Thebing_Format::ConvertDate($_VARS['holiday']['from'], $oSchoolForFormat->id),
				'holidayUntil' => (int) Ext_Thebing_Format::ConvertDate($_VARS['holiday']['until'], $oSchoolForFormat->id)
			);
		} else{
			$aHoliday = array(
				'holidayFrom' => (int) Ext_Thebing_Format::ConvertDate($_VARS['holiday']['from'], $oSchoolForFormat->id),
				'holidayUntil' => (int) Ext_Thebing_Format::ConvertDate($_VARS['holiday']['until'], $oSchoolForFormat->id)
			);
		}

		// Exportiert aus Ext_Thebing_Inquiry_Holidays::isWithinHolidays()
		$cIsWithinHolidays = function($aDates) {
			$bCheck = false;
			$iHolidayFrom = (int)$aDates['holidayFrom'];
			$iHolidayUntil = (int)$aDates['holidayUntil'];
			$iDateFrom = (int)$aDates['from'];
			$iDateUntil = (int)$aDates['until'];
			if(
				$iHolidayFrom <= $iDateUntil &&
				$iHolidayUntil >= $iDateFrom
			) {
				$bCheck = true;
			}
			return $bCheck;
		};

		// Kurse
		if(is_object($oInquiry)){

			$aInquiryCourses = $oInquiry->getCourses(false);

			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			foreach((array) $aInquiryCourses as $iKey => $aInquiryCourse){
				$aTemp = $aInquiryCourse;
				$aTemp['selected'] = false;
				$aDates = $aHoliday;
				$aDates['from'] = $aInquiryCourse['from'];
				$aDates['until'] = $aInquiryCourse['until'];

				if($cIsWithinHolidays($aDates)) {
					$oCourse = Ext_Thebing_Tuition_Course::getInstance((int) $aTemp['course_id']);

					$sFrom = $oDateFormat->format($aTemp['from']);
					$sUntil = $oDateFormat->format($aTemp['until']);
					$sCourseName = $oCourse->getName($oSchoolForFormat->getInterfaceLanguage());

					$bSelect = false;
					if($_VARS['idHoliday'] === 'new') {
						$bSelect = true;
					}
					
					$aCourse = array(
						'value' => $sCourseName . ' (' . $sFrom . '-' . $sUntil . ')',
						'id' => $aTemp['id'],
						'selected' => $bSelect,
						//'ids' => Ext_Thebing_Inquiry_Holidays::getInquiryCourseIdsByInquiry($oInquiry->id)
					);

					$aTransfer['data']['inquiry_courses'][] = $aCourse;
				}
			}

			// Unterkünfte
			$aInquiryAccommodations = $oInquiry->getAccommodations(false);
			
			foreach((array) $aInquiryAccommodations as $iKey => $aInquiryAccommodation){
				$aTemp = $aInquiryAccommodation;
				$aTemp['selected'] = false;
				$aDates = $aHoliday;
				$aDates['from'] = $aInquiryAccommodation['from']; 
				$aDates['until'] = $aInquiryAccommodation['until'];

				if($cIsWithinHolidays($aDates)) {
					$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation((int) $aTemp['id']);
					$sFrom = $oDateFormat->format($aTemp['from']);
					$sUntil = $oDateFormat->format($aTemp['until']);
					
					$bSelect = false;
					if($_VARS['idHoliday'] === 'new') {
						$bSelect = true;
					}
					
					$aAccommodation = array(
						'value' => $oAccommodation->getAccommodationCategoryWithRoomTypeAndMeal() . ' (' . $sFrom . '-' . $sUntil . ')',
						'id' => $aTemp['id'],
						'selected' => $bSelect,
						//'ids' => Ext_Thebing_Inquiry_Holidays::getInquiryAccommodationIdsByInquiry($oInquiry->id)
					);
					$aTransfer['data']['inquiry_accommodations'][] = $aAccommodation; 
				}
			}
		}
		
		\System::wd()->executeHook('ts_inquiry_load_holiday_fields', $aTransfer);
		
		$aTransfer['action'] = 'writeInquiryHolidaySelectFields';
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;
		break;
	case 'loadHolidayFollowingCourses':

		if($_VARS["idHoliday"] == 'new') {
			$aHoliday = array(
				"holidayFrom" => (int) Ext_Thebing_Format::ConvertDate($_VARS["holiday"]["from"], $oSchoolForFormat->id),
				"holidayUntil" => (int) Ext_Thebing_Format::ConvertDate($_VARS["holiday"]["until"], $oSchoolForFormat->id)
			);
		} else{
			$aHoliday = array(
				"holidayFrom" => (int) Ext_Thebing_Format::ConvertDate($_VARS["holiday"]["from"], $oSchoolForFormat->id),
				"holidayUntil" => (int) Ext_Thebing_Format::ConvertDate($_VARS["holiday"]["until"], $oSchoolForFormat->id)
			);
		}

		$aInquiryCourses = $oInquiry->getCourses(false); 
		$aTransfer['data']['courses'] = array();

		foreach((array) $aInquiryCourses as $iKey => $aInquiryCourse) {

			$aTemp = $aInquiryCourse;
			$aTemp["selected"] = false;
			$aDates = $aHoliday;
			$aDates["courseFrom"] = $aInquiryCourse["from"];
			$aDates["courseUntil"] = $aInquiryCourse["until"];
			if(
				$aDates["holidayFrom"] > $aDates["courseFrom"]
			) {
				continue;
			}

			$oCourse = Ext_Thebing_Tuition_Course::getInstance($aTemp["course_id"]);

			$sFrom = Ext_Thebing_Format::LocalDate($aTemp["from"], $oSchoolForFormat->id);
			$sUntil = Ext_Thebing_Format::LocalDate($aTemp["until"], $oSchoolForFormat->id);

			$aTransfer['data']['courses'][] = array(
				"value" => $oCourse->getName()." (" . $sFrom . "-" . $sUntil . ")",
				"id" => $aTemp["id"],
				"title" => L10N::t('Es sind Ferien mit diesem Eintrag verknüpft! Bitte entfernen Sie zuerst die Ferien bevor sie diesen Eintrag verschieben!', 'Thebing » Invoice » Inbox'),
				//"selected" => $bSelectedHolidayCourse,
				//"ids" => Ext_Thebing_Inquiry_Holidays::getInquiryCourseIdsByInquiry($oInquiry->id)
			);

		}

		\System::wd()->executeHook('ts_inquiry_load_holiday_following_courses', $aTransfer);
		
		$aTransfer['data']['vars'] = $_VARS;
		$aTransfer['action'] = 'writeHolidayFollowingCourses';
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;

		break;
	case 'deleteHolidaySet':
		$aTransfer['data']['vars'] = $_VARS;
		$aTransfer['action'] = 'writeDeleteHolidaySet';
		$aTransfer['error'] = [];
		$mHolidayResult = false;
		
		if($_VARS['holiday_id'] === 'new') {
			throw new RuntimeException('Wrong holiday data submitted for deletion');
		}

		try {
			DB::begin('deleteHolidaySet');

			if(!empty($_VARS['holiday_id'])) {
				$oInquiryHoliday = Ext_TS_Inquiry_Holiday::getInstance($_VARS['holiday_id']);
				if($oInquiry->id != $oInquiryHoliday->inquiry_id) {
					throw new RuntimeException('Holiday '.$oInquiryHoliday->id.' does not belong to this inquiry '.$oInquiry->id);
				}

				$oInquiryHoliday->deleteHoliday((bool)$_VARS['restore_service']);
			}

			DB::commit('deleteHolidaySet');
		} catch (\Core\Exception\Entity\ValidationException $e) {
			DB::rollback('deleteHolidaySet');
			foreach ($e->getAdditional()['errors'] as $sField => $aErrors) {
//				$aTransfer['action'] = 'showError';
				foreach ($aErrors as $sError) {
					$aTransfer['error'][] = $this->getErrorMessage($sError, $sField);
				}
			}
		}

        // Im JS wird sofort prepareSaveDialog aufgerufen, damit die Leistungen neu geladen werden
		//$aTransfer['data']['show_skip_errors_checkbox'] = 1;
		$aTransfer['data']['deleted'] = $mHolidayResult;
		$aTransfer['data']['id'] = 'ID_' . $iInquiryId;

		break;
	case 'request':
		// Anzeige Letzte PDFs Inbox
		switch($_VARS['action']) {
			case 'openInvoicePdf':

				$sPdfPath = $oInquiry->getLastDocumentPdf('invoice');

				if(!empty($sPdfPath)) {
					$aTransfer['url'] = '/storage/download'.$sPdfPath;
					$aTransfer['action'] = 'openUrl';
				}
				break;
			case 'openDocumentPdf':
				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);

				// Rechteprüfung, ob Dokument in dieser GUI erzeugt wurde
				if(
					$this->_oGui->getOption('only_documents_from_same_gui') &&
					!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
				) {
					$sGuiName = $this->_oGui->name;
					if(!empty($sGuiName)) {
						$oSearch->setGuiLists(array(array($sGuiName, $this->_oGui->set)));
					}
				}

				// Zusätzliche Rechteprüfung mit Inboxrechten
				// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
				if(System::d('ts_check_inbox_rights_for_document_templates')) {
					$oUser = System::getCurrentUser();
					$aInboxes = $oUser->getInboxes('id');
					$oSearch->setTemplateInboxes($aInboxes);
				}

				$sPdfPath = $oInquiry->getLastDocumentPdf('additional_document', [], $oSearch);

				if(!empty($sPdfPath)) {
					$aTransfer['url'] = '/storage/download'.$sPdfPath;
					$aTransfer['action'] = 'openUrl';
				}
				break;
			case 'convertProforma':
				// TODO Wann wird das hier überhaupt aufgerufen?
				// Da in convertProformat2InquiryDocument keine Numberrange-ID übergeben wird, kommt da immer dieser Dialog zurück?

				// Proforma in Rechnung umwandeln
				$aSelectedIds = $_VARS['id'];
				foreach((array) $aSelectedIds as $iInquiryId){
					$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
					$iLastInquiryDoc = Ext_Thebing_Inquiry_Document_Search::search($iInquiryId, 'invoice_proforma');
					$oDocument = new Ext_Thebing_Inquiry_Document($iLastInquiryDoc);
					$oDocument->convertProformat2InquiryDocument(L10N::t('Proforma-Rechnung in Rechnung umwandeln', $this->_oGui->gui_description));
				}

				$aTransfer['data'] = $aData;
				$aTransfer['success_message'] = L10N::t('Proforma wurden umgeandelt.');
				$aTransfer['data']['selectedRows'] = $aSelectedIds;
				$aTransfer['action'] = "loadTable";
				$aTransfer['error'] = array();
				break;
		}
	default:

		break;
}

if($oSchool){
	$aTemp = $oSchoolForFormat->getNumberFormatData();
}

$aTransfer['number_format']['t'] = $aTemp['t'];
$aTransfer['number_format']['e'] = $aTemp['e'];
$aTransfer['number_format']['dec'] = 2;
