<?php

namespace Ts\Events\Inquiry;

use Core\Enums\AlertLevel;
use Core\Interfaces\HasAlertLevel;
use Core\Interfaces\HasButtons;
use Core\Traits\WithAlertLevel;
use Core\Traits\WithButtons;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\TestableEvent;
use Ts\Interfaces\Events;
use Tc\Facades\EventManager;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Notifications\Buttons\OpenTravellerButton;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Testable\WithInquiryTesting;

class PaymentFailed implements ManageableEvent, Events\InquiryEvent, TestableEvent, HasAlertLevel, HasButtons
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithInquiryTesting,
		WithAlertLevel,
		WithButtons;

	public function __construct(public \Ext_TS_Inquiry $inquiry) {}

	public function getAlertLevel(): ?AlertLevel
	{
		return AlertLevel::DANGER;
	}

	public function getIcon(): ?string
	{
		return 'fas fa-receipt';
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getButtons(): array
	{
		return [
			new OpenTravellerButton($this->inquiry->getTraveller(), $this->inquiry),
		];
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Zahlung ist fehlgeschlagen');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$inquiry = $event ? $event->getInquiry() : new \Ext_TS_Inquiry();
		return $inquiry->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		self::includeCustomerMarketingConditions();
		self::includeCustomerAgeLimitation();
		self::includeInquiryCourseConditions();
		self::addManageableCondition(Conditions\InquiryType::class);
	}

}
