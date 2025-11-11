<?php

class Ext_FormatInput extends Ext_Gui2_View_Format_Abstract{
	public function format($mValue, &$oColumn = null, &$aResultData = null){
		return '<input type="checkbox" />';
	}
}
