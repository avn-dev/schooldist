<?php

namespace TsRdstation\Handler;

use Core\Handler\SessionHandler as Session;
use \League\OAuth2\Client\Token\AccessToken;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'rdstation';

//	const KEY_URL = 'moodle_url';
//	
//	const KEY_ACCESS_TOKEN = 'moodle_access_token';
//	const KEY_DEFAULT_PASSWORD = 'moodle_default_password';
//	const KEY_CUSTOM_FIELDS = 'moodle_custom_fields';

	/**
	 * @var Session
	 */
	protected $oSession;

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('RD Station');
	}

	public function getDescription() : string {
		return \L10N::t('Sends contacts and bookings to RD Station');
	}

	public function getIcon(): string {
		return 'fas fa-plug';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::CRM;
	}

	/**
	 * @return string $sHtml
	 */
	public function getContent() : ?string {

		$oSmarty = new \SmartyWrapper();

		if($this->oSession === NULL) {
			$this->oSession = Session::getInstance();
		}

		$oAccessToken = \TsRdstation\Service\RDStation::getAccessToken();
		
		$bConnected = false;
		$aAuthInfo = null;
		if($oAccessToken instanceof AccessToken) {
			$bConnected = true;
			$oService = new \TsRdstation\Service\RDStation($oAccessToken);
			$aAccountInfo = $oService->getAccountInfo();
		}
		
		$oSmarty->assign('oApp', $this);
		$oSmarty->assign('bConnected', $bConnected);
		$oSmarty->assign('aAccountInfo', $aAccountInfo);
		$oSmarty->assign('oSession', $this->oSession);

		$sHtml = $oSmarty->fetch('@TsRdstation/setup.tpl');

		return $sHtml;
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		return;
	}

}