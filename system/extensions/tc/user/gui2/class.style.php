<?php
 
class Ext_TC_User_Gui2_Style extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$sStyle = "";
		// Storniert
		if($aRowData['master'] == 1) {
			$sStyle .= 'background-color: '.Ext_TC_Util::getColor('highlight', 40).'; ';
		} elseif($aRowData['blocked'] == 1) {
			$sStyle .= 'background-color: '.Ext_TC_Util::getColor('bad').'; ';
		}
		
		if($aRowData['status'] == 0) {
			$sStyle .= 'color: #aaa; ';
		}

		return $sStyle;

	}

}
