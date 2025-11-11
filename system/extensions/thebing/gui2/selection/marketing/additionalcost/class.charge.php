<?php

class Ext_Thebing_Gui2_Selection_Marketing_Additionalcost_Charge extends Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = [
			'auto' => $this->_oGui->t('automatisch'),
			'semi' => $this->_oGui->t('semi-automatisch'),
			'manual' => $this->_oGui->t('manuell'),
		];

		if (
			$oWDBasic instanceof Ext_Thebing_School_Additionalcost &&
			$oWDBasic->type == Ext_Thebing_School_Additionalcost::TYPE_COURSE ||
			$oWDBasic->type == Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION
		) {
			unset($aOptions['manual']);
		}

		if ($oWDBasic->type == Ext_Thebing_School_Additionalcost::TYPE_GENERAL) {
			unset($aOptions['auto']);
			unset($aOptions['semi']);
		}

		return $aOptions;

	}

}
