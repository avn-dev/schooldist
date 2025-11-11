<?php

/**
 * Ticket #15048 – Kursverfügbarkeiten (Starttage) umstellen
 *
 * https://redmine.fidelo.com/issues/15048
 */
class Ext_TS_System_Checks_Course_StartDates extends GlobalChecks {

	public function getTitle() {
		return 'Update course start dates';
	}

	public function getDescription() {
		return 'Migrate limited start dates into specified start dates.';
	}

	public function executeCheck() {

		if(!in_array('kolumbus_course_runtimes', DB::listTables())) {
			return true;
		}

		Util::backupTable('kolumbus_tuition_courses');
		Util::backupTable('kolumbus_course_runtimes');
		Util::backupTable('kolumbus_course_startdates');
		Util::backupTable('kolumbus_course_startdates_levels');

		DB::begin(__CLASS__);

		$sql = "
			SELECT
				`kcr`.*,
			    GROUP_CONCAT(`kcsl`.`level_id`) `level_ids`
			FROM
				`kolumbus_course_runtimes` `kcr` LEFT JOIN
				`kolumbus_course_startdates_levels` `kcsl` ON
				    `kcsl`.`type` = 'runtime' AND
				    `kcsl`.`type_id` = `kcr`.`id`
			WHERE
			    `kcr`.`active` = 1 AND
				`kcr`.`available_until` > '2015-01-01'
			GROUP BY
			   `kcr`.`id`
		";

		$result = (array)DB::getQueryRows($sql);

		foreach ($result as $row) {

			$levelIds = [];
			if (!empty($row['level_ids'])) {
				$levelIds = explode(',', $row['level_ids']);
			}

			$id = DB::insertData('kolumbus_course_startdates', [
				'created' => $row['created'],
				'active' => 1,
				'creator_id' => $row['creator_id'],
				'user_id' => $row['user_id'],
				'course_id' => $row['course_id'],
				'start_date' => $row['available_from'],
				'period' => 1,
				'end_date' => $row['available_until'],
				'minimum_duration' => $row['minimum_duration'],
				'maximum_duration' => $row['maximum_duration'],
				'fix_duration' => $row['fix_duration'],
			]);

			$this->logInfo(sprintf('Migrated course runtime %d to course startdate %d', $row['id'], $id));

			if (!empty($levelIds)) {
				foreach ($levelIds as $levelId) {
					DB::insertData('kolumbus_course_startdates_levels', [
						'type' => 'startdate',
						'type_id' => $id,
						'level_id' => $levelId
					]);
				}
			}

		}

		// Leereintrag auf immer verfügbar
		DB::executeQuery("UPDATE `kolumbus_tuition_courses` SET `avaibility` = 1 WHERE `avaibility` = 0");

		// Limited start dates auf specified start dates
		DB::executeQuery("UPDATE `kolumbus_tuition_courses` SET `avaibility` = 4 WHERE `avaibility` = 3");

		// Level-Tabelle bereinigen
		DB::executeQuery("DELETE FROM `kolumbus_course_startdates_levels` WHERE `type` = 'runtime'");

		DB::commit(__CLASS__);

		DB::executeQuery("DROP TABLE `kolumbus_course_runtimes`");

		// Scheinbar benötigen das manche MySQL-Versionen, sonst kommt beim nächsten Statement: #1072 - Key column 'type' doesn't exist in table
		DB::executeQuery("ALTER TABLE `kolumbus_course_startdates_levels` DROP PRIMARY KEY;");

		DB::executeQuery("ALTER TABLE `kolumbus_course_startdates_levels` DROP `type`");

		DB::executeQuery("ALTER TABLE `kolumbus_course_startdates_levels` ADD PRIMARY KEY( `type_id`, `level_id`);");

		return true;

	}

}