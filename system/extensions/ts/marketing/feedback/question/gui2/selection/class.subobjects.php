<?php

class Ext_TS_Marketing_Feedback_Question_Gui2_Selection_SubObjects extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aReturn = [];

		if($oWDBasic instanceof Ext_TS_Marketing_Feedback_Question) {

			$sType = $oWDBasic->dependency_on;
			$aSchoolIds = (array)$oWDBasic->dependency_objects;

			foreach($aSchoolIds as $iSchoolId) {

				$aList = [];
				$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
				
				switch($sType) {
					case 'course':
					case 'teacher_course':
						$aList = $oSchool->getCourseList(true);
						break;
					case 'course_category':
						$aList = $oSchool->getCourseCategoriesList('select');
						break;
					case 'accommodation_provider':
						$aList = $oSchool->getAccommodationProvider(true);
						break;
					case 'accommodation_category':
						$aList = $oSchool->getAccommodationCategoriesList(true);
						break;
					case 'meal':
						$aList = $oSchool->getMealList(true);
						break;
					case 'rooms':
						$aList = $oSchool->getRoomtypeList(true);
						break;
				}

				foreach($aList as $sId => $sValue) {
					$aReturn[$sId] = $oSchool->short . ' - ' . $sValue;
				}

			}

		}

		return $aReturn;
	}

}