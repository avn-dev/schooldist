<?php

class Ext_TS_System_Checks_Tuition_Course_LessonPackets extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Course Lesson Packets';
	}

	public function getDescription()
	{
		return 'Allow courses to have a fixed selection of lesson amounts.';
	}

	public function executeCheck()
	{
		\Util::backupTable('kolumbus_tuition_courses');
		\Util::backupTable('wdbasic_attributes');

		DB::begin(__CLASS__);

		$sql = "
			SELECT
				ktc.id
			FROM
			    kolumbus_tuition_courses ktc INNER JOIN
				wdbasic_attributes wa ON
					wa.entity = 'kolumbus_tuition_courses' AND
					wa.entity_id = ktc.id AND
					wa.key = 'lessons_fix' AND
					wa.value = 1
			WHERE
			    ktc.active = 1 AND
			    ktc.lessons_list IS NULL
		";

		foreach ((array)DB::getQueryCol($sql) as $courseId) {
			DB::executePreparedQuery("
				UPDATE
					kolumbus_tuition_courses
				SET
				    lessons_fix = 1,
				    lessons_list = CONCAT('[', lessons_per_week, ']'),
				    changed = changed
				WHERE
				    id = :course_id
			", ['course_id' => $courseId]);

			$this->logInfo('Migrated lessons_fix of course '.$courseId);
		}

		DB::executeQuery("
			DELETE FROM
				wdbasic_attributes
			WHERE
				entity = 'kolumbus_tuition_courses' AND
				`key` = 'lessons_fix'
		");

		DB::commit(__CLASS__);

		return true;
	}
}