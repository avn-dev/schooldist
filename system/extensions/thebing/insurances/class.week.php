<?php

class Ext_Thebing_Insurances_Week extends Ext_Thebing_Basic
{
	protected $_sTable = 'kolumbus_insurance_weeks';
	protected $_sTableAlias = 'kinsw';

	protected $_sEditorIdColumn = 'editor_id';

	protected static $sClassName = 'Ext_Thebing_Insurances_Week';

	public function getClassName() {
		return get_class($this);
	}

	public function save($bLog = true) {
		global $user_data;

		$this->client_id = (int)$user_data['client'];

		parent::save();

		return $this;
	}

}
