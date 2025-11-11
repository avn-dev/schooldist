<?php

class Ext_TS_System_Checks_Tuition_Attendance_CacheDurations extends GlobalChecks {
	
	public function getTitle() {
		return 'Cache attendance data';
	}

	public function getDescription() {
		return 'Cache total and attended minutes per student and week.';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2G');
		
		$bSuccess = Util::backupTable('kolumbus_tuition_attendance');
		
		if(!$bSuccess) {
			__pout('couldnt backup table!');
			return false;
		}
		
		$aColumns = DB::describeTable('kolumbus_tuition_attendance', true);

		if (!isset($aColumns['duration'])) {
			DB::addField('kolumbus_tuition_attendance', 'duration', 'INT(11) NULL DEFAULT NULL');
		}
		if (!isset($aColumns['attended'])) {
			DB::addField('kolumbus_tuition_attendance', 'attended', 'INT(11) NULL DEFAULT NULL');
		}
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_attendance` `kta`
			WHERE
			    active = 1
				/*`duration` IS NULL OR
				`attended` IS NULL*/
		";
		
		$oDB = DB::getDefaultConnection();
		
		$oCollection = $oDB->getCollection($sSql, array());
		
		foreach($oCollection as $result) {
			$this->addProcess(['id' => (int)$result['id']], 10);
		}
		
		return true;
	}
	
	public function executeProcess(array $data) {
		
		if(isset($data['id'])) {

			$attendance = Ext_Thebing_Tuition_Attendance::getInstance($data['id']);
			$attendance->refreshIndex();

			$updateColumns = [
				...array_values(\Ext_Thebing_Tuition_Attendance::DAY_MAPPING),
				...['duration', 'attended']
			];

			foreach ($updateColumns as $column) {
				if ($attendance->getData($column) != $attendance->getOriginalData($column)) {
					$sql = "
						UPDATE 
							`kolumbus_tuition_attendance`
						SET
						    `changed` = `changed`,
							#column = :value
						WHERE
						    `id` = :id
					";

					$this->logInfo('Update column', ['attendance_id' => $attendance->id, 'column' => $column, 'value' => $attendance->getData($column), 'original_value' => $attendance->getOriginalData($column)]);

					\DB::executePreparedQuery($sql, [
						'id' => $attendance->id,
						'column' => $column,
						'value' => $attendance->getData($column)
					]);
				}
			}
		}
		
	}
	
}