<?
class Ext_Thebing_System_Checks_Importnightprices extends GlobalChecks {

	public function executeCheck(){

		Ext_Thebing_Util::backupTable('kolumbus_accommodation_nightprices');

		$sSql = " UPDATE
						`kolumbus_accommodation_nightprices` `kan` INNER JOIN
						`kolumbus_accommodation_nightprices_periods` `kanp` ON
							`kanp`.`id` = `kan`.`nightperiod_id` INNER JOIN
						`customer_db_2` `cdb2` ON
							`cdb2`.`id` = `kanp`.`school_id`
					SET
						`kan`.`currency_id` = `cdb2`.`currency`
					WHERE
						`kan`.`currency_id` = 0 AND
						`kanp`.`active` = 1";
		DB::executeQuery($sSql);


		return true;
	}

	public function getTitle() {
		$sTitle = 'Accommodation Prices per night';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Import the Accommodation Prices per night to fix the Problem with more than one Currency';
		return $sDescription;
	}

}