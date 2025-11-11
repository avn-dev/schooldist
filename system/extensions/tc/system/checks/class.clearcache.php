<?php

/**
 * Self-explaining.
 */
class Ext_TC_System_Checks_Clearcache extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Clear System Cache';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Clear System Cache';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {

		WDCache::flush();

		return true;

	}


}
?>

