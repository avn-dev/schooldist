<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentNightsFormat extends \Ext_Thebing_Gui2_Format_Amount {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturn = '';
		if(
			$aResultData['from'] != '0000-00-00' &&
			$aResultData['until'] != '0000-00-00'
		) {
			
			$dFrom = new \DateTime($aResultData['from']);
			$dUntil = new \DateTime($aResultData['until']);
			
			$oDiff = $dFrom->diff($dUntil);
			$iDays = $oDiff->format('%a');

			$sReturn = $iDays;
			
		}
	
		return $sReturn;
	}

	public function align(&$oColumn = null) {
		return 'right';
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {
		if(is_numeric($mValue)) {
			$oCell->setValueExplicit(
				$mValue,
				\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
			);
		}
	}

}