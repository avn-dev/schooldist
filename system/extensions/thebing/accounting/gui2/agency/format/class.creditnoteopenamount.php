<?php

class Ext_Thebing_Accounting_Gui2_Agency_Format_CreditNoteOpenAmount extends Ext_Thebing_Gui2_Format_Amount {

    /**
     * @param mixed $mValue
     * @param null $oColumn
     * @param null|array $aResultData
     * @return string
     */
    public function format($mValue, &$oColumn = null, &$aResultData = null) {

        $mValue = $this->calculate($aResultData);

        return parent::format($mValue, $oColumn, $aResultData);
    }

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {

		if ($aValue['original'] === null) {
			$aValue['original'] = $this->calculate($aResultData);
		}

		parent::setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
	}

	private function calculate(array $aResultData) {
		return $aResultData['creditnote_amount'] - $aResultData['creditnote_payed'];
	}

}