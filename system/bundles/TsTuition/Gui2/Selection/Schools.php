<?php
namespace TsTuition\Gui2\Selection;

class Schools extends \Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array|\TcComplaints\Entity\SubCategory[]
	 * @throws \Exception
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = array();

		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$oTeacher = $oWDBasic->getJoinedObject('teacher');

		$aSchools = array_intersect_key($aSchools, array_flip($oTeacher->schools));
		
		$aSchools = \Ext_Thebing_Util::addEmptyItem($aSchools);
		
		return $aSchools;
	}

}