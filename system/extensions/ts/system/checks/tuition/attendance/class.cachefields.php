<?php


class Ext_TS_System_Checks_Tuition_Attendance_CacheFields extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bSuccess = Util::backupTable('kolumbus_tuition_attendance');
		
		if(!$bSuccess)
		{
			__pout('couldnt backup table!');
			
			return false;
		}
		
		$aColumns = DB::describeTable('kolumbus_tuition_attendance', true);
		
		$bRefreshDescription = false;
		
		$bAddIndexInquiry = false;
		
		$bAddIndexJourneyCourse = false;
		
		$bAddIndexTeacher = false;
		
		$bAddIndexWeek = false;
		
		$bAddIndexCourse = false;
		
		if(!isset($aColumns['inquiry_id']))
		{
			$rRes = DB::addField('kolumbus_tuition_attendance', 'inquiry_id', 'INT(11) NOT NULL');
			
			if(!$rRes)
			{
				__pout('Couldnt add inquiry_id field!');
				
				return false;
			}
			
			$bRefreshDescription = true;
			
			$bAddIndexInquiry = true;
		}
		
		if(!isset($aColumns['journey_course_id']))
		{
			$rRes = DB::addField('kolumbus_tuition_attendance', 'journey_course_id', 'INT(11) NOT NULL');
			
			if(!$rRes)
			{
				__pout('Couldnt add journey_course_id field!');
				
				return false;
			}
			
			$bRefreshDescription = true;
			
			$bAddIndexJourneyCourse = true;
		}
		
		if(!isset($aColumns['teacher_id']))
		{
			$rRes = DB::addField('kolumbus_tuition_attendance', 'teacher_id', 'INT(11) NOT NULL');
			
			if(!$rRes)
			{
				__pout('Couldnt add teacher_id field!');
				
				return false;
			}
			
			$bRefreshDescription = true;
			
			$bAddIndexTeacher = true;
		}
		
		if(!isset($aColumns['week']))
		{
			$rRes = DB::addField('kolumbus_tuition_attendance', 'week', 'DATE NOT NULL');
			
			if(!$rRes)
			{
				__pout('Couldnt add week field!');
				
				return false;
			}
			
			$bRefreshDescription = true;
			
			$bAddIndexWeek = true;
		} 
		
		if(!isset($aColumns['course_id']))
		{
			$rRes = DB::addField('kolumbus_tuition_attendance', 'course_id', 'INT(11) NOT NULL');
			
			if(!$rRes)
			{
				__pout('Couldnt add course_id field!');
				
				return false;
			}
			
			$bRefreshDescription = true;
			
			$bAddIndexCourse = true;
		} 
		
		if($bRefreshDescription)
		{
			$sCacheKey = 'wdbasic_table_description_kolumbus_tuition_attendance';

			WDCache::delete($sCacheKey);	
		}
		
		$sSql = "
			SELECT
				`kta`.`id`,
				`ts_i_j`.`inquiry_id`,
				`ktbic`.`inquiry_course_id` `journey_course_id`,
				`ktbic`.`course_id`,
				`ktb`.`teacher_id`,
				`ktb`.`week`
			FROM
				`kolumbus_tuition_attendance` `kta` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`id` = `kta`.`allocation_id` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_i_j_c` ON
					`ts_i_j_c`.`id` = `ktbic`.`inquiry_course_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `ts_i_j_c`.`journey_id`
		";
		
		$aErrors = array();
		
		$oDB = DB::getDefaultConnection();
		
		$oCollection = $oDB->getCollection($sSql, array());
		
		foreach($oCollection as $aRowData)
		{
			$iId = (int)$aRowData['id'];
			
			unset($aRowData['id']);
			
			$aWhere = array(
				'id' => $iId,
			);
			
			$rRes = DB::updateData('kolumbus_tuition_attendance', $aRowData, $aWhere, true);
			
			if(!$rRes)
			{
				$aErrors[] = $iId;
			}
		}
		
		if($bRefreshDescription)
		{
			try
			{
				if($bAddIndexInquiry)
				{
					$sSql = "ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `inquiry_id`(`inquiry_id`)";
					DB::executeQuery($sSql);	
				}
				
				if($bAddIndexJourneyCourse)
				{
					$sSql = "ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `journey_course_id`(`journey_course_id`)";
					DB::executeQuery($sSql);					
				}

				if($bAddIndexTeacher)
				{
					$sSql = "ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `teacher_id`(`teacher_id`)";
					DB::executeQuery($sSql);	
				}
				
				if($bAddIndexWeek)
				{
					$sSql = "ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `week`(`week`)";
					DB::executeQuery($sSql);
				}
				
				if($bAddIndexCourse)
				{
					$sSql = "ALTER TABLE `kolumbus_tuition_attendance` ADD INDEX `course_id`(`course_id`)";
					DB::executeQuery($sSql);
				}
			}
			catch(Exception $e)
			{
				__pout($e->getMessage()); 
				
				return false;
			}
		}
		
		
		if(empty($aErrors))
		{
			return true;
		}
		else
		{
			__pout($aErrors);

			return false;
		}
	}
}