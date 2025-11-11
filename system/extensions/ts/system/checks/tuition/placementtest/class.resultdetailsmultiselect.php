<?php

class Ext_TS_System_Checks_Tuition_Placementtest_ResultDetailsMultiSelect extends GlobalChecks {

	const CHECK_ALREADY_EXECUTED_KEY = 'check_result_details_multi_select';

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Bugfix multiselect result details';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck()
	{

		set_time_limit(3600);
		ini_set('memory_limit', '8G');

		// Wenn der Check schon ausgeführt wurde
		if(\System::d(self::CHECK_ALREADY_EXECUTED_KEY, false) == 1) {
			return true;
		}

		// Vor dem Backup alle Tabellen holen, weil die Backup Tabelle nicht relevant ist in dem Fall.
		$allTables = DB::listTables();

		$success = Ext_Thebing_Util::backupTable('ts_placementtests_results_details');
		if(!$success) {
			return false;
		}

		// Damit unique gesetzt werden kann (active = 0 Einträge wurden nicht zusammengeführt)
		$sSql = "DELETE FROM ts_placementtests_results_details WHERE `active` = :active";
		$aSql = [ 'active' => 0 ];

		DB::executePreparedQuery($sSql, $aSql);


		// Noch nicht zusammengeführte Antworten (z.B. Checkbox-Antworten bei manchen Installationen vielleicht oder ehemalige
		// Antworten zu Multiselect-Fragen, die jetzt keine Multiselect-Fragen mehr sind und deswegen nicht im alten Check zusammengeführt wurden)
		$answersWithWrongStructure = DB::table('ts_placementtests_results_details')
			->groupBy('result_id', 'question_id')
			->having(DB::raw('COUNT(*)'), '>', '1')
			->get();

		// Antworten zusammenführen, damit danach UNIQUE gesetzt werden kann
		foreach ($answersWithWrongStructure as $answerWithWrongStructure) {

			$answers = DB::table('ts_placementtests_results_details')
				->where('question_id', $answerWithWrongStructure['question_id'])
				->where('result_id', $answerWithWrongStructure['result_id'])
				->get();

			$answerAmount = $answers->count();

			$answerValues = [];
			foreach ($answers as $answer) {

				$answerValues[] = json_decode($answer['value']);

				// Alle Antworten löschen, bis es nur noch ein Eintrag zu der Frage gibt
				// (-> $answerAmount-- am Ende jedes löschens) und dann alle Antworten in den einen Eintrag speichern
				if ($answerAmount > 1) {

					$sSql = "DELETE FROM ts_placementtests_results_details WHERE `id` = :id LIMIT 1";
					$aSql = [ 'id' => $answer['id']];

					DB::executePreparedQuery($sSql, $aSql);

					$answerAmount--;
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
						'id' => $answer['id'],
						'value' => json_encode($answerValues)
					];

					DB::executePreparedQuery($sSql, $aSql);
				}
			}
		}


		try {
			DB::executeQuery('ALTER TABLE `ts_placementtests_results_details` ADD UNIQUE `UNIQUE` (`result_id`, `question_id`)');
		} catch (\Throwable) {
			return false;
		}

		$detailsToCorrect = true;
		$backupTablesExisting = false;
		foreach ($allTables as $table) {

			preg_match('/^\_\_([0-9]{14})\_ts_placementtests_results_details$/', $table, $backupTableData);

			if (!empty($backupTableData)) {

				$backupTablesExisting = true;

				$exampleOldDetailValue = \DB::table($table)
					->select('value')
					->get()
					->first();

				if (!empty($exampleOldDetailValue)) {
					$exampleOldDetailValue = reset($exampleOldDetailValue);
				} else {
					$detailsToCorrect = false;
					continue;
				}

				// Es gibt nur noch JSON-Werte in der neuen Struktur
				// -> Wenn man aber nur nach json_decode() === null Abfragen würde, wäre das falsch, weil numerische Werte json dekodiert
				//	werden können. Also numerischer Wert (= alte Struktur) und json_decode() === null (= alte Struktur)
				if (
					is_numeric($exampleOldDetailValue) ||
					json_decode($exampleOldDetailValue) === null
				) {
					$detailsToCorrect = true;
					// Hier wurde der newstructure Check noch nicht ausgeführt, den Stand wollen wir auch
					$backupTables[$backupTableData[1]] = $table;
				} else {
					$detailsToCorrect = false;
				}
			}
		}

