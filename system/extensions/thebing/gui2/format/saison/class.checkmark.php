<?php

class Ext_Thebing_Gui2_Format_Saison_Checkmark extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if(1 == $mValue) {
			$mValue = '<i class="fa fa-colored '.Ext_Thebing_Util::getIcon('accept').'" alt="accept" title="accept"></i>';
		} else {
			$mValue = null;
		}

		return $mValue;
	}

	public function align(&$oColumn = null) {
		return 'center';
	}

}
