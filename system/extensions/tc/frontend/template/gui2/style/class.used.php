<?php
/**
 * »Freigegeben«-Spalte in der Rechnungsübersicht formatieren
 */
class Ext_TC_Frontend_Template_Gui2_Style_Used extends Ext_Gui2_View_Style_Abstract
{

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if($aRowData['used'] == 1) {
			$sReturn .= 'background: '.Ext_TC_Util::getColor('green', 40).'; ';
		} else {
			$sReturn .= 'background: '.Ext_TC_Util::getColor('red', 40).'; ';
		}

		return $sReturn;

	}

}