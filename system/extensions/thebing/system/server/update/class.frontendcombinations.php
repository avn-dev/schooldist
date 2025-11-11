<?php

class Ext_Thebing_System_Server_Update_FrontendCombinations extends Ext_Thebing_System_Server_Update {

	public $bIgnoreExecutionError = true;

	public function executeUpdate() {
		// TC-Klasse aufrufen, da diese Klasse wegen Thebing-Zeug nicht von der TC-Klasse ableiten darf
		$oFrontendUpdate = new Ext_TC_System_CronJob_Update_Frontend();
		$oFrontendUpdate->bCheckExecutionHour = false;
		$oFrontendUpdate->executeUpdate();
	}

}
