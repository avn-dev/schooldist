<?php

class Ext_Thebing_Gui2_Format_Tuition_Attendance_AttendanceWarning extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param array|null $oColumn
	 * @param array|null $aResultData
	 *
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oFormat = new Ext_Thebing_Gui2_Format_Date_Time();

		$attendanceWarnings = json_decode($aResultData['index_attendance_warning'], true);

		$return = '';
		if(!empty($attendanceWarnings)) {
			$count = 1;
			foreach ($attendanceWarnings as $attendanceWarning) {

				// Damals gab es das nicht
				$templateName = $attendanceWarning['template_name'] ?? 'N/A';

				// Es wurde immer nur das letzte Datum gespeichert damals
				if (empty($attendanceWarning['date'])) {
					$date = 'N/A';
				} else {
					$date = $oFormat->format($attendanceWarning['date'], $oColumn, $aResultData);
				}

				$return .= $count.' | '.$templateName.' '.$date.'<br>';
				$count++;
			}
		}

		return $return;

	}

}