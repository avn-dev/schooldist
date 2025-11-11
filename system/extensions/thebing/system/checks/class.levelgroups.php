<?php


class Ext_Thebing_System_Checks_LevelGroups extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$oDB = DB::getDefaultConnection();

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_tuition_levelgroups` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `school_id` int(11) NOT NULL,
			  `title` varchar(255) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `school_id` (`school_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";

		try
		{
			$rRes = DB::executeQuery($sSql);
		}
		catch(DB_QueryFailedException $e)
		{
			__pout($e->getMessage());
			$rRes = false;
		}

		if(!$rRes)
		{
			__pout($oDB->getLastQuery());
			__pout('couldnt create levelgroups table');
			return true;
		}

		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_tuition_levelgroups_courses` (
			  `levelgroup_id` int(11) unsigned NOT NULL,
			  `course_id` int(11) NOT NULL,
			  KEY `levelgroup_id` (`levelgroup_id`),
			  KEY `course_id` (`course_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";

		try
		{
			$rRes = DB::executeQuery($sSql);
		}
		catch(DB_QueryFailedException $e)
		{
			__pout($e->getMessage());
			$rRes = false;
		}

		if(!$rRes)
		{
			__pout($oDB->getLastQuery());
			__pout('couldnt create levelgroups course table');
			return true;
		}

		$sSql = "TRUNCATE `kolumbus_tuition_levelgroups`";
		DB::executeQuery($sSql);

		$sSql = "TRUNCATE `kolumbus_tuition_levelgroups_courses`";
		DB::executeQuery($sSql);


		$sSql = "
			SELECT
				`cdb2`.`id` `school_id`,
				`cdb3`.`id` `course_id`
			FROM
				`customer_db_2` `cdb2` INNER JOIN
				`customer_db_3` `cdb3` ON
					`cdb3`.`ext_8` = `cdb2`.`id` LEFT JOIN
				`kolumbus_tuition_levelgroups` `ktl` ON
					`ktl`.`school_id` = `cdb2`.`id`
			WHERE
				`cdb2`.`active` = 1 AND
				`cdb3`.`active` = 1 AND
				`ktl`.`id` IS NULL
			ORDER BY
				`cdb2`.`id`
		";

		$aResult		= (array)DB::getQueryRows($sSql);

		$iCurrentSchool = 0;
		$iLevelgroupId	= 0;

		foreach($aResult as $aRowData)
		{
			$iSchoolId	= (int)$aRowData['school_id'];
			$iCourseId	= (int)$aRowData['course_id'];

			if($iCurrentSchool != $iSchoolId)
			{
				$aInsert = array(
					'created'	=> date('Y-m-d H:i:s'),
					'active'	=> 1,
					'school_id'	=> $iSchoolId,
					'title'		=> 'Import'
				);
				
				try
				{
					$iLevelgroupId	= (int)DB::insertData('kolumbus_tuition_levelgroups', $aInsert);
				}
				catch(DB_QueryFailedException $e)
				{
					$iLevelgroupId	= 0;
					__pout($e->getMessage());
				}
			}
			
			if($iLevelgroupId>0)
			{
				$aInsertAllocation = array(
					'levelgroup_id' => $iLevelgroupId,
					'course_id'		=> $iCourseId
				);

				$bSuccess = true;
				try
				{
					DB::insertData('kolumbus_tuition_levelgroups_courses', $aInsertAllocation);
				}
				catch(DB_QueryFailedException $e)
				{
					$bSuccess = false;
					__pout($e->getMessage());
				}

				if(!$bSuccess)
				{
					__pout($aInsertAllocation);
					__pout($oDB->getLastQuery());
					__pout('couldnt allocate labelgroup '.$iLevelgroupId.' for course: '.$iCourseId);
				}

			}
			else
			{
				__pout($aInsert);
				__pout($oDB->getLastQuery());
				__pout('couldnt create labelgroup for school: '.$iSchoolId);
			}

			$iCurrentSchool = $iSchoolId;
		}

		return true;
	}

	public function getTitle()
	{
		return 'Levelgroups';
	}

	public function getDescription()
	{
		return 'Import of default levelgroup.';
	}
}