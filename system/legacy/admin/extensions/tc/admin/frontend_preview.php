<?php

// Das wird benötigt, da nach der ersten Ausgabe vom Snippet noch Cookies gesetzt werden
ob_start();

if(
	!empty($_REQUEST['pid']) &&
	!isset($_COOKIE['__pid'])
) {

	$_COOKIE['__pid']	= $_REQUEST['pid'];
	$_COOKIE['__ppa']	= $_REQUEST['pp'];

	setcookie('__pid', $_REQUEST['pid'], time() + 30 * 86400);
	setcookie('__ppa', $_REQUEST['pp'], time() + 30 * 86400);

}

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess(array('core_frontend_preview', ''));

global $oGui;

// Nur für Übersetung per ->t();
$oGui = new Ext_Gui2();
$oGui->gui_description = Ext_TC_Factory::executeStatic('Ext_TC_System_Navigation', 'tp');
if(empty($oGui->gui_description)) {
	$oGui->gui_description = 'Frontend Preview';
}

$iCode = 0;
$iTemplate = 0;
$sLanguage = '';

######
## Schauen ob in der Session schon Daten stehen
######
if(isset($_SESSION['code_id'])){
	$iCode = (int)$_SESSION['code_id'];
}
if(isset($_SESSION['template_id'])){
	$iTemplate = (int)$_SESSION['template_id'];
}
if(isset($_SESSION['frontend_language'])){
	$sLanguage = $_SESSION['frontend_language'];
}

####
## Schauen ob neue Daten übergeben wurden
#####
if(isset($_VARS['code_id'])){
	$iCode = (int)$_VARS['code_id'];
}
if(isset($_VARS['template_id'])){
	$iTemplate = (int)$_VARS['template_id'];
}
if(isset($_VARS['frontend_language'])){
	$sLanguage = $_VARS['frontend_language'];
}

$aLanguages = array();

####
## Objekte mit den gefunden IDs aufbauen
####
if(!empty($iCode)) {
	$oCombination = Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', $iCode);

	if(!empty($iTemplate)) {
		$oTemplate = Ext_TC_Frontend_Template::getInstance($iTemplate);
	}

	$sLanguageType = '';
	if($oCombination->items_language) {
		$sLanguageType = 'language';
		if(is_array($oCombination->items_language)) {
			$aLanguages = $oCombination->items_language;
		} else {
			$aLanguages[] = $oCombination->items_language;
		}
	} elseif($oCombination->items_languages) {
		$sLanguageType = 'languages';
		$aLanguages = (array)$oCombination->items_languages;
	}

	// Modus setzen (@todo ein Select dafür generieren um den Status über die Oberfläche zu setzen)
	$oCombination->setMode('testing');

}

####
## HTML Header
####
printHeader();

####
## Selects darstellen und ggf. die Objekte überschreiben
####
printKeySelection($oCombination, $oTemplate, $sLanguage, $aLanguages);

#####
## entgültige KEYs schreiben
####
$sCodeKey = $oCombination->key;
$sTemplateKey = $oTemplate->key;

###
## Session füllen damit auf Folgeseiten die Info nicht verloren geht
###
$_SESSION['code_id'] = $oCombination->id;
$_SESSION['template_id'] = $oTemplate->id;
$_SESSION['frontend_language'] = $sLanguage;

###
## Wenn was gewählt ist kann der Inhalt geladen werden
###
if(
	!empty($sCodeKey) &&
	!empty($sTemplateKey)
) {

	$_VARS['code'] = $sCodeKey;
	$_VARS['template'] = $sTemplateKey;

	include_once(\Util::getDocumentRoot()."tools/tc/class.snippet.php");

	$sServer = $_SERVER['HTTP_HOST'];

	###
	## Lokale Installationen klappen auf den normalen Weg nicht daher binden wir hier die Datei direkt ein,
	## ansonsten über den Standard gehen damit wir sicher sind das es auf externen Servern auch klappt
	###
	if(
		$sServer == 'agency.dev.box' ||
		$sServer == 'school.dev.box' ||
		strpos($sServer, '.dev.box') !== false ||
		strpos($sServer, 'agency.localhost') !== false ||
		strpos($sServer, 'school.localhost') !== false
	) {
		if($sLanguageType == 'language') {
			$_VARS['frontend_combination_params'][$sLanguageType] = $sLanguage;
		} else if($sLanguageType == 'languages') {
			$_VARS['frontend_combination_params'][$sLanguageType] = array($sLanguage);
		}
		// Individuelle Daten aus $_VARS in $_GET schreiben, da die tc_api.php $_VARS neu zusammenstellt
		$_GET['code'] = $_VARS['code'];
		$_GET['template'] = $_VARS['template'];
		$_GET['frontend_combination_params'][$sLanguageType] = $_VARS['frontend_combination_params'][$sLanguageType];
		
		\System::setInterface('frontend'); // Wichtig für Translations
		include_once(\Util::getDocumentRoot()."system/extensions/tc_api.php");
	} else {

		$sRequestScheme = 'http://';
		if($_SERVER['HTTPS'] === 'on') {
			$sRequestScheme = 'https://';
		}
		
		$oSnippet = new Thebing_Snippet($sRequestScheme.$sServer, $sCodeKey, $sTemplateKey);
        $oSnippet->setTimeout(60);
		$oSnippet->setCombinationParameter($sLanguageType, $sLanguage);
		$oSnippet->setCombinationParameter('combination_mode', 'testing');
		$oSnippet->execute();
		echo $oSnippet->getContent();
	}

}

