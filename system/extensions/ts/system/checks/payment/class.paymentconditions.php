<?php

class Ext_TS_System_Checks_Payment_PaymentConditions extends GlobalChecks {
	
	public function getTitle() {
		return 'Create the new payment condition table structure';
	}
	
	public function getDescription() {
		return 'Create the new payment condition table structure and import data from the old structure';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		/* Wenn Check schonmal durchgelaufen ist,
		wird es diese Tabelle geben */
		if(Util::checkTableExists('ts_agencies_payments_groups_to_payment_conditions')) {
			return true;
		}

		$bSuccessGroups = Util::backupTable('kolumbus_agencies_payments_groups');
		$bSuccessSchools = Util::backupTable('kolumbus_agencies_payments_groups_schools');
		$bSuccessAmount = Util::backupTable('kolumbus_agencies_payments_groups_schools_amount');
		$bSuccessPercent = Util::backupTable('kolumbus_agencies_payments_groups_schools_percent');
		$bSuccess = $bSuccessGroups && $bSuccessSchools && $bSuccessAmount && $bSuccessPercent;

		if($bSuccess) {

			// @TODO Transaktion funktioniert wegen den ALTERs nicht!
			DB::begin('Ext_TS_System_Checks_Payment_PaymentConditions');

			try {

				// Umbenennen der Tabellen
				DB::executeQuery("RENAME TABLE `kolumbus_agencies_payments_groups` TO `ts_agencies_payments_groups`");
				DB::executeQuery("RENAME TABLE `kolumbus_agencies_payments_groups_schools` TO `ts_payment_conditions`");
				DB::executeQuery("RENAME TABLE `kolumbus_agencies_payments_groups_schools_amount` TO `ts_payment_conditions_amounts`");
				DB::executeQuery("RENAME TABLE `kolumbus_agencies_payments_groups_schools_percent` TO `ts_payment_conditions_percents`");

				// Umbenennen von Spalten
				DB::executeQuery("ALTER TABLE `ts_payment_conditions_amounts` CHANGE `payment_group_school_id` `payment_condition_id` INT(11) NOT NULL");
				DB::executeQuery("ALTER TABLE `ts_payment_conditions_percents` CHANGE `payment_group_school_id` `payment_condition_id` INT(11) NOT NULL");

				// Entfernen der client_id Spalten
				DB::executeQuery("ALTER TABLE `ts_agencies_payments_groups` DROP `client_id`");
				DB::executeQuery("ALTER TABLE `ts_payment_conditions` DROP `client_id`");

				// Neue Spalte von Ticket #6550: Provisions- und Bezahlkategorien sortieren
				DB::executeQuery("ALTER TABLE  `ts_agencies_payments_groups` ADD  `position` INT( 11 ) NOT NULL");

				// Neue Zwischentabelle anlegen
				$sSql = "
					CREATE TABLE IF NOT EXISTS `ts_agencies_payments_groups_to_payment_conditions` (
					  `payment_condition_id` int(11) NOT NULL,
					  `group_id` int(11) NOT NULL,
					  `school_id` int(11) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8
				";

				DB::executeQuery($sSql);

				// Daten in neue Zwischentabelle importieren
				$sSql = "
					SELECT
						`id`,
						`group_id`,
						`school_id`
					FROM
						`ts_payment_conditions`
				";

				$aResults = DB::getQueryData($sSql);

				foreach($aResults as $aResult) {

					$aData = array(
						'payment_condition_id' => $aResult['id'],
						'group_id' => $aResult['group_id'],
						'school_id' => $aResult['school_id']
					);

					DB::insertData('ts_agencies_payments_groups_to_payment_conditions', $aData);

				}

				// Spalten, die in die Zwischentabelle importiert wurden, können gelöscht werden
				DB::executeQuery("ALTER TABLE `ts_payment_conditions` DROP `group_id`, DROP `school_id`");

				// Alte Deposit (Schüler) Einstellungen in die neue Struktur bringen
				DB::executeQuery("ALTER TABLE `customer_db_2` ADD `payment_condition_id` INT NOT NULL DEFAULT '0'");

				$sSql = "
					SELECT
						`id`,
						`prepay_days`,
						`finalpay_days`,
						`prepay`,
						`prepay_type`,
						`currency`
					FROM
						`customer_db_2`
					WHERE
						`active` = 1
				";

				$aResults = DB::getQueryData($sSql);

				foreach($aResults as $aResult) {

					$iStatus = $aResult['prepay_type'] + 1;
					$iNewStatus = $iStatus;

					if(
						$aResult['prepay_days'] == 0 &&
						$aResult['finalpay_days'] == 0 &&
						$aResult['prepay'] == 0 &&
						$aResult['prepay_type'] == 0
					) {
						/* Sollte der User nie was ausgewählt haben,
						soll "nicht vorhanden" ausgewählt werden */
						$iNewStatus = 0;
					}

					$aData = array(
						'status' => $iNewStatus,
						'first_due_days' => $aResult['prepay_days'],
						'first_due_direction' => 1,
						'first_due_status' => 0,
						'final_due_days' => $aResult['finalpay_days'],
						'final_due_direction' => 0,
						'final_due_status' => 1
					);

					$iPaymentConditionId = DB::insertData('ts_payment_conditions', $aData);

					if($iStatus == 1) {

						$aData = array(
							'payment_condition_id' => $iPaymentConditionId,
							'currency_id' => $aResult['currency'],
							'amount' => $aResult['prepay']
						);

						DB::insertData('ts_payment_conditions_amounts', $aData);

					} else if($iStatus == 2) {

						$aData = array(
							'payment_condition_id' => $iPaymentConditionId,
							'amount_percent' => $aResult['prepay'],
							'type_id' => 0,
							'type' => 'all'
						);

						DB::insertData('ts_payment_conditions_percents', $aData);

					}

					DB::updateData('customer_db_2', array('payment_condition_id' => $iPaymentConditionId), array('id' => $aResult['id']));

				}

				// Spalten, die nicht mehr in customer_db_2 verwendet werden, können gelöscht werden
				DB::executeQuery("ALTER TABLE `customer_db_2` DROP `prepay_days`, DROP `finalpay_days`, DROP `prepay`, DROP `prepay_type`");

				DB::commit('Ext_TS_System_Checks_Payment_PaymentConditions');

				// Cache löschen für die neue Spalte
				WDCache::delete('db_table_description_customer_db_2');
				WDCache::delete('wdbasic_table_description_customer_db_2');

				return true;

			} catch(Exception $ex) {

				DB::rollback('Ext_TS_System_Checks_Payment_PaymentConditions');
				__pout($ex);

				return false;

			}

		}

		return false;
	}

}