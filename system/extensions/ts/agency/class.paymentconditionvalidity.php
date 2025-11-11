<?php

class Ext_TS_Agency_PaymentConditionValidity extends Ext_TC_Validity {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_agencies_payment_conditions_validity';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_apcv';

	/**
	 * @var string
	 */
	public $sParentColumn = 'agency_id';

	/**
	 * @var string
	 */
	public $sItemColumn	= 'payment_condition_id';

	/**
	 * @var string
	 */
	public $sDependencyColumn = 'school_id';
	
	/**
	 * @var bool
	 */
	public $bCheckItemId = false;

	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_TS_Payment_Condition
	 */
	public function getItem() {
		return Ext_TS_Payment_Condition::getInstance($this->payment_condition_id);
	}
	
	/**
	 * Analog zu Ext_TC_Validity
	 * @return Ext_Thebing_Agency
	 */
	public function getParent() {
		return Ext_Thebing_Agency::getInstance($this->agency_id);
	}
	
}
