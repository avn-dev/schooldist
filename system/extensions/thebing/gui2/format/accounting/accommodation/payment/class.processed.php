<?php

class Ext_Thebing_Gui2_Format_Accounting_Accommodation_Payment_Processed extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param array|null $oColumn
	 * @param array|null $aResultData
	 *
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oFormat = new Ext_Thebing_Gui2_Format_Date_Time();

		if	(
			!empty($aResultData['processed']) &&
			!empty($aResultData['index_processed_user_id'])
		) {
			$oSystemUser = new Ext_Thebing_User($aResultData['index_processed_user_id']);
			$sDescription = $oSystemUser->lastname .', ' . $oSystemUser->firstname . ' | ' . $oFormat->format($aResultData['processed']);

			$sReturn = '<a href="'.$aResultData['absolute_path'].'" target="_blank">'.$sDescription.'</a>';

			return $sReturn;
		}

	}

}