<?php

class Ext_Thebing_Cancellation_Group extends Ext_TC_Cancellationconditions_Group {

	protected $_sTableAlias = 'kcg';

	protected $_aJoinTables = array(
		'validity_jointables'=>array(
			'table'=>'kolumbus_validity',
			'primary_key_field'=>'item_id',
			'static_key_fields'=> array('item_type' => 'cancellation_group'),
			'delete_check'=>true,
			'autoload'=>false,
			'check_active'=> true
		)
	);

	protected $_aAttributes = [
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	/**
	 *
	 * @param <mixed> $mPrepareForSelect | 'dialog','dropdown'
	 * @return <array>
	 */
	public function getList($mPrepareForSelect=false)
	{
		$query = self::query();

		if($mPrepareForSelect == 'dropdown') {
			$list = $query->pluck('name', 'id');
		} else if($mPrepareForSelect == 'dialog') {
			$list = $query->get()
				->map(fn (Ext_Thebing_Cancellation_Group $group) => ['value' => $group->getId(), 'text' => $group->name]);
		} else {
			$list = $query->get()
				->map(fn (Ext_Thebing_Cancellation_Group $group) => $group->getData());
		}

		return $list->toArray();
	}

	public function getValidity(string $customerType, int $objectId)
	{
		$parentType = ($customerType === 'agency_customer') ? 'agency' : 'school';
		$validity = Ext_Thebing_Validity_WDBasic::getValidity($parentType, $objectId, 'cancellation_group');
		return $validity;
	}

}
