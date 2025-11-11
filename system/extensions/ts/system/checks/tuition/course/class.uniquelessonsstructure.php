<?php

/**
 * Schreibt alle Lektionen in eine einheitliche Struktur um nicht zwei Felder zu haben die je nach Kurstyp benutzt
 * werden. `lessons_per_week` => DEPRECATED
 */
class Ext_TS_System_Checks_Tuition_Course_UniqueLessonsStructure extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Course Lessons';
	}

	public function getDescription()
	{
		return 'Prepare course lessons structure';
	}

	public function executeCheck()
	{
		if ((int)\System::d('fix_unique_lessons_structure') === 1) {
			// Bereits durchgelaufen
			return true;
		}

		\Util::backupTable('kolumbus_tuition_courses');

		\DB::begin(__CLASS__);

		$sql = "
			SELECT
				`id`
			FROM
			   `kolumbus_tuition_courses`
			WHERE
			    `active` = 1 AND
			    `per_unit` IN (:course_types) AND
			    `lessons_per_week` > 0 AND
			    (`lessons_list` IS NULL OR `lessons_list` = '[]')
		";

		$courses = (array)DB::getQueryCol($sql, [
			'course_types' => [
				\Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
				\Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
				\Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
			]
		]);

		foreach ($courses as $courseId) {
			\DB::executePreparedQuery("
				UPDATE
					`kolumbus_tuition_courses`
				SET
				    `changed` = `changed`,
				    `lessons_list` = CONCAT('[', `lessons_per_week`, ']')
				WHERE
				    `id` = :course_id
			", ['course_id' => $courseId]);

			$this->logInfo('Migrated lessons of course '.$courseId);
		}

		// Alle Lektionskurse die in einem Kombinationskurs verwendet und selber nicht direkt gebucht wurden auf
		// "Pro Woche" umstellen (alte Struktur)
		$lessonCoursesIds = $this->getLessonCoursesUsedInProgram();

		foreach ($lessonCoursesIds as $lessonCourseId) {
			$update = "
				UPDATE
					`kolumbus_tuition_courses`
				SET
				    `changed` = `changed`,
				    `lessons_unit` = :per_week
				WHERE 
				    `id` = :course_id
			";

			\DB::executePreparedQuery($update, [
				'per_week' => \TsTuition\Enums\LessonsUnit::PER_WEEK->value,
				'course_id' => $lessonCourseId
			]);
		}

		\DB::commit(__CLASS__);

		\DB::executeQuery("ALTER TABLE `kolumbus_tuition_courses` CHANGE `lessons_per_week` `lessons_per_week` FLOAT(11,2) NOT NULL DEFAULT '0.00' COMMENT 'deprecated'");

		\System::s('fix_unique_lessons_structure', 1);

		// Lektionskontingente aktualisieren
		$check2 = new \Ext_TS_System_Checks_Inquiry_Journey_CourseLessonContingents();
		return $check2->executeCheck();
	}

	/**
	 * Alle Lektionskurse die in einem Kombinationskurs und nicht direkt selber gebucht wurden
	 *
	 * @return array
	 */
	private function getLessonCoursesUsedInProgram(): array
	{
		$sql = "
			SELECT
				`ktc`.`id`
			FROM
				`kolumbus_tuition_courses` `ktc` INNER JOIN
				/* Als Kombinationskurs benutzt und gebucht */
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`type` = 'course' AND
					`ts_tcps`.`type_id` = `ktc`.`id` AND
					`ts_tcps`.`active` = 1 INNER JOIN
				`ts_tuition_courses_programs` `ts_tcp` ON
					`ts_tcp`.`id` = `ts_tcps`.`program_id` AND
					`ts_tcp`.`course_id` !=  `ktc`.`id` AND
					`ts_tcp`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc_combi` ON
				    `ts_ijc_combi`.`course_id` = `ts_tcp`.`course_id` AND
				    `ts_ijc_combi`.`active` = 1 LEFT JOIN
				/* Lektionskurs direkt gebucht */
				`ts_inquiries_journeys_courses` `ts_ijc_direct` ON
				    `ts_ijc_direct`.`course_id` = `ktc`.`id` AND
				    `ts_ijc_direct`.`active` = 1
			WHERE
				`ktc`.`per_unit` = 1 AND
				`ktc`.`active` = 1 AND
				`ktc`.`lessons_unit` != :per_week AND
				`ts_ijc_direct`.`id` IS NULL
			GROUP BY
			    `ktc`.`id`
		";

		return (array)\DB::getQueryCol($sql, ['per_week' => \TsTuition\Enums\LessonsUnit::PER_WEEK->value]);
	}

}