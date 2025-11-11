<?php

/**
 * @property $id
 * @property $changed
 * @property $created
 * @property $editor_id
 * @property $creator_id
 * @property $active
 * @property $name
 */
class Ext_TC_Cancellationconditions_Group extends Ext_TC_Basic { 
	
	protected $_sTable = 'tc_cancellation_conditions_groups';

	protected $_sTableAlias = 'tc_cc';

	protected $_aFormat = [
		'name' => [
			'required' => true
		]
	];

	protected $_aJoinedObjects = [
		'fees' => [
			'class' => Ext_TC_Cancellationconditions_Fee::class,
			'key' => 'group_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		]
	];
	
	/**
	 * get Select Options
	 * @return array
	 */
	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	}

	/**
	 * @return Ext_TC_Cancellationconditions_Fee[]
	 */
	public function getCancellationFees(){
		return $this->getJoinedObjectChild('fees', true);
	}
	
}
