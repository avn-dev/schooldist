<?php


class Ext_TS_Document_Release_Gui2_Format_Pdf extends Ext_Thebing_Gui2_Format_Pdf {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aResultData['pdf_path'] = $mValue;
		
		$mValue = parent::format($mValue, $oColumn, $aResultData);
		
		return $mValue;
	}
}