<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TC_NumberRange_Allocation extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_number_ranges_allocations';
	protected $_sTableAlias = 'tc_nra';

	protected $_aJoinTables = array(
		'objects'=> array(
			'table'=>'tc_number_ranges_allocations_objects',
			'foreign_key_field'=>'object_id',
			'primary_key_field'=>'allocation_id'
		)
	);

	protected $_aJoinedObjects = array(
		'sets'=>array(
			'class'=>'Ext_TC_NumberRange_Allocation_Set',
			'key'=>'allocation_id',
			'type'=>'child',
			'on_delete' => 'cascade'
		)
	);

	public static function getReceiptAllocations() {

		$sSql = "
			SELECT
				`invoice_numberrange_id`,
				`receipt_numberrange_id`
			FROM 
				`tc_number_ranges_allocations_receipts`
			WHERE
				1
			";
		$aAllocations = DB::getQueryPairs($sSql);

		return $aAllocations;
	}
	
}