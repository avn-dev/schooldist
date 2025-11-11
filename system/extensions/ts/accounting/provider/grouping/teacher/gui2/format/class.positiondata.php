<?php

class Ext_TS_Accounting_Provider_Grouping_Teacher_Gui2_Format_PositionData extends Ext_TS_Accounting_Provider_Grouping_Gui2_Format_PositionData {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$aReturn = array();
		$oPayment = Ext_Thebing_Teacher_Payment::getInstance($aResultData['id']);
		$oDummy = null;

		if($oColumn->db_column === 'month_week') {

			$oFormat = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Week();
			$aResultData['select_type'] = $aResultData['payment_type'];
			$aReturn[] = $oFormat->format($mValue, $oDummy, $aResultData);

		} if($oColumn->db_column === 'days') {

			$oBlock = $oPayment->getBlock();
			$oFormat = new Ext_Thebing_Gui2_Format_Day();
			$aBlockDays = (array)$oBlock->days;
			foreach($aBlockDays as $iDay) {
				$aReturn[] = $oFormat->format($iDay);
			}

		} elseif($oColumn->db_column === 'classname') {

			$aBlocks = $this->getBlocks($oPayment);
			foreach($aBlocks as $oBlock) {
				$oClass = $oBlock->getClass();
				$aReturn[$oClass->id] = $oClass->getName();
			}

		}  elseif($oColumn->db_column === 'courses') {

			$oFormat = new Ext_Thebing_Gui2_Format_Course_List();
			$aReturn[] = $oFormat->format($oPayment->course_list);

		} elseif($oColumn->db_column === 'lessons') {

			$oFormat = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Lesson();
			$oFormat->bFormatLessons = true;
			$aReturn[] = $oFormat->format(0, $oDummy, $aResultData);

		} elseif($oColumn->db_column === 'count_bookings') {

			$aBlocks = $this->getBlocks($oPayment);
			$aStudentCount = [];
			foreach($aBlocks as $oBlock) {
				$aStudents = $oBlock->getStudents();
				foreach($aStudents as $aStudent) {
					// Schüler einzeln zählen (relevant für Gehalt je Lektion pro Monat)
					$aStudentCount[$aStudent['student_id']] = true;
				}
			}

			$oFormat = new Ext_Thebing_Gui2_Format_Int();
			$aReturn[] = $oFormat->format(count($aStudentCount), $oDummy, $aResultData);

		} elseif($oColumn->db_column === 'per_lesson_month') {

			$iSalaryId = $oPayment->salary_id;
			if($iSalaryId > 0) {
				$oSalary = Ext_Thebing_Teacher_Salary::getInstance($oPayment->salary_id);
				$oFormat = new Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Singleamount();
				$aResultData['select_type'] = $aResultData['payment_type'];
				if($aResultData['calculation'] == 5) {
					// Bei Gehalt je Lektion pro Monat immer »pro Lektion« anzeigen
					$aResultData['select_type'] = 'week';
				}
				$aReturn[] = $oFormat->format($oSalary->salary, $oDummy, $aResultData);
			}

		} elseif(
			$oColumn->db_column === 'comment' ||
			$oColumn->db_column === 'payed_additional_comment'
		) {
			$aReturn[] = $this->_getCommentData($oColumn->db_column, $oPayment);
		}

		$sReturn = join(', ', $aReturn);
		return $sReturn;
	}

	public function align(&$oColumn = null) {
		if(
			$oColumn->db_column === 'count_bookings' ||
			$oColumn->db_column === 'hours'
		) {
			return 'right';
		} else {
			return 'left';
		}
	}

	/**
	 * @param Ext_Thebing_Teacher_Payment $oPayment
	 * @return Ext_Thebing_School_Tuition_Block[]
	 */
	private function getBlocks(Ext_Thebing_Teacher_Payment $oPayment) {

		$aBlockIds = [$oPayment->block_id];
		if(!empty($oPayment->block_list)) {
			$aBlockIds = explode(',', $oPayment->block_list);
		}

		return array_map(function($iBlockId) {
			return Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		}, $aBlockIds);

	}

}