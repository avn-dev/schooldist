<?php

class Ext_TS_System_Checks_Inquiry_Journey_CourseLessonContingents2 extends GlobalChecks
{
	public function getTitle()
	{
		return 'Lessons Contingents Fix';
	}

	public function getDescription()
	{
		return 'Maintenance for lessons contingents.';
	}

	public function executeCheck()
	{
		$db = DB::getDefaultConnection()->getDBName();

		$sql = "
			SELECT DISTINCT
			    INDEX_NAME,
    			GROUP_CONCAT(COLUMN_NAME) columns
			FROM
				INFORMATION_SCHEMA.STATISTICS
			WHERE 
			    TABLE_SCHEMA = '$db' AND 
			    TABLE_NAME = 'ts_inquiries_journeys_courses_lessons_contingent' AND
			    NON_UNIQUE = 1
			GROUP BY 
			    INDEX_NAME
			HAVING
			    columns = 'program_service_id,journey_course_id' OR
			    columns = 'journey_course_id,program_service_id'
		";

		$indexes = DB::getQueryRows($sql);

		if (empty($indexes)) {
			return true;
		}

		Util::backupTable('ts_inquiries_journeys_courses_lessons_contingent');

		$this->removeDuplicates();

		foreach ($indexes as $index) {
			DB::executeQuery("ALTER TABLE ts_inquiries_journeys_courses_lessons_contingent DROP INDEX ".$index['INDEX_NAME']);
			$this->logInfo('Removed index: '.$index['INDEX_NAME']);
		}

		DB::executeQuery("ALTER TABLE ts_inquiries_journeys_courses_lessons_contingent ADD UNIQUE(journey_course_id, program_service_id)");

		return true;
	}

	private function removeDuplicates()
	{
		$sql = "
			SELECT 
			    COUNT(*) count, 
			    GROUP_CONCAT(id) ids, 
			    CONCAT(journey_course_id, '_', program_service_id) group_key 
			FROM 
			    ts_inquiries_journeys_courses_lessons_contingent
			GROUP BY 
			    group_key 
			HAVING 
			    count > 1
		";

		$rows = DB::getQueryRows($sql);

		foreach ($rows as $row) {
			$ids = explode(',', $row['ids']);
			array_shift($ids);

			foreach ($ids as $id) {
				DB::executeQuery("DELETE FROM ts_inquiries_journeys_courses_lessons_contingent WHERE id = ".$id);
				$this->logInfo('Removed duplicate row: '.$row['group_key']);
			}
		}
	}
}