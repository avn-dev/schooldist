<?php

class Ext_Thebing_System_Server_Update_Depuration extends Ext_Thebing_System_Server_Update {
	
	protected $sExecutionTimeField = 'execution_time_depuration';

	public function executeUpdate() {
		// TC-Klasse aufrufen, da diese Klasse wegen Thebing-Zeug nicht von der TC-Klasse ableiten darf
		$oDepuration = new Ext_TC_System_Cronjob_Update_Depuration();
		$oDepuration->executeUpdate();
	}

}