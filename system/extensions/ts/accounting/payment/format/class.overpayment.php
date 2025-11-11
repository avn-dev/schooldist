<?php

class Ext_TS_Accounting_Payment_Format_Overpayment extends Ext_Thebing_Gui2_Format_Amount {

    /**
     * @var string
     */
    private static $aCache;

    /**
     * @param float $fAmount
     * @param null $oColumn
     * @param null $aResultData
     * @return float|string
     */
	public function format($fAmount, &$oColumn = null, &$aResultData = null) {

        if(!isset(self::$aCache[$aResultData['id']])) {
            self::$aCache[$aResultData['id']] = $fAmount;
        } else {
            $fAmount = 0;
        }

        $mValue = parent::format($fAmount, $oColumn, $aResultData);

        return $mValue;
	}
	
}
