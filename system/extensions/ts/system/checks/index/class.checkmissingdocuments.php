<?php

class Ext_TS_System_Checks_Index_CheckMissingDocuments extends Ext_TC_System_Checks_Index_Complete {

	protected $_sIndexName = 'ts_document';
	protected $_sClass = 'Ext_Thebing_Inquiry_Document';
	
	public function getTitle() {
		$sTitle = 'Checks if the index contains all documents';
		return $sTitle;
	}
		
}