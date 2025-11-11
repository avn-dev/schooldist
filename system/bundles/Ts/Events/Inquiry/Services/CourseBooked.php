<?php

namespace Ts\Events\Inquiry\Services;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions\AccommodationCategoryCustomer;
use Ts\Events\Inquiry\Conditions\InquiryType;
use Ts\Interfaces\Events;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

class CourseBooked implements ManageableEvent, Events\InquiryEvent, Events\Inquiry\JourneyCourseEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableSystemUserCommunication;

	public function __construct(
		private \Ext_TS_Inquiry $inquiry,
		private \Ext_TS_Inquiry_Journey_Course $journeyCourse
	) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kurs gebucht');
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getJourneyCourse(): \Ext_TS_Inquiry_Journey_Course
	{
		return $this->journeyCourse;
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$journeyCourse = ($event) ? $event->getJourneyCourse() : new \Ext_TS_Inquiry_Journey_Course();
		return $journeyCourse->getPlaceholderObject();
	}

	public static function manageEventListenersAndConditions(): void
	{
		self::includeInquiryCourseConditions();
		self::addManageableCondition(InquiryType::class);
		self::addManageableCondition(AccommodationCategoryCustomer::class);
	}
}
