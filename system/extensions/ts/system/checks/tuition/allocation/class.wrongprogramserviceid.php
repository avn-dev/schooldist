<?php

/**
 * lesson_duration von Tuition Allocations neu berechnen
 *
 * @see \Ext_Thebing_School_Tuition_Allocation::$lesson_duration
 */
class Ext_TS_System_Checks_Tuition_Allocation_WrongProgramServiceId extends GlobalChecks {

	public function getTitle() {
		return 'Tuition Allocation Check';
	}

	public function getDescription() {
		return 'Checks if correct program service id is used.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		if(!Util::backupTable('kolumbus_tuition_blocks_inquiries_courses')) {
			__pout('Could not backup table!');
			return false;
		}

		/**
		 * Doppelte Allocations
		 */

		// Alle Kursbuchungen herausfinden (auch active = 0), zu denen mehrere Allocations existieren. Nach diesem Check
		// darf nur noch eine Allocation pro Kursbuchung und Block existieren (egal ob active = 0 oder active = 1)
		$sqlQuery = "
			SELECT 
				`ktbic`.*,
				COUNT(*) c
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic`
			GROUP BY
				block_id,
				inquiry_course_id
			HAVING
				c > 1
			";
		$doubleEntries = \DB::getQueryRows($sqlQuery);

		foreach($doubleEntries as $doubleEntry) {

			$sqlQuery = "
				SELECT 
					`ktbic`.`id`,
					`ktbic`.`course_id`,
					`ktbic`.`active`,
					`ts_ijc`.`program_id` `inquiry_course_program_id`,
					`ts_tcps`.`id` `program_service_id`,
					`ts_tcp`.`id` `program_id`
				FROM
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` JOIN 
					`ts_inquiries_journeys_courses` `ts_ijc` ON 
						`ktbic`.`inquiry_course_id` = `ts_ijc`.`id` LEFT JOIN 
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
						`ktbic`.`program_service_id` = `ts_tcps`.`id` LEFT JOIN 
					`ts_tuition_courses_programs` `ts_tcp` ON 
						`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
						`ts_ijc`.`program_id` = `ts_tcp`.`id`
				WHERE
					`ktbic`.`block_id` = :block_id AND
					`ktbic`.`inquiry_course_id` = :inquiry_course_id 
				ORDER BY 
				    `ktbic`.`id` ASC
			";

			// Alle Allocations zu dieser Kursbuchung und Block heraussuchen (Absteigend nach ID sortiert, damit der letzte
			// Eintrag auch der zuletzt erstellte ist)
			$inquiryDoubleEntries = \DB::getQueryRows($sqlQuery, [
				'block_id' => $doubleEntry['block_id'],
				'inquiry_course_id' => $doubleEntry['inquiry_course_id']
			]);

			// Wenn $item['program_id'] !== null dann ist der Eintrag korrekt (zumindest von der dem program_service_id
			$correctEntries = array_filter($inquiryDoubleEntries, fn ($item) => $item['program_id'] !== null);

			// Der zu behaltene Eintrag
			$keptEntry = null;

			if (!empty($correctEntries)) {

				// Nach aktiven Zuweisungen suchen
				$activeEntries = array_filter($correctEntries, fn ($item) => (int)$item['active'] === 1);

				if (count($activeEntries) > 1) {
					// Das muss man sich dann genauer anschauen, sollte aber eigentlich nicht vorkommen
					throw new RuntimeException(sprintf('More than one correct allocation found! [%d - %d]', $doubleEntry['block_id'], $doubleEntry['inquiry_course_id']));
				}

				if (!empty($activeEntries)) {
					// Den aktiven Eintrag behalten
					$keptEntry = \Illuminate\Support\Arr::last($activeEntries);
				} else {
					// Den zuletzt erstellten Eintrag behalten
					$keptEntry = \Illuminate\Support\Arr::last($correctEntries);
				}

			} else {
				// einen der $doubleEntries reparieren bzw. behalten
				$keptEntry = \Illuminate\Support\Arr::last(
					$inquiryDoubleEntries,
					fn ($entry) => (int)$entry['active'] === 1, // nach einem mit active = 1 suchen
					\Illuminate\Support\Arr::last($inquiryDoubleEntries) // ansonsten einen mit active = 0 nehmen
				);

				if ((int)$keptEntry['active'] === 1) {
					// Nur reparieren wenn die Zuweisung active = 1 ist
					$this->repairAllocation($keptEntry['id'], $keptEntry['course_id'], $keptEntry['inquiry_course_program_id']);
				}

			}

			// Sichergehen, dass eine Allocation erhalten bleibt
			$inquiryDoubleEntries = array_filter($inquiryDoubleEntries, fn ($loop) => $loop['id'] != $keptEntry['id']);

			// Alle weiteren Zuweisungen löschen
			foreach ($inquiryDoubleEntries as $inquiryDoubleEntry) {
				\DB::executePreparedQuery("DELETE FROM `kolumbus_tuition_blocks_inquiries_courses` WHERE id = :id", ['id' => $inquiryDoubleEntry['id']]);
			}

		}

		/**
		 * Falsche Program-Service-Id
		 */

		$sSql = "
			SELECT 
				`ktbic`.`id`,
				`ktbic`.`course_id`,
				`ts_ijc`.`program_id` `inquiry_course_program_id`
			FROM 
				`kolumbus_tuition_blocks_inquiries_courses` ktbic JOIN 
				ts_inquiries_journeys_courses ts_ijc ON 
					ktbic.inquiry_course_id = ts_ijc.id JOIN 
				ts_tuition_courses_programs_services ts_tcps ON 
					ktbic.program_service_id = ts_tcps.id JOIN 
				ts_tuition_courses_programs ts_tcp ON 
					ts_tcps.program_id = ts_tcp.id 
			WHERE 
				ts_tcp.course_id != ts_ijc.course_id 
			ORDER BY 
				`program_service_id` ASC
		";

		$collection =  DB::getDefaultConnection()->getCollection($sSql);

		$this->logInfo('Found '.count($collection).' entries with wrong duration');

		foreach($collection as $row) {
			$this->repairAllocation($row['id'], $row['course_id'], $row['inquiry_course_program_id']);
		}

		/**
		 * Unique-Key
		 */

		// Unique-Key auf 'block_id' und 'inquiry_course_id' setzen, pro Block kann ein Schüler auch nur einmal vorkommen
		\DB::executeQuery("ALTER TABLE `kolumbus_tuition_blocks_inquiries_courses` DROP INDEX `uniquie_ktbic`, ADD UNIQUE `uniquie_ktbic` (`block_id`, `inquiry_course_id`) USING BTREE");

		$this->logInfo('Finished');

		return true;

	}

	private function repairAllocation(int $allocationId, int $courseId, int $programmId) {

		$sql = "
			SELECT
				`id` 
			FROM
			    `ts_tuition_courses_programs_services` 
			WHERE
				`active` = 1 AND
				`program_id` = :program_id AND
				`type` = :type AND
				`type_id` = :type_id
			LIMIT 1
		";

		$correctProgramServiceId = (int)\DB::getQueryOne($sql, [
			'program_id' => $programmId,
			'type' => \TsTuition\Entity\Course\Program\Service::TYPE_COURSE,
			'type_id' => $courseId
		]);

		if ($correctProgramServiceId > 0) {
			$update = "
				UPDATE
					`kolumbus_tuition_blocks_inquiries_courses`
				SET
					`changed` = `changed`,
					`program_service_id` = :program_service_id
				WHERE
					`id` = :id
				LIMIT
					1
			";

			\DB::executePreparedQuery($update, ['program_service_id' => $correctProgramServiceId, 'id' => $allocationId]);

		} else {
			$this->logError('Unable to find correct program service for tuition allocation', ['allocation_id' => $allocationId]);
		}
	}
}
