<?php

class Ext_TS_System_Checks_Index_CheckMissingInquiries extends Ext_TC_System_Checks_Index_Complete {

	protected $_sIndexName = 'ts_inquiry';
	protected $_sClass = 'Ext_TS_Inquiry';
	
	public function getTitle() {
		$sTitle = 'Checks if the index contains all inquiries';
		return $sTitle;
	}

}