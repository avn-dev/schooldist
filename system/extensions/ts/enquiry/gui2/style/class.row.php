<?php

use Carbon\Carbon;

class Ext_TS_Enquiry_Gui2_Style_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$aStyles = [];

//		if (in_array(Ext_TS_Inquiry::TYPE_BOOKING_STRING, (array)$aRowData['type'])) {
		if (in_array('enquiry_converted', $aRowData['invoice_status'])) {

			$aStyles[] = 'background: '.Ext_Thebing_Util::getColor('lightgreen').';';

		} elseif (!empty($aRowData['follow_up_original'])) {

			$dDate = new Carbon($aRowData['follow_up_original']);
			if ($dDate < Carbon::now()) {
				$aStyles[] = 'background: '.Ext_Thebing_Util::getColor('red').';';
			}

		}

		if (!in_array('offer_created', (array)$aRowData['invoice_status'])) {
			$aStyles[] = 'color: '.Ext_TC_Util::getColor('red_font').';';
		}

		return join($aStyles);

	}

}
