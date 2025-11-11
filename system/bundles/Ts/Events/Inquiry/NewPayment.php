<?php

namespace Ts\Events\Inquiry;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\HasButtons;
use Core\Interfaces\HasIcon;
use Core\Notifications\Attachment;
use Core\Traits\WithButtons;
use Core\Traits\WithIcon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Events\Inquiry\Conditions\PaymentSource;
use Ts\Events\Inquiry\Conditions\PaymentType;
use Ts\Interfaces\Events;
use Ts\Listeners\Inquiry\SendAgencyNotification;
use Ts\Notifications\Buttons\OpenTravellerButton;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableInquiryCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

class NewPayment implements ManageableEvent, Events\InquiryEvent, AttachmentsEvent, HasIcon, HasButtons
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableInquiryCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableSystemUserCommunication,
		WithIcon,
		WithButtons;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Neue Zahlung eingegangen');
	}

	public function __construct(
		private \Ext_TS_Inquiry $inquiry,
		private \Ext_Thebing_Inquiry_Payment $payment
	) {}

	public function getIcon(): ?string
	{
		return 'fas fa-receipt';
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->inquiry;
	}

	public function getPayment(): \Ext_Thebing_Inquiry_Payment
	{
		return $this->payment;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getSource(): string
	{
		return $this->payment->getMeta('source', 'backend');
	}

	public function getType(): string
	{
		return $this->payment->type_id;
	}

	public function getButtons(): array
	{
		return [
			new OpenTravellerButton($this->inquiry->getTraveller(), $this->inquiry),
		];
	}

	public function getAttachments($listener): array
	{
		$type = $this->inquiry->hasAgency() && $listener instanceof SendAgencyNotification ? 'receipt_agency' : 'receipt_customer';
		/* @var \Ext_Thebing_Inquiry_Document $receipt */
		$receipt = $this->payment->getReceipts($this->inquiry, $type)->first();

		if ($receipt) {
			$version = $receipt->getLastVersion();
			if ($version && !empty($path = $version->getPath(true))) {
				return [
					(new Attachment(filePath: $path, entity: $version))->icon('fas fa-receipt')
				];
			}
		}

		return [];
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$payment = ($event !== null) ? $event->getPayment() : new \Ext_Thebing_Inquiry_Payment();
		return $payment->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		self::includeInquiryCourseConditions(); // benötigt?
		self::includeInquiryTypeConditions();

		self::addManageableCondition(PaymentSource::class);
		self::addManageableCondition(PaymentType::class);
		self::addManageableCondition(Conditions\AccommodationCategoryCustomer::class);
	}

}