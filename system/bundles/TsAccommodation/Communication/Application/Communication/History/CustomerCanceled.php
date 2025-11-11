<?php

namespace TsAccommodation\Communication\Application\Communication\History;

use Tc\Service\LanguageAbstract;
use TsAccommodation\Communication\Flag;

class CustomerCanceled extends CustomerConfirmed
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » History Kunde absagen');
	}

	public static function getFlags(): array
	{
		return [
			Flag\CancelCustomer::class,
		];
	}
}