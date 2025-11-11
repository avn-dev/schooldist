<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentAmountFormat extends \Ext_Thebing_Gui2_Format_Amount {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturn = '';
		if($mValue !== null) {
			$sReturn = parent::format($mValue, $oColumn, $aResultData);
		}
	
		return $sReturn;
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		// Anmerkung: Laut Mark ist es gewünscht, dass GROUP_CONCAT nicht gesplittet wird (nicht eindeutig dann)
		$aAdditional = (array)json_decode($aResultData['additional'], true);

		// Abwärtskompatibilität wg. Formatänderung
		if(isset($aAdditional['calculation'])) {
			$aCalculation = $aAdditional['calculation'];
		} else {
			$aCalculation = $aAdditional;
		}

		$sDescription = \Ext_Thebing_Inquiry_Document_Version::getItemCalculation($aCalculation);

		$aReturn = array();                
		$aReturn['content'] = $sDescription;        
		$aReturn['tooltip'] = true;        
                                           
		return $aReturn;    
	}

}