<?php

namespace TsFrontend\Events;

use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions\InquiryType;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

class FeedbackFormSaved extends \TcFrontend\Events\FeedbackFormSaved implements ManageableEvent, InquiryEvent
{
	use ManageableEventTrait,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableSystemUserCommunication;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Feedback-Formular abgeschickt');
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->process->getInquiry();
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$inquiry = ($event) ? $event->getInquiry() : new \Ext_TS_Inquiry();
		return $inquiry->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		self::addManageableCondition(InquiryType::class);
	}
}