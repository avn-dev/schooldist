<?php

namespace TsAccommodation\Communication\Application\Communication\History;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use TsAccommodation\Communication\Flag;

class AccommodationConfirmed implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » History Unterkunft bestätigt');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['accommodation_provider'];
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
			/* @var \Ext_Thebing_Accommodation_Allocation $model */
			$accommodation = $model->getAccommodationProvider();

			if ($accommodation) {
				$collection->push(
					(new AddressBookContact('accommodation', $accommodation))
						->groups($l10n->translate('Unterkunftsanbieter'))
						->recipients('accommodation')
						->source($model)
				);
			}
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\ConfirmProvider::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			/* @var \Ext_Thebing_Accommodation_Allocation $model */
			$journeyAccommodation = $model->getInquiryAccommodation();
			if ($journeyAccommodation) {
				$inquiry = $journeyAccommodation->getJourney()->getInquiry();
				$collection = $collection
					->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $inquiry, $channel))
					->merge($this->withSchoolUploads(4, collect([$inquiry->getSchool()]), $language));

			}
		}

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn (\Ext_Thebing_Accommodation_Allocation $model) => $model->getAccommodationJourney()->getJourney()->getInquiry());
	}
}