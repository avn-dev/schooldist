<?php

// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)
				
use Core\Handler\CookieHandler;

/*
 * SETZT POST UND GET VARIABLEN ZUSAMMEN
 */

// Bei Fehlerweiterleitung die übergebenen Parameter wieder ins GET packen
if(
	isset($_GET['errorcode']) && 
	count($_GET) <= 1 && 
	isset($_SERVER['REDIRECT_QUERY_STRING'])
) {
	parse_str($_SERVER['REDIRECT_QUERY_STRING'], $_GET);
}

if (!empty($_COOKIE)) {
    Util::removeMagicQuotes($_COOKIE);
} // end if

if (!empty($_GET)) {
    Util::removeMagicQuotes($_GET);
} // end if

if (!empty($_POST)) {
    Util::removeMagicQuotes($_POST);
} // end if

if (!empty($_FILES)) {
    Util::removeMagicQuotes($_FILES);
} // end if

$_VARS = Util::mergeArrayRecursiveDistinct($_POST, $_GET);

/*
 * ERMITTELT DIE SYSTEMVARIABLEN AUS DER DB
 */
$db_system = $db_data['system'];

$system_data = System::readConfig();

/* ============================ */

/**
 * set debugmode
 */
System::setDebugmode();

/**
 * enable transid
 */
if(
	isset($system_data['use_trans_sid']) &&
	$system_data['use_trans_sid']
) {
	ini_set('arg_separator.output', '&amp;');
	ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,input=src,form=action,link=href,script=src');
	ini_set('session.use_trans_sid', 1);
	ini_set("session.use_cookies", 1);
	ini_set("session.use_only_cookies", 0);
	$session_data['use_trans_sid'] = true;
}

/**
 * START SESSION HANDLER
 */
$oSession = Core\Handler\SessionHandler::getInstance();

/**
 * INITIALISIERT ARRAYS
 */
$page_data = array();

if(
	!isset($objWebDynamicsDAO) &&
	class_exists('Cms\Helper\Data')
) {
	$objWebDynamicsDAO = new Cms\Helper\Data();
}

/*
 * ERMITTELT DIE DATEIANGABEN
 */

