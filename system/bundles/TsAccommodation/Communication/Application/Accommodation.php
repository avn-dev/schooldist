<?php

namespace TsAccommodation\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class Accommodation implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunft » Resourcen » Anbieter');
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
			/* @var \Ext_Thebing_Accommodation $model */
			$collection->push(
				(new AddressBookContact('accommodation.'.$model->id, $model))
					->groups($l10n->translate('Unterkunftsanbieter'))
					->recipients('accommodation_provider')
					->source($model)
			);

			if ($channel === 'mail') {
				$members = $model->getMembersWithEmail();
				foreach ($members as $member) {
					$collection->push(
						(new AddressBookContact('accommodation.member.'.$member->id, $member))
							->groups($l10n->translate('Unterkunftsmitarbeiter'))
							->recipients('accommodation_provider')
							->source($model)
					);
				}
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

	public function getPlaceholderObject(\Ext_Thebing_Accommodation $accommodation, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		// Fake-Vertrag erzeugen, damit die Platzhalterklasse funktioniert (hier kam nie jemand auf die Idee das zu trennen)
		$contract = new \Ext_Thebing_Contract();
		$contract->item = 'accommodation';
		$contract->item_id = $accommodation->id;
		$version = new \Ext_Thebing_Contract_Version();
		$version->setJoinedObject('kcont', $contract);

		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Contract_Placeholder($version);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $version->getPlaceholderObject();
	}
}