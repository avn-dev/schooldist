<?php

class Ext_TS_System_Checks_Tuition_Course_ProgramStructure2 extends \GlobalChecks {

	public function getTitle() {
		return 'Course programs 2';
	}

	public function getDescription() {
		return 'Fix some possible errors.';
	}

	public function executeCheck() {

		Util::backupTable('ts_inquiries_journeys_courses');
		Util::backupTable('ts_tuition_courses_programs');
		Util::backupTable('ts_tuition_courses_programs_services');

		DB::begin(__CLASS__);

		$sql = "
			SELECT
			    GROUP_CONCAT(ts_tcp.id) program_ids,
			    ts_tcp.course_id course_id,
				COUNT(ts_tcp.id) count
			FROM
				ts_tuition_courses_programs ts_tcp INNER JOIN
				kolumbus_tuition_courses ktc ON
				    ktc.id = ts_tcp.course_id AND
				    ktc.per_unit NOT IN (3, 5)
			WHERE
			    ts_tcp.active = 1
			GROUP BY
			    ts_tcp.course_id
			HAVING
			    count > 1
		";

		$rows = (array)DB::getQueryRows($sql);

		$this->logInfo('Wrong courses', $rows);

		foreach ($rows as $row) {

			$rows2 = (array)DB::getQueryRows("
				SELECT
					ts_tcp.id program_id,
					ts_tcps.id program_service_id,
					ts_tcps.type_id
				FROM
				    ts_tuition_courses_programs ts_tcp INNER JOIN
				    ts_tuition_courses_programs_services ts_tcps ON
				        ts_tcps.program_id = ts_tcp.id AND
				        ts_tcps.active = 1 AND
				        ts_tcps.type = 'course'
				WHERE
				    ts_tcp.course_id = :course_id AND
				    ts_tcp.active = 1
			", $row);

			$this->logInfo('Programs for course '.$row['course_id'], $rows);

			$validProgramId = null;
			foreach ($rows2 as $row2) {
				if ($row2['type_id'] == $row['course_id']) {
					$validProgramId = $row2['program_id'];
					break;
				}
			}
			
			if(empty($validProgramId)) {
				throw new RuntimeException('No valid program id for course "'.(int)$row['course_id'].'" found!');
			}

			DB::executePreparedQuery("UPDATE ts_inquiries_journeys_courses SET changed = changed, program_id = :program_id WHERE course_id = :course_id", ['program_id'=>$validProgramId, 'course_id'=>$row['course_id']]);
			
			foreach ($rows2 as $row2) {
				
				// Kurse diesen Typs dürfen nur ein Programm haben, das auf sich selber verweist
				if ($row2['type_id'] != $row['course_id']) {

					DB::executePreparedQuery("UPDATE ts_tuition_courses_programs SET active = 0 WHERE id = :program_id", $row2);
					DB::executePreparedQuery("UPDATE ts_tuition_courses_programs_services SET active = 0 WHERE id = :program_service_id", $row2);

					$this->logInfo(sprintf('Deleted program %d with service %d', $row2['program_id'], $row2['program_service_id']));
				}
				
			}

		}

		DB::commit(__CLASS__);

		return true;

	}

}
