<?php

namespace Ts\Traits\Events\Manageable;

use Ts\Listeners;
use Ts\Events\Inquiry\Conditions;

trait WithManageableInquiryCommunication
{
	use WithManageableCustomerCommunication;

	public static function manageInquiryCommunication(): void
	{
		self::addManageableListener(Listeners\Inquiry\SendAgencyNotification::class);
		self::addManageableListener(Listeners\Inquiry\SendGroupContactNotification::class);
		self::addManageableListener(Listeners\Inquiry\SendSalesPersonNotification::class);
	}

	public static function includeInquiryTypeConditions(): void
	{
		self::addManageableCondition(Conditions\InquiryStatus::class);
		self::addManageableCondition(Conditions\InquiryType::class);
	}

	public static function includeInquiryCourseConditions(): void
	{
		self::addManageableCondition(Conditions\Course::class);
		self::addManageableCondition(Conditions\CourseCategory::class);
	}

}
