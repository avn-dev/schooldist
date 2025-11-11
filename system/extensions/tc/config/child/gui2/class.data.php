<?php

/**
 * @property Ext_TC_Config_Child_Entity $oWDBasic
 */
class Ext_TC_Config_Child_Gui2_Data extends Ext_TC_Gui2_Data {

	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit = false) {

		/** @var Ext_TC_Config_Child_Entity $object */
		$object = $this->getWDBasicObject([]);
		$entries = $object->findAll();

		return [
			'data' => array_map(function ($entry) {
				return $entry->getData();
			}, $entries)
		];

	}

}