<?php

namespace TsComplaints\Gui2\Format;

class ValidUntil extends \Ext_Gui2_View_Format_Abstract {

	/**
	 * Formatiert die Spalte valid_until
	 *
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sFormatedDate = '';

		if($aResultData['valid_until'] !== '0') {

			$oDate = new \Ext_Thebing_Gui2_Format_Date();
			$sFormatedDate = $oDate->format($aResultData['valid_until']);

		}

		return $sFormatedDate;

	}

}