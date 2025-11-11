<?php

namespace Ts\Communication\Flag\Insurance;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class CustomerConfirmed implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Versicherung bestätigt (Kunde/Agentur)');
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
		$journeyInsurances = $message->searchRelations(\Ext_TS_Inquiry_Journey_Insurance::class);

		foreach ($journeyInsurances as $journeyInsurance) {
			$journeyInsurance->info_customer = time();
			$journeyInsurance->changes_info_customer = 0;
			$journeyInsurance->save();
		}
	}
}