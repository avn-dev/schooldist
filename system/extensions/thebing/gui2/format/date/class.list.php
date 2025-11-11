<?php

class Ext_Thebing_Gui2_Format_Date_List extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aValue = explode(',', $mValue);
		$mBack = '';
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
		foreach((array)$aValue as $iValue){
			if(
				$oColumn->select_column == 'transfer_date' ||
				$oColumn->select_column == 'acc_time_from_fulllist' ||
				$oColumn->select_column == 'acc_time_to_fulllist'
			) {
				$mBack .= $oDateFormat->format($iValue);
			}else{
				$mBack .= Ext_Thebing_Format::LocalDate($iValue, (int)$aResultData['school_id']);
			}
			$mBack .= '<br/>';
		}

		$mBack = rtrim($mBack, '<br/>');

		return $mBack;
	}

}
