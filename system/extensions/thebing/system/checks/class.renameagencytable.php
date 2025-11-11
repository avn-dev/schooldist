<?php

class Ext_Thebing_System_Checks_RenameAgencyTable extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		return true;
		
	}
	
	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try {
			Ext_Thebing_Util::backupTable('customer_db_13');

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `customer_db_10` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}
		
		try {
			$sSql = "ALTER TABLE `customer_db_11` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `customer_db_24` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `customer_db_7` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `customer_db_8` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_accounting_accounts` ADD PRIMARY KEY ( `id` ) ";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_accounting_accounts` DROP INDEX `id` ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_accounting_category` ADD PRIMARY KEY ( `id` ) ";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_accounting_category` DROP INDEX `id` ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_accounting_taxes` ADD PRIMARY KEY ( `id` ) ";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_accounting_taxes` DROP INDEX `id` ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_periods` CHANGE `created` `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00'";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_periods` CHANGE `changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_teacher_courses` DROP `id` ";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_teacher_courses` ADD PRIMARY KEY ( `teacher_id` , `course_id` ) ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_teacher_levels` DROP `id` ";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_teacher_levels` ADD PRIMARY KEY ( `teacher_id` , `level_id` ) ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE  `kolumbus_accounting_accounts` CHANGE  `idSchool`  `school_id` INT( 11 ) NOT NULL DEFAULT  '0'";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE  `kolumbus_accounting_accounts` CHANGE  `idClient`  `client_id` INT( 11 ) NOT NULL DEFAULT  '0'";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}
		
		try {
			$sSql = "ALTER TABLE `kolumbus_costs` CHANGE `active` `active` TINYINT( 1 ) NOT NULL DEFAULT '1'";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_costs` ADD `created` TIMESTAMP NOT NULL , ADD `user_id` INT NOT NULL ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "RENAME TABLE `customer_db_13` TO `kolumbus_agencies`";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "ALTER TABLE `kolumbus_agencies` CHANGE `last_changed` `changed` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		try {
			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_transfers_packages` (
				  `id` int(11) NOT NULL auto_increment,
				  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
				  `active` tinyint(1) NOT NULL default '1',
				  `client_id` int(11) NOT NULL,
				  `school_id` int(11) NOT NULL,
				  `currency_id` int(11) NOT NULL,
				  `name` varchar(255) NOT NULL,
				  `price_package` tinyint(1) NOT NULL default '1',
				  `cost_package` tinyint(1) NOT NULL default '0',
				  `individually_transfer` tinyint(1) NOT NULL default '0',
				  `time_from` time NOT NULL,
				  `time_until` time NOT NULL,
				  `amount_price` float(10,2) NOT NULL,
				  `amount_price_two_way` float(10,2) NOT NULL,
				  `amount_cost` float(10,2) NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `client_id` (`client_id`),
				  KEY `school_id` (`school_id`),
				  KEY `currency_id` (`currency_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}


		try {

			DB::addField('kolumbus_kontakt', 'gender', 'VARCHAR(255) NOT NULL');
			DB::addField('kolumbus_kontakt', 'skype', 'VARCHAR(255) NOT NULL');
			DB::addField('kolumbus_kontakt', 'master_contact', 'VARCHAR(255) NOT NULL');

		} catch(Exception $e) {
			__pout($e);
		}

		Ext_Thebing_Util::backupTable('kolumbus_agencies');
		Ext_Thebing_Util::backupTable('kolumbus_kontakt');

		/**
		 *
		 */
		$sSql = "SELECT
					*
				FROM
					`kolumbus_agencies`
				WHERE
					`active` = 1
					";
		$aAgencies = DB::getQueryRows($sSql);

		foreach((array)$aAgencies as $aAgency)
		{

			if(
				empty($aAgency['ext_11']) &&
				empty($aAgency['ext_9'])
			) {
				continue;
			}

			$aInsert = array();
			$aInsert['gender']			= (int)$aAgency['ext_36'];							//
			$aInsert['firstname']		= (string)$aAgency['contact_person_firstname'];
			$aInsert['lastname']		= (string)$aAgency['ext_11'];
			$aInsert['phone']			= (string)$aAgency['ext_7'];
			$aInsert['fax']				= (string)$aAgency['ext_8'];
			$aInsert['email']			= (string)$aAgency['ext_9'];
			$aInsert['skype']			= (string)$aAgency['ext_31'];						//
			$aInsert['parent_id']		= (int)$aAgency['id'];
			$aInsert['parent_typ']		= 'agency';
			$aInsert['master_contact']	= 1;												//
			$aInsert['transfer'] 	 	= 1;
			$aInsert['accommodation']	= 1;
			$aInsert['reminder']		= 1;

			DB::insertData('kolumbus_kontakt', $aInsert);

		}

		try {
		
			// Felder entfernen
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_36`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_11`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_7`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_8`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_9`";
			DB::executeQuery($sSql);
			$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_31`";
			DB::executeQuery($sSql);

		} catch(Exception $e) {
			__pout($e);
		}

		return true;

	}

}

