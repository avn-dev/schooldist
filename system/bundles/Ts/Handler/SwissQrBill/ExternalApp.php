<?php

namespace Ts\Handler\SwissQrBill;

use \Sprain\SwissQrBill as QrBill;

class ExternalApp extends \Ts\Handler\ExternalAppPerSchool
{
	const APP_NAME = 'swiss_qr_bill';

	const L10N_PATH = 'TS » Apps » Swiss QR-Bill';

	const KEY_BESR = 'swiss_qr_bill_besr';
	const KEY_ACCOUNT_HOLDER_NAME = 'swiss_qr_bill_account_holder_name';
	const KEY_ACCOUNT_HOLDER_ADRESS= 'swiss_qr_bill_account_holder_adress';
	const KEY_ACCOUNT_HOLDER_ZIP = 'swiss_qr_bill_account_holder_zip';
	const KEY_ACCOUNT_HOLDER_CITY = 'swiss_qr_bill_account_holder_city';
	const KEY_ACCOUNT_HOLDER_IBAN = 'swiss_qr_bill_account_holder_iban';
	const KEY_NO_FIRST_PAGE = 'swiss_qr_bill_no_first_page';

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return \L10N::t('Swiss QR-Bill', self::L10N_PATH);
	}

	public function getDescription(): string {
		return \L10N::t('Swiss QR-Bill - Beschreibung', self::L10N_PATH);
	}

	public function getIcon(): ?string {
		return 'fas fa-qrcode';
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {

		$config = $oRequest->input('config', []);

		// BESR-ID validieren
		$success = true;
		foreach ($config as $schoolId => $schoolData) {

			$besrId = $schoolData[self::KEY_BESR];

			// Wenn Bank PostFinance ist, dann gibt es keine BESR-ID.
			if ($besrId === 'null') {
				$oRequest->request->set('config', array_replace_recursive($oRequest->input('config'), [
					$schoolId => [self::KEY_BESR => null]
				]));
				$besrId = null;
			}
			try {
				QrBill\Reference\QrPaymentReferenceGenerator::generate(
					$besrId,
					'123'
				);

			} catch (\Throwable) {
				$school = \Ext_Thebing_School::getInstance($schoolId);
				$oSession->getFlashBag()->add('error', \L10N::t('Ungültige BESR-ID'). ' ('.$school->ext_1.')');
				$success = false;
			}
		}

		if ($success) {
			parent::saveSettings($oSession, $oRequest);
		}
	}

	public function getSettings(): array {
		return [
				self::KEY_BESR => [
					'label' => \L10N::t('BESR-ID'),
					'type' => 'input'
				],
				self::KEY_ACCOUNT_HOLDER_NAME => [
					'label' => \L10N::t('Kontoinhaber: Bezeichnung'),
					'type' => 'input'
				],
				self::KEY_ACCOUNT_HOLDER_ADRESS => [
					'label' => \L10N::t('Kontoinhaber: Adresse'),
					'type' => 'input'
				],
				self::KEY_ACCOUNT_HOLDER_ZIP => [
					'label' => \L10N::t('Kontoinhaber: PLZ'),
					'type' => 'input'
				],
				self::KEY_ACCOUNT_HOLDER_CITY => [
					'label' => \L10N::t('Kontoinhaber: Stadt'),
					'type' => 'input'
				],
				self::KEY_ACCOUNT_HOLDER_IBAN => [
					'label' => \L10N::t('Kontoinhaber: IBAN'),
					'type' => 'input'
				],
				self::KEY_NO_FIRST_PAGE => [
					'label' => \L10N::t('QR-Code für Komplettbetrag auslassen'),
					'type' => 'checkbox'
				],
		];
	}
}
