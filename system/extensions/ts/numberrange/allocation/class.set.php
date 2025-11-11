<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TS_NumberRange_Allocation_Set extends Ext_TC_NumberRange_Allocation_Set {

	protected $_aJoinTables = array(
		'applications'=> array(
			'table'=>'tc_number_ranges_allocations_sets_applications',
			'foreign_key_field'=>'application',
			'primary_key_field'=>'set_id'
		),
		'inboxes' => array(
			'table'=>'ts_number_ranges_allocations_sets_inboxes',
			'foreign_key_field'=>'inbox_id',
			'primary_key_field'=>'set_id'
		),
		'currencies' => array(
			'table'=>'ts_number_ranges_allocations_sets_currencies',
			'foreign_key_field'=>'currency_id',
			'primary_key_field'=>'set_id'
		),
		'companies' => array(
			'table'=>'ts_number_ranges_allocations_sets_companies',
			'foreign_key_field'=>'company_id',
			'primary_key_field'=>'set_id'
		)
	);

}