<?php

class Ext_TS_NumberRange_Allocation extends Ext_TC_NumberRange_Allocation {

	protected $_aJoinTables = array(
		'objects'=> array(
			'table'=>'tc_number_ranges_allocations_objects',
			'foreign_key_field'=>'object_id',
			'primary_key_field'=>'allocation_id'
		),
		'schools'=> array(
			'table'=>'tc_number_ranges_allocations_objects',
			'foreign_key_field'=>'object_id',
			'primary_key_field'=>'allocation_id',
			'class'=> Ext_Thebing_School::class,
			'autoload'=>false,
			'readonly'=>true
		)
	);

	protected $_aJoinedObjects = array(
		'sets'=>array(
			'class'=>'Ext_TS_NumberRange_Allocation_Set',
			'key'=>'allocation_id',
			'type'=>'child',
			'on_delete' => 'cascade'
		)
	);
	
	public static function getReceiptAllocations($oInbox = null) {
		
		$sWhere = " WHERE 1";
		$aSql = array();
		
		if($oInbox != null) {
			$sWhere = "
				JOIN 
					`ts_number_ranges_allocations_receipts_inboxes` `ts_nrari` ON
						`ts_nrari`.`allocation_receipt_id` = `ts_nrar`.`id`
				WHERE
					`ts_nrari`.`inbox_id` = :inbox_id OR
					`ts_nrari`.`inbox_id` IS NULL
			";
			
			$aSql['inbox_id'] = $oInbox->id;
		}
		
		$sSql = "
			SELECT
				`invoice_numberrange_id`,
				`receipt_numberrange_id`
			FROM 
				`tc_number_ranges_allocations_receipts` `ts_nrar` 
			" . $sWhere;

		$aAllocations = DB::getQueryPairs($sSql, $aSql);

		return $aAllocations;
		
	}
	
}
