<?php
// copyright by plan-i GmbH
// 06.2001 by Mark Koopmann (mk@plan-i.de)

use Core\Handler\CookieHandler;

// array gleich null setzen
$user_data 	= array();
$user_data['cms'] = false;
$functions 	= 		"";
$enter 		= 		"";
$access 	= 		"";
$status 	= 		"";

\System::wd()->executeHook('login_start', $_VARS);

// set session id

if($_GET['spy']) {
	$CMSSESSID = false;
}

if(
	!CookieHandler::is('CMSSESSID') || 
	strlen(CookieHandler::get('CMSSESSID')) < 30 || 
	$_VARS['killcmssessid'] == 1
) {
	$_SESSIONID = Util::generateRandomString(30);
} else {
	$_SESSIONID = CookieHandler::get('CMSSESSID');
}

CookieHandler::set("CMSSESSID", $_SESSIONID);
$user_data['session'] = $_SESSIONID;
$session_data['id'] = 	$_SESSIONID;
// end set session id

// wenn referer gleiche domain und nicht cookie, dann wird cookie nicht akzeptiert, sonst wird cookie als akzeptiert angesehen
$mixRefererIntern = strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']);
__out(Util::getBacktrace(),1);
if(
	$mixRefererIntern === true && 
	!CookieHandler::is('cookiecheck')
) {
	$user_data['cookie'] = false;
} else {
	$user_data['cookie'] = true;
}

$session_data['cookie'] = $user_data['cookie'];

CookieHandler::set("cookiecheck", "yes");

$oDb = DB::getDefaultConnection();

$oAccessBackend = new Access_Backend($oDb);

// Wenn &uuml;ffentliche CMS Seite
if($session_data['public'] == 1) {

	$oAccessFrontend = new Access_Frontend($oDb);	

	/**
	 * Direct login with access code
	 */
	$oAccessFrontend->checkDirectLogin($_VARS['t'], $_VARS['ac']);
	
	/**
	 * Manueller Login 
	 */
	$oAccessFrontend->checkManualLogin($_VARS);

	// Spy Übergabe
	if($_GET[$system_data['spy_name']]) {
		$_SESSION['spy'] = $_GET[$system_data['spy_name']];
	}
	$session_data['spy'] = $_SESSION['spy'];

}

$bAccessSuccess = false;

// check cookie
if (
	CookieHandler::get('passcookie') != "" && 
	CookieHandler::get('usercookie') != "" && 
	$_VARS['logout'] != "ok" && 
	$_VARS['login'] != "ok" && 
	$_VARS['loginmodul'] != 1
) {

	$bAccess = $oAccessBackend->checkSession(CookieHandler::get('usercookie'), CookieHandler::get('passcookie'));
	if($bAccess === true) {
		$bAccessSuccess = true;
	} elseif(
		$oAccessFrontend instanceof Access_Frontend
	) {
		$bAccess = $oAccessFrontend->checkSession(CookieHandler::get('usercookie'), CookieHandler::get('passcookie'));
		if($bAccess === true) {
			$bAccessSuccess = true;
		}
	}

}

// Einlogvorgang durchgeführt?
$bLogin = false;

// if action loginmodul $_SESSION['db_table']
if(
	$oAccessFrontend instanceof Access_Frontend &&
	$oAccessFrontend->checkExecuteLogin() === true
) {

	$bAccess = $oAccessFrontend->executeLogin();

	if($bAccess === true) {
		$bAccessSuccess = true;
	}
	
	$sErrorCode = $oAccessFrontend->getLastErrorCode();
	if(!empty($sErrorCode)) {
		$_VARS['loginfailed'] = $sErrorCode;
	}

	$bLogin = true;
	
}
// end action loginmodul

// CMS Login
if (
	$_VARS['login'] == "ok" &&
	$oAccessBackend->checkExecuteLogin() === true
) {

	$bAccess = $oAccessBackend->executeLogin($_VARS);

	if($bAccess === true) {
		$bAccessSuccess = true;
	}

	$sErrorCode = $oAccessBackend->getLastErrorCode();
	if(!empty($sErrorCode)) {
		$_VARS['loginfailed'] = $sErrorCode;
	}
	
	$bLogin = true;
	
}
// end action login

// sicherheit niedrig
// sessionpasswort nicht neu
if(
	$system_data['securitylevel'] == "low" && 
	$bLogin == false &&
	CookieHandler::is('passcookie')
) {
	$Password = CookieHandler::get('passcookie');
	// sicherheit hoch
	// sessionpasswort neu
} else {
	$Password = Util::generateRandomString(32);
}

// access ok - make new session password - set cookies
if($bAccessSuccess) {

	if(
		$oAccessBackend instanceof Access_Backend &&
		$oAccessBackend->checkValidAccess() === true
	) {
		
		$oAccessBackend->saveAccessData();
		
		$user_data = $oAccessBackend->getUserData();
		
	}
	
	if(
		$oAccessFrontend instanceof Access_Frontend &&
		$oAccessFrontend->checkValidAccess() === true
	) {
		
		$oAccessFrontend->saveAccessData();
		
		$user_data = $oAccessFrontend->getUserData();
	
	}

	$user_data['cookie'] = $session_data['cookie'];

	// user ist eingeloggt
	$user_data['login'] = 1;

}

// end set cookies

// if action logout

if ($_VARS['logout'] == "ok") {

	if($user_data['cms']) {
		\Log::enterLog('', 'Benutzer ausgeloggt');
	}

	if(
		$oAccessBackend instanceof Access_Backend &&
		$oAccessBackend->checkValidAccess() === true
	) {
		
		$oAccessBackend->deleteAccessData();
		
	}
	
	if(
		$oAccessFrontend instanceof Access_Frontend &&
		$oAccessFrontend->checkValidAccess() === true
	) {
		
		$oAccessFrontend->deleteAccessData();
		
	}

	$passcookie = "";
	$usercookie = "";

	$user_data = array();
	
	$bAccessSuccess = false;

	alert("Sie sind ausgeloggt!");

}

// end action logout
if($bAccessSuccess) {

	if(
		$oAccessBackend instanceof Access_Backend &&
		$oAccessBackend->checkValidAccess() === true
	) {
		$oAccessBackend->reworkUserData($user_data);
	}

	if(
		$oAccessFrontend instanceof Access_Frontend &&
		$oAccessFrontend->checkValidAccess() === true
	) {
		$oAccessFrontend->reworkUserData($user_data);
	}

}

// Wenn &uuml;ffentliche CMS Seite
if($session_data['public'] == 1) {
	CookieHandler::set("incms", 0);
}

if($user_data['cms']) {
	CookieHandler::set("incms", 1);
}

if(is_array($user_data)) {
	$user_data['session'] = $session_data['id'];
}

/*
* user_data array:
* name - benutzername
* id - id des aktuellen benutzers
* functions - liste aller rechte des benutzers
* editmode - user befindet sich im editmode
* editpage - user darf seite editieren
* publishpage - user darf seite ver&uuml;ffentlichen
* login - eingeloggter benutzer (egal welche rechte)
* enter - eintritt ins cm system
*/
