<?php

namespace TsCompany\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use TsCompany\Communication\Flag;
use TsCompany\Entity\JobOpportunity\StudentAllocation;

class JopOpportunityAllocation implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Marketing » Arbeitsangebote');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'company'];
	}

	public function getChannels(LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'sms' => [],
			'notice' => []
		];
	}

	public function getRecipients(LanguageAbstract $l10n, Collection $models, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		foreach ($models as $model) {
			/* @var StudentAllocation $model */
			$inquiry = $model->getInquiry();

			$collection = $collection
				->merge($this->withInquiryTravellers($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryOtherContacts($l10n, $model, $inquiry, $channel));

			$contacts = $model->getCompany()->getContacts();

			foreach ($contacts as $contact) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('company.contact.'.$contact->id, $contact))
						->groups($l10n->translate('Firmenmitarbeiter'))
						->recipients('company')
						->source($model)
				);
			}

		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\JobOpportunityRequested::class,
			Flag\JobOpportunityAllocated::class,
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();
		return $collection;
	}
}