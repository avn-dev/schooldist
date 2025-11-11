<?php

class Ext_Thebing_Tuition_Gui2_Attendance_Gui2 extends Ext_Thebing_Gui2 {

	const TRANSLATION_PATH = 'Thebing » Tuition » Attendance';

	public function getVisibleColumnList($sFlexType = 'list', $aColumnList=null) {
		global $_VARS;

		$aColumns = parent::getVisibleColumnList($sFlexType, $aColumnList);
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if(
			$oSchool->course_startday != 1 &&
			$this->set === 'edit'
		) {
			// Montag-Spalte suchen
			foreach($aColumns as $iKey => $oColumn) {
				if($oColumn->db_column === 'mo') {
					$iOffset = $iKey;
					$aDayColumns = array_slice($aColumns, $iKey, 7);
					break;
				}
			}

			// Columns umsortieren nach entsprechendem Starttag
			for($i=0; $i < $oSchool->course_startday - 1; $i++) {
				$oDayColumn = $aDayColumns[$i];
				unset($aDayColumns[$i]);
				$aDayColumns[] = $oDayColumn;
			}

			array_splice($aColumns, $iOffset, 7, $aDayColumns);
		}

		// In der Übersicht verschiedene Spalten je nach Filter
		if($this->set === 'list') {

			$sAttendanceView = 'journey_course';
			if(!empty($_VARS['filter']['attendance_view'])) {
				$sAttendanceView = $_VARS['filter']['attendance_view'];
			}

			// Spalten je nach Anwesenheit-View ausblenden
			foreach($aColumns as $iKey => $oColumn) {
				if(
					(
						$sAttendanceView === 'inquiry' && (
							$oColumn->db_column === 'course_name_short' ||
							$oColumn->db_column === 'level_name_short' ||
							$oColumn->db_column === 'course_start' ||
							$oColumn->db_column === 'course_end' ||
							$oColumn->db_column === 'teacher_name' ||
							$oColumn->db_column === 'journey_course_teacher_period_attendance' ||
							$oColumn->db_column === 'journey_course_teacher_all_attendance' ||
							$oColumn->db_column === 'journey_course_period_attendance' ||
							$oColumn->db_column === 'journey_course_all_attendance' ||
							$oColumn->db_column === 'journey_course_all_expected_attendance' ||
							$oColumn->db_column === 'index_attendance_warning'
						)
					) || (
						$sAttendanceView === 'journey_course' && (
							$oColumn->db_column === 'journey_course_teacher_period_attendance' ||
							$oColumn->db_column === 'journey_course_teacher_all_attendance' ||
							$oColumn->db_column === 'teacher_name'
						)
					)
				) {
					unset($aColumns[$iKey]);
				}
			}
		}

		// JSON-Array erzwingen
		return array_values($aColumns);
	}

}