<?php

use TsStatistic\Generator\Tool\Columns;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Model\Filter;

// TODO Gegen geplantes Interface ersetzen
return [
	// Ticket #12092 – Celtic - Neuer Report basierend auf Line items
	'celtic_revenue_report' => [
		'title' => 'Celtic Revenue Report',
		'interval' => 'weekly',
		'split_by_service_period' => true,
		'columns' => [
//			['class' => Columns\Revenue::class, 'configuration' => 'net'],
//			['class' => Columns\Revenue::class, 'configuration' => 'net_course'],
			['class' => Columns\Revenue::class, 'grouping' => Groupings\Revenue\Course::class, 'configuration' => 'net'],
//			['class' => Columns\Revenue::class, 'configuration' => 'net_coursefees'],
			['class' => Columns\Revenue::class, 'grouping' => Groupings\Revenue\CourseFees::class, 'configuration' => 'net'],
//			['class' => Columns\Revenue::class, 'configuration' => 'net_accommodation'],
			['class' => Columns\Revenue::class, 'grouping' => Groupings\Revenue\AccommodationCategory::class, 'configuration' => 'net'],
//			['class' => Columns\Revenue::class, 'configuration' => 'net_accommodationfees'],
			['class' => Columns\Revenue::class, 'grouping' => Groupings\Revenue\AccommodationFees::class, 'configuration' => 'net'],
//			['class' => Columns\Revenue::class, 'configuration' => 'net_generalfees'],
			['class' => Columns\Revenue::class, 'grouping' => Groupings\Revenue\GeneralFees::class, 'configuration' => 'net'],
			['class' => Columns\Revenue::class, 'configuration' => 'net_transfer'],
			['class' => Columns\Revenue::class, 'configuration' => 'net_extraposition'],
			//['class' => Columns\Commission::class, 'configuration' => 'commission'],
			['class' => Columns\Commission::class, 'configuration' => 'commission_creditnote'],
		]
	],
	// Ticket #14633 – Expanish - Report über Einnahmen pro Nationalität und Channel
	'revenue_per_nationality_and_channel' => [
		'title' => 'Revenue per nationality / channel',
		'interval' => 'monthly',
		'split_by_service_period' => true,
		'grouping' => new Groupings\Nationality(),
		'columns' => [
			['class' => Columns\Revenue::class, 'grouping' => Groupings\InquiryChannel::class, 'configuration' => 'net',],
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\InquiryChannel::class, 'configuration' => 'type:absolute'],
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\InquiryChannel::class, 'configuration' => 'type:absolute_once'],
			['class' => Columns\Weeks::class, 'grouping' => Groupings\InquiryChannel::class, 'configuration' => 'course'],
		],
		'filters' => [
			Filter\Schools::class,
			Filter\InvoiceType::class,
			Filter\Currency::class,
			Filter\Courses::class,
			Filter\Cancellation::class
		]
	],
	// Ticket #11912 – Unique : Neue Static Report - Nationalität % pro Kurskategorie
	'nationality_per_course_category' => [
		'title' => 'Nationality per course category',
		'interval' => 'completely',
		#'interval' => 'monthly',
		'split_by_service_period' => true,
		'grouping' => new Groupings\Nationality(),
		'grouping_on_y_axis' => true,
		'columns' => [
			//['class' => Columns\StudentCount::class, 'configuration' => 'absolute'],
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\Course\Category::class,  'configuration' => 'type:absolute|based_on:course'],
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\Course\Category::class, 'configuration' => 'type:percentage|based_on:course'],
		]
	],
	// Ticket #12926 – Maltalingua - Totale Schüler pro Kurs
	'students_per_course' => [
		'title' => 'Students per course',
		'interval' => 'yearly',
		'columns' => [
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\Course\Course::class,  'configuration' => 'type:absolute']
		]
	],
	// Ticket #13584 – Piccola - Report für Umsätze pro Unterkunftsanbieter
	'revenue_per_accommodation_provider' => [
		'title' => 'Revenue per accommodation provider',
		'interval' => 'monthly',
		'split_by_service_period' => true,
		'columns' => [
			['class' => Columns\Accommodation\ProviderRevenue::class, 'grouping' => Groupings\Revenue\AccommodationProvider::class],
		]
	],
	// Ticket #15385 – GLS - Teilnehmer und Raumbedarf - Teil 1
	'gls_1' => [
		'title' => 'GLS TN pro Kurs',
		'interval' => 'weekly',
		'split_by_service_period' => true,
		'columns' => [
			['class' => Columns\StudentCount::class, 'grouping' => Groupings\Course\Course::class,  'configuration' => 'type:absolute|based_on:course']
		]
	],
	// Ticket #15385 – GLS - Teilnehmer und Raumbedarf - Teil 1
	'gls_2' => [
		'title' => 'GLS TN',
		'interval' => 'weekly',
		'split_by_service_period' => true,
		'columns' => [
			['class' => Columns\StudentCount::class, 'configuration' => 'type:absolute'],
			['class' => Columns\StudentCount::class, 'configuration' => 'type:absolute|based_on:course'],
			['class' => Columns\StudentCount::class, 'configuration' => 'type:absolute|based_on:course|inquiry_filter:individual'],
			['class' => Columns\StudentCount::class, 'configuration' => 'type:absolute|based_on:course|inquiry_filter:group'],
			['class' => Columns\Course\StudentCountNotAllocated::class],
		]
	],
	// Ticket #15386 – GLS - Teilnehmer und Raumbedarf - Teil 2
	'gls_3' => [
		'title' => 'GLS Raumbedarf',
		'interval' => 'weekly',
		'split_by_service_period' => true,
		'columns' => [
			['class' => Columns\Tuition\RoomDemand::class, 'grouping' => Groupings\Tuition\DefaultTimes::class, 'configuration' => 'type:demand'],
			['class' => Columns\Tuition\RoomAvailable::class, 'grouping' => Groupings\Tuition\DefaultTimes::class, 'configuration' => 'type:absolute'],
			['class' => Columns\Tuition\TeacherAvailable::class, 'grouping' => Groupings\Tuition\DefaultTimes::class, 'configuration' => 'type:absolute']
		],
		'filters' => [
			Filter\Schools::class,
			Filter\Currency::class // TODO Das macht eigentlich keinen Sinn, aber Filter wird aktuell fest in der Basis eingebaut
		]
	],
	// Ticket #15579 – GLS - Übernachtungen nach Nationalität
	'gls_4' => [
		'title' => 'GLS Übernachtungen pro Nationalität',
		#'interval' => 'monthly',
		'interval' => 'completely',
		'split_by_service_period' => true,
		'grouping' => new Groupings\Nationality(),
		'grouping_on_y_axis' => true,
		'columns' => [
			['class' => Columns\Accommodation\StudentCountArrived::class, /*'grouping' => Groupings\Nationality::class*/],
			['class' => Columns\Accommodation\Nights::class, /*'grouping' => Groupings\Nationality::class*/]
		],
		'filters' => [
			Filter\Schools::class,
			Filter\Currency::class, // TODO Das macht eigentlich keinen Sinn, aber Filter wird aktuell fest in der Basis eingebaut
			Filter\AccommodationCategory::class
		]
	]
];
