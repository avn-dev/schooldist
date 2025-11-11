<?php

namespace TsAccommodation\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ConfirmedTransfer implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Unterkunft bestätigen - Transfer');
	}

	public static function getRecipientKeys(): array
	{
		return ['transfer_provider'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$allocations = $message->searchRelations(\Ext_Thebing_Accommodation_Allocation::class);

		foreach ($allocations as $allocation) {
			$allocation->accommodation_transfer_confirmed = time();
			$allocation->save();
		}
	}
}