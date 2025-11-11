<?php

class Ext_Thebing_Gui2_Format_Admin_Form_Usage extends Ext_Thebing_Gui2_Format_YesNo {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($mValue == -1) {
			return '';
		}

		return parent::format($mValue, $oColumn, $aResultData);

	}

}
