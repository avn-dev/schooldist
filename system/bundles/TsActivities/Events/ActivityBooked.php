<?php

namespace TsActivities\Events;

use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use TsActivities\Entity\Activity\BlockTraveller;
use TsActivities\Enums\AssignmentSource;

class ActivityBooked implements ManageableEvent, InquiryEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableCustomerCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithIcon;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Aktivität gebucht');
	}

	public function __construct(private AssignmentSource $source, private BlockTraveller $blockTraveller) {}

	public function getIcon(): ?string
	{
		return 'fa fa-bicycle';
	}

	public function getSource(): AssignmentSource
	{
		return $this->source;
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->blockTraveller->getInquiry();
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getBlockTraveller(): BlockTraveller
	{
		return $this->blockTraveller;
	}

	public static function getPlaceholderObject($event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$blockTraveller = ($event !== null) ? $event->getBlockTraveller() : new BlockTraveller;
		return $blockTraveller->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		// Conditions
		self::addManageableCondition(Conditions\AssignmentSource::class);
	}

}