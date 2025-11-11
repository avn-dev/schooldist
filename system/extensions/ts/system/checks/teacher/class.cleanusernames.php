<?php

class Ext_TS_System_Checks_Teacher_CleanUsernames extends GlobalChecks {
	
	public function getTitle() {
		return 'Teacher';
	}
	
	public function getDescription() {
		return 'Prepares username of copied theachers';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackup = Ext_Thebing_Util::backupTable('ts_teachers');
		
		if(!$bBackup) {
			__pout('backup error!');
			return false;
		}
		
		DB::begin('Ext_TS_System_Checks_Teacher_CleanUsernames');
		
		try {
			
			$aMultipleUserNames = $this->_getMutlipleUsernames();
			
			foreach($aMultipleUserNames as $sUsername => $aIds) {
				// Nur doppelte Usernamen verändern
				if(count($aIds) <= 1) {
					continue;
				}
				// erster gefunden Lehrer brauch nicht verändert zu werden
				unset($aIds[0]);
				
				$iCount = 1;
				foreach($aIds as $iId) {
					
					$sNewUserName = $sUsername . $iCount;
					
					DB::updateData('ts_teachers', array('username' => $sNewUserName), ' `id` = '.$iId.' ');
					
					++$iCount;
				}
				
			}
			
			$sSql = "
				ALTER TABLE 
					`ts_teachers` 
				DROP INDEX 
					`username` ,
				ADD UNIQUE 
					`username` ( `username` ) 
					";
			
			DB::executeQuery($sSql);
			
		} catch(Exception $e) {
			__pout($e);
			DB::rollback('Ext_TS_System_Checks_Teacher_CleanUsernames');
			return false;
		}
		
		DB::commit('Ext_TS_System_Checks_Teacher_CleanUsernames');		
		
		return true;
	}
	
	protected function _getMutlipleUsernames() {
		
		$sSql = "
			SELECT
				`id`,
				`username`
			FROM
				 `ts_teachers`
		";
		
		$aTeacherData = (array) DB::getQueryData($sSql);
		
		$aReturn = array();
		foreach($aTeacherData as $aTeacher) {
			$aReturn[$aTeacher['username']][] = $aTeacher['id'];
		}
		
		return $aReturn;
	}
	
}