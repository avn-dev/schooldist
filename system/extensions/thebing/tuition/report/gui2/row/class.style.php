<?php

class Ext_Thebing_Tuition_Report_Gui2_Row_style extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$inquiry = new Ext_TS_Inquiry($aRowData['inquiry_id']);
		$invoiceStatus = $inquiry->getInvoiceStatus();

		$sStorno = Ext_Thebing_Util::getColor('storno');

		$sStyle = '';
		if(in_array('cancelled', $invoiceStatus)) {
			$sStyle .= 'background-color: '.$sStorno.'; ';
		}

		return $sStyle;
	}

}