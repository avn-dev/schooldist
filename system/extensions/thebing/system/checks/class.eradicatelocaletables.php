<?php

/**
 * Merzt kolumbus_language_skills und kolumbus_countries aus.
 *
 * @author Dennis G. <dg@plan-i.de>
 * @since 30.06.2011 
 */

class Ext_Thebing_System_Checks_EradicateLocaleTables extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update of countries and nationalities';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Update of column structure for an import of countries and nationlities.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}
	
	public function executeCheck() {
		
		Ext_TC_Util::backupTable('kolumbus_countries');
		Ext_TC_Util::backupTable('kolumbus_language_skills');

		$bTableCheck = Util::checkTableExists('kolumbus_countries');

		if($bTableCheck){
			$sSql = "DROP TABLE `kolumbus_countries`";
			DB::executeQuery($sSql);
		}

		$bTableCheck = Util::checkTableExists('kolumbus_language_skills');
		
		if($bTableCheck){
			$sSql = "DROP TABLE `kolumbus_language_skills`";
			DB::executeQuery($sSql);
		}
		
		return true;
		
	}
	
}
