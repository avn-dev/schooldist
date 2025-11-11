<?php

$aData = array();

$_VARS['id'] = (array)$_VARS['id'];

$iGroupId = reset($_VARS['id']);

$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

if($iGroupId == false){
	$iGroupId = 0;
}

$oGroup = new Ext_Thebing_Inquiry_Group($iGroupId);

if($oGroup->id > 0) {
	$oSchool = $oGroup->getSchool();
} elseif(!empty($_VARS['school_for_data'])) {
	$oSchool = Ext_Thebing_School::getInstance($_VARS['school_for_data']);
} else {
	$oSchool = Ext_Thebing_School::getSchoolFromSession();
}
$iSchool = $oSchool->id;

// Loadbars wird abgeleitet da wird in der rückgabe etwas erweitern müssen
if($_VARS['task'] == 'loadBars'){
	$_VARS['task'] = 'loadBars2';
}

$this->_oDb = DB::getDefaultConnection();

//$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
$oSchoolForFormat = $oSchool;

$aTransfer = $this->_switchAjaxRequest($_VARS);

# START : Leisten Daten Laden #
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

	case 'reloadMotherTongue':
		$iNationality							= $_VARS['idNationality'];
		$sMothertongue							= Ext_Thebing_Nationality::getMotherTonguebyNationality($iNationality);
		$aTransfer['data']['idMothertonge']		= $sMothertongue;
		$aTransfer['data']['id']				= 'GROUP_' . $iGroupId;
		$aTransfer['action']					= 'writeMotherTongue';
		break;
