<?php

class Ext_TC_Vat_Allocation extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_vat_rates_allocations';
	protected $_sTableAlias = 'tc_vra';

	protected $_aJoinedObjects = array(
	 	'groups'=>array(
	 		'class'=>'Ext_TC_Vat_Allocation_Group',
	 		'key'=>'allocation_id',
	 		'type'=>'child',
	 		'check_active'=>true
		)
	 );
	
	protected $_aJoinTables = array(
		'objects'=>array(
	 		'table'=>'tc_vat_rates_allocations_objects',
	 		'foreign_key_field'=>'object_id',
	 		'primary_key_field'=>'allocation_id',
			'autoload' => false
	 	),
		'origin_countries'=>array(
	 		'table'=>'tc_vat_rates_allocations_countries',
	 		'foreign_key_field'=>'country',
	 		'primary_key_field'=>'allocation_id',
			'static_key_fields'=>array('type'=>'origin'),
			'autoload' => false
	 	),
		'destination_countries'=>array(
	 		'table'=>'tc_vat_rates_allocations_countries',
	 		'foreign_key_field'=>'country',
	 		'primary_key_field'=>'allocation_id',
			'static_key_fields'=>array('type'=>'destination'),
			'autoload' => false
	 	),
		'address_types'=>array(
	 		'table'=>'tc_vat_rates_allocations_address_types',
	 		'foreign_key_field'=>'type',
	 		'primary_key_field'=>'allocation_id',
			'autoload' => false
	 	)
	 );

}
