<?php

class Ext_Thebing_Gui2_Format_Amount extends Ext_Thebing_Gui2_Format_Format {

	protected $sCurrencyColumn = 'currency_id';

	public function __construct($sCurrencyColumn = 'currency_id') {
		$this->sCurrencyColumn = $sCurrencyColumn;
	}

	// formatiert den wert
	public function get($mValue, &$oColumn = null, &$aResultData = null){

		// Offener Betrag vor Anreise
		if($oColumn !== null && $oColumn->db_column == 'amount_due_arrival'){
			$mValue = (float)$aResultData['amount'] - (float)$aResultData['payments'];
			// Keine Überbezahlung anzeigen
			if($mValue <= 0){
				$mValue = 0;
			}
		}

		// Offener Betrag vorort
		if($oColumn !== null && $oColumn->db_column == 'amount_due_at_school'){
			$mValue = (float)$aResultData['amount_initial'] - (float)$aResultData['payments_local'];
			// Keine Überbezahlung anzeigen
			if($mValue <= 0){
				$mValue = 0;
			}
		}

		// Offener Betrag gesamt
		if($oColumn !== null && $oColumn->db_column == 'amount_due_general'){
			$mValue = ((float)$aResultData['amount'] + $aResultData['amount_initial'] - (float)$aResultData['amount_payed']);
		}

		return $mValue;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if ($mValue instanceof \Ts\Dto\Amount) {
			return $mValue->toString();
		}

		if(!empty($aResultData)){
			$mValue = $this->get($mValue, $oColumn, $aResultData);
		} else {
			$aResultData = $this->aResultData;
		}

		$oCurrency = $iCurrencyId = null;
		if(!empty($aResultData[$this->sCurrencyColumn])) {
			if(is_numeric($aResultData[$this->sCurrencyColumn])) {
				$oCurrency = Ext_Thebing_Currency::getInstance($aResultData[$this->sCurrencyColumn]);
			} else {
				$oCurrency = Ext_Thebing_Currency::getByIso($aResultData[$this->sCurrencyColumn]);
			}		
		}

		$mValue = Ext_Thebing_Format::Number($mValue, $oCurrency, (int)($aResultData['school_id'] ?? 0));

		return $mValue;
	}

	public function align(&$oColumn = null){
		return 'right';
	}

	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		$iSchoolId = (int)$aResultData['school_id'];

		if(empty($aResultData['school_id'])){
			$iSchoolId = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$mValue = Ext_Thebing_Format::convertFloat($mValue, $iSchoolId);

		return $mValue;
	}

	/**
	 * Beträge für Summenzeile
	 * @param type $mValue
	 * @param type $oColumn
	 * @param type $aResultData
	 * @return type 
	 */
	public function getSumValue($mValue, &$oColumn = null, &$aResultData = null){
		
		if(!empty($aResultData)){
			$mValue = $this->get($mValue, $oColumn, $aResultData);
		}
		
		return $mValue;
	}
	
	public function formatSum($mValue, &$oColumn = null, &$aResultData = null){
		
		$mValue = Ext_Thebing_Format::Number($mValue, (int)$aResultData[$this->sCurrencyColumn], (int)$aResultData['school_id']);

				
		return $mValue;
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {

		$oCell->setValue($aValue['original']);

		$oCurrency = Ext_Thebing_Currency::getInstance($aResultData[$this->sCurrencyColumn]);

		if($oCurrency->hasLeftBoundSign()) {
			$sFormat = '"'.$oCurrency->getSign().'" #,##0.00';
		} else {
			$sFormat = '#,##0.00 "'.$oCurrency->getSign().'"';
		}
		
		$oCell->getStyle()->getNumberFormat()->setFormatCode($sFormat);
		
	}

}
