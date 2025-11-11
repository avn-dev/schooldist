<?php

class Ext_Thebing_Gui2_Format_Teacher_Salary extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($aResultData['costcategory_id'] == -1) {

			$sPeriod = '';

			if($aResultData['salary_period']) {
				$sPeriod = Ext_Thebing_Teacher_Salary::getPeriods($aResultData['salary_period']);
			}

			$oFormat = new Ext_Thebing_Gui2_Format_Amount();

			$oSchool = Ext_Thebing_School::getSchoolFromSession();

			$aResultData['currency_id'] = $oSchool->getTeacherCurrency();
			$aResultData['school_id'] = $oSchool->id;

			$mValue = $oFormat->format($mValue, $aDummy, $aResultData);

			if($sPeriod) {
				$mValue = $mValue.' '.L10N::t('pro', 'Thebing » Tuition » Teachers').' '.$sPeriod;
			}

		} else {
			$mValue = '';
		}

		return $mValue;

	}

}
