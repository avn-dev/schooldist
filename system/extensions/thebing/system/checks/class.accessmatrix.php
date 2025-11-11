<?php

class Ext_Thebing_System_Checks_AccessMatrix extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Access database structure update';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		try {

			Ext_Thebing_Util::backupTable('kolumbus_access_matrix_groups');
			Ext_Thebing_Util::backupTable('kolumbus_access_matrix_items');
			Ext_Thebing_Util::backupTable('kolumbus_access_matrix_rights');
			
			$sSql = "RENAME TABLE `kolumbus_access_matrix_groups` TO `tc_access_matrix_groups`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `tc_access_matrix_groups` ENGINE = InnoDB";
			DB::executeQuery($sSql);
			$sSql = "RENAME TABLE `kolumbus_access_matrix_items` TO `tc_access_matrix_items`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `tc_access_matrix_items` ENGINE = InnoDB";
			DB::executeQuery($sSql);
			$sSql = "RENAME TABLE `kolumbus_access_matrix_rights` TO `tc_access_matrix_rights`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `tc_access_matrix_rights` ENGINE = InnoDB";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `tc_access_matrix_items` CHANGE `item_type` `item_type` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		return true;

	}

}
