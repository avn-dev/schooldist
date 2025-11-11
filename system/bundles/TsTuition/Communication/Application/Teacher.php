<?php

namespace TsTuition\Communication\Application;

use Communication\Dto\Message\Attachment;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class Teacher implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Lehrerverwaltung » Lehrer');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['teacher'];
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

			if (!$model instanceof \Ext_Thebing_Teacher) {
				continue;
			}

			$collection->push(
				(new AddressBookContact('teacher', $model))
					->groups($l10n->translate('Lehrer'))
					->recipients('teacher')
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

			if (!$model instanceof \Ext_Thebing_Teacher) {
				continue;
			}

			/* @var \Ext_Thebing_Teacher $model */
			$documents = [
				...$model->getContracts(),
				...$model->getDocumentsOfTypes('additional_document')
			];

			foreach($documents as $document){
				$version = null;
				if ($document instanceof \Ext_Thebing_Contract) {
					$version = $document->getLatestVersion();
				} else if ($document instanceof \Ext_Thebing_Inquiry_Document) {
					$version = $document->getLastVersion();
				}

				if($version && !empty($path = $version->getPath(true)) && file_exists($path)) {
					$collection->push(
						(new Attachment('document.'.$document->id, filePath: $path, fileName: $version->getLabel(), entity: $version))
							->source($model)
					);
				}
			}
		}

		return $collection;
	}

	public function getPlaceholderObject(\Ext_Thebing_Teacher $teacher, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Teacher_Placeholder($teacher);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $teacher->getPlaceholderObject();
	}
}