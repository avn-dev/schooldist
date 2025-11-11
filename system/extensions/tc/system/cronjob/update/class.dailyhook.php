<?php

class Ext_TC_System_Cronjob_Update_DailyHook extends Ext_TC_System_Cronjob_Update {

	public $bIgnoreExecutionError = true;

	public function executeUpdate() {
		global $aDebug;

		try {
			\System::wd()->executeHook('tc_cronjobs_daily_execute', $aDebug);
		} catch (Exception $e) {
			$this->logError('Exception', [$e->getMessage()]);
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
		}

	}

}
