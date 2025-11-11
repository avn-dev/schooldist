<?php

class Ext_TS_System_Checks_Activity_FrontendRelease extends GlobalChecks
{
	public function getTitle()
	{
		return 'Activity blocks';
	}

	public function getDescription()
	{
		return 'Prepare activity block structure for frontend release';
	}

	public function executeCheck()
	{
		if (!\DB::getDefaultConnection()->checkField('ts_activities_blocks', 'released_for_app', true)) {
			return true;
		}

		$backup = Util::backupTable('ts_activities_blocks');

		if (!$backup) {
			__pout('Backup error!');
			return false;
		}

		$update = "
			UPDATE
				`ts_activities_blocks`
			SET 
			    `changed` = `changed`,
			    `frontend_release` = 'bookable'
			WHERE
			    `active` = 1 AND
			    `released_for_app` = 1 AND
			    `frontend_release` IS NULL
		";

		\DB::executeQuery($update);

		$drop = "ALTER TABLE `ts_activities_blocks` DROP `released_for_app`";

		\DB::executeQuery($drop);

		return true;

	}

}
