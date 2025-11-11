<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

//items_school

$sCodeKey = $oConfig->combination_key;
$sTemplateKey = $oConfig->template_key;

$_VARS['code'] = $oConfig->combination_key;
$_VARS['template'] = $oConfig->template_key;

$_VARS['frontend_combination_params'] = [];

$oCombination = \Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getByKey', [$sCodeKey]);

if(!empty($file_data['parameters']['school'])) {
	
	$oSchool = Ext_TA_School::getInstance($file_data['parameters']['school']);

	// Büro
	$iOfficeId = reset($oSchool->offices);
	$_VARS['frontend_combination_params']['office'] = $iOfficeId;
	
	if($oCombination->usage == 'pricelists') {
		$sModule = 'pricelist';
	} else {
		$sModule = 'registration';
	}

	// Saisons sind einstellbar über die Frontend Einstellungen der Schule
	$oRepository = \Ext_TA_School_FrontendSetting::getRepository();
	$oFrontendSetting = $oRepository->findOneByModule($oSchool, 'pricelist');

	if(empty($oFrontendSetting)) {
		return;
	}

	$aSeasons = $oFrontendSetting->seasons;

	$aSeasonSort = [];
	foreach($aSeasons as $iSeasonId) {

		$oSeason = Ext_TA_School_Season::getInstance($iSeasonId);

		$aSeasonSort[$oSeason->from] = $oSeason->id;

	}

	ksort($aSeasonSort);

	$aSeasons = array_values($aSeasonSort);

	// Für Preisliste
	$_VARS['frontend_combination_params']['school'] = $file_data['parameters']['school'];
	$_VARS['frontend_combination_params']['seasons'] = $aSeasons;

	// Für Anmeldeformular
	$_VARS['frontend_combination_params']['productlines'] = [$file_data['parameters']['productline']];
	$_VARS['frontend_combination_params']['schools'] = [$file_data['parameters']['school']];
	
}

if(
	!empty($file_data['parameters']['area']) &&
	!empty($file_data['parameters']['productline']) &&
	!empty($file_data['parameters']['location']) &&
	!empty($file_data['parameters']['language'])
) {
	$_VARS['frontend_combination_params']['productlines'] = [$file_data['parameters']['productline']];

	// Damit man es im Template über $smarty.request abrufen kann (muss besser umgesetzt werden)
	$_REQUEST['area_id'] = (int)$file_data['parameters']['area'];
	$_REQUEST['location_id'] = (int)$file_data['parameters']['location'];
	$_REQUEST['courselanguage_id'] = (int)$file_data['parameters']['language'];

}

$aXVars = [];
$aXVars['X-Originating-IP'] = $_SERVER['REMOTE_ADDR'];
$aXVars['X-Originating-Agent'] = $_SERVER['HTTP_USER_AGENT'];
$aXVars['X-Originating-Host'] = $_SERVER['HTTP_HOST'];
$aXVars['X-Originating-URI'] = $_SERVER['REQUEST_URI'];
$aXVars['X-Originating-HTTPS'] = $_SERVER['HTTPS'];
	
$oRequest->add($aXVars);

include(Util::getDocumentRoot()."system/extensions/tc_api.php");
