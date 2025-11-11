<?php

namespace TsCompany\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class AgencyContact implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Marketing » Agencies » Mitarbeiter');
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
			/* @var \Ext_Thebing_Agency_Contact $model */
			$collection->add(
				(new \Communication\Services\AddressBook\AddressBookContact('agency.contact.'.$model->id, $model))
					->groups($l10n->translate('Agenturmitarbeiter'))
					->recipients('agency')
					->source($model)
			);
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

	public function getPlaceholderObject(\Ext_Thebing_Agency_Contact $contact, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Agency_Placeholder($contact->id, 'contact');
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $contact->getPlaceholderObject();
	}
}