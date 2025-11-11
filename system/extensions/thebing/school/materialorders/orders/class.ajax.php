<?php

class Ext_Thebing_School_Materialorders_Orders_Ajax extends GUI_Ajax_Table {
	
	public function saveRowData() {

		$aBack = parent::saveRowData();

		return $aBack;

	}

	public function convertDateToTimestamp($sDate){
		
			return Ext_Thebing_Format::ConvertDate($sDate);		
	}
	
	public function getTableListData() {
		
		$aTableData = parent::getTableListData();

		foreach((array)$aTableData['data'] as $iKey=>$aItem) {

			$oOrder = new Ext_Thebing_Agency_Materialorder($aItem[0]);

			if($oOrder->sent_date != 0) {
				$aTableData['data'][$iKey][1] = false;
			}
			
			$aTableData['data'][$iKey][6] = $oOrder->getMaterialString();
			
			$aTableData['icon'][(string)$aItem[0]][] = 'view_order';

			if($oOrder->sent_date == 0) {
				$aTableData['icon'][(string)$aItem[0]][] = 'mark_sent';
			}
			
			$aTableData['icon'][(string)$aItem[0]][] = 'write_cover_letter';

			$aCoverLetter = $oOrder->getCoverLetter();
			
			if(!empty($aCoverLetter['txt_subject'])) {
				$aTableData['icon'][(string)$aItem[0]][] = 'view_cover_letter';
			}
			
		}

		return $aTableData;
		
	}
	
}

function thebing_mo_printCheckbox($mValue) {

	if($mValue) {
		$mValue = '<input type="checkbox" name="orders[]" class="orders" value="'.(int)$mValue.'" />';
	}
	
	return $mValue;
	
}