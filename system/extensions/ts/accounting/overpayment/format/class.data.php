<?php

class Ext_TS_Accounting_Overpayment_Format_Data extends Ext_TS_Accounting_Payment_Format_Data {

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null|array $aResultData
	 * @return float|string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		switch($oColumn->select_column) {
			case 'not_assigned_amount':

				// Gesamtbetrag aller Items
				$fSumServiceAmounts = $this->_sumServiceAmounts($aResultData);
				
				$mValue = (float)$aResultData['overpayment'] - $fSumServiceAmounts;
				if($this->_bFormat) {
					$mValue = $this->formatSum($mValue, $oColumn, $aResultData);
				}

				break;
			default:
				$mValue = parent::format($mValue, $oColumn, $aResultData);
		}
		
		return $mValue;
	}
	
}
