<?php

class Ext_TS_System_Checks_Tuition_Attendance_AttendanceLessonDuration extends GlobalChecks
{
	public function getTitle()
	{
		return 'Attendance';
	}

	public function getDescription()
	{
		return 'Matches attendance entries from course lesson duration to class lesson duration';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		if ((int)\System::d('check_attendance_course_lesson_duration', 0) === 1) {
			// Check bereits durchgelaufen
			return true;
		}

		$backup = [
			\Util::backupTable('kolumbus_tuition_blocks_inquiries_courses'),
			\Util::backupTable('kolumbus_tuition_attendance'),
		];
		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		$allocations = $this->getAllocations();
		$attendances = $this->getAttendanceEntries();

		$processes = array_merge($allocations, $attendances);

		foreach ($processes as $process) {
			$this->addProcess($process, 200);
		}

		\System::s('check_attendance_course_lesson_duration', 1);

		return true;
	}

	public function executeProcess(array $data)
	{
		$entity = null;
		$updateColumns = [];

		if (isset($data['allocation_id'])) {
			/* @var \Ext_Thebing_School_Tuition_Allocation $allocation */
			$entity = \Ext_Thebing_School_Tuition_Allocation::getInstance($data['allocation_id']);

			if ($entity->exist()) {
				$entity->recalculateLessonsDuration();

				$updateColumns = ['lesson_duration'];
			}

		} else if (isset($data['attendance_id'])) {

			// Bisher basierte die Lektionsdauer immer auf dem zugewiesenen Kurs
			$courseLessonDuration = $this->getLessonDuration('kolumbus_tuition_courses', $data['course_id']);
			$classLessonDuration = $this->getLessonDuration('kolumbus_tuition_classes', $data['class_id']);

			if (bccomp($courseLessonDuration, $classLessonDuration, 2) !== 0) {
				/* @var \Ext_Thebing_Tuition_Attendance $entity */
				$entity = \Ext_Thebing_Tuition_Attendance::getInstance($data['attendance_id']);

				if ($entity->exist()) {

					$block = $entity->getAllocation()?->getBlock();

					if ($block && $block->exist()) {
						// Die aktuelle volle Dauer basierend auf der Lektionsdauer der Klasse
						$classFullDuration = $block->getLessonDuration();
						$change = true;

						// Sicherheitsabfrage! Prüfen ob die Abwesenheit nicht doch schon auf der Lektionsdauer der Klasse basiert
						foreach (\Ext_Thebing_Tuition_Attendance::DAY_MAPPING as $day) {

							$currentValue = floatval($entity->$day);

							if (bccomp($currentValue, 0, 2) === 0) {
								// Keine Abwesenheit eingetragen
								continue;
							}

							if (bccomp($currentValue, $classFullDuration, 2) === 0) {
								// In diesem Fall gehen wir davon aus dass die aktuell eingetragene Abwesenheit schon auf
								// auf der Lektionsdauer der Klasse basiert und nicht mehr auf dem zugewiesenen Kurs
								$change = false;
								break;
							}
						}

						if ($change) {
							$entity->recalculateAbsence($courseLessonDuration);
							$entity->refreshIndex();

							$updateColumns = [
								...array_values(\Ext_Thebing_Tuition_Attendance::DAY_MAPPING),
								...['duration', 'attended']
							];
						}
					}
				}
			}
		}

		if ($entity && !empty($updateColumns)) {
			foreach ($updateColumns as $column) {
				if ($entity->getData($column) != $entity->getOriginalData($column)) {
					$sql = "
							UPDATE 
								#table
							SET
								`changed` = `changed`,
								#column = :value
							WHERE
								`id` = :id
						";

					$this->logInfo('Update column', ['table' => $entity->getTableName(), 'id' => $entity->id, 'column' => $column, 'value' => $entity->getData($column), 'original_value' => $entity->getOriginalData($column)]);

					\DB::executePreparedQuery($sql, [
						'id' => $entity->id,
						'table' => $entity->getTableName(),
						'column' => $column,
						'value' => $entity->getData($column)
					]);
				}
			}
		}

	}

	private function getAllocations(): array
	{
		$sql = "
			SELECT
				`id` `allocation_id`
			FROM
			    `kolumbus_tuition_blocks_inquiries_courses`
			WHERE
			    `active` = 1 AND
			    `lesson_duration` > 0  
		";

		return (array)\DB::getQueryRows($sql);
	}

	private function getAttendanceEntries(): array
	{
		$days = array_map(
			fn ($day) => sprintf('`kta`.`%s` > 0', $day),
			Ext_Thebing_Tuition_Attendance::DAY_MAPPING
		);

		$sql = "
			SELECT
				`kta`.`id` `attendance_id`,
				`kta`.`course_id`,
				`ktb`.`class_id`
			FROM
			    `kolumbus_tuition_attendance` `kta` INNER JOIN 
			    `kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
			    	`ktbic`.`id` = `kta`.`allocation_id` AND
			    	`ktbic`.`active` = 1 INNER JOIN 
			    `kolumbus_tuition_blocks` `ktb` ON
			    	`ktb`.`id` = `ktbic`.`block_id` AND
			    	`ktb`.`active` = 1
			WHERE
			    `kta`.`active` = 1 AND
			    (
			        ".implode(' OR ', $days)."
			    )
		";

		return (array)\DB::getQueryRows($sql);
	}

	private function getLessonDuration(string $table, int $id): float {

		$sql = "
			SELECT
				`lesson_duration`
			FROM
			    #table
			WHERE
			    `id` = :id
		";

		return floatVal(\DB::getQueryOne($sql, ['table' => $table, 'id' => $id]));
	}

}