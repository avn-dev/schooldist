<?php

namespace TsTuition\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;

class Allocation implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Klassenplanung » Zuweisungen');
	}

	public static function getRecipientKeys(string $application): array
	{
		return ['customer', 'agency', 'teacher'];
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
			/* @var \Ext_Thebing_School_Tuition_Allocation $model */
			$inquiry = $model->getJourneyCourse()->getJourney()->getInquiry();

			$collection = $collection
				->merge($this->withInquiryTravellers($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryOtherContacts($l10n, $model, $inquiry, $channel));
		}

		$teacher = $models->first()?->getBlock()?->getTeacher();

		if ($teacher) {
			$collection->push(
				(new AddressBookContact('teacher', $teacher))
					->groups($l10n->translate('Lehrer'))
					->recipients('teacher')
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

}