// >>>
if(System::getInterface() === 'backend') {

	// set correct env variables when php is used in cgi-mode
	$sEnvState = 'apache-module';

	// unknown case (invalid environment)
	if (
		!array_key_exists('PHP_SELF', $_SERVER) ||
		!array_key_exists('QUERY_STRING', $_SERVER) ||
		!array_key_exists('REQUEST_URI', $_SERVER) ||
		!array_key_exists('SCRIPT_NAME', $_SERVER)
	) {

		$sEnvState = 'unknown case (invalid environment)';

	}

	// php-cgi (1&1 special)
	elseif (
		array_key_exists('REDIRECT_URL', $_SERVER) &&
		array_key_exists('REDIRECT_SCRIPT_URL', $_SERVER) &&
		$_SERVER['PHP_SELF'] == $_SERVER['REDIRECT_URL']
	) {

		$sEnvState = 'php-cgi (1&1 special) [modify REDIRECT_URL]';
		$_SERVER['REDIRECT_URL'] = $_SERVER['REDIRECT_SCRIPT_URL'];

	}

	// php-cgi (without path info)
	elseif (
		array_key_exists('REDIRECT_URL', $_SERVER) &&
		!array_key_exists('PATH_INFO', $_SERVER) &&
		$_SERVER['PHP_SELF'] == $_SERVER['REDIRECT_URL']
	) {

		$sEnvState = 'php-cgi (without path info)';

		// direct access to php file
		if (
			$_SERVER['SCRIPT_NAME'] == $_SERVER['REDIRECT_URL'] &&
			$_SERVER['PHP_SELF'] == $_SERVER['SCRIPT_NAME'] &&
			(
				// case #1: without query string
				$_SERVER['REQUEST_URI'] == $_SERVER['REDIRECT_URL'] ||
				// case #2: with query string
				$_SERVER['REQUEST_URI'] == $_SERVER['REDIRECT_URL'].'?'.$_SERVER['QUERY_STRING']
			)
		) {

			$sEnvState .= ' > direct access to php file [unset REDIRECT_URL]';
			unset($_SERVER['REDIRECT_URL']);

		}

		// url rewrite
		elseif (
			$_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'] &&
			$_SERVER['SCRIPT_NAME'] == $_SERVER['REDIRECT_URL'] &&
			$_SERVER['PHP_SELF'] == $_SERVER['SCRIPT_NAME']
		) {

			$sEnvState .= ' > url rewrite';

			// without query string
			if (
				strlen($_SERVER['QUERY_STRING']) == 0
			) {

				$sEnvState .= ' > without query string [modify REDIRECT_URL]';
				$_SERVER['REDIRECT_URL'] = $_SERVER['REQUEST_URI'];

			}

			// with query string
			elseif (
				strlen($_SERVER['QUERY_STRING']) > 0 &&
				strpos($_SERVER['REQUEST_URI'], '?') !== false &&
				substr($_SERVER['REQUEST_URI'], (strlen($_SERVER['QUERY_STRING'])*-1)-1) == '?'.$_SERVER['QUERY_STRING']
			) {

				$sEnvState .= ' > with query string [modify REDIRECT_URL]';
				$_SERVER['REDIRECT_URL'] = substr($_SERVER['REQUEST_URI'], 0, ((strlen($_SERVER['QUERY_STRING'])*-1)-1));

			}

			// special or unknown case
			else {

				// get resources
				$iQuestionMarkPos = strpos($_SERVER['REQUEST_URI'], '?');
				$iQueryStringLen = (strlen($_SERVER['REQUEST_URI']) - $iQuestionMarkPos - 1);
				$sRequestQueryPart = substr($_SERVER['REQUEST_URI'], $iQuestionMarkPos+1);
				$sRedirectDirname = dirname($_SERVER['REDIRECT_URL']);

				if (substr($sRequestQueryPart, -1, 1) == '&') {

					$sRequestQueryPart = substr($sRequestQueryPart, 0, -1);

				}

				if (substr($sRedirectDirname, -1, 1) == '/') {

					$sRedirectDirname = substr($sRedirectDirname, 0, -1);

				}

				// using QSA rule without query string
				if (
					strlen($_SERVER['QUERY_STRING']) > 0 &&
					strpos($_SERVER['REQUEST_URI'], '?') === false &&
					$sRedirectDirname.'/'.$_SERVER['QUERY_STRING'] == $_SERVER['REQUEST_URI']
				) {

					$sEnvState .= ' > using QSA rule without query string [modify REDIRECT_URL]';
					$_SERVER['REDIRECT_URL'] = $_SERVER['REQUEST_URI'];

				}

				// using QSA rule with query string
				elseif (
					strlen($_SERVER['QUERY_STRING']) > 0 &&
					strpos($_SERVER['REQUEST_URI'], '?') !== false &&
					strlen($_SERVER['QUERY_STRING']) > $iQueryStringLen &&
					substr($_SERVER['QUERY_STRING'], ((strlen($sRequestQueryPart)*-1)-1)) == '&'.$sRequestQueryPart
				) {

					$sEnvState .= ' > using QSA rule with query string [modify REDIRECT_URL]';
					$_SERVER['REDIRECT_URL'] = substr($_SERVER['REQUEST_URI'], 0, $iQuestionMarkPos);

				}

				// unknown case
				else {

					$sEnvState .= ' > unknown case';

				}

				// free resources
				unset($iQuestionMarkPos);
				unset($iQueryStringLen);
				unset($sRequestQueryPart);
				unset($sRedirectDirname);

			}

		}

		// unknown case
		else {

			$sEnvState .= ' > unknown case';

		}

	}
}

