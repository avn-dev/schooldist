<?php

namespace Ts\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Booking implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return match($application) {
			'booking' => $l10n->translate('Buchungen'),
			'simple_view' => $l10n->translate('Schüler » Einfache Schülerliste'),
			'arrival_list' => $l10n->translate('Schüler » Willkommensliste'),
			'departure_list' => $l10n->translate('Schüler » Abreiseliste'),
			'feedback_list' => $l10n->translate('Schüler » Feedbackliste'),
			'visum_list' => $l10n->translate('Schüler » Visa'),
			'client_payment' => $l10n->translate('Buchhaltung » Kundenzahlungen'),
			'placement_test' => $l10n->translate('Unterricht » Ergebnisse des Einstufungstests'),
			default => throw new \InvalidArgumentException(sprintf('Title for application "%s" not defined.', $application)),
		};
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'agency', 'sponsor', 'teacher', 'accommodation_provider'];
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
			/* @var \Ext_TS_Inquiry $model */
			$collection = $collection->merge(
				$this->withAllInquiryContacts($l10n, $model, $model, $channel, ['reminder'])
			);
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return self::withAllInquiryFlags();
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$schools = [];
		foreach ($models as $model) {
			$collection = $collection
				->merge($this->withAllInquiryAttachments($l10n, $model, $model, $channel));

			$school = $model->getSchool();
			$schools[$school->id] = $school;
		}

		$collection = $collection
			->merge($this->withSchoolUploads(4, collect($schools), $language));

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

	public function validate(LanguageAbstract $l10n, \Ext_TS_Inquiry $inquiry, \Ext_TC_Communication_Message $log, bool $finalOutput, array $confirmedErrors): array
	{
		return $this->validateInquiryNettoDocuments($l10n, $inquiry, $log, $finalOutput, $confirmedErrors);
	}
}