<?php

class Ext_TS_System_Checks_Index_Refresh_Inquiry extends Ext_TC_System_Checks_Index_Refresh {

	public function getTitle() {
		return 'Inquiry Index Refresh';
	}

	public function getDescription() {
		return 'Refresh complete index of inquiry entries.';
	}

	public function getIndexName() {
		return 'ts_inquiry';
	}

}
