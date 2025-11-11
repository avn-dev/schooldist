<?php

class Ext_Thebing_Gui2_Format_Nl2br extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Wenn kein HTML, dann nl2br
		if(strpos($mValue, '</') === false) {
			$mValue = nl2br($mValue);
		}

		return $mValue;

	}

}
