<?php

/**
 * Check Klasse für Thebing Checks die Global ausgeführt werden müssen
 */
class Ext_Thebing_System_ThebingCheck extends GlobalChecks {

	/*
	 * Check soll nur von unserer IP aus zu sehen sein
	 */
	public function isNeeded(){

		if(
			$_SERVER['REMOTE_ADDR'] == '87.79.64.219' ||
			$_SERVER['REMOTE_ADDR'] == '188.111.108.114' ||
			$_SERVER['REMOTE_ADDR'] == '87.79.75.171'
		) {
			return true;
		}

		return false;

	}

	
}
