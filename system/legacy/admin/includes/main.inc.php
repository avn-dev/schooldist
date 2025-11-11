<?php

include_once(\Util::getDocumentRoot()."system/includes/admin.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/functions.inc.php");

if(
	$_SERVER['HTTPS'] != 'on' &&
	$system_data['admin_https']
) {
	$sURI = "https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	if(!empty($_SERVER['QUERY_STRING'])) {
		$sURI .=  "?".$_SERVER['QUERY_STRING'];
	}
	header("Location: ".$sURI);
	die();
}

// Create object for localization
$oL10N = new L10N();

//if($user_data['id'] > 0) {
//	$oGlobalChecks = new GlobalChecks();
//	$oGlobalChecks->generateHtml();
//}