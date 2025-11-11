<?php

class Ext_TS_Inquiry_Journey_Accommodation_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'journeyAccommodation'
	);

	protected $_aPlaceholders = array(
		'inquiry' => array(
			'label' => 'Buchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getInquiry',
			'variable_name' => 'oInquiry'
		),
		'accommodation_info' => [
			'label' => 'Information',
			'type' => 'method',
			'source' => 'getInfo',
			'method_parameter' => [
				false
			],
			'pass_language_last' => true
		],
//		'accommodation_category' => array(
//			'label' => 'Unterkunftskategorie',
//			'type' => 'parent',
//			'parent' => 'joined_object',
//			'source' => 'category',
//			'variable_name' => 'oAccommodationCategory',
//			'class' => Ext_Thebing_Accommodation_Category::class
//		),
//		'accommodation_roomtype' => array(
//			'label' => 'Raumtyp',
//			'type' => 'parent',
//			'parent' => 'joined_object',
//			'source' => 'roomtype',
//			'variable_name' => 'oAccommodationRoomtype',
//			'class' => Ext_Thebing_Accommodation_Roomtype::class
//		),
//		'accommodation_board' => array(
//			'label' => 'Verpflegung',
//			'type' => 'parent',
//			'parent' => 'joined_object',
//			'source' => 'meal',
//			'variable_name' => 'oAccommodationBoard',
//			'class' => Ext_Thebing_Accommodation_Meal::class
//		),
		'accommodation_start' => array(
			'label' => 'Beginn',
			'type' => 'field',
			'source' => 'from',
			'format' => 'Ext_Thebing_Gui2_Format_Date',
		),
		'accommodation_end' => array(
			'label' => 'Ende',
			'type' => 'field',
			'source' => 'until',
			'format' => 'Ext_Thebing_Gui2_Format_Date',
		),
		'accommodation_category' => array(
			'label' => 'Kategorie',
			'type' => 'method',
			'source' => 'getCategoryName',
		),
		'accommodation_comment' => array(
			'label' => 'Kommentar',
			'type' => 'field',
			'source' => 'comment'
		),
		'accommodation_duration_weeks' => array(
			'label' => 'Wochen',
			'type' => 'field',
			'source' => 'weeks'
		),
		'accommodation_check_in_time' => array(
			'label' => 'Einzugszeit',
			'type' => 'field',
			'source' => 'from_time'
		),
		'accommodation_check_out_time' => array(
			'label' => 'Auszugszeit',
			'type' => 'field',
			'source' => 'until_time'
		),
	);
	
}
