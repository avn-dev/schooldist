<?php

namespace TsSponsoring\Entity;

class CancellationValidity extends \Ext_TC_Validity {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_sponsors_cancellations_validity';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_scv';

	/**
	 * @var string
	 */
	public $sParentColumn = 'sponsor_id';

	/**
	 * @var string
	 */
	public $sItemColumn	= 'cancellation_id';

	/**
	 * @var bool
	 */
	public $bCheckItemId = false;

}