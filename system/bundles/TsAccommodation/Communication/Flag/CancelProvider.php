<?php

namespace TsAccommodation\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class CancelProvider implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Unterkunft abgesagt - Unterkunft');
	}

	public static function getRecipientKeys(): array
	{
		return ['accommodation_provider'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$allocations = $message->searchRelations(\Ext_Thebing_Accommodation_Allocation::class);

		foreach ($allocations as $allocation) {
			$allocation->accommodation_canceled = time();
			$allocation->save();

			$payments = $allocation->checkPaymentStatus();

			if(empty($payments)){
				$allocation->deleteMatching();
			}
		}
	}
}