//	case 'reloadKorrespondenceTongue':
//		$iMothertonge							= $_VARS['idMothertonge'];
//		$sCorrespondence = Ext_Thebing_Nationality::getCorrespondenceTonguebyMotherTongue($iMothertonge);
//		$aTransfer['data']['idCorrespondence']	= $sCorrespondence;
//		$aTransfer['data']['id']				= 'GROUP_' . $iGroupId;
//		$aTransfer['action']					= 'writeKorrespondenceTongue';
//		break;
	case 'getCoursePeriod':
		// Coursdaten von-bis ausrechnen (Leistungszeitraum)
		$aFrom = json_decode($_VARS['sFrom']);
		$aUntil = json_decode($_VARS['sUntil']);
		$aActive = json_decode($_VARS['aActive']);
		$aFirstLastDates = $oSchool->getFirstLastDate($aFrom, $aUntil, $aActive);

		$getDates = function ($sPrefix) use ($_VARS, $oSchoolForFormat, $aFirstLastDates) {
			if (!empty($_VARS[$sPrefix])) {
				krsort($_VARS[$sPrefix]);
				$accommodationIndex = \Illuminate\Support\Arr::first(array_keys($_VARS[$sPrefix]));
				$categoryId = (int)$_VARS[$sPrefix][$accommodationIndex];
				return [['index' => $accommodationIndex, 'dates' => $oSchoolForFormat->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], Ext_Thebing_Accommodation_Category::getInstance($categoryId))]];
			}
			return [];
		};

		$aTransfer['error'] = array();
		$aTransfer['action'] = 'resultCourseTransferData';
		$aTransfer['data']['accommodationData'] = $getDates('accommodation');
		$aTransfer['data']['accommodationGuideData'] = $getDates('accommodation_guide');
		$aTransfer['data']['returnData'] = $aFirstLastDates;
		$aTransfer['data']['id'] = 'GROUP_' . $iGroupId;
		$aTransfer['data']['field_id'] = $_VARS['sFieldtype'];
		break;
	case 'getAccommodationPeriod':

		$iCategory = (int)$_VARS['category_id'];
		$aFrom = json_decode($_VARS['sFrom']);
		$aUntil = json_decode($_VARS['sUntil']);
		$aActive = json_decode($_VARS['aActive']);

		$aTransfer['error'] = array();
		if ($iCategory > 0) {
			$oCategory = \Ext_Thebing_Accommodation_Category::getInstance($iCategory);

			$aFirstLastDates = $oSchool->getFirstLastDate($aFrom, $aUntil, $aActive);
			$aAccommodationDates = $oSchoolForFormat->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], $oCategory);

			$aAccommodationDates['time_from'] = substr($oCategory->arrival_time, 0, 5);
			$aAccommodationDates['time_until'] = substr($oCategory->departure_time, 0, 5);

			$aTransfer['action'] = 'resultAccommodationData';
			$aTransfer['data']['field'] = $_VARS['field'];
			$aTransfer['data']['returnData'] = $aAccommodationDates;
			$aTransfer['data']['id'] = 'GROUP_' . $iGroupId;
			$aTransfer['data']['transfer_question'] = (int)$_VARS['transfer_question'];
		}

		break;
	case 'calculateCourseUntil':

		if(empty($_VARS['from']))
		{
			break;
		}

		if($oSchool->id == 0){
			$iSchool							= $iSessionSchoolId;
		}

		$sTo = Ext_Thebing_Util::getUntilDateOfCourse($_VARS['from'],$_VARS['weeks'],$iSchool);

		// Wochentag ermitteln
		$iTo = Ext_Thebing_Util::getUntilDateOfCourse($_VARS['from'],$_VARS['weeks'],$iSchool, true);

		$aTransfer['data']['to']				= $sTo;
		$aTransfer['data']['id']				= 'GROUP_' . $iGroupId;
		$aTransfer['data']['field_id']			= $_VARS['field_id'];
		$aTransfer['action']					= 'writeCalculateCourseUntil';
		break;
	case 'calculateAccUntil':

		if(empty($_VARS['from']))
		{
			break;
		}

		$category = \Ext_Thebing_Accommodation_Category::getInstance((int)$_VARS['category_id']);

		if($iSchool <= 0){
			$iSchool							= $iSessionSchoolId;
		}
		$sTo = Ext_Thebing_Util::getUntilDate($_VARS['from'],$_VARS['weeks'],$iSchool, $category);

		// Wochentag ermitteln
		$iTo = Ext_Thebing_Util::getUntilDate($_VARS['from'],$_VARS['weeks'],$iSchool, $category, true);

		$aTransfer['data']['to']				= $sTo;
		$aTransfer['data']['id']				= 'GROUP_' . $iGroupId;
		$aTransfer['action']					= 'writeCalculateAccUntil';
		break;

	case 'getDatesForTransfer':
		$aFrom = json_decode($_VARS['sFrom']);
		$aUntil = json_decode($_VARS['sUntil']);

		if($oSchool->id == 0){
			$iSchool							= $iSessionSchoolId;
		}

		$iFirst = 0;
		$iLast = 0;

		for($i = 0; $i < count($aFrom); $i++){
			$iTempFrom = Ext_Thebing_Format::ConvertDate($aFrom[$i], $iSchool);
			$iTempUntil = Ext_Thebing_Format::ConvertDate($aUntil[$i], $iSchool);
			if($iTempFrom > 0 && $iTempUntil > 0){
				if($i == 0){
					$iFirst = $iTempFrom;
					$iLast = $iTempUntil;
				}else{
					if($iTempFrom < $iFirst){
						$iFirst = $iTempFrom;
					}
					if($iTempUntil > $iLast){
						$iLast = $iTempUntil;
					}
				}
			}
		}

		$aBack = array('first' => Ext_Thebing_Format::LocalDate($iFirst), 'last' => Ext_Thebing_Format::LocalDate($iLast));

		$aTransfer['data']['transferData']	= $aBack;
		$aTransfer['data']['id']			= 'GROUP_' . $iGroupId;
		$aTransfer['action']				= 'resultCourseTransferData';
		$aTransfer['error']					= array();

		break;

	case 'loadNewSchoolData':	

		$oSchool = Ext_Thebing_School::getInstance((int)$_VARS['school_id']);

		$aData['id'] = 'GROUP_'.$iGroupId;
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

            $aData['course_categories'] = Ext_Thebing_Util::addEmptyItem($oSchool->getCourseCategoriesList('select'));

			$aData['courses'] = Ext_Thebing_Util::addEmptyItem($oSchool->getCourseList(true), L10N::t('Kein Kurs'));
			$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);
			$aData['courses_levels'] = Ext_Thebing_Util::addEmptyItem($oSchool->getCourseLevelList());

			$aData['accommodation'] = Ext_Thebing_Util::addEmptyItem($oSchool->getAccommodationList(true), L10N::t('Keine Unterkunft'));
			$aData['roomtypes'] = Ext_Thebing_Util::addEmptyItem($oSchool->getRoomtypeList(true), '---');
			$aData['meals'] = Ext_Thebing_Util::addEmptyItem($oSchool->getMealList(true), '---');

			//$aData['aAccTimes'] = $oSchool->getAccommodationTime();
			$aData['aAccRooms'] = $oSchool->getAccommodationRoomCombinations();
			$aData['aRoomMeals'] = $oSchool->getAccommodationMealCombinations();

			$aData['airports_arrival'] = $oSchool->getTransferLocationsForInquiry('arrival', $oInquiry->id);
			$aData['airports_departure'] = $oSchool->getTransferLocationsForInquiry('departure', $oInquiry->id);
			$aData['airports_individual'] = $oSchool->getTransferLocationsForInquiry('', $oInquiry->id);

			$aData['bSkipDynamicContent'] = 0;

		}	
			
		$aTransfer['data'] = $aData;
		$aTransfer['action'] = 'loadNewSchoolDataCallback';
		
		break;
	default: 
		$bDefaultCase = true;
		break;

}
