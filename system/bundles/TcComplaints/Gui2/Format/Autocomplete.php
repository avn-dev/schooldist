<?php

namespace TcComplaints\Gui2\Format;

class Autocomplete extends \Ext_Gui2_View_Autocomplete_Abstract {

	public function getOption($aSaveField, $sValue) {
		return "";
	}

	public function getOptions($sInput, $aSelectedIds, $aSaveField) {
		return [];
	}

}