// >>>>>>>>>> PHP-CGI SUPPORT - END <<<<<<<<<<

// check rewrite url
if (
	$_SERVER['PHP_SELF'] == '/index.php' || 
	$_SERVER['PHP_SELF'] == '/css.php'
) {

	$aParts = parse_url($_SERVER['REDIRECT_URL']);

	$_SERVER['PHP_SELF_ORIGINAL'] = $aParts['path'];

	$aPathInfo = parse_url($_SERVER['REDIRECT_URL']);
	$_SERVER['PHP_SELF'] = $aPathInfo['path'];

	// check if trailing slash is missing
	$bolCheck = preg_match("=/[a-z\.\-0-9_]+\.[a-z0-9_]{2,4}=i", $_SERVER['PHP_SELF']);
	if (!$bolCheck) {
		$_SERVER['PHP_SELF'] .= '/index.html';
	}

} else {
	$_SERVER['PHP_SELF_ORIGINAL'] = $_SERVER['PHP_SELF'];
}

// Prüft, ob Syntaxfehler in der URL sind
if (strpos($_SERVER['PHP_SELF'],"//") !== false) {
	$_SERVER['PHP_SELF'] = preg_replace("/[\/]+/","/",$_SERVER['PHP_SELF']);
}
$PHP_SELF = $_SERVER['PHP_SELF'];

$file_data = array();

$path = pathinfo($_SERVER['PHP_SELF']);

$path["name"] = substr($path["basename"], 0, strrpos($path["basename"],"."));

// Slash am Ende des Dateinamens abfangen
if(substr($_SERVER['PHP_SELF'], -1) === '/') {
	if(substr($_SERVER['PHP_SELF'], (strlen($path['extension']) + 1) * -1) === $path['extension'].'/') {
		// Dateinamen anpassen, damit nichts gefunden wird
		$path["name"] .= '__';
	}
}

if (strpos($path["dirname"], "\\") !== false) {
    $path["dirname"] = str_replace("\\", "/", $path["dirname"]);
}

$file_data["filename"] 	= $path["basename"];
$file_data["name"] 		= $path["name"];
$file_data["extension"] = $path["extension"];
$file_data["path"]	 	= $path["dirname"];

if ($file_data["extension"] == "css") {
	$system_data['csspage'] = 1;
}

if (
	$file_data["extension"] == "jpg" ||
	$file_data["extension"] == "gif" ||
	$file_data["extension"] == "png"
) {
	$system_data['imagepage'] = 1;
}

if ($file_data["name"] == "favicon") {
	$system_data['faviconpage'] = 1;
}

if (
	$_SERVER['PHP_SELF'] == "/css.php" ||
	$_SERVER['PHP_SELF'] == "/favicon.php" ||
	$_SERVER['PHP_SELF'] == "/robots.php" ||
	$_SERVER['PHP_SELF'] == "/image.php" ||
	$_SERVER['PHP_SELF'] == "/secure.php"
) {
	$system_data['functionpage'] = 1;
}

// Ausnahme für die root-Datei
if ($file_data["path"] == "/" && $file_data["name"] == "index") {
	$file_data["name"] = "";
}

$arr = explode("/",$file_data["path"]);

$file_data["path"] = "";

$i=0;
foreach ($arr as $elem) {
	if ($elem) {
		$file_data["dir"][$i] = $elem;
		if ($i >= 1) {
			$file_data["path"] .= $elem."/";
		}
		$i++;
	}
}

$file_data["level"] = $i;

/*
 * ERMITTELT DIE SPRACHE
 */

// language is first parameter in directory tree
$strLanguage = trim($file_data["dir"][0]);
// if backend
$system_data['adminpage'] = 0;
if (
	$strLanguage == "admin" || 
	$strLanguage == "system"
) {
	$system_data['adminpage'] = 1;	
}

