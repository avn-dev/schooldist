<?php

namespace Ts\Communication\Application\Transfer;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Flag;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class CustomerAgency implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Transfer » Bestätigen Kunde');
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
			/* @var \Ext_TS_Inquiry_Journey_Transfer $model */
			$inquiry = $model->getJourney()->getInquiry();

			$collection = $collection
				->merge($this->withInquiryTravellers($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryOtherContacts($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryAgencyContacts($l10n, $model, $inquiry, $channel, ['transfer']));
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\Transfer\CustomerAgencyInformation::class,
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

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn (\Ext_TS_Inquiry_Journey_Transfer $model) => $model->getJourney()->getInquiry());
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