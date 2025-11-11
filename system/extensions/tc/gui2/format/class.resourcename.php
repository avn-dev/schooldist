<?php

class Ext_TC_Gui2_Format_ResourceName extends Ext_Gui2_View_Format_Abstract{
	
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {
		
		$mValue = Util::getCleanFilename($mValue, '-', false);

		return $mValue;
	}

}