<?php

namespace Ts\Entity\Additionalcost;

class Validity extends \Ext_TC_Validity {

	protected $_sTable = 'ts_costs_validities';

	protected $_sTableAlias = 'ts_cv';

	public $sParentColumn = 'cost_id';

	public $bCheckItemId = false;

}
