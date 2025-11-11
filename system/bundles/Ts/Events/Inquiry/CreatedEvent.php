<?php

namespace Ts\Events\Inquiry;

use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\TestableEvent;
use TcApi\Interfaces\Events\WebhookEvent;
use TcApi\Listeners\SendWebhook;
use Ts\Interfaces\Events;
use Tc\Facades\EventManager;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Testable\WithInquiryTesting;

class CreatedEvent implements ManageableEvent, Events\InquiryEvent, TestableEvent, WebhookEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithInquiryTesting,
		WithIcon;

	public function __construct(public \Ext_TS_Inquiry $inquiry) {}

	public function getIcon(): ?string
	{
		return 'fas fa-user-plus';
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
		return 'inquiry.created';
	}

	public function getWebhookPayload(): array
	{
		return [
			'id' => $this->inquiry->id
		];
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Buchung oder Anfrage wurde angelegt');
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
