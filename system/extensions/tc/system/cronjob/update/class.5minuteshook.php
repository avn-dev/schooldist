<?php

class Ext_TC_System_CronJob_Update_5MinutesHook extends Ext_TC_System_CronJob_Update {
	
	public $bIgnoreExecutionError = true;

	public $iCronjobPeriodInMinutes = 300; // 1 Tag 1440
	
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
			
			\System::wd()->executeHook('tc_cronjobs_5minutes_execute', $aDebug);
			
		} catch (Exception $e) {
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
		}

	}

}