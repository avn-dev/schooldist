<?php

class Ext_TS_Inquiry_Payment_Unallocated_Gui2_RowStyle extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if ($aRowData['status'] !== \Ext_Thebing_Inquiry_Payment::STATUS_PAID) {
			return 'color: '.Ext_TC_Util::getColor('inactive').';';
		}

		return '';

	}

}