<?php


class Ext_Thebing_System_Checks_CleanClassCourses extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sTable = 'kolumbus_tuition_classes_courses';

		Ext_Thebing_Util::backupTable($sTable);

		$sSql = "
			SELECT
				`ktcl`.`id` `class_id`,`kcc`.*
			FROM
				`kolumbus_tuition_classes_courses` `ktclc` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktclc`.`class_id` AND
					`ktcl`.`active` = 1 INNER JOIN
				`customer_db_3` `cdb3` ON
					`cdb3`.`id` = `ktclc`.`course_id` AND
					`cdb3`.`active` = 1 AND
					`cdb3`.`combination` = 1 INNER JOIN
				`kolumbus_course_combination` `kcc` ON
					`kcc`.`master_id` = `cdb3`.`id` INNER JOIN
				`customer_db_3` `cdb3_combination` ON
					`cdb3_combination`.`id` = `kcc`.`course_id` AND
					`cdb3_combination`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`class_id` = `ktcl`.`id` AND
					`ktb`.`active` = 1
			GROUP BY
				`ktclc`.`class_id`,
				`ktclc`.`course_id`,
				`kcc`.`course_id`
		";

		$aResult = (array)DB::getQueryRows($sSql);
		
		$aGroupedByMaster = array();

		foreach($aResult as $aRowData)
		{
			$iClassId	= $aRowData['class_id'];
			$iMasterId	= $aRowData['master_id'];
			$iCourseId	= $aRowData['course_id'];

			$sCombine	= $iClassId.'_'.$iMasterId;

			$aGroupedByMaster[$sCombine][] = $iCourseId;
		}

		foreach($aGroupedByMaster as $sCombine => $aCourseIds)
		{
			$aInfo = explode('_',$sCombine);

			$iClassId	= (int)$aInfo[0];
			$iMasterId	= (int)$aInfo[1];

			if(
				$iClassId <= 0 ||
				$iCourseId <= 0
			)
			{
				continue;
			}

			//Master aus Klassenkursen löschen, Klassen dürfen keine Kombi-Kurse beinhalten
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`class_id` = :class_id AND
					`course_id` = :course_id
			";

			$aSql = array(
				'class_id'	=> $iClassId,
				'course_id'	=> $iMasterId,
				'table'		=> $sTable
			);

			try
			{
				DB::executePreparedQuery($sSql, $aSql);
			}
			catch(DB_QueryFailedException $e)
			{
				__pout($e->getMessage());
			}

			foreach($aCourseIds as $iCourseId)
			{
				$iCourseId = (int)$iCourseId;

				if($iCourseId <= 0)
				{
					continue;
				}

				$sSql = "
					REPLACE INTO
						#table
					VALUES
						(:class_id,:course_id)
				";

				$aSql = array(
					'class_id'	=> $iClassId,
					'course_id'	=> $iCourseId,
					'table'		=> $sTable,
				);

				try
				{
					DB::executePreparedQuery($sSql, $aSql);
				}
				catch(DB_QueryFailedException $e)
				{
					__pout($e->getMessage());
				}
			}
		}

		return true;
	}

	public function getTitle()
	{
		return 'Class Courses';
	}

	public function getDescription()
	{
		return 'Check combined class courses.';
	}
}
