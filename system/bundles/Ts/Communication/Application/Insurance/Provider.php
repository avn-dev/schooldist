<?php

namespace Ts\Communication\Application\Insurance;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Flag;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Provider implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Versicherungen » Anbieter informieren');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['insurance_provider'];
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
			/* @var \Ext_TS_Inquiry_Journey_Insurance $model */
			$provider = $model->getInsuranceProvider();

			$collection->push(
				(new AddressBookContact('insurance.provider.'.$provider->id, $provider))
					->groups($l10n->translate('Versicherungsanbieter'))
					->recipients('insurance_provider')
					->source($model)
			);

		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\Insurance\ProviderConfirmed::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry_Journey_Insurance $model */
			$collection = $collection
				->merge(($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $model->getJourney()->getInquiry(), $channel)));
		}

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn (\Ext_TS_Inquiry_Journey_Insurance $model) => $model->getJourney()->getInquiry());
	}

	public function getPlaceholderObject(\Ext_TS_Inquiry_Journey_Insurance $journeyInsurance, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$inquiry = $journeyInsurance->getJourney()->getInquiry();
			$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
			// Einzelkommunikation: Passender selektierter Eintrag
			$placeholder->oJourneyInsurance = $journeyInsurance;
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $journeyInsurance->getPlaceholderObject();
	}
}