<?php

class Ext_TS_System_Checks_Index_Reset_Document extends Ext_TC_System_Checks_Index_Reset {

	public function getTitle() {
		return 'Document Index Reset';
	}

	public function getDescription() {
		return 'Delete and renew the complete index of document entries.';
	}

	public function getIndexName() {
		return 'ts_document';
	}

}
