<?

include_once("../system/includes/main.inc.php");


$user_data['cms'] = 0;

if(!$_VARS['r']){
	die('No Customer Data');
}

if($_VARS['task'] == 'cancelNewsletter'){
	$bSuccess = Ext_TS_Inquiry_Contact_Traveller::cancelNewsletter($_VARS['r']);
	
	if($bSuccess){
		echo L10N::t('Sie wurden erfolgreich von unserem Newsletter abgemeldet!');
	} else {
		echo L10N::t('Es trat leider ein Fehler auf!');
	}
}
