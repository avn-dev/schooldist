<?php

/**
 * Ungültige Zuweisungen in der Klassenplanung finden und löschen
 *
 * https://redmine.thebing.com/redmine/issues/9886
 */
class Ext_Thebing_System_Checks_Tuition_InvalidAllocations extends GlobalChecks {

	public function getTitle() {
		return 'Check Tuition Allocations';
	}

	public function getDescription() {
		return 'Check for invalid tuition allocations.';
	}

	public function executeCheck() {

		set_time_limit(60);
		ini_set('memory_limit', '1G');

		Util::backupTable('kolumbus_tuition_blocks_inquiries_courses');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`block_id`,
				`inquiry_course_id`,
				GROUP_CONCAT(`id`) `allocation_ids`,
				GROUP_CONCAT(`course_id`) `course_ids`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses`
			WHERE
				`active` = 1
			GROUP BY
				`block_id`, `inquiry_course_id`
			HAVING
				COUNT(`course_id`) > 1
		";

		$aResult = (array)DB::getQueryRows($sSql);
		foreach($aResult as $aRow) {

			$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aRow['inquiry_course_id']);
			$oCourse = $oJourneyCourse->getCourse();
			$aChildCourses = $oCourse->getChildCoursesOrSameCourse();
			$aAllocationIds = explode(',', $aRow['allocation_ids']);
			$aCourseIds = explode(',', $aRow['course_ids']);

			foreach($aAllocationIds as $iKey => $iAllocationId) {
				$bCourseFound = false;
				foreach($aChildCourses as $oChildCourse) {
					if($oChildCourse->id == $aCourseIds[$iKey]) {
						$bCourseFound = true;
						break;
					}
				}

				if(!$bCourseFound) {
					$this->logInfo('Invalid tuition allocation: '.$iAllocationId);
					$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($iAllocationId);
					$oAllocation->delete();
				}
			}
		}

		DB::commit(__CLASS__);

		\Core\Facade\SequentialProcessing::execute();

		return true;

	}

}
