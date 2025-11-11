<?php

/* ==================================================================================================== */

if(isset($_VARS['task']) && $_VARS['task'] == 'manage_project_acceptance')
{
	$iNewState	= $_VARS['state'];
	$iEntryID	= $_VARS['entry_id'];
	$iProjectID	= $_VARS['project_id'];

	DB::updateData(
		'office_project_employees',
		array('state' => $iNewState),
		'`id` = '.intval($iEntryID)
	);

	if($iNewState == 1)
	{
		$sFlag = 'accepted';
	}
	if($iNewState == 0)
	{
		$sFlag = 'declined';
	}

	$sSQL = "SELECT * FROM `consulimus_office_project_addons` WHERE `project_id` = :iProjectID LIMIT 1";
	$aAddons = DB::getQueryRow($sSQL, array('iProjectID' => $iProjectID));

	$oProject = new Ext_Office_Project('office_projects', $iProjectID);
}

/* ==================================================================================================== */

$oSmarty = new \Cms\Service\Smarty();
$oSmarty->assign('sFlag', $sFlag);
$oSmarty->assign('oProject', $oProject);
$oSmarty->assign('aAddons', $aAddons);
$oSmarty->displayExtension($element_data);

?>