<?php

class Ext_Thebing_Gui2_Format_Contract_DateUser extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		if(empty($mValue)){
			return '';
		}

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
		$oFormatName = new Ext_Gui2_View_Format_UserName(true);

		$aCopy = $aResultData;
		unset($aCopy['firstname']);
		unset($aCopy['lastname']);

		$sDate = $oFormatDate->format($aResultData[$oColumn->db_column], $oColumn, $aCopy);
		$sName = $oFormatName->format($aResultData[$oColumn->db_column.'_by'], $oColumn, $aCopy);

		$mValue = '';

		if(!empty($sDate)) {
			$mValue .= $sDate;
		}

		if(
			!empty($sName) &&
			!empty($sDate)
		) {
			$mValue .= ', ';
		}

		if(!empty($sName)) {
			$mValue .= $sName;
		}

		return $mValue;

	}

}
