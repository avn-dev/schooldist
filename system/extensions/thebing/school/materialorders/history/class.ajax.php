<?php

class Ext_Thebing_School_Materialorders_History_Ajax extends GUI_Ajax_Table {
	
	public function saveRowData() {

		$aBack = parent::saveRowData();

		return $aBack;

	}
	
	public function convertDateToTimestamp($sDate){
		
			return Ext_Thebing_Format::ConvertDate($sDate);		
	}
	
	public function getTableListData() {
		
		
		
		$aTableData = parent::getTableListData();

		foreach((array)$aTableData['data'] as $aItem) {

			

		}

		return $aTableData;
		
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