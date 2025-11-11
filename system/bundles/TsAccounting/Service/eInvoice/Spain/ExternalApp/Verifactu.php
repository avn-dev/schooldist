<?php

namespace TsAccounting\Service\eInvoice\Spain\ExternalApp;

use Random\RandomException;
use TsAccounting\Handler\ExternalApp\AbstractCompanyApp;

class Verifactu extends AbstractCompanyApp
{
	const APP_NAME = 'verifactu';

	const CONFIG_ENCRYPTION_METHOD = 'verifactu_encryption_method';

	const CONFIG_ENCRYPTION_KEY = 'verifactu_encryption_key';

	const CONFIG_CERTIFICATE = 'verifactu_certificate';

	const CONFIG_CERTIFICATE_PASSWORD = 'verifactu_certificate_password';

	public function getTitle(): string
	{
		return \L10N::t('XML Rechnungsübermittlung (Veri*factu)');
	}

	public function getDescription(): string
	{
		return \L10N::t('XML Rechnungsübermittlung (Veri*factu) - Beschreibung
			<br/><br/><a href="https://fidelo.com/DECLARACIÓN RESPONSABLE FIDELO SOFTWARE GmbH.pdf" target="_blank">
				<button type="button">'.\L10N::t('DECLARACIÓN RESPONSABLE').'</button>
			</a>');
	}

	public function getIcon(): string
	{
		return 'fas fa-link';
	}

	public function getCategory(): string
	{
		return \TcExternalApps\Interfaces\ExternalApp::CATEGORY_ACCOUNTING;
	}

	public function canBeInstalled() : bool {
		// Um die Verifactu zu nutzen, muss vorher immutable_invoices aktiviert sein
		return \Ext_Thebing_Client::immutableInvoicesForced();
	}

	public function canBeUninstalled() : bool {
		// Nur erlauben, wenn immutable_invoices nicht aktiv ist
		return !\Ext_Thebing_Client::immutableInvoicesForced();
	}

	protected function getConfigKeys(): array
	{
		return [
			[
				'title' => \L10N::t('Certificate'),
				'key' => self::CONFIG_CERTIFICATE,
				'type' => 'textarea'
			],
			[
				'title' => \L10N::t('Certificate Password'),
				'key' => self::CONFIG_CERTIFICATE_PASSWORD,
				'type' => 'input'
			]
		];
	}

	/**
	 * @param \Core\Handler\SessionHandler $oSession
	 * @param \MVC_Request $oRequest
	 * @return void
	 * @throws RandomException
	 */
	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest): void
	{
		$config = $oRequest->input('config', []);
		$dbConfig = \Ext_TS_Config::getInstance();
		foreach ($this->getConfigKeys() as $configKey) {
			if (
				$configKey['key'] == self::CONFIG_CERTIFICATE ||
				$configKey['key'] == self::CONFIG_CERTIFICATE_PASSWORD
			) {
				$config[$configKey['key']] = self::encryptConfigValue($config[$configKey['key']]);
			}
			$key = $configKey['key'];
			$userInputValue = $config[$key];
			$dbConfig->set($key, $userInputValue);
		}
		parent::saveSettings($oSession, $oRequest);
	}

	/**
	 * @return string
	 */
	public static function getCertificate(): string
	{
		return \System::d(self::CONFIG_CERTIFICATE, '');
	}

	/**
	 * Zertifikat und Passwort verschlüsseln
	 *
	 * @param string $value
	 * @return string
	 * @throws RandomException
	 */
	public static function encryptConfigValue(string $value): string
	{
		$key = hash('sha256', self::getEncriptionKey(), true);
		$iv = random_bytes(openssl_cipher_iv_length(self::getEncriptionMethod()));
		$encrypted = openssl_encrypt($value, self::getEncriptionMethod(), $key, 0, $iv);

		return base64_encode($iv . $encrypted);
	}

	/**
	 * @return string
	 */
	public static function getCertificatePassword(): string
	{
		return \System::d(self::CONFIG_CERTIFICATE_PASSWORD, '');
	}

	/**
	 * @return string
	 */
	public static function getEncriptionMethod(): string
	{
		return \System::d(self::CONFIG_ENCRYPTION_METHOD, '');
	}

	/**
	 * @return string
	 */
	public static function getEncriptionKey(): string
	{
		return \System::d(self::CONFIG_ENCRYPTION_KEY, '');
	}

}