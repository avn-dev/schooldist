<?php

namespace TsTuition\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\MultipleInquiriesEvent;
use Ts\Interfaces\Events\SchoolEvent;
use Ts\Interfaces\Events\MultipleTeachersEvent;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Manageable\WithManageableTeacherCommunication;

class ClassConfirmed implements ManageableEvent, MultipleInquiriesEvent, SchoolEvent, MultipleTeachersEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableCustomerCommunication,
		WithManageableSchoolCommunication,
		WithManageableSystemUserCommunication,
		WithManageableTeacherCommunication;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Klasse wurde bestätigt');
	}

	public function __construct(
		private \Ext_Thebing_Tuition_Class $class
	){}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->class->getSchool();
	}

	public function getTeachers(): array
	{
		$teachers = [];
		$blocks = $this->class->getBlocks();
		foreach ($blocks as $block) {
			$teachers[$block->getTeacher()->id] = $block->getTeacher();
		}
		return array_values($teachers);
	}

	public function getInquiries(): array
	{
		$inquiries = [];
		$blocks = $this->class->getBlocks();
		foreach ($blocks as $block) {
			$allocations = $block->getAllocations();

			foreach ($allocations as $allocation) {
				$inquiry = $allocation->getJourneyCourse()?->getJourney()?->getInquiry();
				if (
					$inquiry &&
					!$inquiries[$inquiry->id]
				) {
					$inquiries[$inquiry->id] = $inquiry;
				}
			}
		}

		return array_values($inquiries);
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$class = $event->class ?? new \Ext_Thebing_Tuition_Class();
		return $class->getPlaceholderObject();
	}

	public static function manageCondtitionsAndListeners() {}

}