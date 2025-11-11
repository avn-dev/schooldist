<?php


class Ext_TS_Tuition_Attendance_Gui2_Icon_Active extends Ext_Thebing_Gui2_Icon_Inbox {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->id == 'student_login_release' ||
			$oElement->id == 'student_login_release_remove' ||
			$oElement->task === 'deleteRow'
		) {
            if(!empty($aSelectedIds) && !empty($aRowData)) {
				foreach($aRowData as $aRow) {
					if($aRow['has_attendance'] != 1) {
						return 0;
					}
				}
				
				return 1;
			} else {
				return 0;
			}
		} else {
			return parent::getStatus($aSelectedIds, $aRowData, $oElement);
		}

	}
}