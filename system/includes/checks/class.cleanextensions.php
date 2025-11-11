<?php

class Checks_CleanExtensions extends GlobalChecks {
	
	public function getTitle() {
		return 'Clean extensions';
	}
	
	public function getDescription() {
		return 'Removes double extensions and adds unique index.';
	}

	public function executeCheck() {
		
		$bBackup = Util::backupTable('system_elements');
		
		if($bBackup === false) {
			return false;
		}
		
		$sSqlClean = "
			DELETE FROM 
				`system_elements`
			WHERE 
				`id` NOT IN (
					SELECT 
						* 
                    FROM 
						(
							SELECT 
								MIN(`se`.`id`)
                            FROM 
								`system_elements` `se`
							GROUP BY 
								`se`.`file`
						) `x`
				)
		";
		DB::executeQuery($sSqlClean);
		
		try {
			
			$sSqlRemoveIndex = "ALTER TABLE `system_elements` DROP INDEX `file`";
			DB::executeQuery($sSqlRemoveIndex);

		} catch (Exception $ex) {
		}
		
		$sSqlUnique = "ALTER TABLE `system_elements` ADD UNIQUE KEY `file` (`file`)";
		DB::executeQuery($sSqlUnique);

		return true;
	}
	
}