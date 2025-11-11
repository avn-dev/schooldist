<?php


class Ext_Thebing_Gui2_Style_Accounting_Transfer_Payment extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		if(
			isset($aRowData['id']) &&
			isset($aRowData['id']) > 0
		){
			$sStyle = '';
			if($aRowData['active'] == 0) {
				$sStyle .= 'background-color: '.Ext_Thebing_Util::getColor('bad').';';
			} else if(
				$aRowData['updated'] > 0
			) {
				$sStyle .= 'background-color: '.Ext_Thebing_Util::getColor('changed').';';
			} else if(
				$aRowData['imported'] > 0 &&
				$aRowData['amount'] < $aRowData['cost']
			){
				$sStyle .= 'background-color: '.Ext_Thebing_Util::getColor('neutral').';';
			} else if(
				$aRowData['payment_amount'] > 0
			) {
				$sStyle .= 'background-color: '.Ext_Thebing_Util::getColor('good').';';
			}

			if(
				$aRowData['confirmed'] == 0
			) {
				$sStyle .= 'color: '.Ext_Thebing_Util::getColor('red_font').';';
			}

			return $sStyle;

		} else {
			return '';
		}
	}


}