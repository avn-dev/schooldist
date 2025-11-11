<?php

class Ext_TS_System_Checks_Activity_MultipleProviders extends GlobalChecks
{
	public function getTitle()
	{
		return 'Multiple Providers';
	}

	public function getDescription()
	{
		return 'Move Provider from Activity to JoinTable And Block';
	}

	public function executeCheck()
	{

		if (!\DB::getDefaultConnection()->checkField('ts_activities', 'provider_id', true)) {
			return true;
		}

		$backup = Util::backupTable('ts_activities');

		if (!$backup) {
			__pout('Backup error!');
			return false;
		}

		$backup = Util::backupTable('ts_activities_blocks');

		if (!$backup) {
			__pout('Backup error!');
			return false;
		}

		$backup = Util::backupTable('ts_activities_to_activities_providers');

		if (!$backup) {
			__pout('Backup error!');
			return false;
		}

		$sql = "SELECT `id`, `provider_id` FROM `ts_activities` WHERE `provider_id` > 0";
		$entries = \DB::getQueryData($sql);

		// Write activity <-> provider relations to join table
		foreach($entries as $entry) {
			$exists = \DB::getQueryData('SELECT * FROM `ts_activities_to_activities_providers` WHERE `provider_id` = :provider_id AND `activity_id` = :id LIMIT 1', $entry);
			if (empty($exists)) {
				\DB::insertData('ts_activities_to_activities_providers', [
					'provider_id' => $entry['provider_id'],
					'activity_id' => $entry['id']
				]);
			}
			// Set deprecated provider_id field 0
			\DB::updateData('ts_activities', ['provider_id' => 0], ['id' => $entry['id']]);

			// Set ts_activities_blocks provider_id field
			$blocks = \DB::getQueryData('SELECT `block_id` FROM `ts_activities_blocks_to_activities` WHERE `activity_id` = :activity_id', ['activity_id' => $entry['id']]);
			if (!empty($blocks)) {
				DB::executePreparedQuery("UPDATE ts_activities_blocks SET provider_id = :provider_id WHERE id IN (:ids)", ['provider_id' => $entry['provider_id'], 'ids' => implode(",", array_column($blocks, 'block_id'))]);
			}
		}

		$drop = "ALTER TABLE `ts_activities` DROP `provider_id`";

		\DB::executeQuery($drop);

		return true;
	}
}