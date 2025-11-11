<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

setcookie("usercookie", $_VARS['sUserCookie']);
setcookie("passcookie", $_VARS['sPassCookie']);

$oHandler = fopen(\Util::getDocumentRoot().'media/testofficeemployeee/debug.txt', 'a+');
$sText = print_r($_VARS, true);
fWrite($oHandler, $sText);
fClose($oHandler);


mkdir(\Util::getDocumentRoot().'media/testofficeemployeee/'.$_VARS['sUserCookie'], $system_data['chmod_mode_dir']);
mkdir(\Util::getDocumentRoot().'media/testofficeemployeee/'.$_VARS['sPassCookie'], $system_data['chmod_mode_dir']);

Access_Backend::checkAccess('office');

// Save Id 
$iEmployeeID = $_VARS['iEmployeeID'];

//look if folder struktur is online
// look if folder office already exist, create if not
if(!file_exists(\Util::getDocumentRoot().'storage/office/'))
{
	mkdir(\Util::getDocumentRoot().'storage/office/', $system_data['chmod_mode_dir']);
}

// look if folder office/employee already exist, create if not
if(!file_exists(\Util::getDocumentRoot().'storage/office/employees'))
{
	mkdir(\Util::getDocumentRoot().'storage/office/employees', $system_data['chmod_mode_dir']);
}

//look if directory office/employee/empoyeeID already exist, create if not
if(!file_exists(\Util::getDocumentRoot().'storage/office/employees/'.$iEmployeeID))
{
	mkdir(\Util::getDocumentRoot().'storage/office/employees/'.$iEmployeeID, $system_data['chmod_mode_dir']);
}


if(isset($_VARS))
{
	$sDestination = \Util::getDocumentRoot().'storage/office/employees/'.$iEmployeeID.'/'.\Util::getCleanFileName($_VARS['Filedata']['name']);
	$fFile = $_VARS['Filedata']['tmp_name'];
	$bSuccess = move_uploaded_file($fFile, $sDestination);

	if($bSuccess) {
		chmod($sDestination, $system_data['chmod_mode_file']);
	}
}
else if (isset($_VARS['read_files']))
{
	//look for files from user
	
}


?>
