<?php

class Ext_TC_System_Checks_ParallelProcessing_Combination_Type extends GlobalChecks {
	
	public function getTitle() {
		return 'Parallel processing - Combination type';
	}
	
	public function getDescription() {
		return 'Change type of combination stack entries';
	}
	
	public function executeCheck() {
		
		DB::begin('Ext_TC_System_Checks_ParallelProcessing_Combination_Type');
		
		try {
			
			$sSql = "
				UPDATE
					`core_parallel_processing_stack`
				SET 
					`type` = :type
				WHERE
					`type` = :old_type
			";
			
			DB::executePreparedQuery($sSql, array('type' => 'tc/combination-initialize', 'old_type' => 'tc-combination/initialize'));
			
		} catch (Exception $e) {
			DB::rollback('Ext_TC_System_Checks_ParallelProcessing_Combination_Type');
			__pout($e);
			return false;
		}
		
		DB::commit('Ext_TC_System_Checks_ParallelProcessing_Combination_Type');
		
		return true;
	}
	
}

