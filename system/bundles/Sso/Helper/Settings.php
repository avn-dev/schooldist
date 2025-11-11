<?php

namespace Sso\Helper;

use Sso\Service\SsoApp;
use Core\Helper\Routing;

class Settings {
	
	public function get() {

		$settingsInfo = array (
			'strict' => true,
			'debug' => false,
			'sp' => array (
				'entityId' => Routing::generateUrl('Sso.metadata'),
				'assertionConsumerService' => array (
					'url' => Routing::generateUrl('Sso.acs'),
				),
				'singleLogoutService' => array (
					'url' => Routing::generateUrl('Sso.logout'),
				),
				'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',

				"attributeConsumingService"=> array(
					"serviceName" => "Fidelo SSO",
					"requestedAttributes" => array(
						array(
							"name" => "email",
							"isRequired" => true
						),
						array(
							"name" => "firstname",
							"isRequired" => true
						),
						array(
							"name" => "lastname",
							"isRequired" => true
						)
					)
				)

			),
			'idp' => array (
				'entityId' => \System::d(SsoApp::KEY_ENTITY_ID),
				'singleSignOnService' => array (
					'url' => \System::d(SsoApp::KEY_SINGLE_SSOS),
				),
				'singleLogoutService' => array (
					'url' => \System::d(SsoApp::KEY_SLS),
				),
				'x509cert' => \System::d(SsoApp::KEY_CERT),
			),
		);

		return $settingsInfo;
	}
	
}
