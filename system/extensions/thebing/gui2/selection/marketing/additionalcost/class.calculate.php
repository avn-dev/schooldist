<?php

class Ext_Thebing_Gui2_Selection_Marketing_Additionalcost_Calculate extends Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$t = function ($sTranslate) {
			if ($this->_oGui) {
				return $this->_oGui->t($sTranslate);
			}
			return $sTranslate;
		};

		$aOptions = [
			Ext_Thebing_School_Additionalcost::CALCULATION_ONCE => $t('Einmalig'),
			Ext_Thebing_School_Additionalcost::CALCULATION_PER_SERVICE => $t('Pro Kurs / Unterkunft'),
			Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK => $t('Pro Kurs- / Unterkunftswoche'),
			Ext_Thebing_School_Additionalcost::CALCULATION_PER_NIGHT => $t('Pro Unterkunftsnacht'),
		];

		if($oWDBasic->type != '1') {
			// Pro Nacht nur bei Unterkünften
			unset($aOptions[3]);
		}

		if($oWDBasic->type == '2') {
			// Generelle Gebühren sind nur einmalig
			$aOptions = array_slice($aOptions, 0, 1, true);
		}

		return $aOptions;

	}

}