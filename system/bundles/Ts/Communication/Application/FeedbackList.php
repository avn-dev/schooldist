<?php

namespace Ts\Communication\Application;

use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;

// TODO gehört wahrscheinlich in ein anderes Bundle
class FeedbackList implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Schüler » Feedbackliste');
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
			/* @var \Ext_TS_Marketing_Feedback_Questionary_Process $model */
			$inquiry = $model->getInquiry();

			$collection = $collection
				->merge($this->withAllInquiryContacts($l10n, $model, $inquiry, $channel));
		}

		return $collection;
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$schools = [];
		foreach ($models as $model) {
			/* @var \Ext_TS_Marketing_Feedback_Questionary_Process $model */
			$inquiry = $model->getInquiry();

			$collection = $collection
				->merge($this->withAllInquiryAttachments($l10n, $model, $inquiry, $channel));

			$school = $inquiry->getSchool();
			$schools[$school->id] = $school;
		}

		$collection = $collection
			->merge($this->withSchoolUploads(4, collect($schools), $language));

		return $collection;
	}

	public static function getFlags(): array
	{
		return static::withAllInquiryFlags();
	}

	public function getPlaceholderObject(\Ext_TS_Marketing_Feedback_Questionary_Process $process, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		$inquiry = $process->getInquiry();

		if ($template && (bool)$template->legacy) {
			$placeholder = new \Ext_Thebing_Inquiry_Placeholder($inquiry);
			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $inquiry->getPlaceholderObject();
	}

	public function validate(LanguageAbstract $l10n, \Ext_TS_Marketing_Feedback_Questionary_Process $process, \Ext_TC_Communication_Message $log, bool $finalOutput, array $confirmedErrors): array
	{
		return $this->validateInquiryNettoDocuments($l10n, $process->getInquiry(), $log, $finalOutput, $confirmedErrors);
	}

}