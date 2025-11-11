<?php

class Ext_TS_System_Checks_SetExcusedAbsence extends GlobalChecks {

	const CHECK_ALREADY_EXECUTED_KEY = 'check_set_excused_absence';

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Setting default value for new teacher right to every teacher';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck()
	{
		// Wenn der Check schon ausgeführt wurde
		if(\System::d(self::CHECK_ALREADY_EXECUTED_KEY, false) == 1) {
			return true;
		}

		$success = Ext_Thebing_Util::backupTable('ts_teachers');
		if(!$success) {
			return false;
		}

		\DB::executeQuery("
			UPDATE `ts_teachers`
			SET `access_rights` = `access_rights` | 128,
			`changed` = `changed`
		");

		\System::s(self::CHECK_ALREADY_EXECUTED_KEY, 1);

		return true;
	}

}