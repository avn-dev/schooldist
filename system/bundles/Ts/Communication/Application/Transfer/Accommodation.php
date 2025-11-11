<?php

namespace Ts\Communication\Application\Transfer;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Flag\Transfer\AccommodationInformation;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Accommodation implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Transfer » Bestätigen Unterkunft');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'accommodation_provider'];
	}

	public function getChannels(LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'notice' => []
		];
	}

	public function getRecipients(LanguageAbstract $l10n, Collection $models, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry_Journey_Transfer $model */
			$transferAccommodations = $model->getMatchingAccommodationProvidersMails();

			foreach ($transferAccommodations as $accommodationArray) {
				/* @var \Ext_Thebing_Accommodation $accommodation */
				$accommodation = \Factory::getInstance($accommodationArray['object'], $accommodationArray['object_id']);
				$collection = $collection->push(
					(new AddressBookContact('accommodation.'.$accommodation->id, $accommodation))
						->groups($l10n->translate('Unterkunftsanbieter'))
						->recipients('accommodation_provider')
						->source($model)
				);
			}
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			AccommodationInformation::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry_Journey_Transfer $model */
			$collection = $collection
				->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $model->getJourney()->getInquiry(), $channel));
		}

		return $collection;
	}

	public function getPlaceholderObject(\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$inquiry = $journeyTransfer->getJourney()->getInquiry();
			$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
			// Einzelkommunikation: Passender selektierter Eintrag
			$placeholder->oJourneyTransfer = $journeyTransfer;
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $journeyTransfer->getPlaceholderObject();
	}
}