<?php

namespace Sso\Service;

class SsoApp extends \TcExternalApps\Interfaces\SystemConfigApp {
	
	const APP_NAME = 'sso';
	
	const KEY_ENTITY_ID = 'sso_entityId';
	const KEY_SINGLE_SSOS = 'sso_singleSignOnService';
	const KEY_SLS = 'sso_singleLogoutService';
	const KEY_CERT = 'sso_x509cert';
	const KEY_USERGROUP = 'sso_usergroup';
	
	public function getTitle(): string {
		return \L10N::t('Single Sign-on');
	}
	
	public function getDescription(): string {
		return \L10N::t('Provides the possibilty to use single sign-on via SAML 2. Different identity providers can be used, e.g. Microsoft Azure Active Directory, OneLogin.');
	}

	public function getCategory(): string {
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_AUTHENTICATION;
	}

	public function getIcon() {
		return 'fa fa-sign-in';
	}
	
	protected function getConfigKeys(): array {
		
		return [
			[
				'title' => 'Issuer URL', #entityId
				'key' => self::KEY_ENTITY_ID
			],
			[
				'title' => 'SAML 2.0 Endpoint (HTTP)', #singleSignOnService
				'key' => self::KEY_SINGLE_SSOS
			],
			[
				'title' => 'SLO Endpoint (HTTP)', #singleLogoutService
				'key' => self::KEY_SLS
			],
			[
				'title' => 'X.509 Certificate', #x509cert
				'key' => self::KEY_CERT,
				'type' => 'textarea'
			],
			[
				'title' => $this->t('User group'),
				'key' => self::KEY_USERGROUP,
				'type' => 'select',
				'options' => \DB::getQueryPairs("SELECT id, name FROM system_roles ORDER BY name")
			]
		];
		
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		
		parent::saveSettings($oSession, $oRequest);
		
		$settingsHelper = new \Sso\Helper\Settings;
		$settingsInfo = $settingsHelper->get();

		$settings = new \OneLogin\Saml2\Settings($settingsInfo, true);
		$metadata = $settings->getSPMetadata();
		$errors = $settings->validateMetadata($metadata);
		
		if(!empty($errors)) {
			$oSession->getFlashBag()->add('error', \L10N::t('Data not valid').' ('.implode(', ', $errors).')!');
		}
		
	}
	
}
	
