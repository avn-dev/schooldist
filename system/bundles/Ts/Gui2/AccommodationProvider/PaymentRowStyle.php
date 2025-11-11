<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentRowStyle extends \Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {
		
		if($aRowData['type'] === \Ts\Entity\AccommodationProvider\Payment::ERROR_PAYMENT_CATEGORY_NOT_FOUND) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('red').';';
		} elseif($aRowData['type'] === \Ts\Entity\AccommodationProvider\Payment::ERROR_NO_MATCHING_PERIOD) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('red', 50).';';
		} elseif($aRowData['type'] === \Ts\Entity\AccommodationProvider\Payment::ERROR_PAYMENT_COST_CATEGORY_NOT_FOUND) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('orange').';';
		} elseif($aRowData['type'] === \Ts\Entity\AccommodationProvider\Payment::ERROR_NO_MATCHING_COST_PERIOD) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('orange', 50).';';
		}
		
	}

}