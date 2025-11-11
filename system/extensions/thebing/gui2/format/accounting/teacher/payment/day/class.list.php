<?php

class Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Day_list extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oFormat = new Ext_Thebing_Gui2_Format_Day();

		$mBack = '';

		$aDayData = array();

		$aSubstituteData = explode(',', $aResultData['substitute_days']);

		foreach((array)$aSubstituteData as $sData){
			$aData = explode('_', $sData);
			$iDay = $aData[0];
			$iLessions = $aData[1];
			$aDayData[$iDay] += $iLessions;
		}

		$sLessonsOfDay = $aResultData['lessons_of_day'];
		$aLessonsOfDayTemp = explode(',', $sLessonsOfDay);
		$aLessonsOfDay = array();

		$sLessonsOfDayHoliday = $aResultData['lessons_of_day_holiday'];
		$aLessonsOfDayHolidayTemp = explode(',', $sLessonsOfDayHoliday);

		foreach((array)$aLessonsOfDayTemp as $sData){
			$aData = explode('_', $sData);
			$iDay = $aData[0];
			$iLessions = $aData[1];
			$aLessonsOfDay[$iDay] += $iLessions;
		}

		foreach((array)$aLessonsOfDayHolidayTemp as $sData){
			$aData = explode('_', $sData);
			$iDay = $aData[0];
			$iLessions = $aData[1];
			$aLessonsOfDay[$iDay] += $iLessions;
		}

		$aTeacherAbsence	= array();
		$sTeacherAbsence	= $aResultData['teacher_absence'];
		if(!empty($sTeacherAbsence)){
			$aTeacherAbsence	= explode(',',$sTeacherAbsence);
		}

		$aDays = explode(',', $aResultData['days']);

		foreach((array)$aDays as $iDay){

			if($iDay <= 0){
				continue;
			}

			$sColor = '';

			if(
				(
				$aDayData[$iDay] > 0 &&
				$aDayData[$iDay] >= $aLessonsOfDay[$iDay]
				) ||
				in_array($iDay,$aTeacherAbsence)
			){
				$sColor = Ext_Thebing_Util::getColor('substitute_full');
			} else if($aDayData[$iDay] > 0){
				$sColor = Ext_Thebing_Util::getColor('substitute_part');
			}

			if($sColor != ""){
				$mBack .= '<span style="color:'.$sColor.';">';
			}
			
			$mBack .= $oFormat->format($iDay, $oColumn, $aResultData);

			if($sColor != ""){
				$mBack .= '</span>';
			}

			$mBack .= ', ';
			
		}

		$aDays = explode(',', $aResultData['days_holiday']);

		foreach((array)$aDays as $iDay){

			if($iDay <= 0){
				continue;
			}

			$sColor = Ext_Thebing_Util::getColor('soft_purple');

			if(
				(
				$aDayData[$iDay] > 0 &&
				$aDayData[$iDay] >= $aLessonsOfDay[$iDay]
				) ||
				in_array($iDay,$aTeacherAbsence)
			){
				$sColor = Ext_Thebing_Util::getColor('substitute_full');
			} else if($aDayData[$iDay] > 0){
				$sColor = Ext_Thebing_Util::getColor('substitute_part');
			} 

			if($sColor != ""){
				$mBack .= '<span style="color:'.$sColor.';">';
			}

			$mBack .= $oFormat->format($iDay, $oColumn, $aResultData);

			if($sColor != ""){
				$mBack .= '</span>';
			}

			$mBack .= ', ';

		}

		$mBack = rtrim($mBack, ', ');

		return $mBack;
	}

}
