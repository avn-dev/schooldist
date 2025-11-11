<?php

return [
	'bases' => [
		\TsReporting\Generator\Bases\Booking::class,
		\TsReporting\Generator\Bases\BookingServicePeriod::class,
	],
	'groupings' => [
		\TsReporting\Generator\Groupings\Aggregated::class,
		\TsReporting\Generator\Groupings\Booking\Accommodation::class,
		\TsReporting\Generator\Groupings\Booking\AgeGroup::class,
		\TsReporting\Generator\Groupings\Booking\Agency::class,
		\TsReporting\Generator\Groupings\Booking\Booking::class,
		\TsReporting\Generator\Groupings\Booking\Course::class,
		\TsReporting\Generator\Groupings\Booking\Gender::class,
		\TsReporting\Generator\Groupings\Booking\Group::class,
		\TsReporting\Generator\Groupings\Booking\Inbox::class,
		\TsReporting\Generator\Groupings\Booking\Nationality::class,
		\TsReporting\Generator\Groupings\Booking\SalesPerson::class,
		\TsReporting\Generator\Groupings\Booking\StudentStatus::class,
		\TsReporting\Generator\Groupings\Document\Accommodation::class,
		\TsReporting\Generator\Groupings\Document\Course::class,
		\TsReporting\Generator\Groupings\Document\Fees::class,
		\TsReporting\Generator\Groupings\Document\ItemType::class,
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class,
		\TsReporting\Generator\Groupings\Tuition\TuitionClass::class,
		\TsReporting\Generator\Groupings\Tuition\TuitionTime::class
	],
	'columns' => [
		\TsReporting\Generator\Columns\Booking\Commission::class,
		\TsReporting\Generator\Columns\Booking\StudentCount::class,
		\TsReporting\Generator\Columns\Booking\Revenue::class,
		\TsReporting\Generator\Columns\Booking\Weeks::class,
		\TsReporting\Generator\Columns\Tuition\StudentCountByStatus::class,
		\TsReporting\Generator\Columns\Tuition\StudentCountNotAllocated::class
	],
	'filter' => [
		\TsReporting\Generator\Filter\Period::class,
		\TsReporting\Generator\Filter\Booking\School::class,
		\TsReporting\Generator\Filter\Booking\InvoiceStatus::class,
		\TsReporting\Generator\Filter\Booking\Confirmed::class,
		\TsReporting\Generator\Filter\Booking\Cancelation::class,
		\TsReporting\Generator\Filter\Booking\Group::class,
		\TsReporting\Generator\Filter\Booking\StudentStatus::class,
		\TsReporting\Generator\Filter\Booking\Course::class,
		\TsReporting\Generator\Filter\Booking\CourseCategory::class,
		\TsReporting\Generator\Filter\Booking\CourseOnline::class,
		\TsReporting\Generator\Filter\Booking\AgeGroup::class,
		\TsReporting\Generator\Filter\Booking\BookingType::class,
		\TsReporting\Generator\Filter\Booking\CreationDate::class,
		\TsReporting\Generator\Filter\Booking\ServiceStart::class
	]
];
