<?php

class Ext_Thebing_Gui2_Format_Intpositiv extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if((int)$mValue > 0){
			return (int)$mValue;
		}else{
			return '';
		}

	}

}
