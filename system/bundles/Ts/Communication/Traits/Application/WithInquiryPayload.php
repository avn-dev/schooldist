<?php

namespace Ts\Communication\Traits\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Flag;
use TsCompany\Communication\Traits\Application\WithAgencyPayload;

/*
 * TODO Optimieren: es ist nicht so gut pro Dokumenttyp einen eigenen Query auszuführen
 */
trait WithInquiryPayload
{
	use WithSchoolPayload,
		WithAgencyPayload;

	/**
	 * Kontakte
	 */

	protected function withAllInquiryContacts(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel, array $sections = []): AddressContactsCollection {
		return (new AddressContactsCollection())
			->merge($this->withInquiryTravellers($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryGroupContacts($l10n, $source, $inquiry, $channel))
			->merge($this->withInquirySchool($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryOtherContacts($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryAgencyContacts($l10n, $source, $inquiry, $channel, $sections))
			->merge($this->withInquirySponsorsContacts($l10n, $source, $inquiry, $channel))
			->merge($this->withInquirySalesPerson($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryTeachers($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryAccommodationProviders($l10n, $source, $inquiry, $channel))
		;
	}

	protected function withInquiryTravellers(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection {
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms', 'app'])) {
			$travellers = $inquiry->getTravellers();
			foreach ($travellers as $traveller) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('traveller.'.$traveller->id, $traveller))
						->groups($l10n->translate('Schüler'))
						->recipients('customer')
						->source($source)
				);
			}
		}

		return $collection;
	}

	protected function withInquiryGroupContacts(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection {
		$collection = new AddressContactsCollection();

		if ($inquiry->hasGroup() && in_array($channel, ['mail', 'sms'])) {
			$group = $inquiry->getGroup();

			$contactPerson = $group->getContactPerson();
			$inquiries = $group->getInquiries();

			$collection->add(
				(new \Communication\Services\AddressBook\AddressBookContact('group_contact', $contactPerson))
					->groups($l10n->translate('Gruppenansprechpartner'))
					->recipients('customer')
					->source($source)
			);

			foreach ($inquiries as $groupInquiry) {
				if ($inquiry->id == $groupInquiry->id) {
					continue;
				}

				$travellers = $groupInquiry->getTravellers();

				foreach ($travellers as $traveller) {
					$collection->add(
						(new \Communication\Services\AddressBook\AddressBookContact('group_member.'.$traveller->id, $traveller))
							->groups($l10n->translate('Gruppenmitglieder'))
							->recipients('customer')
							->source($source)
					);
				}
			}
		}

		return $collection;
	}

	protected function withInquirySchool(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail'])) {
			$collection->add(
				(new \Communication\Services\AddressBook\AddressBookContact('school', $inquiry->getSchool()))
					->groups($l10n->translate('Schule'))
					->recipients('school')
					->source($source)
			);
		}

		return $collection;
	}

	protected function withInquiryOtherContacts(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms'])) {
			$booker = $inquiry->getBooker();
			$emergencyContact = $inquiry->getEmergencyContact();
			$otherContacts = $inquiry->getOtherContacts();

			if ($booker && $booker->exist()) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('booker', $booker))
						->groups($l10n->translate('Rechnungskontakt'))
						->recipients('customer')
						->source($source)
				);
			}

