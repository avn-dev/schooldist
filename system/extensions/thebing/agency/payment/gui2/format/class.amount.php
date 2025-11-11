<?php

class Ext_Thebing_Agency_Payment_Gui2_Format_Amount extends Ext_Thebing_Gui2_Format_Amount {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$mValue = $this->calculateValue($mValue, $oColumn, $aResultData);
		return parent::format($mValue, $oColumn, $aResultData);
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {
		$aValue['original'] = $this->calculateValue($mValue, $oColumn, $aResultData);
		parent::setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
	}

	private function calculateValue($mValue, $oColumn, $aResultData) {

		if($oColumn->db_column === 'amount_used') {

			$mValue = $this->getAmountUsed($aResultData);

		} elseif($oColumn->db_column === 'amount_open') {

			$mValue = (float)$aResultData['amount'] - (float)$this->getAmountUsed($aResultData);

		} elseif($oColumn->db_column === 'amount_credit') {

			// Normale Creditnotes müssen umgedreht werden, da Bezahlungen negativ in der Datenbank sind
			$mValue = ($aResultData['amount_used_document_creditnotes'] * -1) + (float)$aResultData['amount_used_manual_creditnotes'];

		}

		return $mValue;
	}

	/**
	 * Manuelle Creditnotes müssen manuell abgezogen werden, da diese nicht in der thebing_inquiries_payments stehen
	 * Normale Creditnotes wurden bereits abgezogen, da diese durch das SUM() miteinander verrechnet werden (negative Zahlungen)
	 *
	 * @see Ext_Thebing_Agency_Payment::getPayedAmount()
	 *
	 * @param array $aResultData
	 * @return float
	 */
	private function getAmountUsed($aResultData) {

		return (float)$aResultData['amount_used_with_document_creditnotes'] - (float)$aResultData['amount_used_manual_creditnotes'];

	}

}