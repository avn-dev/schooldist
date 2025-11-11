<?php

class Ext_TS_System_Checks_Tuition_ClassLessonDuration extends GlobalChecks
{
	public function getTitle()
	{
		return 'Classes';
	}

	public function getDescription()
	{
		return 'Prefills class lesson duration with first assigned course';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		if (!\Util::backupTable('kolumbus_tuition_classes')) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			$classes = $this->getClassesWithoutLessonDuration();

			foreach ($classes as $classId) {

				$lessonDuration = $this->getFirstCourseDuration((int)$classId);

				$update = "
					UPDATE
						`kolumbus_tuition_classes`
					SET
					    `changed` = `changed`,
					    `lesson_duration` = :lesson_duration
					WHERE
					    `id` = :class_id
				";

				\DB::executePreparedQuery($update, [
					'lesson_duration' => $lessonDuration,
					'class_id' => $classId
				]);

			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	private function getClassesWithoutLessonDuration(): array
	{
		$sql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_classes`
			WHERE
			    `active` = 1 AND
			    `lesson_duration` IS NULL
		";

		return (array)\DB::getQueryCol($sql);
	}

	private function getFirstCourseDuration(int $classId): string
	{
		$sql = "
			SELECT
				`ts_tc`.`lesson_duration`
			FROM
			    `kolumbus_tuition_classes_courses` `ts_tc_c` INNER JOIN
				`kolumbus_tuition_courses` `ts_tc` ON 
					`ts_tc`.`id` = `ts_tc_c`.`course_id` AND
					`ts_tc`.`active` = 1
			WHERE
			    `ts_tc_c`.`class_id` = :class_id
			LIMIT 1
		";

		return (string)\DB::getQueryOne($sql, ['class_id' => $classId]);
	}
}