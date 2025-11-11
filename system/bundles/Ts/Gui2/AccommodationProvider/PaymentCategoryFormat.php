<?php

namespace Ts\Gui2\AccommodationProvider;

use Ts\Helper\Accommodation\AllocationCombination;

class PaymentCategoryFormat extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		if($mValue === null) {
			$mValue = $this->oGui->t('Zusatzleistung');
		}

		return $mValue;
	}
	
}