###
## HTML Foooter darstellen
###
printFooter();

/**
 * Gibt das Div mit den Select zur Kombination/Template Auswahl aus und überschreibt die Objekte
 * falls sie nicht zu einander passen oder es keinen Standardwert gibt
 *
 * @param Ext_TC_Frontend_Combination &$oCombination
 * @param Ext_TC_Frontend_Template &$oTemplate
 * @param string &$sLanguage
 * @param mixed[] &$aLanguages
 * @return bool
 */
function printKeySelection(Ext_TC_Frontend_Combination $oCombination=null, Ext_TC_Frontend_Template $oTemplate=null, &$sLanguage=null, &$aLanguages) {
	global $oGui;

	if(empty($oCombination)) {
		$oCombination = Ext_TC_Frontend_Combination::getInstance();
	}
	
	if(empty($oTemplate)) {
		$oTemplate = Ext_TC_Frontend_Template::getInstance();
	}
	
	// Alle Kombinationen holen und sicherstellen das eine gültige gewählt ist
	$aCombinations = $oCombination->getRepository()->findAll();

	$aCombinations = array_filter($aCombinations, function (Ext_TC_Frontend_Combination $oCombination) {
		$oCombination2 = $oCombination->getObjectForUsage(new SmartyWrapper());
		return !($oCombination2 instanceof \TcFrontend\Interfaces\WidgetCombination);
	});

	/* @var $aCombinations Ext_TC_Frontend_Combination[] */
	$bCombinationFound = false;
	foreach($aCombinations as $oCurrentCombination) {
		if(
			$oCombination->id > 0 &&
			$oCurrentCombination->id == $oCombination->id
		) {
			$bCombinationFound = true;
		}
	}
	if(
		$oCombination->id > 0 &&
		!$bCombinationFound
	) {
		$oCombination = Ext_TC_Factory::getInstance('Ext_TC_Frontend_Combination', 0);
	}

	// Alle Templates holen und sicherstellen das ein gültiges gewählt ist
	$aTemplates = $oTemplate->getRepository()->findBy(array('usage' => $oCombination->usage));
	/* @var $aTemplates Ext_TC_Frontend_Template[] */
	$bTemplateFound = false;
	foreach($aTemplates as $oCurrentTemplate) {
		if(
			$oCombination->id > 0 &&
			$oTemplate->id > 0 &&
			$oCurrentTemplate->id == $oTemplate->id
		) {
			$bTemplateFound = true;
		}
	}
	if(
		$oTemplate->id > 0 &&
		!$bTemplateFound
	) {
		$oTemplate = Ext_TC_Frontend_Template::getInstance(0);
	}

	// Alle Sprachen holen und sicherstellen das eine gültige gewählt ist
	if(
		!$oCombination->id ||
		!$oTemplate->id
	) {
		$sLanguage = '';
	}
	$aPossibleLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true, 'de'));

	// abbrechen wenn kein HTML generiert werden soll
	if(!shouldGenerateHtml()) {
		return false;
	}

	$oHeader = new Ext_Gui2_Html_Div();
	
	// HTML Generieren
	$oDiv = new Ext_Gui2_Html_Div();
	$oDiv->id = 'key_selection';
	$oForm = new Ext_Gui2_Html_Form();
	$oForm->method = "post";

	$oLabel = new Ext_Gui2_Html_Label();
	$oLabel->setElement($oGui->t('Kombination, Template und Sprache wechseln: '));
	$oForm->setElement($oLabel);

	// HTML Generieren :: Kombinationen
	$oSelect = new Ext_Gui2_Html_Select();
	$oSelect->name = 'code_id';
	$oSelect->onchange = "submit();";
	$aCombinationOptions = array();
	foreach ($aCombinations as $oCombinationItem) {
		$aCombinationOptions[$oCombinationItem->id] = $oCombinationItem->getName();
	}
	$oOption = new Ext_Gui2_Html_Option();
	$oOption->value = 0;
	$oOption->setElement('');
	$oSelect->setElement($oOption);
	asort($aCombinationOptions);
	foreach ($aCombinationOptions as $iCombinationId => $sCurrentCombination) {
		$oCurrentCombination = Ext_TC_Frontend_Combination::getInstance($iCombinationId);
		$oOption = new Ext_Gui2_Html_Option();
		$oOption->value = $iCombinationId;
		$oOption->setElement($sCurrentCombination.' ('.$oCurrentCombination->key.')');
		if($oCombination->id == $iCombinationId) {
			$oOption->selected = "selected";
		}
		$oSelect->setElement($oOption);
	}
	$oForm->setElement($oSelect);

	// HTML Generieren :: Templates
	$oSelect = new Ext_Gui2_Html_Select();
	$oSelect->name = 'template_id';
	$oSelect->onchange = "submit();";
	$oOption = new Ext_Gui2_Html_Option();
	$oOption->value = 0;
	$oOption->setElement('');
	$oSelect->setElement($oOption);
	foreach ($aTemplates as $oCurrentTemplate) {
		$oOption = new Ext_Gui2_Html_Option();
		$oOption->value = $oCurrentTemplate->id;
		$oOption->setElement($oCurrentTemplate->getName().' ('.$oCurrentTemplate->key.')');
		if($oTemplate->id == $oCurrentTemplate->id) {
			$oOption->selected = "selected";
		}
		$oSelect->setElement($oOption);
	}
	$oForm->setElement($oSelect);

	// HTML Generieren :: Sprache
	$oSelect = new Ext_Gui2_Html_Select();
	$oSelect->name = 'frontend_language';
	$oSelect->onchange = "submit();";
	$bLanguageSelected = false;
	foreach ($aLanguages as $sLanguageItem) {
		if(
			!$oCombination->id ||
			!$oTemplate->id ||
			!isset($aPossibleLanguages[$sLanguageItem])
		) {
			continue;
		}
		$oOption = new Ext_Gui2_Html_Option();
		$oOption->value = $sLanguageItem;
		$oOption->setElement($aPossibleLanguages[$sLanguageItem]);
		if($sLanguageItem == $sLanguage) {
			$oOption->selected = "selected";
			$bLanguageSelected = true;
		}
		$oSelect->setElement($oOption);
	}
	$oForm->setElement($oSelect);
	if(!$bLanguageSelected) {
		$sLanguage = '';
		$aElements = $oSelect->getElements();
		if(count($aElements)) {
			$oFirstElement = reset($aElements);
			$sLanguage = $oFirstElement->value;
		}
	}

	// HTML Generieren :: Debug Checkbox
	$oCheckbox = new Ext_Gui2_Html_Input;
	$oCheckbox->type = 'checkbox';
	$oCheckbox->name = 'debug';
	$oCheckbox->onchange = 'submit();';
	if($_REQUEST['debug']) {
		$oCheckbox->checked = true;
	}
	$oForm->setElement($oCheckbox);
	
	// HTML Generieren :: Ausgabe
	$oDiv->setElement($oForm);
	
	$oHeader->setElement($oDiv);
	
	$aHookData = [
		'combination' => $oCombination,
		'template' => $oTemplate, 
		'language' => $sLanguage,
		'container' => $oHeader
	];
	
	System::wd()->executeHook('tc_frontend_preview_selection_hook', $aHookData);
	
	echo $oHeader->generateHTML();

}

/**
 * Gibt den Header aus
 *
 * @return bool
 */
function printHeader() {

	// abbrechen wenn kein HTML generiert werden soll
	if(!shouldGenerateHtml()) {
		return false;
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<title>Thebing - Frontend</title>
		<style>
			html, body {
				padding: 0px;
				margin: 0px;
				width: 100%;
				height: 100%;
			}
			#key_selection {
				padding: 3px;
				border: 1px solid #CCC;
				background-color: #EEE;
			}
		</style>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>

	</head>
	<body class="area-holidays">
<?php

}

/**
 * Gibt den Footer aus
 *
 * @return bool
 */
function printFooter() {

	// abbrechen wenn kein HTML generiert werden soll
	if(!shouldGenerateHtml()) {
		return false;
	}

?>
	</body>
</html>		
<?php

}

/**
 * Gibt false zurück wenn keine HTML-Ausgabe generiert werden soll
 */
function shouldGenerateHtml() {

	global $_VARS;

	if(
		!empty($_VARS['get_request']) ||
		!empty($_VARS['get_file'])
	) {
		return false;
	}

	return true;

}
