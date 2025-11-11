<?php

global $_VARS;

// In der .mod-Datei vom CMS steht das $oRequest direkt zur Verfügung
if(isset($this->_oRequest)) {
	$oRequest = $this->_oRequest;
}

/*
 * Das aktuelle Form verbraucht sauviel Ram, da alles auf einmal geladen wird (Speakup-Registation-Form)
 */
ini_set("memory_limit", "1G");

/*
 * TODO Reporting bei allen "die();" einbauen
 */
// TODO Das wird gar nicht mehr ausgeführt
if(!isset($_VARS)) {

//	// Minimale Includes
//	require_once \Util::getDocumentRoot()."system/includes/config.inc.php";
//	require_once \Util::getDocumentRoot().'system/includes/dbconnect.inc.php';
//	require_once \Util::getDocumentRoot().'system/includes/autoload.inc.php';
//	require_once \Util::getDocumentRoot().'system/includes/debug.inc.php';
//
//	// Systemeinstellungen
//	$system_data = System::readConfig();
//
//	// Debugmodus prüfen und setzen
//	System::setDebugmode();

	// Für Hooks
	// TODO Wird das irgendwo im Frontend verwendet?
	global $objWebDynamics;
	$objWebDynamics = webdynamics::getInstance('frontend');
	\System::wd()->getIncludes();

//	// Get DB Objekt
//	DB::setResultType(MYSQL_ASSOC);
//	$oDb = DB::getDefaultConnection();
//
//	Factory::executeStatic('Util', 'getAndSetTimezone');

}

// TODO Hier sollte man sich dringend eine bessere Lösung ausdenken
if($_VARS['debug']) {
	$bDebug = true;
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
	ini_set('display_errors', '1');
	$system_data['debugmode'] = 2;
} else {
	$bDebug = false;
	$system_data['debugmode'] = 0;
}

// Damit die Snippets die Verbindung prüfen können (siehe Wordpress-Snippet)
if(isset($_VARS['task']) && $_VARS['task'] === 'check_installation') {
	die('ok');
}

// Check Inputs
if(
	empty($_VARS['code']) ||
	empty($_VARS['template'])
) {
	throw new Exception('Configuration data is missing!');
}

$oTemplate = Ext_TC_Frontend_Template::getByKey($_VARS['template']);
$oCombination = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getByKey', array($_VARS['code']));

if(
	!$oCombination instanceof Ext_TC_Frontend_Combination
) {
	
	if($_VARS['debug']) {
		__out($session_data['queryhistory']);
	}
	
	throw new Exception('Combination key is wrong!');
}

if(
	!$oTemplate instanceof Ext_TC_Frontend_Template
) {
	
	if($_VARS['debug']) {
		__out($session_data['queryhistory']);
	}
	
	throw new Exception('Template key is wrong!');
}

// Das muss bereits hier passieren, da das auch bei AJAX-Requests benötigt wird
//System::setInterfaceLanguage($oCombination->getLanguage());
//Factory::executeStatic('System', 'setLocale');

if(isset($_VARS['get_request'])){

	// Eventuelle vorherige Ausgaben entfernen
	while(ob_get_level()) {
		ob_end_clean();
	}
	
	$aTransfer = $oCombination->executeRequest($oRequest, $oTemplate, $bDebug);
	echo json_encode($aTransfer);
	die();

// Check if "get file" was set
} else if(isset($_VARS['get_file'])){

	// Eventuelle vorherige Ausgaben entfernen
	while(ob_get_level()) {
		ob_end_clean();
	}
	
	$aFileData = $oCombination->getFileData($_VARS['get_file'], $bDebug);

	$sFile = $aFileData['file'];
	$sContentType = $aFileData['content_type'];

	$sFile = \Util::getDocumentRoot().$sFile;

	if(
		// TODO Warum passiert das hier und in getFileData() doppelt?
		is_file($sFile) &&
		!empty($sContentType)
	) {

		header('Content-Type: '.$sContentType);

		if($aFileData['is_attachment']) {
			header('Content-Disposition: attachment; filename="'.$aFileData['file_name'].'"');
			header('Cache-Control: max-age=0');
		}

		$fp = @fopen($sFile, 'r');
		@fpassthru($fp);
		@fclose($fp);

		exit();
		
	} else {
		throw new Exception('File not found!');
	}

// Rendering Template
} else {

	$oCombinationParams = new Ext_TC_Frontend_Combination_Helper_Params($oCombination);
	
//	$oRequest = new MVC_Request();
//	$oRequest->add($_VARS);
	
	$oCombinationParams->overwrite($oRequest);

	$bCheckPlausibility = $oCombinationParams->checkPlausibility($oRequest->get('debug', false));

	if($bCheckPlausibility === false) {

		Ext_TC_Util::sendErrorMessage([$oCombination->getPlausibilityDebug(), $_SERVER, $_REQUEST], 'Setted parameter are not compatible');
		echo 'Setted parameter are not compatible!';

	} else {

		// Da evt eine Zeitüberschreitung während des Speicherns passieren könnte
		// muss das mit Transactionen abgesichert werden da sonst evt die "items" zwischentabelle nicht komplett befüllt wird
		DB::begin('tc_api_last_use');

		try {
			// Letzte Verwendung speichern
			$oTemplate->updateLastUse();
			$oCombination->updateLastUse();
			DB::commit('tc_api_last_use');
		} catch (Exception $exc) {
			DB::rollback('tc_api_last_use');
			Ext_TC_Util::reportError('TC Api debug', $exc);
		}

		// Smarty3 Objekt initialisieren
		$oSmarty = new SmartyWrapper();
		// Request uri falls übergeben mit übermitteln damit wir es in Templates benutzen können
		// ansonsten wird nur die API Datei angegeben wir brauchen aber ggf. die aufgerufene URL
		if($_VARS['REQUEST_URI']){
			$REQUEST_URI = $_VARS['REQUEST_URI'];
			$oSmarty->assign('REQUEST_URI', $REQUEST_URI);
		}
		// Inhalte generieren
		$mContent = $oCombination->generateContent($oSmarty, $oTemplate, $oRequest, $bDebug);

		if($mContent === false) {
			$sResult = 'An error occured! ('.$oCombination->getError().')';
		} else {
			$sResult = $mContent;
		}

	}

}

if($_VARS['debug']) {
	__out($oTemplate->aData);
	__out($oCombination->aData);
}

// Header setzen falls nötig
$oCombination->setHeaderInformations();

echo $sResult;