			if ($emergencyContact->exist()) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('emergency', $emergencyContact))
						->groups($l10n->translate('Notfallkontakt'))
						->recipients('customer')
						->source($source)
				);
			}

			foreach ($otherContacts as $contact) {
				$group = match ($contact->type) {
					'parent' => $l10n->translate('Eltern'),
					default => $l10n->translate('Sonstige'),
				};

				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('other.'.$contact->id, $contact))
						->groups($group)
						->recipients('customer')
						->source($source)
				);
			}
		}

		return $collection;
	}

	protected function withInquiryAgencyContacts(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel, array $sections = []): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms']) && $inquiry->hasAgency()) {
			if ($inquiry->agency_contact_id > 0) {
				// Alle anderen Agenturmitarbeiter außer den gewählten bei der Buchung unter "Sonstige" anzeigen
				$otherAgencyContacts = $this->withAgencyContacts($l10n, $inquiry->getAgency(), $channel, [], null)
					->filter(fn (AddressBookContact $contact) => $contact->getContact()->id != $inquiry->agency_contact_id);

				$collection = $collection
					->add($this->buildAgencyContactRecipient($l10n, $inquiry->getAgencyContact(), $source))
					->merge($otherAgencyContacts);
			} else {
				$masterOrSectionContacts = $this->withAgencyContacts($l10n, $inquiry->getAgency(), $channel, $sections, $source);
				$masterOrSectionContactIds = $masterOrSectionContacts->map(fn ($contact) => $contact->getContact()->id);

				$otherAgencyContacts = $this->withAgencyContacts($l10n, $inquiry->getAgency(), $channel, [], null)
					->filter(fn (AddressBookContact $contact) => !$masterOrSectionContactIds->contains($contact->getContact()->id));

				$collection = $collection
					->merge($masterOrSectionContacts)
					->merge($otherAgencyContacts);
			}
		}

		return $collection;
	}

	protected function withInquirySponsorsContacts(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms']) && $inquiry->isSponsored()) {

			$contacts = $inquiry->getSponsorContactsWithValidEmails();

			foreach ($contacts as $contact) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('sponsor.'.$contact->id, $contact))
						->groups($l10n->translate('Sponsor'))
						->recipients('sponsors')
						->source($source)
				);
			}
		}

		return $collection;
	}

	protected function withInquirySalesPerson(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (
			in_array($channel, ['mail']) &&
			!empty($salesperson = $inquiry->getSalesPerson()) &&
			$salesperson->isActive()
		) {
			$collection->add(
				(new \Communication\Services\AddressBook\AddressBookContact('salesperson', $salesperson))
					->groups($l10n->translate('Vertriebsmitarbeiter'))
					->source($source)
			);
		}

		return $collection;
	}

	protected function withInquiryTeachers(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail', 'sms'])) {
			$teachers = $inquiry->getTuitionTeachers(bAsObjects: true);
			foreach ($teachers as $teacher) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('teacher.'.$teacher->id, $teacher))
						->groups($l10n->translate('Lehrer'))
						->recipients('teacher')
						->source($source)
				);
			}
		}

		return $collection;
	}

	protected function withInquiryAccommodationProviders(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		if (in_array($channel, ['mail'])) {
			$accommodations = $inquiry->getAccommodationProvider();
			foreach ($accommodations as $accommodation) {
				$collection->push(
					(new \Communication\Services\AddressBook\AddressBookContact('accommodation.'.$accommodation->id, $accommodation))
						->groups($l10n->translate('Unterkunftsanbieter'))
						->recipients('accommodation')
						->source($source)
				);

				if ($channel === 'mail') {
					$members = $accommodation->getMembersWithEmail();
					foreach ($members as $member) {
						$collection->push(
							(new \Communication\Services\AddressBook\AddressBookContact('accommodation.member.'.$member->id, $member))
								->groups($l10n->translate('Unterkunftsmitarbeiter'))
								->recipients('accommodation')
								->source($source)
						);
					}
				}
			}
		}

		return $collection;
	}

	/**
	 * Markierungen
	 */

	protected static function withAllInquiryFlags(): array
	{
		return [
			...static::withInquiryPaymentsFlag(),
			...static::withInquiryFeedbackFlags(),
			...static::withInquiryPlacementtestFlags(),
		];
	}

	protected static function withInquiryPaymentsFlag(): array
	{
		return [
			Flag\PaymentReminder::class
		];
	}

	protected static function withInquiryFeedbackFlags(): array
	{
		return [
			Flag\FeedbackInvited::class,
		];
	}

	protected static function withInquiryPlacementtestFlags(): array
	{
		$flags = [
			Flag\PlacementTestInvited::class
		];

		if (\TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\HalloAiApp::APP_NAME)) {
			$flags[] = Flag\PlacementTestInvitedHalloAi::class;
		}

		return $flags;
	}

	/**
	 * Anhänge
	 */

	protected function withAllInquiryAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return collect()
			->merge($this->withInquiryInvoicesAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryCustomerReceiptsAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryAgencyReceiptsAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryCustomerPaymentOverviewAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryAgencyPaymentOverviewAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryExaminationAttachments($l10n, $source, $inquiry, $channel))
			->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $source, $inquiry, $channel));
	}

	protected function withInquiryInvoicesAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['invoice_with_creditnote'], 'fas fa-file-invoice', $l10n->translate('Rechnungen'));
	}

	protected function withInquiryCustomerReceiptsAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['receipt_customer'], 'fas fa-receipt', $l10n->translate('Quittungen (Kunde)'));
	}

	protected function withInquiryAgencyReceiptsAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['receipt_agency'], 'fas fa-receipt', $l10n->translate('Quittungen (Agentur)'));
	}

	protected function withInquiryCustomerPaymentOverviewAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['document_payment_customer', 'document_payment_overview_customer'], groups: $l10n->translate('Kundenzahlungen je Rechnung'));
	}

	protected function withInquiryAgencyPaymentOverviewAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['document_payment_agency', 'document_payment_overview_agency'], groups: $l10n->translate('Agenturzahlungen je Rechnung'));
	}

	protected function withInquiryExaminationAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['examination'], groups: $l10n->translate('Prüfungsunterlagen'));
	}

	protected function withInquiryAdditionalDocumentAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, string $channel): Collection
	{
		return $this->buildInquiryAttachments($l10n, $source, $inquiry, ['additional_document']);
	}

	/**
	 * @param array $types
	 * @return void
	 */
	private function buildInquiryAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, array $types, string $icon = null, string|array $groups = []): Collection
	{
		$documents = $inquiry->getDocuments($types, bReturnObjects: true);

		$collection = new Collection();

		foreach ($documents as $document) {
			/* @var \Ext_Thebing_Inquiry_Document $document */
			$version = $document->getLastVersion();

			if (!$version || empty($path = $version->getPath(true)) || !file_exists($path)) {
				continue;
			}

			$attachment = (new Attachment('document.'.$document->id, filePath: $path, fileName: $version->getLabel(), entity: $version))
				->source($source);

			// TODO wird nicht korrekt sein. (siehe Ext_TC_Communication::getSelectInvoiceTypes())
			/*if (str_contains($document->type, 'netto')) {
				$attachment->types('netto');
			} else if (str_contains($document->type, 'brutto')) {
				$attachment->types('brutto');
			} else if (in_array($document->type, ['receipt_customer', 'receipt_agency'])) {
				$attachment->types('receipt');
			}*/

			if (!empty($icon)) {
				$attachment->icon($icon);
			}

			if (!empty($groups)) {
				$attachment->groups($groups);
			}

			$collection->push($attachment);
		}

		return $collection;
	}

	private function withInquiryAccommodationAttachments(LanguageAbstract $l10n, HasCommunication $source, \Ext_TS_Inquiry $inquiry, $language): Collection
	{
		$files = $inquiry->getAvailableFamilieDocuments(language: $language, asObjects: true);
		$images = $inquiry->getFamiliePicturePdf();

		$collection = new Collection();

		foreach ($files as $upload) {
			$attachment = (new Attachment('accommodation.upload.'.$upload->id, filePath: $upload->getPath(), fileName: $upload->title, entity: $upload))
				->source($source);

			$collection->push($attachment);
		}

		foreach ($images as $path => $name) {
			$fullpath = storage_path(Str::after($path, 'storage/'));

			$attachment = (new Attachment('accommodation.images'.md5($path), filePath: $fullpath, fileName: $name))
				->source($source);

			$collection->push($attachment);
		}

		return $collection;
	}

	/**
	 * Validierung
	 */

	/**
	 * Prüft ob Nettodokumente an Empfänger geht die eigentlich keine Nettodokumente empfangen sollten
	 * TODO übernommen aus \Ext_Thebing_Communication
	 *
	 * @param LanguageAbstract $l10n
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param \Ext_TC_Communication_Message $log
	 * @param bool $finalOutput
	 * @return array
	 */
	public function validateInquiryNettoDocuments(LanguageAbstract $l10n, \Ext_TS_Inquiry $inquiry, \Ext_TC_Communication_Message $log, bool $finalOutput, array $confirmedErrors): array
	{
		if (in_array('send_net_to_gross_receivers', $confirmedErrors)) {
			return [];
		}

		$attachments = $log->getJoinedObjectChilds('files', true);
		$hasNetDocuments = false;

		foreach ($attachments as $attachment) {
			/* @var \Ext_TC_Communication_Message_File $attachment */

			/* @var \Ext_Thebing_Inquiry_Document $document */
			$document = $attachment->searchRelations(\Ext_Thebing_Inquiry_Document_Version::class)->first()?->getDocument();

			if (
				$document &&
				(
					// TODO übernommen aus \Ext_Thebing_Communication
					$document->isNetto() ||
					str_contains($document->type, 'creditnote') ||
					in_array($document->type, ['document_payment_agency', 'document_payment_overview_agency'])
				)
			) {
				$hasNetDocuments = true;
				break;
			}
		}

		if (!$hasNetDocuments) {
			return [];
		}

		$netDocumentSetting = $inquiry->getSchool()->net_email_warning;

		if ($netDocumentSetting === 'main_reception_field') {
			$recipients = $log->getAddresses('to');
		} else {
			$recipients = [
				...$log->getAddresses('to'),
				...$log->getAddresses('cc'),
				...$log->getAddresses('bcc'),
			];
		}

		// Manuell eingetragen Empfänger, ausgewählte Empfänger
		[$unknown, $withRelations] = collect($recipients)
			->map(fn (\Ext_TC_Communication_Message_Address $address) => $address->relations)
			->partition(fn (array $relations) => empty($relations));

		$hasGrossReceiver = false;

		if ($unknown->isNotEmpty()) {
			// Sobald ein Empfänger manuell eingetragen wurde kann man davon ausgehen dass dieser eigentlich keine
			// Nettodokumente erhalten sollte
			$hasGrossReceiver = true;
		} else {

			if ($netDocumentSetting === 'recipient_is_sender') {

				if ($withRelations->count()	> 1) {
					// Mehr als ein Empfänger kann nur bedeuten dass die Nachricht nicht nur an den Sender geht
					$hasGrossReceiver = true;
				} else {
					$from = $log->getAddresses('from')[0]->searchRelations(\User::class)->first();

					$fromRelation = $withRelations->flatten(1)
						->first(fn ($relation) => $relation['relation'] === $from::class && $relation['relation_id'] == $from->id);

					if (!$fromRelation) {
						$hasGrossReceiver = true;
					}
				}

			} else {

				$nonAgencyRelation = $withRelations->flatten(1)
					->first(fn ($relation) => !in_array($relation['relation'], [\Ext_Thebing_Agency::class, \Ext_Thebing_Agency_Contact::class]));

				if ($nonAgencyRelation) {
					// Schüler sollten keine Nettodokumente erhalten
					$hasGrossReceiver = true;
				}

			}

		}

		if ($hasGrossReceiver) {
			return [
				[
					'type' => 'warning',
					'message' => $l10n->translate('Die E-Mail beinhaltet Netto Dokumente, der Adressat entspricht aber nicht der Agentur. Möchten Sie diese wirklich versenden?'),
					'confirm' => true,
					'confirm-message' => $l10n->translate('Ja, ich bin mir sicher!'),
					'confirm-key' => 'send_net_to_gross_receivers'
				]
			];
		}

		return [];
	}

}