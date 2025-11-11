<?php

namespace TsAccommodation\Communication\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use TsAccommodation\Communication\Flag;

class AccommodationContract implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterkunftsverträge');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['contract_partner'];
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
			/* @var \Ext_Thebing_Contract_Version $model */
			$accommodation = $model->getContract()->getItemObject();

			$collection->push(
				(new AddressBookContact('accommodation', $accommodation))
					->groups($l10n->translate('Unterkunftsanbieter'))
					->recipients('contract_partner')
					->source($model)
			);
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\ContractSent::class
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		foreach ($models as $model) {
			/* @var \Ext_Thebing_Contract_Version $model */
			if(!empty($path = $model->getPath(true)) && file_exists($path)) {
				$collection->push(
					(new Attachment('contract.'.$model->id, filePath: $path, fileName: $model->getLabel(), entity: $model))
						->source($model)
				);
			}
		}

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn (\Ext_Thebing_Contract_Version $model) => $model->getContract()->getItemObject());
	}

	public function getPlaceholderObject(\Ext_Thebing_Contract_Version $version, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Contract_Placeholder($version);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $version->getPlaceholderObject();
	}
}