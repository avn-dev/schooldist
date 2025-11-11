<?php

namespace Ts\Traits\Events\Manageable;

use Ts\Listeners;
use Ts\Events\Inquiry\Conditions;

trait WithManageableCustomerCommunication
{
	/**
	 * Wird automatisch eingelesen
	 *
	 * @return void
	 */
	public static function manageCustomerCommunicationCommunication(): void
	{
		self::addManageableListener(Listeners\Inquiry\SendCustomerEmail::class);
		self::addManageableListener(Listeners\Inquiry\SendCustomerAppNotification::class);
	}

	public static function includeCustomerMarketingConditions(): void
	{
		self::addManageableCondition(Conditions\MarketingEnabled::class);
		self::addManageableCondition(Conditions\DaysSinceLastMessage::class);
	}

	public static function includeCustomerAgeLimitation(): void
	{
		self::addManageableCondition(Conditions\MinorCustomer::class);
		self::addManageableCondition(Conditions\AgeLimitation::class);
	}

}
