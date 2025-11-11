<?php

namespace TsSponsoring\Entity;

class PaymenttermsValidity extends \Ext_TC_Validity {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_sponsors_payment_conditions_validity';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_spcv';

	/**
	 * @var string
	 */
	public $sParentColumn = 'sponsor_id';

	/**
	 * @var string
	 */
	public $sItemColumn	= 'payment_condition_id';

	/**
	 * @var bool
	 */
	public $bCheckItemId = false;

}