<?php

/**
 * Korrigiert die Werte, die durch einen Bug vom Lehrerlogin AttendanceController immer eine Woche davor gespeichert wurden.
 *
 * https://redmine.thebing.com/issues/13421
 */
class Ext_TS_System_Checks_Tuition_Attendance_FixTeacherLoginEntries extends GlobalChecks {

	public function getTitle() {
		return 'Tuition Attendance Check';
	}

	public function getDescription() {
		return 'Correct attendance values saved in the previous week';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_tuition_attendance');

		DB::begin(__CLASS__);

		$aAttendanceDays = Ext_Thebing_System::getAttendanceDays();

		$sSql = "
			SELECT 
				kta.*,
 				ktb.week block_week 
			FROM 
				`kolumbus_tuition_attendance` kta JOIN 
				kolumbus_tuition_blocks_inquiries_courses ktbic ON 
				kta.allocation_id = ktbic.id JOIN 
				kolumbus_tuition_blocks ktb ON 
				ktbic.block_id = ktb.id 
 			WHERE 
 				kta.week != ktb.week AND
 				kta.active = 1
 			";

		$aFalseAttendances = DB::getQueryRows($sSql);

		foreach($aFalseAttendances as $aFalseAttendance) {

			$sSql = "
				SELECT
					*
				FROM 
					`kolumbus_tuition_attendance` kta
				WHERE
					kta.allocation_id = :allocation_id AND
					kta.week = :block_week
			";

			$aTrueAttendances = DB::getQueryRows($sSql, $aFalseAttendance);

			if(empty($aTrueAttendances)) {

				// Woche korrigieren
				DB::updateData('kolumbus_tuition_attendance', ['week' => $aFalseAttendance['block_week']], "`id` = ".$aFalseAttendance['id']);
				$this->logInfo('KTA '.$aFalseAttendance['id'].': Set week '.$aFalseAttendance['week'].' to '.$aFalseAttendance['block_week']);

			} else {

				if(count($aTrueAttendances) > 1) {
					throw new \RuntimeException('kta returned more than one row');
				}

				$aTrueAttendance = reset($aTrueAttendances);
				$aData = [];

				foreach($aAttendanceDays as $sDayKey) {
					if(
						$aTrueAttendance[$sDayKey] === null &&
						$aFalseAttendance[$sDayKey] !== null
					) {
						$aData[$sDayKey] = $aFalseAttendance[$sDayKey];
					}
				}

				if(
					trim($aTrueAttendance['score']) === '' &&
					trim($aFalseAttendance['score']) !== ''
				) {
					$aData['score'] = $aFalseAttendance['score'];
				}

				if(
					trim($aTrueAttendance['comment']) === '' &&
					trim($aFalseAttendance['comment']) !== ''
				) {
					$aData['comment'] = $aFalseAttendance['comment'];
				} else {
					if(trim($aFalseAttendance['comment']) !== '') {
						$aData['comment'] = $aTrueAttendance['comment'].' '.$aFalseAttendance['comment'];
					}
				}

				// Eintrag wurde nach Bugfix ggf. neu angelegt
				if(!empty($aData)) {
					DB::updateData('kolumbus_tuition_attendance', $aData, "`id` = ".$aTrueAttendance['id']);
				}

				DB::updateData('kolumbus_tuition_attendance', ['active' => 0], "`id` = ".$aFalseAttendance['id']);
				$this->logInfo('KTA '.$aTrueAttendance['id'].' updated, KTA '.$aFalseAttendance['id'].' deleted', $aData);

			}
		}

		DB::commit(__CLASS__);

		return true;

	}

}
