<?php

namespace TsTuition\Enums;

use Tc\Service\LanguageAbstract;

enum LessonsUnit: string
{
	case PER_WEEK = 'per_week';
	case ABSOLUTE = 'absolute';

	public function isPerWeek(): bool
	{
		return $this === self::PER_WEEK;
	}

	public function isAbsolute(): bool
	{
		return $this === self::ABSOLUTE;
	}

	public function getLabelText(LanguageAbstract $l10n): string
	{
		return match ($this) {
			self::PER_WEEK => $l10n->translate('Pro Woche'),
			self::ABSOLUTE => $l10n->translate('Absolut'),
		};
	}

}
