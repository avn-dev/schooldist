<?php

class Ext_TS_System_Checks_Tuition_Progress_LatestLevelChange extends GlobalChecks {
	
	public function getTitle() {
		return 'Latest level change';
	}

	public function getDescription() {
		return 'Indexes a reference to the last change to the level.';
	}

	public function executeCheck() {
		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		$tableInfo = \DB::describeTable('ts_inquiries_journeys_courses', true);
		
		if(isset($tableInfo['index_latest_level_change_progress_id'])) {
			return true;
		}

		\Util::backupTable('ts_inquiries_journeys_courses');
		
		\DB::executeQuery("ALTER TABLE `ts_inquiries_journeys_courses` ADD `index_latest_level_change_progress_id` INT NULL DEFAULT NULL, ADD INDEX `index_latest_level_change_progress_id` (`index_latest_level_change_progress_id`)");

		\Ext_TS_Inquiry_Journey_Course::deleteTableCache();

		$processes = \DB::getQueryRows("
			SELECT 
				`ktp`.`inquiry_course_id`,
				`ktp`.`program_service_id`
			FROM 
				`ts_inquiries_journeys_courses` `ts_ijc` JOIN
				`kolumbus_tuition_progress` `ktp` ON
					`ts_ijc`.`id` = `ktp`.`inquiry_course_id` AND
					`ktp`.`active` = 1
			WHERE 
				`ts_ijc`.`active` = 1 
			ORDER BY 
				`ts_ijc`.`id` DESC
		");

		foreach ($processes as $process) {
			$this->addProcess($process, 200);
		}

		return true;
	}

	public function executeProcess(array $data) {

		$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($data['inquiry_course_id']);
		$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($data['program_service_id']);

		$progressId = Ext_Thebing_Tuition_Progress::getCurrentLevelCount($oInquiryCourse, $oProgramService, true);

		if(!empty($progressId)) {
			\DB::executePreparedQuery("UPDATE `ts_inquiries_journeys_courses` SET `changed` = `changed`, `index_latest_level_change_progress_id` = :progress_id WHERE `id` = :id", ['id' => $data['inquiry_course_id'], 'progress_id' => $progressId]);
		}
		
	}

}
