<?php

class Ext_TS_Accounting_Provider_Grouping_Teacher_Gui2_Format_Timeframe extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		$oGrouping = Ext_TS_Accounting_Provider_Grouping_Teacher::getInstance($aResultData['id']);

		$aPayments = $oGrouping->getPayments();
		foreach($aPayments as $oPayment) {
			$sTimeFrame = $oPayment->getTimeFrameInformation();
			if(!in_array($sTimeFrame, $aReturn)) {
				$aReturn[] = $oPayment->getTimeFrameInformation();
			}
		}

		$sReturn = join(', ', $aReturn);
		return $sReturn;
	}

}