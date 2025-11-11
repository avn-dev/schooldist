<?php

class Ext_TC_Countrygroup_Object extends Ext_TC_Basic {

	protected $_sTable = 'tc_countrygroups_objects';
	
	protected $_sTableAlias = 'tc_cg_o';
	
	protected $_aJoinTables = array(
		'countries' => array(
			'table' => 'tc_countrygroups_objects_to_countries',
	 		'foreign_key_field'=> 'country_iso',
	 		'primary_key_field'=> 'countrygroup_object_id'
		),
		'objects' => array(
			'table' => 'tc_countrygroups_objects_to_objects',
	 		'foreign_key_field'=> 'object_id',
	 		'primary_key_field'=> 'countrygroup_object_id'
		)
	);
	
}


