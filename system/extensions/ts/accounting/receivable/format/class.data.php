<?php

class Ext_TS_Accounting_Receivable_Format_Data extends Ext_TS_Accounting_Payment_Format_Data {
	
	protected $_aAmountColumns = array(
		'course_pending_amount',
		'accommodation_pending_amount',
		'transfer_pending_amount',
		'insurance_pending_amount',
		'extraPosition_pending_amount',
		'storno_pending_amount',
		'additional_course_pending_amount',
		'additional_accommodation_pending_amount',
		'additional_general_pending_amount'
	);
		
	protected static $_aExpectedAccommodationAmountCache = array();
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		if(
			in_array($oColumn->select_column, $this->_aAmountColumns) ||
			strpos($oColumn->select_column, 'additional_cost_amount_') !== false
		) {
			
			$sService = str_replace('_pending_amount', '', $oColumn->select_column);
			$iCostId = 0;
			
			// Bei den Additional-Costs die Id rausfiltern
			if(strpos($oColumn->select_column, 'additional_cost_amount_') !== false) {
				$iCostId = (int) str_replace('additional_cost_amount_', '', $oColumn->select_column);
				$sService = null;
			}
						
			$fAmount = $this->getPendingServiceAmount($sService, $aResultData, $iCostId);
			
			if($this->_bFormat) {
				$mValue = $this->formatSum($fAmount, $oColumn, $aResultData);
			} else {
				// Der Wert für die Formatierung der Summenspalte darf nicht formatiert werden
				$mValue = $fAmount;
			}
		} else if($oColumn->select_column == 'open_amount') {

			// Offenen Betrag ausrechnen: Erwartet und bezahlt liefert bereits der Query
			$mValue = $aResultData['expected_amount'] - $aResultData['payed_amount'];
			if($this->_bFormat) {
				$mValue = $this->formatSum($mValue, $oColumn, $aResultData);
			}

		} else {
			$mValue = parent::format($mValue, $oColumn, $aResultData);
		}
		
		if(is_array($mValue)) {
			$mValue = implode($mValue, '<br/>');
		}
		
		return $mValue;
	}
	
	/**
	 * liefert den ausstehenden Betrag für einen Service oder eine Zusatzgebühr
	 * 
	 * @param string $sService
	 * @param array $aResultData
	 * @param int $iTypeId
	 * @return float
	 */
	public function getPendingServiceAmount($sService, $aResultData, $iTypeId = 0) {
			
		$bNetto = false;
		if(strpos($aResultData['type'], 'netto') !== false) {
			$bNetto = true;
		}
		
		$fPayedAmount = $this->getAmountFromArray($aResultData['service_amount_data'], $sService, $iTypeId, false, false);
		$fExpectedAmount = $this->getAmountFromArray($aResultData['service_item_amount_data'], $sService, $iTypeId, $bNetto);

		if($sService === 'accommodation') {
			$fPayedAmount += $this->getAmountFromArray($aResultData['service_amount_data'], 'extra_nights', $iTypeId);
			$fPayedAmount += $this->getAmountFromArray($aResultData['service_amount_data'], 'extra_weeks', $iTypeId);
			$fExpectedAmount += $this->getAmountFromArray($aResultData['service_item_amount_data'], 'extra_nights', $iTypeId, $bNetto);
			$fExpectedAmount += $this->getAmountFromArray($aResultData['service_item_amount_data'], 'extra_weeks', $iTypeId, $bNetto);
		}

		if($fPayedAmount == 0) {
			$fAmount = $fExpectedAmount;
		} else if($fExpectedAmount == $fPayedAmount) {
			$fAmount = 0;
		} else {
			$fAmount = (float) ($fExpectedAmount - $fPayedAmount);
		}
	
		return $fAmount;
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {

		if(
			in_array($oColumn->select_column, $this->_aAmountColumns) ||
			strpos($oColumn->select_column, 'additional_cost_amount_') !== false ||
			$oColumn->select_column == 'open_amount'
		) {

			// Leider gibt es den Originalwert nicht, daher muss der formatierte Wert zurückgewandelt werden :-(
			$mValue = preg_replace('/[^0-9\.,]/', '', $mValue);
			$mValue = \Ext_Thebing_Format::convertFloat($mValue);

			$oCell->setValue($mValue);

			$oCurrency = Ext_Thebing_Currency::getInstance($aResultData['currency_id']);

			if($oCurrency->hasLeftBoundSign()) {
				$sFormat = '"'.$oCurrency->getSign().'" #,##0.00';
			} else {
				$sFormat = '#,##0.00 "'.$oCurrency->getSign().'"';
			}

			$oCell->getStyle()->getNumberFormat()->setFormatCode($sFormat);
		
		} else {
			parent::setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
		}
		
	}

}