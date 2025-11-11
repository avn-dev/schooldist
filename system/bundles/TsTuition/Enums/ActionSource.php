<?php

namespace TsTuition\Enums;

use Tc\Service\LanguageAbstract;

enum ActionSource: string
{
	case TEACHER_PORTAL = 'teacher_portal';
	case SCHEDULER = 'scheduler';

	public function isTeacherPortal(): bool
	{
		return $this === self::TEACHER_PORTAL;
	}

	public function isScheduler(): bool
	{
		return $this === self::SCHEDULER;
	}

	public function getLabelText(LanguageAbstract $l10n): string
	{
		return match ($this) {
			self::TEACHER_PORTAL => $l10n->translate('Lehrerportal'),
			self::SCHEDULER => $l10n->translate('Klassenplanung'),
		};
	}

}