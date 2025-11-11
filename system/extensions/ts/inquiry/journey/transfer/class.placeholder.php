<?php

class Ext_TS_Inquiry_Journey_Transfer_Placeholder extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = array(
		'variable_name' => 'journeyTransfer'
	);

	protected $_aPlaceholders = array(
		'date' => array(
			'label' => 'Datum',
			'type' => 'field',
			'source' => 'transfer_date',
			'format' => 'Ext_Thebing_Gui2_Format_Date',
		),
		'time' => array(
			'label' => 'Zeit',
			'type' => 'field',
			'source' => 'transfer_time',
			'format' => 'Ext_Thebing_Gui2_Format_Time',
		),
		'pickup_time' => array(
			'label' => 'Abholzeit',
			'type' => 'field',
			'source' => 'pickup',
			'format' => 'Ext_Thebing_Gui2_Format_Time',
		),
		'inquiry' => array(
			'label' => 'Buchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getInquiry',
			'variable_name' => 'oInquiry'
		),
	);

}
