<?php

namespace Ts\Enums;

use Tc\Service\LanguageAbstract;

enum CommissionType: string
{
	case PERCENT = 'percent';
	case AMOUNT = 'amount';

	public function isPercent(): bool
	{
		return $this === self::PERCENT;
	}

	public function isFixAmount(): bool
	{
		return $this === self::AMOUNT;
	}

	public function getLabel(LanguageAbstract $l10n)
	{
		return match ($this) {
			self::PERCENT => $l10n->translate('Prozent'),
			self::AMOUNT => $l10n->translate('Fester Betrag'),
		};
	}
}