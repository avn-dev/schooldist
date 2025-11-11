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

class CustomerAgency implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Versicherungen » Kunde informieren');
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
			/* @var \Ext_TS_Inquiry_Journey_Insurance $model */
			$inquiry = $model->getJourney()->getInquiry();

			$collection = $collection
				->merge($this->withInquiryTravellers($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryOtherContacts($l10n, $model, $inquiry, $channel));

			if ($channel === 'mail') {
				$agencyContacts = $inquiry->getAgencyContactsWithValidEmails();

				foreach ($agencyContacts as $agencyContact) {
					$collection = $collection->push(
						(new AddressBookContact('agency.contact.'.$agencyContact->id, $agencyContact))
							->groups($l10n->translate('Agenturmitarbeiter'))
							->source($model)
					);
				}
			}
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\Insurance\CustomerConfirmed::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry_Journey_Insurance $model */
			$collection = $collection
				->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $model->getJourney()->getInquiry(), $channel));
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