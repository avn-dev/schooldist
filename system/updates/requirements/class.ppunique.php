<?php

/**
 * 
 */
class Updates_Requirements_PPUnique extends Requirement {

	/**
	 * @return boolean
	 */
	public function checkSystemRequirements() {

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFields = DB::describeTable('core_parallel_processing_stack', true);
		
		if(!isset($aFields['hash'])) {

			$sSql = "ALTER TABLE `core_parallel_processing_stack` ADD `hash` CHAR(32) CHARACTER SET ASCII COLLATE ascii_bin NOT NULL AFTER `type`";
			DB::executeQuery($sSql);

			$this->cleanStack();

			$sSql = "ALTER TABLE `core_parallel_processing_stack` ADD UNIQUE `unique1` (`type`, `hash`)";
			$bSuccess = DB::executeQuery($sSql);
			
			return (bool)$bSuccess;
		}

		return true;
	}

	/**
	 * Entfernt doppelte Einträge
	 */
	private function cleanStack() {

		$sSql = "
			SELECT 
				* 
			FROM 
				`core_parallel_processing_stack`
		";
		
		$oDb = DB::getDefaultConnection();
		$aItems = $oDb->getCollection($sSql);
		
		if(!empty($aItems)) {
			$aUnique = array();
			foreach($aItems as $aItem) {
		
				$sMD5 = md5($aItem['data']);
				$sType = $aItem['type'];
				
				// Wenn es den Eintrag schon gibt, dann löschen
				if(isset($aUnique[$sType][$sMD5])) {
					$sSql = "
						DELETE FROM
							`core_parallel_processing_stack`
						WHERE
							`id` = :id
						";
					$aSql = array(
						'id' => (int)$aItem['id']
					);
					DB::executePreparedQuery($sSql, $aSql);
				} else {
					$aUpdate = array(
						'hash' => $sMD5
					);
					DB::updateData('core_parallel_processing_stack', $aUpdate, "`id` = ".(int)$aItem['id']);
					$aUnique[$sType][$sMD5] = 1;
				}
				
			}
		}

	}
	
}