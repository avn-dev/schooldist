<?php

class Ext_TS_Document_Release_Gui2_Format_Addressee extends Ext_Thebing_Gui2_Format_Amount {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if (!is_array($mValue)) {
			return '';
		}

		return sprintf('%s: %s', $mValue[0], Ext_Thebing_Document_Address::getTypeLabel($mValue[1]));

	}

}
