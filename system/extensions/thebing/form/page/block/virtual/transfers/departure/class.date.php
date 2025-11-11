<?php

/**
 * Virtueller Block: Transfer > Abreise > Datum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Departure_Date extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Date {

	const SUBTYPE = 'transfers_departure_date';

	const TRANSFER_TYPE = 'departure';

	protected $sCourseDateBlock = Ext_Thebing_Form_Page_Block_Virtual_Courses_Enddate::class;

	protected $sAccommodationDateBlock = Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Enddate::class;

	protected $sAttributeUpdateToValueType = 'max';

}
