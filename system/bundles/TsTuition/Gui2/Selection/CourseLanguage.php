<?php
namespace TsTuition\Gui2\Selection;

class CourseLanguage extends \Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array|\TcComplaints\Entity\SubCategory[]
	 * @throws \Exception
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$courseLanguageIds = [];

		$courses = $oWDBasic->getJoinTableObjects('courses');

		foreach($courses as $course) {
			if(empty($courseLanguageIds)) {
				$courseLanguageIds = $course->course_languages;
			} else {
				$courseLanguageIds = array_intersect($courseLanguageIds, $course->course_languages);
			}
		}
		
		$courseLanguages = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		
		$courseLanguages = array_intersect_key($courseLanguages, array_flip($courseLanguageIds));
		
		return $courseLanguages;
	}

}