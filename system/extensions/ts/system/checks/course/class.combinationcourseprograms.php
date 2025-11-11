<?php

use Illuminate\Support\Str;

class Ext_TS_System_Checks_Course_CombinationCoursePrograms extends GlobalChecks {

	public function getTitle() {
		return 'Course programs: Check combination courses';
	}

	public function getDescription() {
		return 'Check for invalid combination course data which has been created by "Save as new entry".';
	}

	public function executeCheck() {

		Util::backupTable('ts_tuition_courses_programs');
		Util::backupTable('ts_tuition_courses_programs_services');

		DB::begin(__CLASS__);

		$sql = "
			SELECT
				ktc.id,
				COUNT(DISTINCT ts_tcp.id) count,
				GROUP_CONCAT(DISTINCT ts_tcp.id ORDER BY ts_tcp.id) program_ids,
				GROUP_CONCAT(CONCAT(ts_tcp.id, '_', ts_ijc.id)) program_courses
			FROM
				kolumbus_tuition_courses ktc INNER JOIN
				ts_tuition_courses_programs ts_tcp ON
				    ts_tcp.course_id = ktc.id LEFT JOIN
				ts_inquiries_journeys_courses ts_ijc ON
					ts_ijc.program_id = ts_tcp.id AND
					ts_ijc.active = 1
			WHERE
				ktc.per_unit = ".Ext_Thebing_Tuition_Course::TYPE_COMBINATION."
			GROUP BY
				ktc.id
			HAVING
				count > 1
		";

		$rows = (array)DB::getQueryRows($sql);

		foreach ($rows as $row) {
			$programIds = explode(',', $row['program_ids']);
			$keepId = array_shift($programIds); // Erstbestes Programm behalten (wurde immer nach from, also null, sortiert)

			// Prüfen, ob die zu löschenden Programme irgendwie verwendet werden, was nicht vorkommen sollte
			Str::of($row['program_courses'])
				->explode(',')
				->each(function (string $concat) use ($programIds) {
					[$programId, $journeyCourseId] = explode('_', $concat, 2);
					if (in_array($programId, $programIds)) {
						$msg = sprintf('Program %d is about to be deleted but in use by journey course %d', $programId, $journeyCourseId);
						$this->logError($msg);
						throw new \RuntimeException($msg);
					}
				});

			DB::executePreparedQuery("DELETE FROM ts_tuition_courses_programs WHERE id IN (:ids)", ['ids' => $programIds]);

			DB::executePreparedQuery("DELETE FROM ts_tuition_courses_programs_services WHERE program_id IN (:ids)", ['ids' => $programIds]);

			$this->logInfo(sprintf('Combination course %d: %d programs; kept program: %d, deleted program ids: %s', $row['id'], $row['count'], $keepId, join(',', $programIds)));
		}

		DB::commit(__CLASS__);

		return true;

	}

}