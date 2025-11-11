<?php

/**
 * Virtueller Block: Transfer > Anreise > Datum
 */
class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Arrival_Date extends Ext_Thebing_Form_Page_Block_Virtual_Transfers_Abstract_Date {

	const SUBTYPE = 'transfers_arrival_date';

	const TRANSFER_TYPE = 'arrival';

	protected $sCourseDateBlock = Ext_Thebing_Form_Page_Block_Virtual_Courses_Startdate::class;

	protected $sAccommodationDateBlock = Ext_Thebing_Form_Page_Block_Virtual_Accommodations_Startdate::class;

	protected $sAttributeUpdateToValueType = 'min';

}