/*
 * make webdynamics class instance
 */ 
if (!isset($objWebDynamics)) {

	if (System::getInterface() === 'backend') {
		$objWebDynamics = webdynamics::getInstance('backend');
	} else {
		$objWebDynamics = webdynamics::getInstance('frontend');
	}

	\System::wd()->getIncludes();

}

/*
 * ERMITTELT DIE SITE ID
 */
if($session_data['public']) {

	$system_data['sites'] = $objWebDynamicsDAO->getSites();
	
	if(!isset($_SESSION['site_id']))
	{
		$arrSite = $objWebDynamicsDAO->getSiteFromDomain($_SERVER['HTTP_HOST']);

		if(empty($arrSite))
		{
			$aFirstSite = reset($system_data['sites']);

			$sHttpPart = 'http://';

			if($aFirstSite['force_https'])
			{
				$sHttpPart = 'https://';
			}

			switch($system_data['error_page_global'])
			{
				case '404':
				{
					header("HTTP/1.0 404 Not Found");
					header("Location: " . $sHttpPart . $aFirstSite['domain']);
					exit();
				}
				case '404_die':
				{
					echo "404 Not Found";
					header("HTTP/1.0 404 Not Found");
					header("Status: 404 Not Found");
					exit();
				}
				case '301':
				{
					header("HTTP/1.0 301 Moved Permanently");
					header("Location: " . $sHttpPart . $aFirstSite['domain']);
					exit();
				}
			}
		}

		$_SESSION['site_id'] = $arrSite['id'];
	}
	else {
		$arrSite = $system_data['sites'][$_SESSION['site_id']];
	}

	if(!is_array($arrSite)) {
		$arrSite = reset($system_data['sites']);
	}
	if(is_array($arrSite)) {
		// get master domain name
		$system_data['domain'] 				= "http://".$system_data['sites'][$arrSite['id']]['domain'];

		$system_data['project_name'] 		= $arrSite['name'];
		$system_data['admin_email'] 		= $arrSite['email'];
		$system_data['site_id'] 			= $arrSite['id'];
		$system_data['no_redirect_to_host']	= ($arrSite['redirect_to_domain'])?0:1;
		$system_data['force_https']			= (bool)$arrSite['force_https'];
		
	} else {

		$system_data['site_id'] = 1;
	
	}

	$oSite = \Cms\Entity\Site::getInstance($system_data['site_id']);
	$system_data['arrLanguages'] = $oSite->getLanguages(1);

	// dynamic, search engine friendly url hook
	\System::wd()->executeHook('url_parser', $file_data);

}

if(CookieHandler::get('incms')) {
	$system_data['no_redirect_to_host']	= 1;	
}

$aDomain = parse_url($system_data['domain']);
$system_data['host'] 	= $aDomain['host'];
$system_data['scheme'] 	= $aDomain['scheme'];
if(isset($aDomain['port'])) {
	$system_data['port'] = $aDomain['port'];
}

$system_data['status'] 	= $system_data['status_template'];

// get languages


if(System::getInterface() === 'frontend') {

	// Wenn der Internetaufrtitt Sprachordner deaktiviert hat
	if($oSite->no_language_folder == 1) {
		// Zwingt das System dazu den Sprachordner zu ignorieren
		$strLanguage = 'xxx';
		// Den Root auf den Root der ersten Sprache setzen
		if(
			empty($file_data['path']) &&
			empty($file_data['name'])
		) {
			$file_data['name'] = 'index';
		}
	}
	
	$bolPageError = 0;
	if(
		$strLanguage && 
		in_array($strLanguage, (array)$system_data['arrLanguages'])
	) {
	
		$page_data['language'] = $strLanguage;
	
	} elseif(
		$strLanguage && 
		!in_array($strLanguage, (array)$system_data['arrLanguages'])
	) {

		// check if page exists without language dir
		$file_data['path'] = "";
		foreach((array)$file_data["dir"] as $sItem) {
			$file_data['path'] .= $sItem."/";
		}

	}

	// if no language found
	if(
		empty($page_data['language']) ||
		!in_array($page_data['language'], (array)$system_data['arrLanguages'])
	) {

		//  if language is set in session
		if(
			!empty($_SESSION['page_language']) &&
			in_array($_SESSION['page_language'], $system_data['arrLanguages'])
		) {

			$page_data['language'] = $_SESSION['page_language'];

		} else {

			$page_data['language'] = $system_data['arrLanguages'][0];

		}

	}

	$_SESSION['page_language'] = $page_data['language'];

	System::setInterfaceLanguage($page_data['language']);

}

