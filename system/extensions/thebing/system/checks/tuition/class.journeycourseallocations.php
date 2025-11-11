<?php


class Ext_Thebing_System_Checks_Tuition_JourneyCourseAllocations extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Journey Course Allocations';
	}
	
	public function getDescription()
	{
		return 'Check for not compatible allocations between tuition and journey course.';
	}
	
	public function executeCheck()
	{
		Util::backupTable('kolumbus_tuition_blocks_inquiries_courses');
		
		$sSql = "
			SELECT
				`ts_i_j`.`id`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_i_j` ON
					`ts_i_j`.`id` = `ktbic`.`inquiry_course_id` AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_i_j`.`course_id` AND
					`ktc`.`active` = 1 AND
					`ktc`.`combination` = 0
			WHERE
				`ktbic`.`active` = 1 AND
				`ktbic`.`course_id` != `ts_i_j`.`course_id`
			GROUP BY
				`ts_i_j`.`id`
		";
		
		$aResult = (array)DB::getQueryCol($sSql);
		
		foreach($aResult as $iJourneyCourseId)
		{
			$oJourneyCourse = new Ext_TS_Inquiry_Journey_Course($iJourneyCourseId);
			
			$oJourneyCourse->checkCourseAllocation();
		}

		return true;
	}
}