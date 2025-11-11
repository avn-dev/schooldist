<?php

class Ext_TS_Accounting_Payment_Format_Name extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

        $sValue = 'Export';

		if(!empty($mValue)) {
		    $sValue .= ': '.$mValue;
		}
		return $sValue;

	}

}
