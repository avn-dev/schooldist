<?php

$fStartMicrotime = microtime(true);

// Minimale Includes
require_once '../config/config.php';
require_once '../system/includes/autoload.inc.php';
require_once '../system/includes/debug.inc.php';

/*
 * DB connection
 */
try {
	// Request the default database connection to establish the connection automatically.
	\DB::setResultType(MYSQL_ASSOC);
	$oDb = DB::getDefaultConnection();
} catch (Exception $e) {
	\Util::handleErrorMessage("The database connection could not be established.", 1, 1, 1);
	exit;
}

// Build request object
$oRequest = \MVC_Request::capture();

// Systemeinstellungen
$system_data = \System::readConfig();

// Debugmodus prüfen und setzen
\System::setDebugmode();

// System booten
$app = \System::boot();

$aUriParts = parse_url($_SERVER['REQUEST_URI']);
$sUrl = $aUriParts['path'];

$aUrl = explode('/', $sUrl);

// Leeres Element entfernen
array_shift($aUrl);

// Optional Prefix abschneiden
if(str_starts_with($sUrl, '/wdmvc/')) {
	array_shift($aUrl);
}

$sUrl = implode('/', $aUrl);

// Laravel kann keine Routen mit / am Ende -> Abfangen und weiterleiten
if(str_ends_with($sUrl, '/') === true) {
	$sUrl = rtrim($sUrl, '/');
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: /".$sUrl);
	die();
}

// In jedem Fall aufrufen, auch wenn irgendwo die oder exit aufgerufen wird
register_shutdown_function([\Core\Facade\SequentialProcessing::class, 'execute']);

// Init controller and run
$oController = new \MVC_Controller($app, $oRequest);
$oController->setStartTime($fStartMicrotime);
$oController->setDatabase($oDb);
$oController->run($sUrl);
