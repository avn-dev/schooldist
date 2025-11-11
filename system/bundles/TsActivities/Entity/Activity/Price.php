<?php

namespace TsActivities\Entity\Activity;

/**
 * @property float $price
 * @property int $school_id
 * @property int $activity_id
 * @property string $currency_iso
 */
class Price extends \Ext_Thebing_Basic{

	protected $_sTable = 'ts_activities_prices';

	protected $_sEditorIdColumn = 'changed_by';

	protected $_sTableAlias = 'ts_actpr';


}
