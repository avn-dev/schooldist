<?php

namespace TsAccounting\Communication\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class AccommodationPayments implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Buchhaltung » Unterkunftsanbieter');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['accommodation_provider'];
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
			/* @var \Ext_Thebing_Accommodation $accommodation */
			$accommodation = $model->getItem();

			$collection->add(
				(new \Communication\Services\AddressBook\AddressBookContact('accommodation.'.$accommodation->id, $accommodation))
					->groups($l10n->translate('Unterkunftsanbieter'))
					->recipients('accommodation_provider')
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

		foreach ($models as $model) {
			/* @var \Ext_TS_Accounting_Provider_Grouping_Accommodation $model */
			if (empty($path = $model->getPdfPath()) || !file_exists($path)) {
				continue;
			}

			$collection->push(
				(new Attachment('provider.receipt.'.$model->id, filePath: $path, entity: $model))
					->source($model)
					->icon('fas fa-receipt')
			);
		}

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn (\Ext_TS_Accounting_Provider_Grouping_Accommodation $model) => $model->getItem());
	}

	public function getPlaceholderObject(\Ext_TS_Accounting_Provider_Grouping_Accommodation $grouping, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$accommodation = $grouping->getItem();
			$placeholder = new \Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder($accommodation, $grouping);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $grouping->getPlaceholderObject();
	}

}