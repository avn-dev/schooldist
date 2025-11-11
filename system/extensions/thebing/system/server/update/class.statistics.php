<?php

class Ext_Thebing_System_Server_Update_Statistics extends Ext_Thebing_System_Server_Update {
	
	protected $sExecutionTimeField = 'execution_time_statistics_update';

	public $bIgnoreExecutionError = true;

	public function executeUpdate() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		self::log('Statistics update: start');

		Ext_Thebing_Welcome::generatePendingHousingPlacementsStatistic(true);
		Ext_Thebing_Welcome::generateStudentsInSchoolStatistic(true);

		self::log('Statistics update: end');
		
	}
	
}