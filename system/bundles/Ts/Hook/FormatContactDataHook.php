<?php

namespace Ts\Hook;

class FormatContactDataHook extends \Core\Service\Hook\AbstractHook
{

	public function run($oEntity)
	{

		if (
			$oEntity instanceof \Ext_Thebing_Accommodation &&
			\System::d('lastname_capital_letters_accommodation')
		) {

			foreach ($oEntity->getMembers() as $member) {
				$member->lastname = mb_strtoupper($member->lastname);
			}

			$oEntity->ext_104 = mb_strtoupper($oEntity->ext_104);
		} elseif (
				$oEntity instanceof \TsAccommodation\Entity\Member &&
				\System::d('lastname_capital_letters_accommodation')
		) {
			$oEntity->lastname = mb_strtoupper($oEntity->lastname);
		} elseif(
			$oEntity instanceof \Ext_TS_Inquiry &&
			(
				\System::d('lastname_capital_letters_inquiry') ||
				\System::d('format_additional_contactdata')
			)
		) {

			$contacts = [];
			$contacts[] = $oEntity->getFirstTraveller();

			$booker = $oEntity->getBooker();
			if ($booker !== null) {
				$contacts[] = $booker;
			}

			foreach ($oEntity->getJoinedObjectChilds('other_contacts') as $otherContact) {
				$contacts[] = $otherContact;
			}

			$contactDataToChange = [
				'company',
				'address',
				'address_addon',
				'city',
				'state'
			];

			foreach ($contacts as $contact) {
				// Der Hook wird nach dem parent::save() ausgeführt, also hier immer nochmal updateField()
				// -> unten einmal $oEntity->save() geht nicht, da dann der Hook wieder aufgerufen wird (unendliche Rekursion)
				if (\System::d('lastname_capital_letters_inquiry')) {
					$contact->lastname = mb_strtoupper($contact->lastname);
					$contact->updateField('lastname');
				}

				if (\System::d('format_additional_contactdata')) {
					if (!empty($contact->firstname)) {
						$contact->firstname = $this->formatFirstWordCapitalOthersLowercase($contact->firstname);
						$contact->updateField('firstname');
					}

					if (
						!$contact instanceof \Ext_TS_Inquiry_Contact_Booker &&
						!$contact instanceof \Ext_TS_Inquiry_Contact_Traveller
					) {
						// Nur bei den beiden Kontakten gibt es die folgenden Adressdaten, sonst nicht.
						// -> Hier also abbrechen, es gibt nichts mehr zu formatieren.
						// Eigentlich continue; aber weil die "otherContacts" am Ende des Arrays sind, funktioniert hier
						// gleichzeitig auch return und macht es schneller.
						return;
					}

					// Default-Parameter ist 'contact', hier brauch ich einmal aber auch 'billing' für den Booker.
					// -> Der Default-Zweig in der Methode geht immer also gebe ich irgend ein Parameter außer die beiden an
					// um da rein zu kommen.
					$address = $contact->getAddress('all');

					foreach ($contactDataToChange as $contactData) {
						if (!empty($address->$contactData)) {
							$address->$contactData = $this->formatFirstWordCapitalOthersLowercase($address->$contactData);
							$address->updateField($contactData);
						}
					}
				}

			}
		}

	}

	public function formatFirstWordCapitalOthersLowercase(string $sentence) {
		return ucwords(strtolower($sentence));
	}

}
