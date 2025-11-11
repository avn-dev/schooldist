<?php

use Core\Entity\ParallelProcessing\Stack;

class Checks_Gui2_RepairStack extends GlobalChecks {

	public function getTitle() {
		return 'Index Stack';
	}
	
	public function getDescription() {
		return 'Checks the stack for corrupt entries and repairs them.';
	}
	
	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		// Check muss nicht ausgeführt werden, wenn Tabelle nicht da
		if(!Util::checkTableExists('core_parallel_processing_stack')){
			return true;
		}		

		$bBackup = Util::backupTable('core_parallel_processing_stack');
		if(!$bBackup) {
			return false;
		}
	
		$sSql = "
			SELECT 
				*
			FROM 
				`core_parallel_processing_stack`
			WHERE 
				`type` = 'gui2/index'
		";
		$aSql = array();
		
		$oDB = DB::getDefaultConnection();
		$aItems = $oDB->getCollection($sSql, $aSql);
		
		foreach($aItems as $aItem) {
			
			$aData = json_decode($aItem['data'], true);
			
			if(
				isset($aData['index']) &&
				!isset($aData['index_name'])
			) {
				
				$aData['index_name'] = $aData['index'];
				unset($aData['index']);
				
				$aUpdate = array();
				$aUpdate['data'] = json_encode($aData);
				
				DB::updateData('core_parallel_processing_stack', $aUpdate, '`id` = '.(int)$aItem['id']);
				
			}
			
		}
		
		return true;
	}

}

