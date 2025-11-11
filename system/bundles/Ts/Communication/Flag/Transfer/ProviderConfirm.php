<?php

namespace Ts\Communication\Flag\Transfer;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ProviderConfirm implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Transfer bestätigen - Provider');
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
		$journeyTransfers = $message->searchRelations(\Ext_TS_Inquiry_Journey_Transfer::class);

		$providers = $message->searchRelations([\Ext_Thebing_Accommodation::class, \Ext_Thebing_Pickup_Company::class]);

		foreach ($journeyTransfers as $journeyTransfer) {
			foreach ($providers as $provider) {
				if(
					$provider instanceof \Ext_Thebing_Accommodation &&
					$journeyTransfer->provider_type == 'accommodation' &&
					$journeyTransfer->provider_id == $provider->id
				){
					// Bestätigung an Familie erfolgreich verschickt
					$journeyTransfer->provider_confirmed = time();
					$journeyTransfer->save();
				}elseif(
					$provider instanceof \Ext_Thebing_Pickup_Company &&
					$journeyTransfer->provider_type == 'provider' &&
					$journeyTransfer->provider_id == $provider->id
				){
					$journeyTransfer->provider_confirmed = time();
					$journeyTransfer->save();
				}
			}
		}
	}
}