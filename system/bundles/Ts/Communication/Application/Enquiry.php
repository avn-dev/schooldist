<?php

namespace Ts\Communication\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Enquiry implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Anfragen');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'agency', 'sponsor'];
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
			/* @var \Ext_TS_Inquiry $model */
			// TODO: ist "reminder" hier wirklich richtig? übernommen aus \Ext_Thebing_Communication
			$collection = $collection->merge(
				$this->withAllInquiryContacts($l10n, $model, $model, $channel, ['reminder'])
			);
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return static::withInquiryPlacementtestFlags();
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$schools = [];
		foreach ($models as $model) {
			/* @var \Ext_TS_Inquiry $model */
			$search = new \Ext_Thebing_Inquiry_Document_Search($model->id);
			$search->addJourneyDocuments();
			$search->setType('offer');
			$documents = $search->searchDocument();

			foreach ($documents as $document) {
				$version = $document->getLastVersion();

				if (!$version || empty($path = $version->getPath(true)) || !file_exists($path)) {
					continue;
				}

				$attachment = (new Attachment('offer.'.$document->id, filePath: $path, fileName: $version->getLabel(), entity: $version))
					->source($model);

				$collection->push($attachment);
			}

			$school = $model->getSchool();
			$schools[$school->id] = $school;
		}

		$collection = $collection
			->merge($this->withInquiryAdditionalDocumentAttachments($l10n, $model, $model, $channel))
			->merge($this->withSchoolUploads(3, collect($schools), $language));

		return $collection;
	}

	public function getPlaceholderObject(\Ext_TS_Inquiry $inquiry, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $inquiry->getPlaceholderObject();
	}
}