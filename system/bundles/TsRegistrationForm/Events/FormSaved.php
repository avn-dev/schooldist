<?php

namespace TsRegistrationForm\Events;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Notifications\Attachment;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions;
use Ts\Interfaces\Events;
use Ts\Listeners;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use TsFrontend\Events\Conditions\CombinationLanguage;
use TsFrontend\Interfaces\Events\CombinationEvent;
use TsRegistrationForm\Events\Conditions\Combination;
use TsRegistrationForm\Interfaces\RegistrationCombination;

class FormSaved implements ManageableEvent, Events\InquiryEvent, CombinationEvent, AttachmentsEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication;

	public function __construct(
		private RegistrationCombination $combination,
		private \Ext_TS_Inquiry $inquiry,
		private ?\Ext_Thebing_Inquiry_Document $document = null
	) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Online-Formular abgeschickt');
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getCombination(): \Ext_TC_Frontend_Combination
	{
		return $this->combination->getCombination();
	}

	public function getLanguage(): string
	{
		return $this->combination->getLanguage()->getLanguage();
	}

	public function getAttachments($listener): array
	{
		if ($this->document && $this->combination->getForm()->email_attach_document) {
			$version = $this->document->getLastVersion();
			if ($version && !empty($path = $version->getPath(true))) {
				return [new Attachment(filePath: $path, entity: $version)];
			}
		}

		return [];
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$inquiry = ($event) ? $event->getInquiry() : new \Ext_TS_Inquiry();
		return $inquiry->getPlaceholderObject();
	}

	public static function manageEventListenersAndConditions(): void
	{
		// Listeners
		self::addManageableListener(Listeners\Inquiry\SendCustomerEmail::class);
		self::addManageableListener(Listeners\Inquiry\SendBookerEmail::class);
		// Conditions
		self::addManageableCondition(Combination::class);
		self::addManageableCondition(CombinationLanguage::class);
		self::addManageableCondition(Conditions\CourseCategory::class);
		self::addManageableCondition(Conditions\Course::class);
		self::addManageableCondition(Conditions\AccommodationCategoryCustomer::class);
	}
}
