<?php

namespace Ts\Events\Inquiry;

use Carbon\Carbon;
use Core\Interfaces\HasButtons;
use Core\Interfaces\HasIcon;
use Core\Traits\WithButtons;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\EventManager\TestableEvent;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\Manageable\WithManageableRecipientType;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Ts\Interfaces\Events;
use Tc\Gui2\Data\EventManagementData;
use Tc\Facades\EventManager;
use Ts\Notifications\Buttons\OpenTravellerButton;
use Ts\Traits\Events\Manageable\WithManageableExecutionTime;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Testable\WithInquiryTesting;

class CustomerBirthday implements ManageableEvent, Events\InquiryEvent, TestableEvent, HasIcon, HasButtons
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableExecutionTime,
		WithManageableSchoolCommunication,
		WithManageableSystemUserCommunication,
		WithManageableCustomerCommunication,
		WithManageableRecipientType,
		WithManageableIndividualCommunication,
		WithInquiryTesting,
		WithIcon,
		WithButtons;

	public function __construct(private \Ext_TS_Inquiry_Contact_Traveller $customer) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kundengeburtstag');
	}

	public static function toReadable(Settings $settings): string
	{
		return sprintf(
			EventManager::l10n()->translate('Kundengeburtstag "%s"'),
			self::getRecipientTypeSelectOptions()[$settings->getSetting('recipient_type')]
		);
	}

	public function getIcon(): ?string
	{
		return 'fas fa-birthday-cake';
	}

	public function getButtons(): array
	{
		return [
			new OpenTravellerButton($this->customer, $this->getInquiry()),
		];
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->customer->getClosestInquiry();
	}

	public function getCustomer(): \Ext_TS_Inquiry_Contact_Traveller
	{
		return $this->customer;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$customers = $school->getBirthdayCustomers($time, $process->getSetting('recipient_type', 'all_customers'));

		if (empty($customers)) {
			EventManager::logger('CustomerBirthday')->info('No customers found', ['school_id' => $school->id, 'process' => $process->getIdentifier()]);
		}

		foreach ($customers as $customer) {
			EventManager::logger('CustomerBirthday')->info('Execute birthday event', ['school_id' => $school->id, 'customer_id' => $customer->id, 'process' => $process->getIdentifier()]);
			self::dispatch($customer);
		}
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$customer = ($event !== null) ? $event->getCustomer() : new \Ext_TS_Inquiry_Contact_Traveller();
		return $customer->getPlaceholderObject();
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		self::addExecutionTimeRow($dialog, $eventTab, $data);
		self::addExecutionWeekendRow($dialog, $eventTab, $data);
		self::addRecipientTypeRow($dialog, $eventTab);
	}

	public static function manageListenersAndConditions(): void
	{
		self::includeCustomerMarketingConditions();
		self::includeCustomerAgeLimitation();

		self::addManageableCondition(Conditions\InquiryType::class);
		self::addManageableCondition(Conditions\InquiryStatus::class);
		self::addManageableCondition(Conditions\CourseCategory::class);
		self::addManageableCondition(Conditions\AccommodationCategoryCustomer::class);
	}

	public static function buildTestEvent(Settings $settings): static
	{
		/* @var \Ext_TS_Inquiry $inquiry */
		$inquiry = \Ext_TS_Inquiry::query()->findOrFail($settings->getSetting('inquiry_id'));
		return new self($inquiry->getTraveller());
	}

}
