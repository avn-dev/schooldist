<?php

/**
 * Class Ext_TS_Accounting_Provider_Grouping_Accommodation_History
 * @property int $id
 * @property string $created
 * @property string $changed
 * @property string $creator_id
 * @property int $editor_id
 * @property int $active 
 */
class Ext_TS_Accounting_Provider_Grouping_Accommodation_History extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_accommodations_payments_groupings_histories';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'ts_apgh';

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * @return array
	 */
	public static function getOrderby() {
		return ['id' => 'ASC'];
	}

}