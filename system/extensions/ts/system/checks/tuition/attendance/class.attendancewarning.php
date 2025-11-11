<?php

class Ext_TS_System_Checks_Tuition_Attendance_AttendanceWarning extends GlobalChecks
{
	public function getTitle()
	{
		return 'Attendance Warning';
	}

	public function getDescription()
	{
		return 'New Attendance Warning Table Structure';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		// Check bereits durchgelaufen
		if(
			DB::getDefaultConnection()->checkField('ts_inquiries_journeys_courses', 'index_attendance_warning', true) &&
			!DB::getDefaultConnection()->checkField('ts_inquiries_journeys_courses', 'index_attendance_warning_count', true) &&
			!DB::getDefaultConnection()->checkField('ts_inquiries_journeys_courses', 'index_attendance_warning_latest_date', true)
		) {
			return true;
		}

		if (!\Util::backupTable('ts_inquiries_journeys_courses')) {
			__pout('Backup error');
			return false;
		}

		// Die Kurse, denen eine Anwesenheitswarnung geschickt wurde
		$journeyCourses = Ext_TS_Inquiry_Journey_Course::query()
			->where('index_attendance_warning_count', '!=', '0')
			->where('index_attendance_warning_latest_date', '!=', '0000-00-00 00:00:00')
			->get();

		foreach ($journeyCourses as $journeyCourse) {
			$attendanceWarnings = [];
			$warningAmount = (int)$journeyCourse->index_attendance_warning_count;

			while ($warningAmount != 0) {

				// Die letzte geschickte Warnung
				if ($warningAmount == 1) {
					$attendanceWarnings[] =
						[
							'date' => date('Y-m-d H:i:s', $journeyCourse->index_attendance_warning_latest_date),
						];
				} else {
					$attendanceWarnings[] = [];
				}
				$warningAmount--;
			}


			$sSql = "
						UPDATE 
							ts_inquiries_journeys_courses 
						SET
							`changed` = `changed`,
							`index_attendance_warning` = :index_attendance_warning	
						WHERE
							`id` = :id 
					";

			$aSql = [
				'id' => $journeyCourse->id,
				'index_attendance_warning' => json_encode($attendanceWarnings)
			];

			DB::executePreparedQuery($sSql, $aSql);
		}

		$queries = [
			"ALTER TABLE `ts_inquiries_journeys_courses` drop `index_attendance_warning_count`",
			"ALTER TABLE `ts_inquiries_journeys_courses` drop `index_attendance_warning_latest_date`",
		];

		foreach($queries as $query) {
			try {
				DB::executeQuery($query);
			} catch (Exception $e) {
				__pout($e->getMessage(), 1);
				return false;
			}
		}

		return true;
	}

}