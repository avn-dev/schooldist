<?php

class Ext_Thebing_System_Checks_TeacherUsername extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Generate Teacher Usernames';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Generate teacher usernames by firstname and lastname.';
		return $sDescription;
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('ts_teachers');

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_teachers`
			ORDER BY 
				`id` ASC
		";
		
		$aResult = DB::getQueryCol($sSql);
		
		foreach($aResult as $iTeacherId) { 
			
			try {
				$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
				$oTeacher->username = $oTeacher->generateUsername();
				
				$aUpdate = array(
					'username' => $oTeacher->username
				);
				
				$sWhere = ' id = '.(int)$oTeacher->id;
				
				$rRes = DB::updateData('ts_teachers', $aUpdate, $sWhere);
				
				if(
					!$rRes
				) {
					__pout($oTeacher->id);
				}
				
			} catch(Exception $e) {
				__pout($e->getMessage());
			}
			
		}

		return true;
	}

}