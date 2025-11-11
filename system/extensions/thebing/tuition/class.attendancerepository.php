<?php

class Ext_Thebing_Tuition_AttendanceRepository extends WDBasic_Repository {

	/**
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param string $sWeek
	 * @return Ext_Thebing_Tuition_Attendance
	 */
	public function getOrCreateAttendanceObject(Ext_Thebing_School_Tuition_Allocation $oAllocation): Ext_Thebing_Tuition_Attendance {

		$block = $oAllocation->getBlock();
		$sWeek = $block->week;
		
		$oAttendance = Ext_Thebing_Tuition_Attendance::query()
			->withTrashed()
			->where('allocation_id', $oAllocation->id)
			->where('week', $sWeek)
			->first();

		if ($oAttendance === null) {

			$oBlock = $oAllocation->getBlock();

			$inquiry = $oAllocation->getJourneyCourse()->getInquiry();
			
			$oAttendance = Ext_Thebing_Tuition_Attendance::getInstance();
			$oAttendance->journey_course_id = $oAllocation->inquiry_course_id;
			$oAttendance->week = $sWeek;
			$oAttendance->inquiry_id = $inquiry->id;
			$oAttendance->allocation_id = $oAllocation->id;
			$oAttendance->program_service_id = $oAllocation->program_service_id;
			$oAttendance->course_id = $oAllocation->course_id;
			$oAttendance->teacher_id = $oBlock->teacher_id;

		}

		$oAttendance->active = 1;

		return $oAttendance;
	}

	/**
	 * Anwesenheit aus GUI speichern
	 *
     * @todo Es gibt mehrere Stellen wo die Anwesenheit gespeichert wird, vllt sollte man hier mal eine Klasse für schreiben
     * - \Ext_Thebing_Tuition_AttendanceRepository::saveAttendance()
     * - \TsStudentApp\Pages\Attendance::scanQrCode()
     * - \TsTeacherLogin\Controller::saveAttendance()
     *
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation
	 * @param array $aData
	 */
	public function saveAttendance(Ext_Thebing_School_Tuition_Allocation $oAllocation, array $aData) {

		$aAbsencePerDay = $aData['absence_per_day'];
		$aCompleteAbsencePerDay = $aData['absence_complete_per_day'];
		$aExcusedPerDay = $aData['absence_excused_per_day'];
		$absenceReasonPerDay = array_filter($aData['absence_reason_per_day']);

		$oBlock = $oAllocation->getBlock();
		$oJourneyCourse = $oAllocation->getJourneyCourse();
		$room = $oAllocation->getRoom();

		$oAttendance = $this->getOrCreateAttendanceObject($oAllocation);

		// Jeden Wochentag durchlaufen
		$aDuration = Ext_Thebing_School_Tuition_Allocation::getRepository()->getWeekDayDurations($oAllocation);

		foreach(range(1 ,7) as $iDay) {

			if ($oBlock->getUnit($iDay)->isCancelled()) {
				continue;
			}

			$sDay = Ext_TC_Util::convertWeekdayToString($iDay);
			$oAttendance->$sDay = 0;

			// Abwesenheit für Tag wurde eingetragen
			if(!empty($aAbsencePerDay[$iDay])) {
				$oAttendance->$sDay = $aAbsencePerDay[$iDay];
			}

			// Komplett abwesend (Checkbox) oder eingetragene Abwesenheit größer als mögliche Anwesenheit
			if(
				!empty($aCompleteAbsencePerDay[$iDay]) ||
				$aAbsencePerDay[$iDay] > $aDuration[$iDay]
			) {
				$oAttendance->$sDay = (float)$aDuration[$iDay];
			}

			// $room->id == 0 bei virtuellen Räumen
			if ($room->isOnline() || $room->id == 0) {
				$oAttendance->online |= pow(2, ($iDay - 1));
			}

		}

		// Entschuldigte Abwesenheit als 7-bit
		$oAttendance->excused = 0;
		foreach(array_keys($aExcusedPerDay) as $iDay) {
			$oAttendance->excused |= pow(2, ($iDay - 1));
		}

		if(empty($absenceReasonPerDay)) {
			$oAttendance->absence_reasons = null;
		} else {
			$oAttendance->absence_reasons = $absenceReasonPerDay;
		}

		if(!empty($aData['score'])) {
			$oAttendance->score = $aData['score'];
		}

		if(!empty($aData['comment'])) {
			$oAttendance->comment = $aData['comment'];
		}

		$oAttendance->save();

	}

}
