<?php


class Ext_Thebing_System_Checks_TuitionCourseMasterChange extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks_inquiries_courses');

		//alle zugewiesenen Schüler finden wo die course_id ein Mastercourse ist
		$sSql = "
			SELECT
				`ktbic`.`id`,
				`kcc`.`course_id` `combined_course_id`,
				`ktcl`.`id` `class_id`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_course_combination` `kcc` ON
					`kcc`.`master_id` = `ktbic`.`course_id` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id`
			WHERE
				`ktbic`.`active` = 1 AND
				`ktb`.`active` = 1 AND
				`ktcl`.`active` = 1
		";

		//Array vorbereiten
		$aBlockInquiriesCourses			= (array)DB::getQueryRows($sSql);
		$aPreparedBlockInquiriesCourses = array();
		foreach($aBlockInquiriesCourses as $aRowData)
		{
			$iBlockInquiryCourseId	= $aRowData['id'];
			$iClassId				= $aRowData['class_id'];
			$iCourseId				= $aRowData['combined_course_id'];

			$aPreparedBlockInquiriesCourses[$iBlockInquiryCourseId]['class_id']		= $iClassId;
			$aPreparedBlockInquiriesCourses[$iBlockInquiryCourseId]['courses'][]	= $iCourseId;
		}
		
		foreach($aPreparedBlockInquiriesCourses as $iBlockInquiryCourseId => $aPreparedData)
		{
			$iClassId	= (int)$aPreparedData['class_id'];
			$aCourses	= (array)$aPreparedData['courses'];

			//alle gewählten Kurse finden für diese Klasse
			$sSql = "
				SELECT
					`course_id`
				FROM
					`kolumbus_tuition_classes_courses`
				WHERE
					`class_id` = :class_id
			";

			$aSql['class_id'] = $iClassId;

			$aAllowedCourses	= (array)DB::getQueryCol($sSql,$aSql);
			
			if(!empty($aAllowedCourses) && !empty($aCourses))
			{
				$iMatchedCourse		= false;

				foreach($aCourses as $iCourseId)
				{
					if(in_array($iCourseId,$aAllowedCourses))
					{
						$iMatchedCourse = $iCourseId;
						break;
					}
				}

				if(!$iMatchedCourse)
				{
					//Wenn keine Übereinstimmung, nehme den erstbesten aus den Kombinierten Kursen
					$iMatchedCourse = reset($aCourses);
					$sError = 'keine Übereinstimmung der Kurse: '.implode(',',$aCourses).' in den erlaubten Kursen: '.implode(',',$aAllowedCourses);
					__pout($sError);
					__pout("es handelt sich um die ktbic.id: $iBlockInquiryCourseId");
				}

				//KursID austauschen
				$sSql = "
						UPDATE
							`kolumbus_tuition_blocks_inquiries_courses`
						SET
							`course_id` = :new_course_id
						WHERE
							`id` = :block_inquiry_course_id
				";

				$aSql = array(
					'new_course_id'				=> $iMatchedCourse,
					'block_inquiry_course_id'	=> $iBlockInquiryCourseId
				);

				$rRes = DB::executePreparedQuery($sSql, $aSql);
				if(!$rRes)
				{
					__pout("Fehler beim Update");
					$oDb = DB::getDefaultConnection();
					__pout($oDb->getLastQuery());
				}
			}
			else
			{
				__pout("Da stimmt was nicht mit ktbic.id: $iBlockInquiryCourseId");
			}
		}


		return true;
	}

	public function getTitle()
	{
		return 'Change Mastercourses';
	}

	public function getDescription()
	{
		return 'Change mastercourses to course ids.';
	}
}
