<?php

class Ext_Thebing_Tuition_Attendance_Document_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oTuitionAttendanceDocument'
	);

	protected $_aPlaceholders = array(
		'inquiry' => array(
			'label' => 'Buchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getInquiry',
			'variable_name' => 'oInquiry'
		),
		'allocations' => array(
			'label' => 'Unterrichtszuweisungen',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getAllocations',
			'variable_name' => 'aTuitionAllocations',
			'class' => 'Ext_Thebing_School_Tuition_Allocation'
		)
	);

}
