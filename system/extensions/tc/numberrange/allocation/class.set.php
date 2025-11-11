<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TC_NumberRange_Allocation_Set extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_number_ranges_allocations_sets';
	protected $_sTableAlias = 'tc_nras';

	protected $_aJoinTables = array(
		'applications'=> array(
			'table'=>'tc_number_ranges_allocations_sets_applications',
			'foreign_key_field'=>'application',
			'primary_key_field'=>'set_id'
		)
	);

}