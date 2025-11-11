<?php

class Ext_Thebing_System_Checks_Tuition_MigrateCombinationCourseSettings extends GlobalChecks {

	public function getTitle() {
		return 'Migration of combination course settings';
	}

	public function getDescription() {
		return 'Prepare database structure to include preparation course settings for examination courses, for frontend registration forms.';
	}

	public function executeCheck() {

		if (!in_array('kolumbus_course_combination', DB::listTables())) {
			return true;
		}

		Util::backupTable('kolumbus_course_combination');

		DB::executeQuery("
			INSERT INTO
				ts_tuition_courses_to_courses
			SELECT
				master_id,
				course_id,
			   'combination' type
			FROM
				kolumbus_course_combination kcc INNER JOIN
				kolumbus_tuition_courses ktc1 ON	
					ktc1.id = master_id AND
					ktc1.active = 1 INNER JOIN
				kolumbus_tuition_courses ktc2 ON
					ktc2.id = course_id AND
					ktc2.active = 1
		");

		DB::executeQuery("DROP TABLE kolumbus_course_combination");

		return true;

	}

}
