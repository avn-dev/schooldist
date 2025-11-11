<?php

namespace Ts\Events\Inquiry;

use Core\Enums\AlertLevel;
use Core\Interfaces\HasAlertLevel;
use Core\Traits\WithAlertLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class PaymentAllocationFailed implements ManageableEvent, HasAlertLevel
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithAlertLevel;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Zuordnung einer Zahlung ist fehlgeschlagen');
	}

	public function getAlertLevel(): AlertLevel
	{
		return AlertLevel::DANGER;
	}

	public function __construct(private \Ext_TS_Inquiry_Payment_Unallocated $payment) {}

	public function getUnallocatedPayment(): \Ext_TS_Inquiry_Payment_Unallocated
	{
		return $this->payment;
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$payment = ($event) ? $event->getUnallocatedPayment() : new \Ext_TS_Inquiry_Payment_Unallocated();
		return $payment->getPlaceholderObject();
	}

}