<?php

namespace TsCompany\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class Agency implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Marketing » Agencies');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['agency'];
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
			/* @var \Ext_Thebing_Agency $model */
			$contacts = $model->getContacts(bAsObjects: true);

			foreach ($contacts as $contact) {
				$collection->add(
					(new \Communication\Services\AddressBook\AddressBookContact('agency.contact.'.$contact->id, $contact))
						->groups($l10n->translate('Agenturmitarbeiter'))
						->recipients('agency')
						->source($model)
				);
			}

		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();
		return $collection;
	}

	public function getPlaceholderObject(\Ext_Thebing_Agency $agency, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			// TODO übernommen aus \Ext_Thebing_Communication
			$agencyContact = $to
				->map(fn(\Communication\Dto\Message\Recipient $recipient) => $recipient->getModel())
				->filter(fn($contact) => $contact instanceof \Ext_Thebing_Agency_Contact)
				->first();

			if ($agencyContact) {
				$placeholder = new \Ext_Thebing_Agency_Placeholder($agencyContact->id, 'contact');
			} else {
				$placeholder = new \Ext_Thebing_Agency_Placeholder($agency->id, 'agency');
			}
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $agency->getPlaceholderObject();
	}
}