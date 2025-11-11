<?php

namespace Ts\Communication\Flag\Insurance;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ProviderConfirmed implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Versicherung bestätigt (Anbieter)');
	}

	public static function getRecipientKeys(): array
	{
		return ['insurance_provider'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$journeyInsurances = $message->searchRelations(\Ext_TS_Inquiry_Journey_Insurance::class);

		foreach ($journeyInsurances as $journeyInsurance) {
			$journeyInsurance->info_provider = time();
			$journeyInsurance->changes_info_provider = 0;
			$journeyInsurance->save();
		}
	}
}