<?php

class Ext_TC_Vat_Allocation_Group extends Ext_TC_Basic {
	
	// Tabellenname
	protected $_sTable = 'tc_vat_rates_allocations_groups';
	protected $_sTableAlias = 'tc_vrag';
	
	protected $_aJoinedObjects = array(
	 	'allocation'=>array(
	 		'class'=>'Ext_TC_Vat_Allocation',
	 		'key'=>'allocation_id',
	 		'type'=>'parent'
		)
	);
	
	protected $_aJoinTables = array(
		'positiontypes'=>array(
	 		'table'=>'tc_vat_rates_allocations_groups_positiontypes',
	 		'foreign_key_field'=>'position_type',
	 		'primary_key_field'=>'group_id'
	 	)
	);

}