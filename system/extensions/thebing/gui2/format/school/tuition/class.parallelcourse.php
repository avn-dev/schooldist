<?php

class Ext_Thebing_Gui2_Format_School_Tuition_Parallelcourse extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($mValue) {
			return 'x';
		} else {
			return '';
		}

	}

	public function align(&$oColumn = null){
		return 'center';
	}

}