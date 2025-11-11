<?php

namespace TsFrontend\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions\InquiryType;
use Ts\Interfaces\Events;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

class PlacementtestResult implements ManageableEvent, Events\InquiryEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableSystemUserCommunication;

	public function __construct(private \Ext_Thebing_Placementtests_Results $result) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Einstufungstest abgeschickt');
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->result->getInquiry();
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getPlacementTestResult() {
		return $this->result;
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$placementTestResult = ($event) ? $event->getPlacementTestResult() : new \Ext_Thebing_Placementtests_Results();
		return $placementTestResult->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		self::addManageableCondition(InquiryType::class);
	}

}
