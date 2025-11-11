<?php

namespace TcComplaints\Gui2\Format;

use \Ext_Gui2_View_Format_Abstract;
use \Ext_TC_Factory;
use TcComplaints\Entity\Category;

class DependencyPersonalNames extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturn = '';

		/** @var Category $oCategory */
		$oCategory = Category::getInstance($aResultData['category_id']);

		$aResultData['type'] = $oCategory->type;

		$aDependencyTypes = (array) Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getDependencies', array($mValue, $aResultData));

		if(isset($aDependencyTypes[$aResultData['type']])) {
			$sReturn = $aDependencyTypes[$aResultData['type']];
		}

		return $sReturn;
	}

}