<?php

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $payment_condition_id
 * @property int $position
 * @property string $type (ENUM)
 * @property int $due_days
 * @property string $due_direction (ENUM)
 * @property string $due_type (ENUM)
 * @property string $installment_type (ENUM)
 * @property string $installment_split (ENUM)
 * @property int $installment_charging
 * @property array $amounts
 */
class Ext_TS_Payment_Condition_Setting extends Ext_TC_Basic {

	protected $_sTable = 'ts_payment_conditions_settings';

	protected $_aJoinTables = [
		'amounts' => [
	 		'table' => 'ts_payment_conditions_settings_amounts',
	 		'primary_key_field' => 'setting_id',
	 		'on_delete' => 'delete',
		]
	];
	
}
