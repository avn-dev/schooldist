<?php

class Ext_TS_System_Checks_User_SalespersonSettings extends GlobalChecks {

	public function getTitle()
	{
		return 'Salesperson';
	}

	public function getDescription()
	{
		return 'Checks given salesperson settings';
	}

	public function executeCheck()
	{
		$invalid = $this->getInvalidSettings();

		if (empty($invalid)) {
			return true;
		}

		$backup = [
			\Util::backupTable('ts_system_user_sales_persons_agencies'),
			\Util::backupTable('ts_system_user_sales_persons_nationalities'),
			\Util::backupTable('ts_system_user_sales_persons_schools'),
			\Util::backupTable('ts_system_user_sales_persons_settings'),
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {
			foreach ($invalid as $settingId) {
				\DB::executePreparedQuery("DELETE FROM ts_system_user_sales_persons_agencies WHERE setting_id = :id", ['id' => $settingId]);
				\DB::executePreparedQuery("DELETE FROM ts_system_user_sales_persons_nationalities WHERE setting_id = :id", ['id' => $settingId]);
				\DB::executePreparedQuery("DELETE FROM ts_system_user_sales_persons_schools WHERE setting_id = :id", ['id' => $settingId]);
				\DB::executePreparedQuery("DELETE FROM ts_system_user_sales_persons_settings WHERE id = :id", ['id' => $settingId]);
			}
		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	private function getInvalidSettings(): array
	{
		$sql = "
			SELECT
				`ts_susps`.`id`
			FROM 	
				`ts_system_user_sales_persons_settings` `ts_susps` LEFT JOIN
				`system_user` ON
					`system_user`.`id` = `ts_susps`.`user_id`
			WHERE
			    `system_user`.`id` IS NULL OR 
			   	`system_user`.`active` = 0 
		";

		return (array)\DB::getQueryCol($sql);
	}

}
