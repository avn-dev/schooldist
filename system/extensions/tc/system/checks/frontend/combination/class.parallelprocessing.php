<?php

class Ext_TC_System_Checks_Frontend_Combination_ParallelProcessing extends GlobalChecks {
	
	protected $aUsages = array();
	
	public function getTitle() {
		return 'Frontend Combinations';
	}
	
	public function getDescription() {		
		return 'Update of all frontend combinations';
	}
	
	public function executeCheck() {
		
		set_time_limit(120);
		ini_set("memory_limit", '512M');
		
		try {
			$oHelper = new Ext_TC_Frontend_Combination_Helper_ParallelProcessing();

			if(!empty($this->aUsages)) {
				$oHelper->updateByUsage($this->aUsages);
			} else {
				$oHelper->updateAll();
			}
		} catch (Exception $e) {
			__pout($e);
			return false;
		}
		
		return true;
	}
	
}

