<?php

class Ext_TC_System_Checks_Flexibility_UsageAttribute extends GlobalChecks {
	
	public function getTitle() {
		return 'Change database structure for flexible fields';
	}
		
	public function getDescription() {
		return '...';
	}
	
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		$bBackup = Ext_TC_Util::backupTable('tc_flex_sections_fields');

		if(!$bBackup) {
			__pout('Backup failed');
			return false;
		}

		$bAddField = DB::addField('tc_flex_sections_fields', 'usage', 'VARCHAR( 255 ) NOT NULL', false, 'INDEX');
		
		if($bAddField === true) {

			$sCacheKey = 'wdbasic_table_description_tc_flex_sections_fields';
			WDCache::delete($sCacheKey);

			$oRepo = Ext_TC_Flexibility::getRepository();
			
			$aFields = $oRepo->findAll();
			
			foreach($aFields as $oField) {
				
				$sUsage = $oField->gui_designer_usage;
				if(!empty($sUsage)) {
					$oField->usage = $sUsage;
					$oField->save();
				}

			}
			
		}

		return true;
	}
	
}