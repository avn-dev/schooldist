<?php

/**
 * Bereitet die Programmstruktur für Kurse vor und legt für existierenden Kurse die entsprechenden Programme inkl. Programmleistung
 * an. Bei Kombinationskurse werden die Unterkurse als Programmleistung angelegt und bei den anderen Kursen wird der Kurs
 * selber als Programmleistung angelegt
 */
class Ext_TS_System_Checks_Tuition_Course_ProgramStructure3 extends \GlobalChecks {

	private static $tableCache = [];
	private static $cache = [];

	public function getTitle() {
		return 'Course programs';
	}

	public function getDescription() {
		return 'Fix program structure';
	}

	public function executeCheck() {

		$sql = "
			SELECT
				`ktc`.`id`,
				`ktc`.`combination_` `combination`
			FROM
				`kolumbus_tuition_courses` `ktc` LEFT JOIN
				`ts_tuition_courses_programs` `ts_tcp` ON 
					`ts_tcp`.`course_id` = `ktc`.`id`
			WHERE
				`ts_tcp`.`id` IS NULL
		";

		// Alle Kurse die noch kein Programm haben
		$coursesWithoutProgram = \DB::getQueryData($sql);

		if(empty($coursesWithoutProgram)) {
			return true;
		}

		ini_set("memory_limit", "2G");
		set_time_limit(7200);

		$backup = [
			\Util::backupTable('kolumbus_tuition_courses'),
			\Util::backupTable('ts_inquiries_journeys_courses'),
			\Util::backupTable('kolumbus_groups_courses'),
			\Util::backupTable('ts_tuition_courses_to_courses'),
			\Util::backupTable('kolumbus_tuition_blocks_inquiries_courses'),
			\Util::backupTable('ts_inquiries_journeys_courses_tuition_index'),
			\Util::backupTable('kolumbus_tuition_attendance'),
			\Util::backupTable('kolumbus_tuition_progress'),
			\Util::backupTable('kolumbus_examination'),
		];

		if (in_array(false, $backup)) {
			__pout('Backup failed');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			// Programme für existierende Kurse erstellen

			$combiCoursesIds = [];

			foreach($coursesWithoutProgram as $course) {

				// Für jeden Kurs ein Programm anlegen
				$programId = \DB::insertData('ts_tuition_courses_programs', ['created' => date('Y-m-d H:i:s'), 'course_id' => $course['id']]);

				if((int)$course['combination'] === 1) {

					// Kombinationskurse in Programmstruktur schreiben

					$childCourseSql = "
						SELECT 
						    DISTINCT `ts_tctc`.`course_id` 
						FROM 
						    `ts_tuition_courses_to_courses` `ts_tctc` INNER JOIN
						    `kolumbus_tuition_courses` `ktc` ON 	
						        `ktc`.`id` = `ts_tctc`.`course_id` AND
						        `ktc`.`active` = 1
						WHERE 
						    `ts_tctc`.`master_id` = :master_id AND 
						    `ts_tctc`.`type` = 'combination'
					";

					$childCoursesIds = (array)\DB::getQueryCol($childCourseSql, ['master_id' => $course['id']]);

					foreach($childCoursesIds as $childCourseId) {
						\DB::insertData('ts_tuition_courses_programs_services', [
							'created' => date('Y-m-d H:i:s'),
							'program_id' => $programId,
							'type' => \TsTuition\Entity\Course\Program\Service::TYPE_COURSE,
							'type_id' => $childCourseId
						]);
					}

					// Alte Kombinationskursstruktur löschen
					\DB::executePreparedQuery("DELETE FROM `ts_tuition_courses_to_courses` WHERE `master_id` = :master_id AND `type` = 'combination'", [
						'master_id' => $course['id']
					]);

					// Kursart umstellen
					$updateCourse = "UPDATE `kolumbus_tuition_courses` SET `changed` = `changed`, `per_unit` = :type WHERE `id` = :id";
					\DB::executePreparedQuery($updateCourse, ['id' => $course['id'], 'type' => \Ext_Thebing_Tuition_Course::TYPE_COMBINATION]);

					$combiCoursesIds[$course['id']] = $course['id'];

				} else {
					// Andere Kurse verweisen in der Programmleistung auf sich selbst
					$insertedProgramServiceId = \DB::insertData('ts_tuition_courses_programs_services', [
						'created' => date('Y-m-d H:i:s'),
						'program_id' => $programId,
						'type' => \TsTuition\Entity\Course\Program\Service::TYPE_COURSE,
						'type_id' => $course['id']
					]);

					// Caching - Hilft unten in der getProgramServiceId()
					self::$cache[$course['id']] = $insertedProgramServiceId;
				}

				// neue Programm-ID bei gebuchten Journey-Courses eintragen
				$updateJourneyCourse = "UPDATE `ts_inquiries_journeys_courses` SET `program_id` = :program_id, `changed` = `changed` WHERE `course_id` = :course_id";
				\DB::executePreparedQuery($updateJourneyCourse, ['course_id' => $course['id'], 'program_id' => $programId]);
				// neue Programm-ID bei gebuchten Group-Courses eintragen
				$updateGroupCourse = "UPDATE `kolumbus_groups_courses` SET `program_id` = :program_id, `changed` = `changed` WHERE `course_id` = :course_id";
				\DB::executePreparedQuery($updateGroupCourse, ['course_id' => $course['id'], 'program_id' => $programId]);

			}

			// program_service_id setzen

			$tables = [
				'kolumbus_tuition_blocks_inquiries_courses',
				'ts_inquiries_journeys_courses_tuition_index',
				'kolumbus_tuition_attendance',
				'kolumbus_tuition_progress',
				'kolumbus_examination'
			];

			$getInquiryCourseColumn = function ($table) {
				if (in_array($table, ['ts_inquiries_journeys_courses_tuition_index', 'kolumbus_tuition_attendance'])) {
					return 'journey_course_id';
				}
				return 'inquiry_course_id';
			};

			$processed = [];

			foreach($tables as $table) {

				// Inquiry-Course-Spalte heißt in den Tabellen unterschiedlich
				$inquiryCourseColumn = $getInquiryCourseColumn($table);

				$tableStructure = $this->getTableStructure($table);

				// ID generieren
				if (isset($tableStructure['id'])) {
					$select = "`id`";
				} else {
					$select = "CONCAT(#inquiry_course_column, '_', `course_id`) as `id`";
				}

				$entriesQuery = "
					SELECT 
					      ".$select.", #inquiry_course_column, `course_id`, `program_service_id` 
					FROM 
					     #table 
					WHERE 
					     `program_service_id` = 0 AND
					     #inquiry_course_column > 0 AND 
					     `course_id` > 0
				";

				$entries = (array)\DB::getPreparedQueryData($entriesQuery, ['table' => $table, 'inquiry_course_column' => $inquiryCourseColumn]);

				// Alle Tabellen merken die bisher noch nicht durchlaufen sind (aktuelle mit eingeschlossen)
				$unprocessedTables = array_diff($tables, $processed);

				$tableLoopCache = [];
				foreach ($entries as $entry) {

					$loopCacheKey = $entry[$inquiryCourseColumn].'_'.$entry['course_id'];

					if (
						// Es gibt einen Bug bei dem der Kombinationskurs als course_id hinterlegt wird, das darf eigentlich
						// nicht sein da es immer die Unterkurse sein sollten. In dem Fall den Eintrag überspringen.
						isset($combiCoursesIds[$entry['course_id']]) ||
						// Kombination aus inquiry_course und course_id wurde schon durchlaufen. Da unten der Update-Query
						// Einträge dieser Kombination die Werte setzt kann man hier überspringen
						isset($tableLoopCache[$loopCacheKey])
					) {

						continue;
					}

					$programServiceId = $this->getProgramServiceId($entry[$inquiryCourseColumn], $entry['course_id']);

					// Das sollte eigentlich nicht vorkommen da durch den Fallback $programServiceSql2 eigentlich immer ein
					// Programm-Service da sein sollte
					if ($programServiceId === 0) {
						throw new \RuntimeException(sprintf('No program service id found for entry (table: %s, id: %s)', $table, $entry['id']));
					}

					// Direkt alle anderen Tabellen, die noch nicht durchlaufen wurden, mit anpassen da die ID für alle
					// gleich sein sollte und man im weiteren Verlauf weniger Einträge prüfen muss
					foreach($unprocessedTables as $unprocessedTable) {

						// Bei den Anwesenheiten heißt die Spalte auf einmal 'journey_course_id'
						$inquiryCourseColumn2 = $getInquiryCourseColumn($unprocessedTable);

						$tableStructure2 = $this->getTableStructure($unprocessedTable);

						$set = "";
						// Changed darf nicht verändert werden wenn es die Spalte gibt
						if (isset($tableStructure2['changed'])) {
							$set = ", `changed` = `changed`";
						}

						$updateQuery = "
							UPDATE 
								#table 
							SET 
								`program_service_id` = :program_service_id 
								".$set." 
							WHERE 
								#inquiry_course_column = :inquiry_course_id AND 
								`course_id` = :course_id AND 
								`program_service_id` = 0
						";

						\DB::executePreparedQuery(
							$updateQuery,
							['table' => $unprocessedTable, 'inquiry_course_column' => $inquiryCourseColumn2,
								'inquiry_course_id' => $entry[$inquiryCourseColumn],
								'course_id' => $entry['course_id'],
								'program_service_id' => $programServiceId
							]
						);
					}

					$tableLoopCache[$loopCacheKey] = 1;

				}

				unset($entries);

				$processed[] = $table;
			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		// Alte "combination"-Spalte umbenennen damit diese nicht mehr benutzt wird
		#\DB::executeQuery("ALTER TABLE `kolumbus_tuition_courses` CHANGE `combination` `combination_` TINYINT(1) NOT NULL");

		return true;

	}

	private function getProgramServiceId($inquiryCourseId, $courseId) {

		$cachKey = $inquiryCourseId.'_'.$courseId;

		if (!isset(self::$cache[$cachKey])) {

			$programServiceSql = "
						SELECT
							`ts_tcps`.`id`
						FROM
							`ts_inquiries_journeys_courses` `ts_ijc` INNER JOIN
							`ts_tuition_courses_programs_services` `ts_tcps` ON 		
								`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
								`ts_tcps`.`type_id` = :course_id
						WHERE
							`ts_ijc`.`id` = :inquiry_course_id
						LIMIT 1
					";

			// Programmservice-ID versuchen herauszufinden
			self::$cache[$cachKey] = (int)\DB::getQueryOne($programServiceSql, ['inquiry_course_id' => $inquiryCourseId, 'course_id' => $courseId]);
		}

		$programServiceId = (int) self::$cache[$cachKey];

		// Falls die course_id des Eintrages nicht mehr zum Inquiry-Kurs passt
		if($programServiceId === 0) {

			if (!isset(self::$cache[$courseId])) {
				// Da man nicht erschließen kann ob es ein Kurs innerhalb eines Kombinationskurses war nehmen wir mal das Program des Kurses selber.
				// Annahme: course_id kann kein Kombinationskurs sein, es sind immer die Unterkurse
				$programServiceSql2 = "
							SELECT
								`ts_tcps`.`id`
							FROM
								`kolumbus_tuition_courses` `ktc` INNER JOIN
								`ts_tuition_courses_programs` `ts_tcp` ON 
									`ts_tcp`.`course_id` = `ktc`.`id` INNER JOIN
								`ts_tuition_courses_programs_services` `ts_tcps` ON 		
									`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
									`ts_tcps`.`type_id` = :course_id
							WHERE
							    `ktc`.`id` = :course_id
							LIMIT 1
						";

				self::$cache[$courseId] = (int)\DB::getQueryOne($programServiceSql2, ['course_id' => $courseId]);
			}

			$programServiceId = (int)self::$cache[$courseId];
		}

		return $programServiceId;
	}

	private function getTableStructure(string $table) {

		if (!isset(self::$tableCache[$table])) {
			self::$tableCache[$table] = \DB::describeTable($table);
		}

		return self::$tableCache[$table];

	}

}
