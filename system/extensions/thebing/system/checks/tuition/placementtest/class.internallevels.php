<?php

/**
 * Interne Level des Placementtests in der entsprechenden Tabelle eintragen
 *
 * Bisher wurde in den beiden oberen Listen der Klassenplanung immer geschaut,
 * ob es einen Einstufungstest gibt und dieses Level wurde einfach ins Select
 * gesetzt, wenn es keinen internen Fortschritt gab. Problematisch daran ist aber,
 * dass es dadurch auch im System keinen internen Fortschritt gibt und das Select
 * auch nicht bearbeitet wird, solange da nichts geändert wird. Das Verhalten wurde
 * entfernt und die entsprechenden Werte, die vorher angezeigt wurden, werden mit diesem
 * Check in die Datenbank eingetragen.
 *
 * Der Einstufungstest füllt nun immer die erste Woche des internen Fortschritts eines Kurses.
 * Der Check füllt zusätzlich die Lücken zwischen erster Woche und erstem internen Fortschritt.
 * Kurse, welche gar keinen internen Fortschritt gespeichert haben, bekommen keine Einträge,
 * da man hier nicht unterscheiden kann, ob der Kurs überhaupt ein internes Level haben soll
 * oder nicht.
 *
 * https://redmine.thebing.com/redmine/issues/8666
 */
class Ext_Thebing_System_Checks_Tuition_Placementtest_InternalLevels extends GlobalChecks {

	private $iAffectedCourses = 0;
	private $iMissingWeeks = 0;

	public function getTitle() {
		return 'Check internal levels of placement tests';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_tuition_progress');

		DB::begin(__CLASS__);

		// Alle Kursbuchungen von allen Buchungen mit gespeichertem Einstufungstest
		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_ptr`.`level_id` `pt_level_id`,
				`ts_ijc`.`id` `course_id`,
				`ts_ijc`.`from` `course_from`,
				`ts_ijc`.`until` `course_until`,
				`ktc`.`id` `tuition_course_id`,
				`ktc`.`combination`
			FROM
				`ts_placementtests_results` `ts_ptr` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ptr`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 AND
					`ts_ijc`.`visible` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_ijc`.`course_id`
			WHERE
				`ts_ptr`.`active` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql);
		$aCourses = [];

		foreach($aResult as $aRow) {
			// Kombinationskurse gruppieren nach Unterkurs
			if($aRow['combination']) {
				$aCourses = array_merge($aCourses, $this->groupByChildCourses($aRow));
			} else {
				$aCourses[] = $aRow;
			}
		}

		foreach($aCourses as $aCourse) {
			$this->checkCourse($aCourse);
		}

		$this->logInfo($this->iAffectedCourses.' affected courses ('.$this->iMissingWeeks.' missing week entries)');

		DB::commit(__CLASS__);

		return true;

	}

	/**
	 * Kombinationskurse beachten: Hauptkurs aufteilen auf Unterkurse
	 *
	 * @param array $aData
	 * @return array
	 */
	private function groupByChildCourses(array $aData) {
		$aReturn = [];

		$sSql = "
			SELECT
				`course_id`
			FROM
				`kolumbus_course_combination`
			WHERE
				`master_id` = :tuition_course_id
		";

		$aResult = (array)DB::getQueryCol($sSql, $aData);
		foreach($aResult as $iChildCourseId) {
			$aTmp = $aData;
			$aTmp['tuition_course_id'] = $iChildCourseId;
			$aReturn[] = $aTmp;
		}

		return $aReturn;
	}

	private function checkCourse(array $aData) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`active` = 1 AND
				`inquiry_course_id` = :course_id AND
				`course_id` = :tuition_course_id
			ORDER BY
				`week`
		";

		$aProgressEntries = (array)DB::getQueryRows($sSql, $aData);
		if(empty($aProgressEntries)) {
			// Wenn kein interner Fortschritt bisher existiert: Kurs ignorieren
			return;
		}

		$dFrom = new DateTime($aData['course_from']);
		$dUntil = new DateTime($aData['course_until']);
		$dWeekFrom = Ext_Thebing_Util::getWeekFromCourseStartDate($dFrom);
		$dWeekFrom->setTime(0, 0, 0);
		$dUntil->setTime(0, 0, 0);
		$oPeriod = new DatePeriod($dWeekFrom, new DateInterval('P1W'), $dUntil);

		$aProgressEntriesWeekDates = array_map(function($aProgressEntry) {
			return new DateTime($aProgressEntry['week']);
		}, $aProgressEntries);

		// Wochen ermitteln, welche zwischen Startwoche und erstem internen Fortschritt fehlen
		$aMissingWeeks = [];
		foreach($oPeriod as $dWeek) {
			if(!in_array($dWeek, $aProgressEntriesWeekDates)) {
				$aMissingWeeks[] = $dWeek;
			} else {
				break;
			}
		}

		if(!empty($aMissingWeeks)) {
			$this->iAffectedCourses++;
			$this->iMissingWeeks += count($aMissingWeeks);
		}

		// Fehlende Wochen eintragen
		foreach($aMissingWeeks as $dWeek) {

			$aFirstEntry = reset($aProgressEntries);

			$aInsertData = [
				'active' => 1,
				'inquiry_course_id' => $aData['course_id'],
				'course_id' => $aData['tuition_course_id'],
				'week' => $dWeek->format('Y-m-d'),
				'level' => $aData['pt_level_id'],
				'inquiry_id' => $aData['inquiry_id'],
				'levelgroup_id' => $aFirstEntry['levelgroup_id']
			];

			$this->insertProgressWeek($aInsertData);

			$this->logInfo(sprintf('Inserted internal progress: week %s, journey course %d, course %d', $aInsertData['week'], $aInsertData['inquiry_course_id'], $aInsertData['course_id']));

		}

	}

	private function insertProgressWeek(array $aInsertData) {

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_progress`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`week` = :week AND
				`levelgroup_id` = :levelgroup_id AND
				`inquiry_course_id` = :inquiry_course_id AND
				`course_id` = :course_id
		";

		// Da es hier einen UNIQUE ohne active gibt, muss das hier gemacht werden…
		$iId = DB::getQueryOne($sSql, $aInsertData);
		if(!is_null($iId)) {
			DB::updateData('kolumbus_tuition_progress', ['active' => 1], '`id` = '.$iId);
		} else {
			DB::insertData('kolumbus_tuition_progress', $aInsertData, true);
		}

	}

}