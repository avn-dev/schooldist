<?php

include(\Util::getDocumentRoot().'system/legacy/admin/includes/main.inc.php');

if(isset($_VARS['item']) && $_VARS['item'] == 'accommodation') {
	Ext_Thebing_Access::accesschecker("thebing_accommodation_accommodations");
} elseif(
		isset($_SESSION['thebing']['absence']['item']) &&
		$_SESSION['thebing']['absence']['item'] == 'holiday'
){
	Ext_Thebing_Access::accesschecker("thebing_marketing_school_holidays");
	$_VARS['item'] = 'holiday';
} else {
	Ext_Thebing_Access::accesschecker("thebing_tuition_resource_teachers_absence");
}

/* ==================================================================================================== */

$oAbsence = new Ext_Thebing_Absence();
$oSchool = Ext_Thebing_School::getSchoolFromSession();
$sLanguage = $oSchool->getInterfaceLanguage();

/* ==================================================================================================== */

if(isset($_VARS['action']) && $_VARS['action'] == 'get_absences_list') {

	$aCategories = Ext_Thebing_Absence_Category::getList(false);

	if(isset($_VARS['item']) && $_VARS['item'] == 'accommodation') {
		$iId = \Illuminate\Support\Arr::first($_VARS['parent_gui_id']);
		$oAccommodation = Ext_Thebing_Accommodation::getInstance((int)$iId);
		$aItems = $oAccommodation->getRoomList(true, $sLanguage);
	} elseif(isset($_VARS['item']) && $_VARS['item'] == 'holiday') {
		$oClient	= Ext_Thebing_Client::getInstance($user_data['client']);
		$aItems		= $oClient->getSchools(true);
	} else {
		$dDate = new DateTime($_VARS['year'].'-'.$_VARS['month'].'-1');
		$aItems = $oSchool->getTeacherList(true, $dDate);
	}
	
	$oAbsence->setItems($aItems);

	$aData = $oAbsence->getAbsencesList($_VARS['month'], $_VARS['year']);

	$aData['categories'] = (array)$aCategories;

	echo json_encode($aData);

	exit();

}
