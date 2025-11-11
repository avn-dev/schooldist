<?php


class Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Week extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if(
			$aResultData['calculation'] != 5 &&
			$aResultData['select_type'] == 'week' ||
			$aResultData['select_type'] == 'fix_week'
		) {
			$oWDDate = new WDDate();
			$oWDDate->set($aResultData['timepoint'], WDDate::DB_DATE);
			$mValue = $oWDDate->get(WDDate::TIMESTAMP);
			$mValue = Ext_Thebing_Util::getWeekTitle($mValue);
		} else {
			$oWDDate = new WDDate();
			$oWDDate->set($aResultData['timepoint'], WDDate::DB_DATE);
			$mValue = $oWDDate->get(WDDate::STRFTIME, '%B %Y');
			$mValue = L10N::t('Monat').' '.$mValue;
		}

		return $mValue;
	}

}