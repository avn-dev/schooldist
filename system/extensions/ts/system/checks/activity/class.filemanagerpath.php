<?php

class Ext_TS_System_Checks_Activity_FilemanagerPath extends GlobalChecks {

	public function getTitle() {
		return 'Migrate activity filemanger items';
	}

	public function getDescription() {
		return '';
	}

	/**
	 * @see \TsActivities\Entity\Activity::getFileManagerEntityPath()
	 */
	public function executeCheck() {

		$dir = storage_path('filemanager/tsactivities_entity_activity');
		if (is_dir($dir)) {
			rename($dir, storage_path('filemanager/ts_activity'));
		}

		$dir = storage_path('public/filemanager/tsactivities_entity_activity');
		if (is_dir($dir)) {
			rename($dir, storage_path('public/filemanager/ts_activity'));
		}

		return true;

	}

}
