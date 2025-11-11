<?php

namespace TcComplaints\Gui2\Format;

class ComplaintState extends \Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aDependencies = (array) \Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\ComplaintHistory', 'getState');

		if(isset($aDependencies[$aResultData['state']])) {
			$sReturn = $aDependencies[$aResultData['state']];
		}

		return $sReturn;

	}
}