<?php

namespace TsTuition\Events;

use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\MultipleInquiriesEvent;
use Ts\Interfaces\Events\SchoolEvent;
use Ts\Interfaces\Events\TeacherEvent;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Manageable\WithManageableTeacherCommunication;
use TsTuition\Entity\Block\Unit;
use TsTuition\Enums\ActionSource;

class BlockCanceled implements ManageableEvent, MultipleInquiriesEvent, SchoolEvent, TeacherEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableCustomerCommunication,
		WithManageableSchoolCommunication,
		WithManageableSystemUserCommunication,
		WithManageableTeacherCommunication,
		WithIcon;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterrichtseinheit wurde abgesagt');
	}

	public function __construct(
		private Unit $unit,
		private ActionSource $source,
		private \Ext_Thebing_Teacher|\User $user
	){}

	public function getIcon(): ?string
	{
		return 'fas fa-chalkboard-teacher';
	}

	public function getUnit(): Unit
	{
		return $this->unit;
	}

	public function getSource(): ActionSource
	{
		return $this->source;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->unit->getBlock()->getSchool();
	}

	public function getTeacher(): \Ext_Thebing_Teacher
	{
		return $this->unit->getBlock()->getTeacher();
	}

	public function getInquiries(): array
	{
		$block = $this->unit->getBlock();
		$allocations = $block->getAllocations();

		$inquiries = [];
		foreach ($allocations as $allocation) {
			$inquiry = $allocation->getJourneyCourse()?->getJourney()?->getInquiry();
			if ($inquiry) {
				$inquiries[] = $inquiry;
			}
		}

		return $inquiries;
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$entity = ($event)
			? $event->getUnit()
			: new Unit();

		return $entity->getPlaceholderObject();
	}

	public static function manageCondtitionsAndListeners()
	{
		self::addManageableCondition(\TsTuition\Events\Conditions\ActionSource::class);
	}

}