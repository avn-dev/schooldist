<?php

class Ext_TC_Upload_Gui2_Format_Size extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sDir = Ext_TC_Upload_Gui2_Data::getUploadPath(true);
		$sDir .= $mValue;

		if(is_file($sDir)){

			$iSize = filesize($sDir);
			return Ext_TC_Util::getFilesize($iSize);

		} else {

			return '';
	
		}

	}

	public function align(&$oColumn = null) {
		return 'right';
	}

}
