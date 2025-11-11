<?php

namespace Ts\Events\Inquiry\Services;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Services\Conditions\TransferDataMissing;
use Ts\Events\Inquiry\Services\Conditions\TransferType;
use Ts\Interfaces\Events;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

// TODO Platzhalter nicht auf Inquiry sondern auf den JourneyTransfer
class NewJourneyTransfer implements Events\InquiryEvent, ManageableEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableSystemUserCommunication;

	public function __construct(private \Ext_TS_Inquiry $inquiry, private \Ext_TS_Inquiry_Journey_Transfer $journeyTransfer) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Neuer Transfer gebucht');
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getJourneyTransfer(): \Ext_TS_Inquiry_Journey_Transfer
	{
		return $this->journeyTransfer;
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$inquiry = $event ? $event->getInquiry() : new \Ext_TS_Inquiry();
		return $inquiry->getPlaceholderObject();
	}

	public static function manageEventListenersAndConditions(): void
	{
		self::includeInquiryTypeConditions();
		self::addManageableCondition(TransferType::class);
		self::addManageableCondition(TransferDataMissing::class);
	}

}
