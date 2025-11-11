<?php

namespace Ts\Entity\AccommodationProvider\Payment\Category;

class Validity extends \Ext_TC_Validity {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accommodation_providers_payment_categories_validity';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_appcv';

	/**
	 * @var string
	 */
	public $sParentColumn = 'provider_id';

	/**
	 * @var string
	 */
	public $sItemColumn	= 'category_id';

	/**
	 * @var bool
	 */
	public $bCheckItemId = false;

}