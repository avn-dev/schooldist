<?php

namespace TsActivities\Entity\Activity;

class Validity extends \Ext_TC_Validity {

	protected $_sTable = 'ts_activities_validities';

	protected $_sTableAlias = 'ts_actv';

	public $sParentColumn = 'activity_id';

	public $bCheckItemId = false;

}
