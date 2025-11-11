<?php

/*
 * Aus Zend_Locale (ZF1) Sprache nach data_countries und data_languages importieren
 *
 * Entweder muss das hier auf V5 ausgeführt werden oder die DB-Tabellen müssen von V5 geholt werden.
 * Fehlende Einträge (z.B. Serbien und Montenegro) müssen ergänzt werden, da ansonsten auch ggf. Select-Options einfach leer sind!
 */

$sLocale = null;

include_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL ^ E_STRICT ^ E_NOTICE);
ini_set('display_errors', 1);

$oLogger = new Monolog\Logger(basename(__FILE__));
$oLogger->pushHandler(new Monolog\Handler\StreamHandler('php://output', Monolog\Logger::DEBUG));

function loadXml($sLocale) {
	$sFile = Util::getDocumentRoot().'/vendor/thebingservices/locale/library/Zend/Locale/Data/'.$sLocale.'.xml';

	if(!is_file($sFile)) {
		throw new RuntimeException('Locale XML not found!');
	}

	return simplexml_load_file($sFile);
}

function importCountries($sLocale, SimpleXMLElement $oXml, Monolog\Logger $oLogger) {

	$sColumn = 'cn_short_'.$sLocale;
	$aCountries = DB::getQueryPairs(" SELECT `cn_iso_2`, `{$sColumn}` FROM `data_countries`");

	foreach($aCountries as $sDbIso => $sCurrentDbName) {

		$sXmlName = null;
		foreach($oXml->localeDisplayNames->territories->territory as $oNode) {
			$sNodeIso = (string)$oNode->attributes()->type;

			if($sDbIso === $sNodeIso) {
				$sXmlName = (string)$oNode;
				break;
			}
		}

		if(!empty($sXmlName)) {
			DB::updateData('data_countries', [$sColumn => $sXmlName], " `cn_iso_2` = '{$sDbIso}'");
			if(empty($sCurrentDbName)) {
				$oLogger->addInfo('Inserted country '.strtoupper($sDbIso));
			} else {
				$oLogger->addInfo('Updated country '.strtoupper($sDbIso));
			}
		} else {
			$oLogger->addWarning('Country '.strtoupper($sDbIso).' missing!');
		}

	}

}

function importLanguages($sLocale, SimpleXMLElement $oXml, Monolog\Logger $oLogger) {

	$sColumn = 'name_'.$sLocale;
	$aLanguages = DB::getQueryPairs(" SELECT `iso_639_1`, `{$sColumn}` FROM `data_languages`");

	foreach($aLanguages as $sDbIso => $sCurrentDbName) {

		$sXmlName = null;
		foreach($oXml->localeDisplayNames->languages->language as $oNode) {
			$sNodeIso = (string)$oNode->attributes()->type;

			if($sDbIso === $sNodeIso) {
				$sXmlName = (string)$oNode;
				break;
			}
		}

		if(!empty($sXmlName)) {
			DB::updateData('data_languages', [$sColumn => $sXmlName], " `iso_639_1` = '{$sDbIso}'");
			if(empty($sCurrentDbName)) {
				$oLogger->addInfo('Inserted language '.strtoupper($sDbIso));
			} else {
				$oLogger->addInfo('Updated language '.strtoupper($sDbIso));
			}
		} else {
			$oLogger->addWarning('Language '.strtoupper($sDbIso).' missing!');
		}
	}

}

$oXml = loadXml($sLocale);
importCountries($sLocale, $oXml, $oLogger);
importLanguages($sLocale, $oXml, $oLogger);
