<?php

namespace TsAccommodation\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ConfirmCustomer implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Kunde/Agentur bestätigen - Unterkunft');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		if ($used && !$model instanceof \Ext_Thebing_Accommodation_Allocation) {
			return [
				sprintf($l10n->translate('Es wurde die Markierung "%s" gesetzt, allerdings existiert für den gewählten Eintrag keine Unterkunftszuweisung.'), self::getTitle($l10n))
			];
		}

		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$allocations = $message->searchRelations(\Ext_Thebing_Accommodation_Allocation::class);

		foreach ($allocations as $allocation) {
			$allocation->customer_agency_confirmed = time();
			$allocation->save();
		}
	}
}