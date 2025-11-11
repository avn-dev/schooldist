<?php

namespace TsStudentSso\Helper;

use Core\Helper\Routing;

class Settings {
	
	public function get() {

		$saml = new \TsStudentSso\Service\Saml;
		$storagePath = $saml->getCertPath();
		
		$key = sprintf('%s/%s', $storagePath, 'key.pem');
        $cert = sprintf('%s/%s', $storagePath, 'cert.pem');
		
		$settingsInfo = [
			'debug' => true,
			// The URI to your login page
			'login_uri' => Routing::generateUrl('TsStudentSso.login'),
			// The URI to the saml metadata file, this describes your idP
			'issuer_uri' => Routing::generateUrl('TsStudentSso.metadata'),
			// The certificate
			'cert' => file_get_contents($cert),
			// Name of the certificate PEM file, ignored if cert is used
			'certname' => 'cert.pem',
			// The certificate key
			'key' => file_get_contents($key),
			// Name of the certificate key PEM file, ignored if key is used
			'keyname' => 'key.pem',
			// Encrypt requests and responses
			'encrypt_assertion' => true,
			// Make sure messages are signed
			'messages_signed' => true,
			// List of all Service Providers
			'sp' => [
				// Base64 encoded ACS URL
				base64_encode(\System::d(\TsStudentSso\Handler\ExternalApp::KEY_URL_ACS)) => [
					// ACS URL of the Service Provider
					'destination' => \System::d(\TsStudentSso\Handler\ExternalApp::KEY_URL_ACS),
					// Simple Logout URL of the Service Provider
					'logout' => \System::d(\TsStudentSso\Handler\ExternalApp::KEY_URL_LOGOUT),
					'encrypt_assertion' => false
				],
			],
		];

		return $settingsInfo;
	}
	
}
