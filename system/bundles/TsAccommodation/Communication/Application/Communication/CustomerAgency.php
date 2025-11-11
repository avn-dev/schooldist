<?php

namespace TsAccommodation\Communication\Application\Communication;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use TsAccommodation\Communication\Flag;

class CustomerAgency implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » Kunden informieren');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'agency'];
	}

	public function getChannels(LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'app' => [],
			'sms' => [],
			'notice' => []
		];
	}

	public function getRecipients(LanguageAbstract $l10n, Collection $models, string $channel): AddressContactsCollection
	{
		$collection = new AddressContactsCollection();

		foreach ($models as $model) {
			$inquiry = $this->getInquiry($model);

			if ($inquiry) {
				$collection = $collection
					->merge($this->withInquiryTravellers($l10n, $model, $inquiry, $channel))
					->merge($this->withInquiryOtherContacts($l10n, $model, $inquiry, $channel))
					->merge($this->withInquiryAgencyContacts($l10n, $model, $inquiry, $channel, ['accommodation']));
			}
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\RequestArrival::class,
			Flag\ConfirmCustomer::class,
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			$inquiry = $this->getInquiry($model);

			if ($inquiry) {
				$collection = $collection
					->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $inquiry, $channel));
			}
		}

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		$relations = [];
		foreach ($models as $model) {
			$inquiry = $this->getInquiry($model);

			if ($inquiry) {
				$relations[] = $inquiry;
			}
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
			$inquiry = $this->getInquiry($allocation);

			if ($inquiry) {
				$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
				$placeholder->sTemplateLanguage = $language;
				$placeholder->bInitialReplace = !$finalOutput;
				return $placeholder;
			}
		}

		return $allocation->getPlaceholderObject();
	}

	private function getInquiry(\Ext_TS_Inquiry_Journey_Accommodation|\Ext_Thebing_Accommodation_Allocation $model): ?\Ext_TS_Inquiry
	{
		if ($model instanceof \Ext_Thebing_Accommodation_Allocation) {
			// TODO Warum auch immer da false kommt
			$journeyAccommodation = $model->getInquiryAccommodation();

			if ($journeyAccommodation === false) {
				return null;
			}

			$inquiry = $journeyAccommodation->getJourney()->getInquiry();
		} else {
			$inquiry = $model->getJourney()->getInquiry();
		}

		return $inquiry;
	}

}