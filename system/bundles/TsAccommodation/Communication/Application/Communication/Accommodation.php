<?php

namespace TsAccommodation\Communication\Application\Communication;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use TsAccommodation\Communication\Traits\Application\WithAccommodationPayload;

class Accommodation implements Application
{
	use WithAccommodationPayload,
		WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » Unterkunft informieren');
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

			if (!$model instanceof \Ext_Thebing_Accommodation_Allocation) {
				continue;
			}

			$accommodation = $model->getAccommodationProvider();
			$collection->push($this->buildAccommodationRecipient($l10n, $model, $accommodation));
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			\TsAccommodation\Communication\Flag\ConfirmProvider::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$schools = [];
		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry_Journey_Accommodation|\Ext_Thebing_Accommodation_Allocation $model */

			if (!$model instanceof \Ext_Thebing_Accommodation_Allocation) {
				continue;
			}

			$inquiry = $model->getInquiryAccommodation()->getJourney()->getInquiry();

			$collection = $collection
				->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $inquiry, $channel))
				->merge($this->withAccommodationUploads($l10n, $model, $model->getAccommodationProvider(), $language));

			$school = $inquiry->getSchool();

			$schools[$school->id] = $school;
		}

		$collection = $collection
			->merge($this->withSchoolUploads(4, collect($schools), $language));

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		$relations = [];
		foreach ($models as $model) {
			if ($model instanceof \Ext_Thebing_Accommodation_Allocation) {
				$inquiry = $model->getInquiryAccommodation()->getJourney()->getInquiry();
			} else {
				$inquiry = $model->getJourney()->getInquiry();
			}

			$relations[] = $inquiry;
		}

		return collect($relations);
	}

	public function getPlaceholderObject(\Ext_TS_Inquiry_Journey_Accommodation|\Ext_Thebing_Accommodation_Allocation $model, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($model instanceof \Ext_TS_Inquiry_Journey_Accommodation) {
			$allocation = new \Ext_Thebing_Accommodation_Allocation();
			$allocation->setJoinedObject('journey_accommodation', $model);
		} else {
			$allocation = $model;
		}

		if ($template && (bool)$template->legacy) {
			$inquiry = $allocation->getInquiryAccommodation()->getJourney()->getInquiry();
			$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $allocation->getPlaceholderObject();
	}
}