<?php

namespace Ts\Events\Inquiry;

use Core\Interfaces\HasButtons;
use Core\Interfaces\HasIcon;
use Core\Traits\WithButtons;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\TestableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use TcApi\Interfaces\Events\WebhookEvent;
use TcApi\Listeners\SendWebhook;
use Ts\Interfaces\Events;
use Ts\Notifications\Buttons\OpenTravellerButton;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Testable\WithInquiryTesting;

class UpdatedEvent implements ManageableEvent, Events\InquiryEvent, TestableEvent, WebhookEvent, HasIcon, HasButtons
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithInquiryTesting,
		WithIcon,
		WithButtons;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Buchung oder Anfrage wurde aktualisiert');
	}

	public function __construct(public \Ext_TS_Inquiry $inquiry) {}

	public function getIcon(): ?string
	{
		return 'fas fa-user-edit';
	}
	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getWebhookUrl(): ?string
	{
		return null;
	}

	public function getWebhookAction(): string
	{
		return 'inquiry.updated';
	}

	public function getWebhookPayload(): array
	{
		return [
			'id' => $this->inquiry->id
		];
	}

	public function getButtons(): array
	{
		return [
			new OpenTravellerButton($this->inquiry->getTraveller(), $this->inquiry),
		];
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

		self::addManageableListener(SendWebhook::class);

		self::addManageableCondition(Conditions\AccommodationCategoryCustomer::class);
		self::addManageableCondition(Conditions\InquiryType::class);
		self::addManageableCondition(Conditions\CreatorCondition::class);
		self::addManageableCondition(Conditions\TypeCondition::class);
	}

}
