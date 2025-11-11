<?php

namespace TsAccounting\Communication\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Invoices implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Buchhaltung » Rechnungsübersicht');
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
			/* @var \Ext_Thebing_Inquiry_Document $model */
			$collection = $collection
				->merge($this->withInquiryTravellers($l10n, $model, $model->getInquiry(), $channel))
				->merge($this->withInquiryOtherContacts($l10n, $model, $model->getInquiry(), $channel))
				->merge($this->withInquiryAgencyContacts($l10n, $model, $model->getInquiry(), $channel));
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
			/* @var \Ext_Thebing_Inquiry_Document $model */
			$version = $model->getLastVersion();

			if (!$version || empty($path = $version->getPath(true)) || !file_exists($path)) {
				continue;
			}

			$collection->push(
				(new Attachment('document.'.$model->id, filePath: $path, fileName: $version->getLabel(), entity: $version))
					->source($model)
			);
		}

		return $collection;
	}
}