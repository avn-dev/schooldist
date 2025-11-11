<?php

namespace TsAccommodation\Communication\Application\Communication\History;

use Tc\Service\LanguageAbstract;
use TsAccommodation\Communication\Flag;

class AccommodationCanceled extends AccommodationConfirmed
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » History Unterkunft absagen');
	}

	public static function getFlags(): array
	{
		return [
			Flag\CancelProvider::class
		];
	}
}