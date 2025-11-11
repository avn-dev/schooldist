<?php

namespace Ts\Communication\Application\Transfer;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Flag;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use Ts\Communication\Traits\Application\WithPickupPayload;

class ProviderRequest implements Application
{
	use WithInquiryPayload,
		WithPickupPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Transfer » Anfragen Provider');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['transfer_provider'];
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
			$transferProvider = \Ext_TS_Inquiry_Journey_Transfer::getAllProviderMails([$model->id]);

			foreach ($transferProvider as $providerArray) {
				/* @var \Ext_Thebing_Pickup_Company $provider*/
				$provider = \Factory::getInstance($providerArray['object'], $providerArray['object_id']);

				$collection = $collection->push(
					(new AddressBookContact('provider.'.$providerArray['object_id'], $provider))
						->groups($l10n->translate('Anbieter'))
						->recipients('transfer_provider')
						->source($model)
				);
			}
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\Transfer\ProviderRequest::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$excelList = $this->withPickupExcelAttachment($l10n, $models, $models->first()->getJourney()->getSchool());

		if ($excelList) {
			$collection->push($excelList);
		}

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