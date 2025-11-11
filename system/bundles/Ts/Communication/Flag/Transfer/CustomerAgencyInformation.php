<?php

namespace Ts\Communication\Flag\Transfer;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class CustomerAgencyInformation implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Transfer bestätigen - Kunde/Agentur');
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
		$journeyTransfers = $message->searchRelations(\Ext_TS_Inquiry_Journey_Transfer::class);

		$contacts = $message->searchRelations([\Ext_Thebing_Agency_Contact::class, \Ext_TS_Inquiry_Contact_Abstract::class]);

		foreach ($journeyTransfers as $journeyTransfer) {
			foreach ($contacts as $contact) {

				$inquiry = $journeyTransfer->getJourney()->getInquiry();

				if(
					$contact instanceof \Ext_Thebing_Agency_Contact && // Agenturen
					$inquiry->agency_id > 0 &&
					$inquiry->agency_id == $contact->company_id
				) {
					// Agentur
					$journeyTransfer->customer_agency_confirmed = time();
					$journeyTransfer->save();
				}

				if($contact instanceof \Ext_TS_Inquiry_Contact_Abstract) {
					$inquiryTemp = $contact->getInquiryById($inquiry->id);
					if($inquiryTemp){
						// Kunde
						$journeyTransfer->customer_agency_confirmed = time();
						$journeyTransfer->save();
					}
				}
			}
		}
	}

}