<?php

namespace Ts\Communication\Flag\Transfer;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class AccommodationInformation implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Transfer bestätigen - Unterkunft');
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
		$journeyTransfers = $message->searchRelations(\Ext_TS_Inquiry_Journey_Transfer::class);
		$accommodations = $message->searchRelations(\Ext_Thebing_Accommodation::class);

		foreach ($journeyTransfers as $journeyTransfer) {
			foreach ($accommodations as $accommodation) {
				if(
					(
						$journeyTransfer->end_type == 'accommodation' &&
						$journeyTransfer->end == $accommodation->id
					) || (
						$journeyTransfer->start_type == 'accommodation' &&
						$journeyTransfer->start == $accommodation->id
					)
				){
					$journeyTransfer->accommodation_confirmed = time();
					$journeyTransfer->save();
				} else if (
					(
						$journeyTransfer->end_type == 'accommodation' &&
						$journeyTransfer->end == '0'
					) || (
						$journeyTransfer->start_type == 'accommodation' &&
						$journeyTransfer->start == '0'
					)
				){
					// Es wurde eine Unterkunft gewählt ohne spez. Familie
					// Es kann sich nur um eine Anreise handeln
					$inquiry = $journeyTransfer->getJourney()->getInquiry();
					$firstLastAcc = $inquiry->getFirstLastMatchedAccommodation();
					if(
						is_object($firstLastAcc['first']) &&
						$firstLastAcc['first']->id == $accommodation->id
					){
						$journeyTransfer->accommodation_confirmed = time();
						$journeyTransfer->save();
					}
				}
			}
		}
	}
}