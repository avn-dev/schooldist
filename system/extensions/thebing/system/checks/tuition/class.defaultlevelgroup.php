<?php

/**
 * 1. Tabelle kolumbus_tuition_levelgroups_courses umwandeln in ktc.levelgroup_id und Tabelle löschen
 * 2. Default-Levelgroups pro Schule anlegen und ID 0 ersetzen (Kurse und Fortschritt)
 *
 * https://redmine.thebing.com/redmine/issues/8330
 */
class Ext_Thebing_System_Checks_Tuition_DefaultLevelGroup extends GlobalChecks {

	private $bCourseTableBackup = false;

	public function getTitle() {
		return 'Default Level groups';
	}

	public function getDescription() {
		return 'Add default levelgroup and allocate all courses without levelgroup.';
	}

	public function executeCheck() {

		set_time_limit(60);
		ini_set('memory_limit', '1G');

		$this->migrateLevelgroupAllocations();
		$this->createDefaultLevelGroups();

		return true;

	}

	private function migrateLevelGroupAllocations() {

		if(!Util::checkTableExists('kolumbus_tuition_levelgroups_courses')) {
			return;
		}

		Util::backupTable('kolumbus_tuition_courses');
		Util::backupTable('kolumbus_tuition_levelgroups_courses');
		$this->bCourseTableBackup = true;

		$bFieldExists = DB::getDefaultConnection()->checkField('kolumbus_tuition_courses', 'levelgroup_id', true);
		if(!$bFieldExists) {
			DB::executeQuery(" ALTER TABLE `kolumbus_tuition_courses` ADD `levelgroup_id` SMALLINT UNSIGNED NOT NULL AFTER `school_id`; ");
		}

		$aLevelgroupAllocations = (array)DB::getQueryRows("
			SELECT
				*
			FROM
				`kolumbus_tuition_levelgroups_courses`
		");

		foreach($aLevelgroupAllocations as $aRow) {
			DB::executePreparedQuery("
				UPDATE
					`kolumbus_tuition_courses`
				SET
					`levelgroup_id` = :levelgroup_id
				WHERE
					`id` = :course_id
			", $aRow);
		}

		DB::executeQuery(" DROP TABLE IF EXISTS `kolumbus_tuition_levelgroups_courses` ");

	}

	private function createDefaultLevelGroups() {

		Util::backupTable('kolumbus_tuition_progress');
		if(!$this->bCourseTableBackup) {
			Util::backupTable('kolumbus_tuition_courses');
		}

		DB::begin(__METHOD__);

		$aSchools = Ext_Thebing_School::getRepository()->findAll();

		foreach($aSchools as $oSchool) {

			$aCourseIds = (array)DB::getQueryCol("
				SELECT
					`ktc`.`id`
				FROM
					`kolumbus_tuition_courses` `ktc`
				WHERE
					`ktc`.`school_id` = :school_id AND
					`ktc`.`levelgroup_id` = 0
			", ['school_id' => $oSchool->id]);

			$aProgressEntryIds = (array)DB::getQueryCol("
				SELECT
					`ktp`.`id`
				FROM
					`kolumbus_tuition_progress` `ktp` INNER JOIN
					`ts_inquiries_journeys_courses` `ts_ijc` ON
						`ts_ijc`.`id` = `ktp`.`inquiry_course_id` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`id` = `ts_ijc`.`journey_id`
				WHERE
					`ktp`.`levelgroup_id` = 0 AND
					`ts_ij`.`school_id` = :school_id
			", ['school_id' => $oSchool->id]);

			// Wenn es keine 0er-Einträge gibt, wird keine Default-Levelgroup benötigt
			if(
				empty($aCourseIds) &&
				empty($aProgressEntryIds)
			) {
				continue;
			}

			$oLevelGroup = Ext_Thebing_Tuition_LevelGroup::getRepository()->findOneBy([
				'title' => 'Default level group',
				'school_id' => $oSchool->id
			]);

			if($oLevelGroup === null) {
				$oLevelGroup = new Ext_Thebing_Tuition_LevelGroup();
				$oLevelGroup->school_id = $oSchool->id;
				$oLevelGroup->title = 'Default level group';
				$oLevelGroup->active = 1; // Vorher kein Default-Wert?
				$oLevelGroup->save();
			}

			$oUpdate = function($sTable, $aIds) use($oLevelGroup) {
				DB::executePreparedQuery("
					UPDATE
						{$sTable}
					SET
						`changed` = `changed`,
						`levelgroup_id` = :levelgroup_id
					WHERE
						`id` IN ( :ids )
				", ['ids' => $aIds, 'levelgroup_id' => $oLevelGroup->id]);
			};

			$oUpdate('kolumbus_tuition_courses', $aCourseIds);
			$oUpdate('kolumbus_tuition_progress', $aProgressEntryIds);

		}

		DB::commit(__METHOD__);

	}

}