// calculate session validity
if($system_data['session_validity'] == "global") {
	$system_data['session_expire'] = intval(time()+($system_data['session_time'] * 60));
} else {
	$system_data['session_expire'] = 0;
}

// TODO json_encode erzeugt invaliden Inhalt für setrawcookie()
// PHP message: PHP Fatal error:  Uncaught ValueError: setrawcookie(): Argument #2 ($value) cannot contain ",", ";", " ", "\t", "\r", "\n", "\013", or "\014"
if(CookieHandler::is('browsercookie')) {
	$browser_data = json_decode(stripslashes(CookieHandler::get('browsercookie')), true);
} else {
	$browser_data = \Core\Helper\Agent::getInfo();
	CookieHandler::set("browsercookie", json_encode($browser_data));
}

if(System::getInterface() === 'backend') {

	// Verfügbare Systemsprachen
	$system_data['allowed_languages'] = System::d('allowed_languages');
	
	if(
		CookieHandler::is('systemlanguage') &&
		CookieHandler::get('systemlanguage') && 
		(
			!isset($_POST['systemlanguage']) ||
			!$_POST['systemlanguage']
		)
	) {
		$system_data['systemlanguage'] = CookieHandler::get('systemlanguage');
	} else {
		if($_POST['systemlanguage']) {
			$system_data['systemlanguage'] = $_POST['systemlanguage'];
		} else {
			$aLanguages = array_flip($system_data['allowed_languages']);
			$system_data['systemlanguage'] = \Core\Helper\Agent::getBrowserLanguage($aLanguages,$aLanguages[0]); //$system_data['allowed_languages'][getBrowserLanguage($aLanguages,$aLanguages[0])];
			if(empty($system_data['systemlanguage'])){
				$system_data['systemlanguage'] = reset($aLanguages);
			}
		}
		CookieHandler::set("systemlanguage", $system_data['systemlanguage']);
	}

	System::setInterfaceLanguage($system_data['systemlanguage']);

}

// Setzt lokale Zeitzone, Zeiteinstellung und Sprache
Factory::executeStatic('Util', 'getAndSetTimezone');
System::setLocale();

/*
 * ERMITTELT DIE SEITENANGABEN
 */
