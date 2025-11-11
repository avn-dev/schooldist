<?php

class Ext_TC_System_CronJob_Update_60MinutesHook extends Ext_TC_System_CronJob_Update {
	
	public $bIgnoreExecutionError = true;
	
	/**
	 * gibt den Klassennamen zurück
	 */
	public function getClassName() {
		return get_class($this);
	}

	protected function getUpdateData(){
		$aPostData = array();

		return $aPostData;
	}

	public function executeUpdate(){
		global $_VARS, $objWebDynamics, $aDebug;
		
		try {			
			
			\System::wd()->executeHook('tc_cronjobs_hourly_execute', $aDebug);
			
		} catch (Exception $e) {
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e->getMessage()."\n\n".$e->getTraceAsString());
		}

	}

}