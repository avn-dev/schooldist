<?php

namespace TsTuition\Communication\Application;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Communication\Traits\Application\WithInquiryPayload;
use TsTuition\Communication\Flag;

class Attendance implements Application
{
	use WithInquiryPayload;

	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Unterricht » Anwesenheit');
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
			$block = $model->getBlock();
			$subsititudeTeachers = array_map(fn ($id) => \Ext_Thebing_Teacher::getInstance($id), $block->getSubstituteTeachers(0, true));
			$teachers = [$block->getTeacher(), ...$subsititudeTeachers];

			foreach ($teachers as $teacher) {
				$collection->push(
					(new AddressBookContact('teacher.'.$teacher->id, $teacher))
						->groups($l10n->translate('Lehrer'))
						->recipients('teacher')
						->source($model)
				);
			}

			$inquiry = $model->getJourneyCourse()->getJourney()->getInquiry();

			$collection = $collection->merge(
				$this->withAllInquiryContacts($l10n, $model, $inquiry, $channel, ['reminder'])
			);
		}

		return $collection;
	}

	public static function getFlags(): array
	{
		return [
			Flag\AttendanceWarning::class,
			...static::withInquiryFeedbackFlags()
		];
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		$collection = new AttachmentsCollection();

		$schools = [];
		foreach ($models as $model) {
			/* @var \Ext_Thebing_School_Tuition_Allocation $model */
			$inquiry = $model->getJourneyCourse()->getJourney()->getInquiry();

			$collection = $collection
				->merge($this->withAllInquiryAttachments($l10n, $model, $inquiry, $channel))
				->merge($this->withInquiryAccommodationAttachments($l10n, $model, $inquiry, $language));

			$school = $inquiry->getSchool();
			$schools[$school->id] = $school;
		}

		$collection = $collection->merge(
			$this->withSchoolUploads(4, collect($schools), $language)
		);

		return $collection;
	}

	public function getAdditionalModelRelations(Collection $models): Collection
	{
		return $models->map(fn ($allocation) => $allocation->getJourneyCourse()->getJourney()->getInquiry());
	}

	public function getPlaceholderObject(\Ext_Thebing_School_Tuition_Allocation $allocation, \Ext_TC_Communication_Template|null $template, Collection $to, string $language, bool $finalOutput)
	{
		if ($template && (bool)$template->legacy) {
			$agencyContact = $to
				->map(fn(\Communication\Dto\Message\Recipient $recipient) => $recipient->getModel())
				->filter(fn($contact) => $contact instanceof \Ext_Thebing_Agency_Contact)
				->first();

			if ($agencyContact) {
				/* @var \Ext_Thebing_Agency_Contact $agencyContact */
				$agency = $agencyContact->getParentObject();
				$placeholder = new \Ext_Thebing_Agency_Placeholder($agency->id, 'agency');
				$placeholder->_oAgencyStaff = $agencyContact;
			} else {
				$placeholder = new \Ext_Thebing_Inquiry_Placeholder($allocation->getJourneyCourse()->getJourney()->getInquiry());
				$placeholder->setTuitionAllocation($allocation);
			}

			$placeholder->sTemplateLanguage = $language;
			$placeholder->bInitialReplace = !$finalOutput;
			return $placeholder;
		}

		return $allocation->getPlaceholderObject();
	}

	public function validate(LanguageAbstract $l10n, \Ext_Thebing_School_Tuition_Allocation $allocation, \Ext_TC_Communication_Message $log, bool $finalOutput, array $confirmedErrors): array
	{
		$inquiry = $allocation->getJourneyCourse()->getJourney()->getInquiry();

		return $this->validateInquiryNettoDocuments($l10n, $inquiry, $log, $finalOutput, $confirmedErrors);
	}
}