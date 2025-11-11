<?php

namespace TsActivities\Enums;

use Tc\Service\LanguageAbstract;

enum AssignmentSource: string
{
	case APP = 'app';
	case SCHEDULER = 'scheduler';
	case REGISTRATION_FORM = 'registration_form';

	public function isApp(): bool
	{
		return $this === self::APP;
	}

	public function isScheduler(): bool
	{
		return $this === self::SCHEDULER;
	}

	public function isRegistrationForm(): bool
	{
		return $this === self::REGISTRATION_FORM;
	}

	public function isFrontend(): bool
	{
		return $this->isApp() || $this->isRegistrationForm();
	}

	public function isBackend(): bool
	{
		return $this->isScheduler();
	}

	public function getLabelText(LanguageAbstract $l10n): string
	{
		return match ($this) {
			self::APP => $l10n->translate('App'),
			self::SCHEDULER => $l10n->translate('Aktivitätsplanung'),
			self::REGISTRATION_FORM => $l10n->translate('Anmeldeformular'),
		};
	}

}