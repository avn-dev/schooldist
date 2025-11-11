<?php

namespace TsStudentSso\Handler;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'student_sso';

	const KEY_URL_METADATA = 'ts_student_sso_metadata';
	const KEY_URL_ACS = 'ts_student_sso_acs';
	const KEY_URL_LOGOUT = 'ts_student_sso_logout';
	
	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('SSO identity provider for students');
	}

	public function getDescription() : string {
		return \L10N::t('Provides an single Sign-on identity provider for SAML2.0.');
	}

	public function getIcon() {
		return 'fa fa-sign-in';
	}

	public function getCategory(): string {
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_AUTHENTICATION;
	}

	public function getContent(): ?string {

		$smarty = new \SmartyWrapper();

		$smarty->assign('sAppKey', $this->oAppEntity->app_key);
		$smarty->assign('oApp', $this);

		$saml = new \TsStudentSso\Service\Saml;
		$storagePath = $saml->getCertPath();
		
		// Zertifikat generieren
        $cert = sprintf('%s/%s', $storagePath, 'cert.pem');
        $fingerprint = sprintf('%s/%s', $storagePath, 'fingerprint.pem');

		$smarty->assign('cert', file_get_contents($cert));
		$smarty->assign('fingerprint', file_get_contents($fingerprint));
		
		$fields = $this->getConfigKeys();
		foreach($fields as &$aField) {
			if(
				empty($aField['type']) ||
				$aField['type'] !== 'headline'
			) {
				$aField['value'] = \System::d($aField['key']);
			}
		}
		
		$smarty->assign('aFields', $fields);

		return $smarty->fetch('@TsStudentSso/external_app_settings.tpl');
	}
	
	public function install() {
		
		$saml = new \TsStudentSso\Service\Saml;
		$storagePath = $saml->getCertPath();
		
		// Zertifikat generieren
		$key = sprintf('%s/%s', $storagePath, 'key.pem');
        $cert = sprintf('%s/%s', $storagePath, 'cert.pem');
        $fingerprint = sprintf('%s/%s', $storagePath, 'fingerprint.pem');

        if(
			!file_exists($key) && 
			!file_exists($cert)
		) {
			
			$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			
            $command = 'openssl req -x509 -sha256 -nodes -days %s -newkey rsa:2048 -keyout %s -out %s -subj "/C='.$school->country_id.'/ST=./L=./O='.$school->ext_1.'/OU=./CN=./emailAddress='.$school->email.'"';
            $response = \Update::executeShellCommand(sprintf($command, 7300, $key, $cert));

			$commandFingerprint = "openssl x509 -in %s -noout -fingerprint -sha256 -out %s";
			$response = \Update::executeShellCommand(sprintf($commandFingerprint, $cert, $fingerprint));
			
        }
		
	}

	protected function getConfigKeys(): array {
		
		return [
			[
				'title' => \L10N::t('Metadata URL'),
				'key' => self::KEY_URL_METADATA
			],
			[
				'title' => \L10N::t('ACS URL'),
				'key' => self::KEY_URL_ACS
			],
			[
				'title' => \L10N::t('Single Logout URL'),
				'key' => self::KEY_URL_LOGOUT
			]
		];
		
	}

}
