<?php

class Ext_TS_Insurances_Gui2_Row_style extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){
		
		$sStyle = '';
		if($aRowData['canceled'] != '0000-00-00 00:00:00') {
			$sStorno	= Ext_Thebing_Util::getColor('storno');
			$sStyle .= 'background-color: ' . $sStorno . '; ';
		}
		
		return $sStyle;		
	}
	
}