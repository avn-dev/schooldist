<?php

/*
 * Ticket #13056 - Einstufungstest komplett neu schreiben
 *
 * Check resetten:
 *
 * Alle Tabellen ts_placementtests_* wiederherstellen
 * DROP TABLE `ts_placementtests`;
 * ALTER TABLE `customer_db_2` DROP default_placementtest_id;
 * ALTER TABLE `customer_db_2` ADD `placementtest_automatic_comparison` INT NOT NULL, ADD `placementtest_accuracy_in_percent` INT NOT NULL;
 */
class Ext_TS_System_Checks_Tuition_Placementtest_NewStructure extends Ext_TC_System_Checks_System_Tabs_AbstractMoved {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Update placementtest structure';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return self::getTitle();
    }

	protected function getMovedTabs(): array {

		return [
			'/admin/extensions/thebing/tuition/placementtest_questions.html' => '/ts/tuition/gui2/page/placementtests'
		];
	}

    /**
     * Neue Tabelle anlegen, ermitteln, welche Schulen PT haben, diese PT in neuer Tabelle anlegen,
	 * Default-Placementtest in Schule hinterlegen, Navigationspunkt aktualisieren in gespeicherten Tabs,
	 * Für alle beantworteten Placementtests die neuen Spalten füllen (evaluateResult ausführen) und viel mehr...
	 *
     * @return boolean
     */
    public function executeCheck()
	{

		set_time_limit(3600);
		ini_set('memory_limit', '4G');

		// Wenn Check noch nicht ausgeführt wurde
		if(Util::checkTableExists('ts_placementtests')) {
			return true;
		}

		$backupTables = [
			'ts_placementtests_questions_answers',
			'customer_db_2',
			'ts_placementtests_results_inquiries_journeys_courses',
			'ts_placementtests_questions',
			'ts_placementtests_categories',
			'ts_placementtests_results',
			'ts_placementtests_results_details'
		];

		foreach($backupTables as $table) {
			$success = Ext_Thebing_Util::backupTable($table);
			if(!$success) {
				return false;
			}
		}

		\DB::executeQuery("
			CREATE TABLE `ts_placementtests` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
				`created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ,
				`changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' ,
				`creator_id` INT UNSIGNED NOT NULL,
				`editor_id` INT UNSIGNED NOT NULL ,
				`name` VARCHAR(255) NOT NULL ,
				`placementtest_automatic_comparison` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`placementtest_accuracy_in_percent` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
				`courselanguage_id` INT UNSIGNED NULL DEFAULT NULL,
				PRIMARY KEY (`id`)
			 ) ENGINE = InnoDB
		");

		DB::addField('customer_db_2', 'default_placementtest_id', 'SMALLINT UNSIGNED NULL DEFAULT NULL');

		$schools = DB::getQueryRows("SELECT * FROM customer_db_2 /*WHERE active = 1*/");

		foreach ($schools as $school) {
			$checkQuestions = \DB::getQueryOne("SELECT * FROM `ts_placementtests_questions` WHERE `school_id` = :school_id AND `active` = 1", ['school_id' => $school['id']]);

			// Wenn Schule PTs benutzt hat
			if (!empty($checkQuestions)) {

				$sSql = "
					SELECT 
						`ts_ptri`.`inquiry_course_id`
					FROM
					    `ts_inquiries_journeys` `ts_i_j` INNER JOIN
						`ts_inquiries` `ts_i` ON 
						    `ts_i`.`id` = `ts_i_j`.`inquiry_id` INNER JOIN
					    `ts_placementtests_results` `ts_ptr` ON
							`ts_ptr`.`inquiry_id` = `ts_i`.`id` INNER JOIN
					    `ts_placementtests_results_inquiries_journeys_courses` `ts_ptri` ON
							`ts_ptri`.`placementtest_result_id` = `ts_ptr`.`id`
					WHERE
						`ts_i_j`.`school_id` = :school_id
					ORDER BY 
					    `ts_ptri`.`inquiry_course_id` DESC
						LIMIT 1
				";

				$aSql = ['school_id' => $school['id']];

				// die ID vom aktuellsten Kurs (höchste ID)
				$mostRecentCourseId = DB::getQueryOne($sSql, $aSql);
				$courseLanguageId = Ext_TS_Inquiry_Journey_Course::getInstance($mostRecentCourseId)->getCourseLanguage()->id;

				DB::insertData('ts_placementtests', [
					'id' => $school['id'],
					'changed' => date('Y-m-d H:i:s'),
					'created' => date('Y-m-d H:i:s'),
					'editor_id' => 0,
					'creator_id' => 0,
					'name' => $school['short'],
					'placementtest_automatic_comparison' => $school['placementtest_automatic_comparison'],
					'placementtest_accuracy_in_percent' => $school['placementtest_accuracy_in_percent'],
					'courselanguage_id' => $courseLanguageId
				]);

				$sSql = "
					UPDATE 
						customer_db_2 
					SET
						`changed` = `changed`,
						`default_placementtest_id` = :id	
					WHERE
						`id` = :id 
				";

				// Schul-ID und default_placementtest_id sind für diesen Check gleich
				$aSql = ['id' => $school['id']];

				DB::executePreparedQuery($sSql, $aSql);

			}

		}

		$queries = [
			"ALTER TABLE `ts_placementtests_questions` CHANGE `school_id` `placementtest_id` SMALLINT NOT NULL",
			"ALTER TABLE `ts_placementtests_questions` CHANGE `text` `text` TEXT NOT NULL",
			"ALTER TABLE `ts_placementtests_questions` ADD `always_evaluate` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `optional`",
			"ALTER TABLE `ts_placementtests_categories` CHANGE `school_id` `placementtest_id` SMALLINT NOT NULL",
			"ALTER TABLE `ts_placementtests_results` ADD `placementtest_id` SMALLINT NOT NULL AFTER `inquiry_id`",
			"ALTER TABLE `ts_placementtests_questions` CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'",
			"ALTER TABLE `ts_placementtests_categories` CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'",
			"ALTER TABLE `ts_placementtests_questions_answers` CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'",
			"ALTER TABLE `ts_placementtests_questions_answers` CHANGE `text` `text` TEXT NOT NULL",
			"ALTER TABLE `ts_placementtests_results_details` CHANGE `active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'",
			// Die Spalte ist unnötig, es gibt schon eine Verknüpfung zu den Fragen (idQuestion) und die Fragen haben eine
			// Verknüpfung zu den PTs
			"ALTER TABLE `ts_placementtests_questions_answers` DROP `school_id`",
			"ALTER TABLE `customer_db_2` DROP `placementtest_automatic_comparison`",
			"ALTER TABLE `customer_db_2` DROP `placementtest_accuracy_in_percent`",
			// Die Tabelle ergibt keinen Sinn
			"DROP TABLE `ts_placementtests_results_inquiries_journeys_courses`",
			"ALTER TABLE `ts_placementtests_results_details` CHANGE `value` `value` TEXT NULL DEFAULT NULL;",
			// Unnötige Spalte
			"ALTER TABLE `ts_placementtests_results_details` DROP `answer_id`;",
			"ALTER TABLE `ts_placementtests_results` ADD `result_summary` TEXT NULL DEFAULT NULL AFTER `answered`,
				ADD `questions_answered` SMALLINT NOT NULL AFTER `result_summary`,
				ADD `questions_answered_correct` SMALLINT NOT NULL AFTER `questions_answered`;
			",
			// Leere Antworten wurden früher gebraucht, sind aber jetzt unnötig und sorgen beim Klonen für Fehler
			"DELETE FROM `ts_placementtests_questions_answers` WHERE `text` = ''",
			"DELETE FROM `ts_placementtests_results` WHERE `inquiry_id` = 0",
		];

		foreach($queries as $query) {
			try {
				DB::executeQuery($query);
			} catch (Exception $e) {
				__pout($e->getMessage(), 1);
				return false;
			}
		}

		// Manche Installationen haben dieses Feld nicht
		\DB::addField('ts_placementtests_results_details', 'answer_correctness', 'FLOAT (5,2) NULL DEFAULT NULL');
		
		// Cache leeren, damit die WDBasic die neuen Felder von ts_placementtests_results kennt
		// (für das ->findall() )
		Ext_Thebing_Placementtests_Results::deleteTableCache();
		Ext_Thebing_Placementtests_Question::deleteTableCache();
		Ext_Thebing_Placementtests_Results_Details::deleteTableCache();

		$sSql = "
			SELECT
				`id`,`value`
			FROM
				`ts_placementtests_results_details`
		";

		$answers = DB::getDefaultConnection()->getCollection($sSql);
		// Alte "value"-Spalten-Werte (strings) in JSON umwandeln
		foreach ($answers as $answer) {

			$sSql = "
					UPDATE 
						ts_placementtests_results_details 
					SET
						`changed` = `changed`,
						`value` = :value	
					WHERE
						`id` = :id 
			";

			$aSql = [
				'id' => $answer['id'],
				'value' => json_encode($answer['value'])
			];

			DB::executePreparedQuery($sSql, $aSql);
		}

		// Die alten MultiSelect-Antworten, die gegeben wurden, nach der neuen Struktur abspeichern
		$multiSelectQuestions = Ext_Thebing_Placementtests_Question::query()
			->where('type', Ext_Thebing_Placementtests_Question::TYPE_MULTISELECT)
			->get();

		// Jetzt nicht mehr mehrere Einträge pro Frage mit der gleichen Fragen-ID und verschiedenen values, sondern
		// ein Eintrag und die values als JSON
		foreach ($multiSelectQuestions as $multiSelectQuestion) {

			$multiSelectAnswerObjects = Ext_Thebing_Placementtests_Results_Details::query()
				->where('question_id', $multiSelectQuestion->id)
				->get();
			$multiSelectAnswerAmount = $multiSelectAnswerObjects->count();

			foreach ($multiSelectAnswerObjects as $multiSelectAnswerObject) {

				$multiSelectAnswers[] = $multiSelectAnswerObject->value;

				// Alle Multiselect-Antworten löschen, bis es nur noch ein Eintrag zu der Frage gibt
				// (-> $multiSelectAnswerAmount-- am Ende jedes löschens) und dann alle Antworten in den einen Eintrag speichern
				if ($multiSelectAnswerAmount > 1) {

					$sSql = "DELETE FROM ts_placementtests_results_details WHERE `id` = :id LIMIT 1";
					$aSql = [ 'id' => $multiSelectAnswerObject->id];

					DB::executePreparedQuery($sSql, $aSql);

					$multiSelectAnswerAmount--;
				} else {
					// JSON
					$sSql = "
							UPDATE 
								ts_placementtests_results_details 
							SET
								`changed` = `changed`,
								`value` = :value	
							WHERE
								`id` = :id 
					";
					$aSql = [
						'id' => $multiSelectAnswerObject->id,
						'value' => json_encode($multiSelectAnswers)
					];

					DB::executePreparedQuery($sSql, $aSql);
				}
			}
		}

		// Da school_id einfach auf placementtest_id umgeschrieben wird, muss bei gelöschten Schulen entweder AUTO_INCREMENT angepasst werden oder die Leichen entfernt werden
		$deleted = (array)DB::getQueryRows("SELECT ts_pt.* FROM ts_placementtests ts_pt LEFT JOIN customer_db_2 cdb2 ON cdb2.id = ts_pt.id WHERE cdb2.active = 0");
		foreach ($deleted as $row) {
			$placementTest = \TsTuition\Entity\Placementtest::getObjectFromArray($row);
			$placementTest->enablePurgeDelete();
			$placementTest->delete();
		}

		return parent::executeCheck();
	}

}