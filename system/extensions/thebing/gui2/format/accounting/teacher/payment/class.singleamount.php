<?php

class Ext_Thebing_Gui2_Format_Accounting_Teacher_Payment_Singleamount extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oFormat = new Ext_Thebing_Gui2_Format_Amount();

		$sBack = '';

		if(!empty($aResultData['days']) || $aResultData['select_type'] != 'week'){
			$sBack = $oFormat->format($mValue, $oColumn, $aResultData);
			if(!empty($aResultData['days_holiday'])){
				$sBack .= ' / ';
			}
		}
		if(!empty($aResultData['days_holiday'])){
			$sColor = Ext_Thebing_Util::getColor('soft_purple');
			$sBack .= '<span style="color:'.$sColor.'">'.$oFormat->format($aResultData['amount_holiday'], $oColumn, $aResultData);
			$sBack .= '</span>';
		} 

		if($aResultData['select_type'] == 'week') {
			if(!empty($aResultData['lesson_school_option'])) {
				$sBack .= ' '.L10N::t('je Stunde', 'Thebing » Tuition » Teachers');
			} else {
				$sBack .= ' '.L10N::t('je Lektion', 'Thebing » Tuition » Teachers');
			}
		} else if($aResultData['select_type'] == 'fix_week'){
			$sBack .= ' '.L10N::t('je Woche', 'Thebing » Tuition » Teachers');
		} else {
			$sBack .= ' '.L10N::t('je Monat', 'Thebing » Tuition » Teachers');
		}

		return $sBack;
	}

}
