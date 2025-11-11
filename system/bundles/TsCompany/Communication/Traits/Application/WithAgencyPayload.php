<?php

namespace TsCompany\Communication\Traits\Application;

use Communication\Services\AddressBook\AddressBookContact;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

trait WithAgencyPayload
{
	protected function withAgencyContacts(LanguageAbstract $l10n, \Ext_Thebing_Agency $agency, string $channel, array $sections, HasCommunication $source = null): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms'])) {
			$contacts = $agency->getContacts(bAsObjects: true);

			// Nur die für die Agentur relevanten Bereiche beachten
			$agencySections = array_intersect($sections, array_keys(\Ext_Thebing_Agency_Contact::getFlags($l10n)));

			if (
				!empty($agencySections) &&
				!empty($sectionContacts = array_filter($contacts, fn ($contact) => $contact->isMasterContact() || $contact->isResponsibleFor($agencySections)))
			) {
				$contacts = $sectionContacts;
			}

			foreach ($contacts as $contact) {
				$collection->add(
					$this->buildAgencyContactRecipient($l10n, $contact, $source)
				);
			}
		}

		return $collection;
	}

	protected function buildAgencyContactRecipient(LanguageAbstract $l10n, \Ext_Thebing_Agency_Contact $contact, HasCommunication $source = null): AddressBookContact
	{
		$addressContact = (new AddressBookContact('agency.contact.'.$contact->id, $contact))
			->groups($l10n->translate('Agenturmitarbeiter'))
			->recipients('agency');

		if ($source) {
			$addressContact->source($source);
		}

		return $addressContact;
	}
}