if(
	$session_data['public'] &&
	!$system_data['csspage'] && 
	!$system_data['faviconpage'] && 
	!$system_data['functionpage'] && 
	!$system_data['imagepage'] &&
	!$system_data['adminpage']
) {

	$rows_template = 0;
	
	if(!$bolPageError) {

		if($file_data["name"] == "index") {

			$sPageQuery = "
							SELECT 
								* 
							FROM 
								`cms_pages` 
							WHERE 
								`element` != 'template' AND
								`site_id` = :intSiteId AND
								`path` = :strFilePath AND 
								`indexpage` = 1 AND 
								(
									`language` = :strLanguage OR 
									`language` = ''
								)
							LIMIT 1";
			$arrTransfer = array();
			$arrTransfer['intSiteId'] 	= (int)$system_data['site_id'];
			$arrTransfer['strFilePath'] = $file_data["path"];
			$arrTransfer['strLanguage'] = $page_data['language'];

		} else {

			$sPageQuery = "
							SELECT  
								*  
							FROM  
								cms_pages  
							WHERE  
								`element` != 'template' AND
								`site_id` = :intSiteId AND
								`path` = :strFilePath AND  
								`file` = :strFileName AND  
								( 
									`language` = :strLanguage OR  
									`language` = ''
								)
							LIMIT 1";
			$arrTransfer = array();
			$arrTransfer['intSiteId'] 	= (int)$system_data['site_id'];
			$arrTransfer['strFilePath'] = $file_data['path'];
			$arrTransfer['strFileName'] = $file_data['name'];
			$arrTransfer['strLanguage'] = $page_data['language'];

		}

		$arrPageData = DB::getPreparedQueryData($sPageQuery, $arrTransfer);	
		$rows_template = count($arrPageData);
		$my_template = $arrPageData[0];

	}

	/**
	 * @todo #227 
	 */
	
	// page not found or page not active
	if(
		$rows_template == 0 OR
		(
			$my_template['active'] != 1 && 
			CookieHandler::get('incms') == 0
		)
	) {
		
		// Prüfen, ob es eine Weiterleitung gibt
		$mRedirection = null;
		if(class_exists('\Cms\Entity\Redirection')) {
			
			$aUrl = parse_url($_SERVER['REQUEST_URI']);

			$mRedirection = \Cms\Entity\Redirection::getByUrl($system_data['site_id'], $aUrl['path']);

		}
		
		if($mRedirection !== null) {
			
			$bRedirect = true;
			switch($mRedirection->return_code) {
				case 'http_200':
				{
					header("HTTP/1.0 200 OK");
					$_VARS['errorcode'] = 200;
					break;
				}
				case 'http_404':
				{
					header("HTTP/1.0 404 Not Found");
					$_VARS['errorcode'] = 404;
					$bRedirect = false;
					break;
				}
				case 'http_301':
				default:
				{
					header("HTTP/1.0 301 Moved Permanently");
					$_VARS['errorcode'] = 301;
					break;
				}
			}

			if($bRedirect) {
				header("Location: ".$mRedirection->target);
			}
			exit;

		}
		
		// set error log
		//error("Die Seite konnte nicht gefunden werden!", 1, 0);

		$intErrorPageId = $system_data['error_page_'.$system_data['site_id'].'_'.$page_data['language']];

		if(!$intErrorPageId) {
			$intErrorPageId = $system_data['error_page_'.$page_data['language']];
		}

		switch($system_data['error_page_' . $system_data['site_id'] . '_disabled'])
		{
			case '200':
			{
				header("HTTP/1.0 200 OK");
				$_VARS['errorcode'] = 200;
				break;
			}
			case '404':
			{
				header("HTTP/1.0 404 Not Found");
				$_VARS['errorcode'] = 404;
				break;
			}
			case '301':
			{
				header("HTTP/1.0 301 Moved Permanently");
				$_VARS['errorcode'] = 301;
				break;
			}
			case '200_rel':
			{
				header("HTTP/1.0 200 OK");
				header("Location: " . idtopath($intErrorPageId));
				exit();
			}
			case '404_rel':
			{
				header("HTTP/1.0 404 Not Found");
				header("Location: " . idtopath($intErrorPageId));
				exit();
			}
			case '301_rel':
			{
				header("HTTP/1.0 301 Moved Permanently");
				header("Location: " . idtopath($intErrorPageId));
				exit();
			}
			case '200_dom':
			{
				header("HTTP/1.0 200 OK");
				header("Location: /");
				exit();
			}
			case '404_dom':
			{
				header("HTTP/1.0 404 Not Found");
				header("Location: /");
				exit();
			}
			case '301_dom':
			{
				header("HTTP/1.0 301 Moved Permanently");
				header("Location: /");
				exit();
			}
		}

		$sPageQuery = "
						SELECT  
							*  
						FROM  
							cms_pages  
						WHERE  
							id = :intErrorPageId
						LIMIT 1";
		$strTransfer = array();
		$strTransfer['intErrorPageId'] = $intErrorPageId;

		$arrPageData = DB::getPreparedQueryData($sPageQuery, $strTransfer);	
		$rows_template = count($arrPageData);
		$my_template = $arrPageData[0];

		// set new php_self
		$_SERVER['PHP_SELF'] = idtopath($my_template['id'], $page_data['language']);
		$PHP_SELF = $_SERVER['PHP_SELF'];

	} else {
		header("HTTP/1.0 200 OK");
	}

	$page_data['cms'] 				= "on";
	$page_data['id'] 				= $my_template['id'];
	$page_data['site_id'] 			= $my_template['site_id'];
	$page_data['title'] 			= $my_template['title'];
	$page_data['description'] 		= $my_template['description'];
	$page_data['message']			= $my_template['message'];
	$page_data['element'] 			= $my_template['element'];
	$page_data['author'] 			= $my_template['author'];
	$page_data['stats'] 			= $my_template['stats'];
	$page_data['file'] 				= $my_template['file'];
	$page_data['path'] 				= $my_template['path'];
	$page_data['original_language']	= $my_template['language'];
	$page_data['header'] 			= $my_template['header'];
	$page_data['active'] 			= $my_template['active'];
	$page_data['emailcoding'] 		= $my_template['emailcoding'];
	$page_data['layout'] 			= $my_template['layout'];
	$page_data['template'] 			= $my_template['template'];
	$page_data['parameter'] 		= $my_template['parameter'];
	$page_data['editable'] 			= $my_template['editable'];
	$page_data['indexpage'] 		= $my_template['indexpage'];
	$page_data['internal_link'] 	= $my_template['internal_link'];
	$page_data['access'] 			= \Util::decodeSerializeOrJson($my_template['access']);
	$page_data['htmltitle'] 		= $system_data['title_template'];

	$session_data['page_data'][$page_data['id']] = &$page_data;

	if($my_template['language'] == "" && $my_template['localization'] == 1) {
		$page_data['localization'] = 1;
	} else {
		$page_data['localization'] = 0;
	}

} else {
	
	if($system_data['adminpage']) {
		$page_data['title'] = $language_data['administration'];
		$page_data['htmltitle'] = $system_data['project_name']." .::. Administration";
		
		// hook
		\System::wd()->executeHook('system_htmltitle', $page_data);
	}

}

