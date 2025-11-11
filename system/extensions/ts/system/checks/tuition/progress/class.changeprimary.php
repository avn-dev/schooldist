<?php


class Ext_TS_System_Checks_Tuition_Progress_ChangePrimary extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bSuccess = Util::backupTable('kolumbus_tuition_levelgroups_courses');
		
		if(!$bSuccess)
		{
			__pout("Couldnt backup table!"); 

			return false;
		}
		
		// Vor dem droppen/adden des Primarys abchecken
		$sSql = "
			SELECT 
				`course_id`, 
				count( * ) `counter`
			FROM 
				`kolumbus_tuition_levelgroups_courses`
			GROUP BY 
				`course_id`
			HAVING 
				`counter` > 1	
		";
		
		$aResult = (array)DB::getQueryRows($sSql);
		
		if(!empty($aResult))
		{
			__pout('Table error!'); 
			
			__pout($aResult);

			return false;
		}
		
		// Primary droppen falls vorhanden
		$sSql = "
			ALTER TABLE 
				`kolumbus_tuition_levelgroups_courses` 
			DROP PRIMARY KEY
		";
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout("Couldnt drop Primary!");
			
			return false;
		}
		
		// Primary adden
		$sSql = "
			ALTER TABLE 
				`kolumbus_tuition_levelgroups_courses` 
			ADD PRIMARY KEY ( `course_id` ) 
		";
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout("Couldnt add Primary!");
			
			return false;
		}
		
		// Alle anderen Indizierungen löschen
		$sSql = "
			SHOW INDEX FROM 
				`kolumbus_tuition_levelgroups_courses` 
		";
		
		$aRows = (array)DB::getQueryRows($sSql);
		
		foreach($aRows as $aIndexData)
		{
			if($aIndexData['Key_name'] != 'PRIMARY')
			{
				$sSql = "
					ALTER TABLE
						`kolumbus_tuition_levelgroups_courses` 
					DROP INDEX
						#index_name
				";
				
				$aSql = array(
					'index_name' => $aIndexData['Key_name'],
				);
				
				$rRes = DB::executePreparedQuery($sSql, $aSql);
				
				if(!$rRes)
				{
					__pout('Couldnt drop index "' . $aIndexData['Key_name'] . '"!');
					
					return false;
				}
			}
		}
		
		// Index hinzufügen
		
		$sSql = "
			ALTER TABLE
				`kolumbus_tuition_levelgroups_courses` 
			ADD INDEX 
				`levelgroup_id` ( `levelgroup_id` ) 	
		";
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout('Couldnt add index for levelgroup_id!');

			return false;
		}
		
		return true;
	}
	
	public function getTitle()
	{
		return 'Change Progress Structure';
	}
	
	public function getDescription()
	{
		return 'Change the structure for student progress data.';
	}
}