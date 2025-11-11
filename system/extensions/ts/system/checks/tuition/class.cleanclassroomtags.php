<?php

include_once Util::getDocumentRoot().'system/includes/admin.inc.php';

class Ext_TS_System_Checks_Tuition_CleanClassroomTags extends GlobalChecks {

	public function getTitle() {
		return 'Clean classroom properties';
	}

	public function getDescription() {
		return 'Broken X removed when classroom properties are empty.';
	}

	public function executeCheck() {

		$backupTables = [
			'ts_classrooms_tags',
			'ts_classrooms_to_tags'
		];

		foreach ($backupTables as $backupTable) {
			$result = Util::backupTable($backupTable);
			if (!$result) {
				return false;
			}
		}

		DB::executeQuery("DELETE ts_ct, ts_ctt
			FROM ts_classrooms_tags ts_ct
			JOIN ts_classrooms_to_tags ts_ctt ON ts_ct.id = ts_ctt.tag_id
			WHERE ts_ct.tag = ''");

		return true;
	}

}
