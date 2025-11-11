<?php

class Ext_TS_System_Checks_Accommodation_Provider_DeletedProviderMails extends GlobalChecks {

	public function getTitle()
	{
		return 'Accommodation Provider';
	}

	public function getDescription()
	{
		return 'Cleans up deleted accommodation provider emails';
	}

	/**
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');


		$backup = Util::backupTable('customer_db_4');
		if(!$backup) {
			__pout('Backup error');
			return false;
		}

		$rows = \DB::getQueryData("SELECT `id`,`email` FROM `customer_db_4` WHERE `active` = 0 AND `email` != ''");

		\DB::begin(__METHOD__);

		try {

			foreach ($rows as $row) {

				$update = "UPDATE `customer_db_4` SET `changed` = `changed`, `email` = :email WHERE `id` = :id AND `active` = 0";

				\DB::executePreparedQuery($update, [
					'email' => \Ext_TC_Util::generateRandomString(8).'_'.$row['email'],
					'id' => $row['id']
				]);
			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);
		
		return true;
	}

}