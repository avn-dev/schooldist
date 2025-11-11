<?php

namespace TcComplaints\Gui2\Format;

use \Ext_Gui2_View_Format_Abstract;
use \Ext_TC_Factory;

class DependencyOnFormat extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturn = '';
		$aDependencyTypes = (array)Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getAreas');

		if(isset($aDependencyTypes[$mValue])) {
			$sReturn = $aDependencyTypes[$mValue];
		}

		return $sReturn;
	}

}