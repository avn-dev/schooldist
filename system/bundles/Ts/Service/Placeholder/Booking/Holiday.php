<?php

namespace Ts\Service\Placeholder\Booking;

class Holiday extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oHoliday'
	];

	protected $_aPlaceholders = [
		'holiday_start_date' => [
			'label' => 'Startdatum',
			'type' => 'field',
			'source' => 'from',
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'holiday_end_date' => [
			'label' => 'Enddatum',
			'type' => 'field',
			'source' => 'until',
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'holiday_weeks' => [
			'label' => 'Wochen',
			'type' => 'field',
			'source' => 'weeks'
		],
	];

}
