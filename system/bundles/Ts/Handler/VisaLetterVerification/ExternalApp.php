<?php

namespace Ts\Handler\VisaLetterVerification;

use Ts\Handler\ExternalAppPerSchool;

class ExternalApp extends ExternalAppPerSchool
{
	const APP_NAME = 'visa_check';

	const KEY_LOGO = 'visacheck_logo';

	const L10N_PATH = 'TS » Apps » Visa Check';

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return \L10N::t('Visa verification letter', self::L10N_PATH);
	}

	public function getDescription(): string {
		return \L10N::t('Visa verification letter - Beschreibung', self::L10N_PATH);
	}

	public function getIcon(): string {
		return 'fas fa-shield-alt';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public function getSettings(): array {
		return [
			self::KEY_LOGO => [
				'label' => 'Logo',
				'type' => 'upload'
			]
		];
	}
}
