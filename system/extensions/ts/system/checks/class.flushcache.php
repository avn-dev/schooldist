<?php

class Ext_TS_System_Checks_FlushCache extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Flush system cache';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck() {
		
		WDCache::flush();

		return true;
	}
		
}
