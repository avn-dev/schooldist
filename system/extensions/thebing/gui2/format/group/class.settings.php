<?php

class Ext_Thebing_Gui2_Format_Group_Settings extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aInquiryData = Ext_Thebing_Inquiry_Gui2_Group::getInquiryDataOptions();

		return $aInquiryData[$mValue];

	}

}