$page_data['uptodate'] = true;

/*
 * ERMITTELT DIE SESSIONDATEN
 */

if(isset($_VARS['sw'])) {
	$_SESSION['system']['sw'] = $_VARS['sw'];
}
if(isset($_VARS['sh'])) {
	$_SESSION['system']['sh'] = $_VARS['sh'];
}

$session_data['mode'] 		= $_GET['mode'];
$session_data['session'] 	= $user_data['session'];
$session_data['queries']	= 0;
$session_data['ob']			= 0;

$sDomain = 'http';
if ($_SERVER['HTTPS'] == 'on') {
	$sDomain .= 's';
	$system_data['domain'] = str_replace("http://","https://",$system_data['domain']);
}

$sDomain .= '://'.$_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] == 'localhost') { // for debugging and demo purposes
	$sDomain = "http://localhost/";
	$system_data['domain'] = "http://localhost/";
	$system_data['host'] = "localhost";
}
$session_data['domain']		= $sDomain;

if ($_VARS['errorcode']) { 
	$session_data['error'] = 1;
}

$system_data['mail_from'] = "From: ".$system_data['project_name']." <".$system_data['admin_email'].">\nX-Sender-IP: ".$_SERVER['REMOTE_ADDR']."\n";

/*
 * set the webDynamics exception handler, defined in functions.inc.php
 */
//set_exception_handler('exceptionHandler');
