<?php


class Ext_TS_System_Checks_Tuition_Placementtest_SetResultCourseLanguages extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Setting course languages of results';
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
		ini_set('memory_limit', '4G');

		$results = \Ext_Thebing_Placementtests_Results::query()
			->where('courselanguage_id', '0')
			->get();

		// Wenn Check schon ausgeführt wurde / es keine Einträge mit courselanguage_id = 0 gibt.
		if($results->isempty()) {
			return true;
		}

		$success = Ext_Thebing_Util::backupTable('ts_placementtests_results');
		if(!$success) {
			return false;
		}

		foreach ($results as $result) {

			$placementtest = $result->getPlacementtest();

			// courselanguage_id sollte eigentlich nur bei Einträgen 0 sein, wo es auch ein Placementtest gibt.
			// -> Sonst wurde der Dialog gespeichert und da kommt die courselanguage_id aus der GUI mit $this->_oGui->decodeId()
			if ($placementtest->id == 0) {
				// Damals gab es Schulen die keine Fragen hatten, trotzdem aber Einträge in ts_placementtests_results.
				// Durch die Abfrage in Ext_TS_System_Checks_Tuition_Placementtest_NewStructure gibt es keine PTs dazu.

				// Wir nehmen hier den erstbesten Kurs, könnte natürlich falsch sein, aber wir haben hier nicht die
				// Information welchen Kurs sonst.
				$inquiry = $result->getInquiry();
				$inquiryCourse = $inquiry->getFirstCourse(true, true, true, false);
				if (!$inquiryCourse) {
					$courses = $inquiry->getSchool()->getCourses();
					$courselanguageId = reset($courses)->getLevelgroup()->id;
				} else {
					$courselanguageId = $inquiryCourse->getCourseLanguage()->id;
				}
			} else {
				// Ist immer gegeben, durch den Check "class.newstructure.php" und bei neuen Einträgen ist das Feld ein Pflichtfeld.
				$courselanguageId = $placementtest->courselanguage_id;
			}


			$sSql = "
					UPDATE 
						ts_placementtests_results
					SET
						`changed` = `changed`,
						`courselanguage_id` = :courselanguage_id
					WHERE
						`id` = :id 
				";

			$aSql = [
				'id' => $result->id,
				'courselanguage_id' => $courselanguageId,
			];

			DB::executePreparedQuery($sSql, $aSql);
		}

		return parent::executeCheck();
	}

}