<?php

class Ext_Thebing_System_Checks_Tuition_ClassroomUsage extends GlobalChecks {

	public function getTitle() {
		return 'Classroom usage across schools';
	}

	public function getDescription() {
		return 'Makes it possible to sort and select single rooms for usage in other schools instead of all rooms at once.';
	}

	public function executeCheck() {

		$aTables = DB::listTables();
		if(!in_array('kolumbus_classroom_usage', $aTables)) {
			return true;
		}

		Util::backupTable('kolumbus_classroom_usage');

		DB::executeQuery("TRUNCATE TABLE `ts_schools_classrooms_usage`");

		$aSchools = Ext_Thebing_School::getRepository()->findAll();

		// Sollte theoretisch auch alte Sortierung sein, da Schulen im alten Select nie sortiert wurden
		/*
		 	Alte Sortier-Logik vom Query (Ext_Thebing_School::getClassRoomList()):

			IF(
				(kc.`idSchool` = :school_id),
				0,
				1
			),
			kc.`idSchool` ASC,
			kc.`sort_order` ASC
		 */
		$iPosition = 1;

		foreach($aSchools as $oSchool) {

			$sSql = "
				SELECT
					`offerer_school_id`
				FROM
					`kolumbus_classroom_usage`
				WHERE
					`user_school_id` = {$oSchool->id}
			";

			$aOffererSchoolIds = (array)DB::getQueryCol($sSql);
			foreach($aOffererSchoolIds as $iOffererSchoolId) {

				$aSql = [
					'school_id' => $oSchool->id,
					'offerer_school_id' => $iOffererSchoolId,
				];

				$sSql = "
					SELECT
						`id`
					FROM
						`kolumbus_classroom`
					WHERE
						`active` = 1 AND
						`idSchool` = :offerer_school_id
					ORDER BY
						`sort_order`
				";

				$aClassroomIds = (array)DB::getQueryCol($sSql, $aSql);
				foreach($aClassroomIds as $iClassroomId) {

					$aSql['classroom_id'] = $iClassroomId;
					$aSql['position'] = $iPosition++;

					$sSql = "
						INSERT INTO
							`ts_schools_classrooms_usage`
						SET
							`school_id` = :school_id,
							`classroom_id` = :classroom_id,
							`position` = :position
					";

					DB::executePreparedQuery($sSql, $aSql);

				}

			}
		}

		DB::executeQuery("DROP TABLE `kolumbus_classroom_usage`");

		return true;
	}

}
