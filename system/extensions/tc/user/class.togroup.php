<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TC_User_ToGroup extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_system_user_to_groups';
	
	protected $_sTableAlias = 'tc_sutg';

	public function __get($sName) {
		
		if($sName == 'name'){
			$oFormat = new Ext_TC_User_Format_Group();
			$mValue = $oFormat->formatByValue($this->group_id);
		} else {
			$mValue = parent::__get($sName);
		}
		
		return $mValue;
	}

	public function save($bLog = true) {

		parent::save($bLog);
		
		Ext_TC_User::resetAccessCache();
		
		return $this;
	}
	
}