		// Hier gibt es keine Ergebnisse in der/den Backup Tabellen und somit muss hier nicht weiter gemacht werden
		if ($backupTablesExisting && !$detailsToCorrect) {
			\System::s(self::CHECK_ALREADY_EXECUTED_KEY, 1);
			return true;
		}

		if (!$backupTablesExisting) {
			$exampleCurrentDetailValue = Ext_Thebing_Placementtests_Results_Details::query()
				->select('value')
				->get()
				->first();

			if (!empty($exampleCurrentDetailValue)) {
				$mailContent = sprintf(
					'Bei der Installation "%s" gibt es Einstufungstest-Antworten, aber keine Backup 
					Tabellen der Antworten mehr.', \System::d('domain')
				);

				try {
					$mail = new \WDMail();
					$mail->subject = 'Placementtest Details';
					$mail->text = $mailContent;
					$mail->send(['m.priebe@fidelo.com']);
				} catch (\Throwable) {
					return false;
				}
			}

			\System::s(self::CHECK_ALREADY_EXECUTED_KEY, 1);
			return true;
		}

		// Falls es mehrere Backup Tabellen gibt, die aktuellste (vor dem newstructure Check)
		krsort($backupTables);

		$latestBackupTable = reset($backupTables);

		$results = Ext_Thebing_Placementtests_Results::query()->get();

		// Alten Einträge fixen
		foreach ($results as $result) {

			// Die alten MultiSelect-Antworten, die gegeben wurden, nach der neuen Struktur abspeichern
			$multiSelectQuestions = DB::table('ts_placementtests_questions')
				->where('type', Ext_Thebing_Placementtests_Question::TYPE_MULTISELECT)
				->where('placementtest_id', $result->getPlacementtest()->id)
				->get();

			// Jetzt nicht mehr mehrere Einträge pro Frage mit der gleichen Fragen-ID und verschiedenen values, sondern
			// ein Eintrag und die values als JSON
			foreach ($multiSelectQuestions as $multiSelectQuestion) {

				$oldDetails = \DB::table($latestBackupTable)
					->where('question_id', $multiSelectQuestion['id'])
					->where('result_id', $result->id)
					->get();

				$multiSelectAnswerAmount = $oldDetails->count();

				$multiSelectAnswers = [];
				foreach ($oldDetails as $oldDetail) {

					$multiSelectAnswers[] = $oldDetail['value'];

					// Alle Multiselect-Antworten löschen, bis es nur noch ein Eintrag zu der Frage gibt
					// (-> $multiSelectAnswerAmount-- am Ende jedes löschens) und dann alle Antworten in den einen Eintrag speichern
					if ($multiSelectAnswerAmount > 1) {

						$sSql = "DELETE FROM ts_placementtests_results_details WHERE `id` = :id LIMIT 1";
						$aSql = [ 'id' => $oldDetail['id']];

						DB::executePreparedQuery($sSql, $aSql);

						$multiSelectAnswerAmount--;
					} else {
						$oldDetail['value'] = json_encode($multiSelectAnswers);

						$sSql = "
							REPLACE
								ts_placementtests_results_details
							SET
								`changed` = :changed,
								`created` = :created,
								`active` = :active,
								`creator_id` = :creator_id,
								`result_id` = :result_id,
								`question_id` = :question_id,
								`value` = :value,
								`answer_is_right` = :answer_is_right,
								`answer_correctness` = :answer_correctness
						";

						DB::executePreparedQuery($sSql, $oldDetail);
					}
				}
			}
		}

		$success = Ext_Thebing_Util::backupTable('ts_placementtests_results');
		if(!$success) {
			return false;
		}

		// Damit der Backup Name nicht der gleiche und somit doppelt ist.
		sleep(1);

		$success = Ext_Thebing_Util::backupTable('ts_placementtests_results_details');
		if(!$success) {
			return false;
		}

		$resultDetailsNewCheck = new Ext_TS_System_Checks_Tuition_Placementtest_ResultDetailsNew();
		$success = $resultDetailsNewCheck->executeCheck();
		if (!$success) {
			return false;
		}

		\System::s(self::CHECK_ALREADY_EXECUTED_KEY, 1);

		return true;
	}

}