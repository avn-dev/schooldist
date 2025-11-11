<?php

namespace TcFrontend\Controller;

use \Core\Handler\CookieHandler;
use \Core\Handler\SessionHandler;

class ApiController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	public function handleLegacyApi() {

		SessionHandler::getInstance();

		// Session ID und Name des Session Cookies übergeben
		CookieHandler::set('thebing_snippet_session_id', SessionHandler::getInstance()->getId());
		CookieHandler::set('thebing_snippet_session_name', SessionHandler::getInstance()->getName());

		global $_VARS;
		$_VARS = $this->_oRequest->getAll();
 
		try {
			// TODO tc_api in Controller migrieren und $_VARS entfernen
			require(\Util::getDocumentRoot().'system/extensions/tc_api.php');
		} catch(\Throwable $e) {
			echo \L10N::t($e->getMessage());
		}

		die;
	}

}
