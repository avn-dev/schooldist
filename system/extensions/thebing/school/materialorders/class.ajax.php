<?php

class Ext_Thebing_School_Materialorders_Ajax extends GUI_Ajax_Table {
	
	public function saveRowData() {

		$aBack = parent::saveRowData();

		$oItem = new Ext_Thebing_School_Materialorders_Item((int)$aBack[0]);
		$oItem->school_id = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$oItem->save();

		return $aBack;

	}
	
	public function getTableListData() {
		
		$aTableData = parent::getTableListData();

		foreach((array)$aTableData['data'] as $aItem) {

			$aTableData['icon'][(string)$aItem[0]][] = 'orderable_active';
			$aTableData['icon'][(string)$aItem[0]][] = 'orderable_inactive';

		}

		return $aTableData;
		
	}
	
	public function switchOrderable($iRowId, $bValue) {
		
		$oItem = new Ext_Thebing_School_Materialorders_Item((int)$iRowId);
		$oItem->orderable = (int)$bValue;
		$oItem->save();
		
	}
	
}

function thebing_mo_printOrderable($mValue) {

	if($mValue == 1) {
		$mValue = L10N::t('Ja');
	} else {
		$mValue = L10N::t('Nein');
	}
	
	return $mValue;
	
}