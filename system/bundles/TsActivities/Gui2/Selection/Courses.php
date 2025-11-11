<?php

namespace TsActivities\Gui2\Selection;

use Core\Helper\DateTime;

/**
 * @property \TsActivities\Entity\Activity\ActivitySchool $oJoinedObject
 */
class Courses extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = [];

		$iSchoolId = $this->oJoinedObject->school_id;

		if($iSchoolId > 0) {
			/** @var \Ext_Thebing_Tuition_Course[] $aCourses */
			$aCourses = \Ext_Thebing_Tuition_Course::getRepository()->findBy([
				'school_id' => $iSchoolId
			]);

			foreach ($aCourses as $oCourse) {
				if(
					// Repo kann kein valid_until
					!\Core\Helper\DateTime::isDate($oCourse->valid_until, 'Y-m-d') ||
					new \DateTime($oCourse->valid_until) > new DateTime()
				) {
					$aOptions[$oCourse->id] = $oCourse->getName();
				}
			}
		}

		return $aOptions;

	}

}