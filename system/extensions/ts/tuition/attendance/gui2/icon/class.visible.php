<?php

class Ext_TS_Tuition_Attendance_Gui2_Icon_Visible extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

        if(
	        $oElement->id === 'student_login_release' ||
	        $oElement->id === 'student_login_release_remove'
        ) {

            if(!empty($aSelectedIds) && !empty($aRowData))  {

                $iAttendanceCounter     = 0;
                $iNotReleasedCounter    = 0;

                foreach($aRowData as $aRow) {

                    if($aRow['has_attendance'] == 1) {
                        $iAttendanceCounter++;
                    }
                    if($aRow['student_login_release'] == '0000-00-00') {
                        $iNotReleasedCounter++;
                    }

                }

                if($iAttendanceCounter > 0) {

                    if($oElement->id == 'student_login_release') {

                        if($iNotReleasedCounter > 0) {
                            return 1;
                        } else {
                            return 0;
                        }

                    } else {

                        if($iNotReleasedCounter > 0) {
                            return 0;
                        } else {
                            return 1;
                        }

                    }

                } else {
                    return 0;
                }

            } else {
                return 0;
            }

        } else {
            return parent::getStatus($aSelectedIds, $aRowData, $oElement);
        }

	}

}