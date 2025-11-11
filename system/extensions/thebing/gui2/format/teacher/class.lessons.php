<?php

class Ext_Thebing_Gui2_Format_Teacher_Lessons extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sPeriod = '';

		$mValue = $aResultData['lessons'];

		if($aResultData['lessons_period']) {
			$sPeriod = Ext_Thebing_Teacher_Salary::getPeriods($aResultData['lessons_period']);
		}

		$oFormat = new Ext_Thebing_Gui2_Format_Float(2, true);

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aResultData['school_id'] = $oSchool->id;

		if((int)$mValue > 0){
			$mValue = $oFormat->format($mValue, $aDummy, $aResultData);

			if($sPeriod) {
				$mValue = $mValue.' '.L10N::t('Lektionen pro', 'Thebing » Tuition » Teachers').' '.$sPeriod;
			}
		} else {
			$mValue = '';
		}

		return $mValue;

	}

}