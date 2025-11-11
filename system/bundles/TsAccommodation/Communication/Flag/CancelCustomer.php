<?php

namespace TsAccommodation\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class CancelCustomer implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Kunde/Agentur abgesagt - Unterkunft');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$allocations = $message->searchRelations(\Ext_Thebing_Accommodation_Allocation::class);

		foreach ($allocations as $allocation) {
			$allocation->customer_agency_canceled = time();
			$allocation->save();

			$payments = $allocation->checkPaymentStatus();

			if(empty($payments)){
				$allocation->deleteMatching();
			}
		}
	}
}