<?php

class Ext_TS_System_Checks_Index_Refresh_Document extends Ext_TC_System_Checks_Index_Refresh {

	public function getTitle() {
		return 'Document Index Refresh';
	}

	public function getDescription() {
		return 'Refresh complete index of documents.';
	}

	public function getIndexName() {
		return 'ts_document';
	}

}
