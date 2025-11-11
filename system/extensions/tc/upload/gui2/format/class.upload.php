<?php

class Ext_TC_Upload_Gui2_Format_Upload extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sDir = Ext_TC_Upload_Gui2_Data::getUploadPath(true);
		$sDir .= $mValue;

		$sOpenPath = Ext_TC_Upload_Gui2_Data::getUploadPath(false);
		$sOpenPath .= $mValue;
		$sOpenPath = str_replace('/storage', '', $sOpenPath);

		if(!is_file($sDir)){
			$sOnClick = '';
			$sIcon = Ext_TC_Util::getFileTypeIcon('blanko');
		}else{
			$sOnClick = 'onclick="window.open(\'/storage/download'.$sOpenPath.'\'); return false"';
			$sIcon = Ext_TC_Util::getFileTypeIcon($mValue);
		}

		return '<img ' . $sOnClick . ' src="' . $sIcon . '" alt="'.L10N::t('Datei', Ext_TC_Upload_Gui2_Data::$sL10NDescription).'" title="'.L10N::t('Datei', Ext_TC_Upload_Gui2_Data::$sL10NDescription).'"/>';

	}

	public function align(&$oColumn = null){
		return 'center';
	}

}
