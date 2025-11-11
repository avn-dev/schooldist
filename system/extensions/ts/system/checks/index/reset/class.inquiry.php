<?php

class Ext_TS_System_Checks_Index_Reset_Inquiry extends Ext_TC_System_Checks_Index_Reset {

	public function getTitle() {
		return 'Inquiry Index Reset';
	}

	public function getDescription() {
		return 'Delete and renew the complete index of inquiry entries.';
	}

	public function getIndexName() {
		return 'ts_inquiry';
	}

}
