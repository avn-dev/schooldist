<?php
namespace TsTuition\Gui2\Selection;

class CostCategories extends \Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array|\TcComplaints\Entity\SubCategory[]
	 * @throws \Exception
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = array();

		$iSchoolId = $oWDBasic->school_id;

		if(!empty($iSchoolId)) {
		
			$oSchool = \Ext_Thebing_School::getInstance($iSchoolId);
			
			$aCostcategories = \Ext_Thebing_Marketing_Costcategories::getTeacherCategories(true, $oSchool);

			$aFirst = array('-1'=>$this->_oGui->t('Festgehalt'));
			$aCostcategories = (array)$aFirst + (array)$aCostcategories;
			$aOptions = \Ext_Thebing_Util::addEmptyItem($aCostcategories);

			asort($aOptions);
		}
		
		return $aOptions;
	}

}