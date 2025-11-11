<?php

class Ext_Thebing_Gui2_Format_Accommodation_Provider extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aAccommodations = Ext_Thebing_Data_Accommodation::getAccommodations(true);

		// Fallback für den Fall, dass Unterkunft deaktiviert wurde o.ä.
		if(!isset($aAccommodations[$mValue])) {
			$aAccommodations[$mValue] = Ext_Thebing_Accommodation::getInstance($mValue)->getName();
		}

		return $aAccommodations[$mValue];
	}

}
