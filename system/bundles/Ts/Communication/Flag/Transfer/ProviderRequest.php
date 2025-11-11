<?php

namespace Ts\Communication\Flag\Transfer;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ProviderRequest implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Transfer anfragen - Provider');
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
			/* @var \Ext_TS_Inquiry_Journey_Transfer $journeyTransfer */
			foreach ($providers as $provider) {
				$request = $journeyTransfer->getNewProviderRequest();
				$request->provider_id = $provider->id;

				if ($provider instanceof \Ext_Thebing_Accommodation) {
					$request->provider_type	= 'accommodation';
				} else if ($provider instanceof \Ext_Thebing_Pickup_Company) {
					$request->provider_type	= 'provider';
				}

				if ($request->transfer_id > 0 && $request->provider_id > 0){
					$request->save();
				}
			}
		}
	}
}