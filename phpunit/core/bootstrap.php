<?php

$aDbData = array(
	'system' => 'thebing_unittest',
	'module' => 'thebing_unittest',
	'username' => 'thebing_unittest',
	'password' => 'koeln23',
	'host' => 'localhost',
	'port' => '3306'
);

if(
	isset($sSystem) &&
	$sSystem == 'school'
){
	$aDbData = array(
		'system' => 'ts_unittest',
		'module' => 'ts_unittest',
		'username' => 'ts_unittest',
		'password' => 'porsche07',
		'host' => 'localhost',
		'port' => '3306'
	);
}

// Relativ gelöst, damit es bei core, agency und school klappt
$sRoot = __DIR__;
$sRoot = str_replace('phpunit/core', '', $sRoot);
$sRoot = str_replace('phpunit\core', '', $sRoot);//Windoof Variante

$GLOBALS['_SERVER'] = array(
	'HTTP_HOST' => 'localhost:8080',
	'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2',
	'SERVER_ADDR' => '127.0.0.1',
	'REMOTE_ADDR' => '127.0.0.1',
	'DOCUMENT_ROOT' => $sRoot,
);

$GLOBALS['session_data']['backend'] = 1;
$GLOBALS['system_data']['systemlanguage'] = 'de';

// Wegen fehlender Dependency Incection... unsauber lösen über include_ONCE
// DB Connection ändern
include_once(\Util::getDocumentRoot()."system/includes/config.inc.php");

$db_data = $GLOBALS['db_data'] = $aDbData;

include_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Ext_TC_System::setInterfaceLanguage('de');

include_once \Util::getDocumentRoot().'phpunit/core/testSetup.php';

// Damit PHPUnit die Klassen automatisch laden kann
require_once 'PHPUnit/Autoload.php';

error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
ini_set('display_errors', true);

?>