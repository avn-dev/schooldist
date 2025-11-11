<?php

require_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

$aMonths = $aMonthsShort = $aDays = $aDaysShort = array();

if(!isset($_VARS['dateFormat'])) {
	$sFormat = strftime('%x', mktime(12,13,14, 12, 31, 1990));
	$sFormat = str_replace(array('31','12','1990','90'), array('%d', '%m', '%Y', '%y'), $sFormat);
} else {
	$sFormat = $_VARS['dateFormat'];
}

$aParams = array(
	'dateField'			=> $_VARS['dateField'],
	'triggerElement'	=> $_VARS['triggerElement'],
	'parentElement'		=> $_VARS['parentElement'],
	'selectHandler'		=> $_VARS['selectHandler'],
	'closeHandler'		=> $_VARS['closeHandler'],
);

for($i = 1; $i <= 12; $i++) {
	$iTS = mktime(0,0,0, $i, 1, date('Y'));

	$aMonths[] = strftime('%B', $iTS);
	$aMonthsShort[] = mb_substr(strftime('%B', $iTS), 0, 3);
}

for($i = 1; $i <= 7; $i++) {
	$iTS = mktime(0,0,0, 1, $i, date('Y'));

	$aDays[strftime('%w', $iTS)] = strftime('%A', $iTS);
	$aDaysShort[strftime('%w', $iTS)] = mb_substr($aDays[strftime('%w', $iTS)], 0, 2);
}

ksort($aDays);
ksort($aDaysShort);

$aDays[] = $aDays[0];
$aDaysShort[] = $aDaysShort[0];

$aActiveDays = array(0, 1, 2, 3, 4, 5, 6);

$sFrom = '';
$sUntil = '';

/*
 * example for limiting calender days
$aActiveDays = array(0, 1, 4, 5, 6);
$sFrom = str_replace(array('%d', '%m', '%Y', '%y'), array('15','06','2010','10'), $sFormat);
$sUntil = str_replace(array('%d', '%m', '%Y', '%y'), array('15','09','2010','10'), $sFormat);
*/

$aData = array(
	'aDays'			=> $aDays,
	'aDaysShort'	=> $aDaysShort,
	'aMonths'		=> $aMonths,
	'aMonthsShort'	=> $aMonthsShort,
	'aParams'		=> $aParams,
	'sFormat'		=> $sFormat,
	'sToday'		=> L10N::t('Today'),
	'aActiveDays'	=> $aActiveDays,
	'sFirstDay'		=> $sFrom,
	'sLastDay'		=> $sUntil
);

// Call the hook if required
\System::wd()->executeHook('manupulate_calendar_data', $aData);
\System::wd()->executeHook('manipulate_calendar_data', $aData);

echo json_encode($aData);
