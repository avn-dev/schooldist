<?php

/**
 * Anwesenheitsstruktur verändern, in die tuition_attendance nicht mehr die inquiry_course_id, course_id, teacher_id
 * abspeichern, stattdessen die Zuwesiungs-ID(kolumbus_tuition_blocks_inquiries_courses.id) in der Klassenplanung
 * 
 * @author Mehmet Durmaz
 */
class Ext_Thebing_System_Checks_Tuition_AttendanceAllocation extends GlobalChecks
{
	public function getTitle()
	{
		return 'Attendance Allocation';
	}
	
	public function getDescription()
	{
		return 'Change attendance allocations.';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		Util::backupTable('kolumbus_tuition_attendance');
		
		$aColumns = DB::describeTable('kolumbus_tuition_attendance', true);
		
		if(isset($aColumns['allocation_id']))
		{
			return true;
		}
		
		// Neue Spalte hinzufügen
		$sSql = '
			ALTER TABLE 
				`kolumbus_tuition_attendance` 
			ADD 
				`allocation_id` INT NOT NULL
		';
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout('Couldnt add alocation_id column!');
			
			return true;
		}
		
		// Neuen Index hinzufügen
		$sSql = '
			ALTER TABLE 
				`kolumbus_tuition_attendance`
			ADD INDEX 
				`allocation_id` ( `allocation_id` ) 
		';
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout('Couldnt add alocation_id index!');
			
			return true;
		}
		
		$sSql = '
			ALTER TABLE 
				`kolumbus_tuition_attendance` 
			DROP INDEX
				`kta_1`
		';
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout('Couldnt drop index kta_1!');
			
			return true;
		}
		
		$aDays = Ext_Thebing_System::getAttendanceDays();
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_attendance`
			ORDER BY
				`id`
			DESC
		";
		
		$aSql = array();
		
		$oDB = DB::getDefaultConnection();
		$oCollection = $oDB->getCollection($sSql, $aSql);

		$aAttendanceInsert = array();
		
		// Alle eingetragenen Anwesenheiten durchgehen
		foreach($oCollection as $aRowData)
		{
			// Die Zuweisungen in der Klassenplanung suchen, die zur Anwesenheit passen
			$sSql = "
				SELECT
					`ktbic`.`id` `allocation_id`,
					`ktt`.`lessons` * `ktc`.`lesson_duration` `allocation_duration`,
					GROUP_CONCAT(`ktbd`.`day`) `days`
				FROM
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
					`kolumbus_tuition_blocks` `ktb` ON
						`ktb`.`id` = `ktbic`.`block_id` AND
						`ktb`.`active` = 1 INNER JOIN
					`kolumbus_tuition_templates` `ktt` ON
						`ktt`.`id` = `ktb`.`template_id` AND
						`ktt`.`active` = 1 INNER JOIN
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ktbic`.`course_id` AND
						`ktc`.`active` = 1 INNER JOIN
					`kolumbus_tuition_blocks_days` `ktbd` ON
						`ktbd`.`block_id` = `ktb`.`id`
				WHERE
					`ktb`.`week` = :week AND
					`ktb`.`teacher_id` = :teacher_id AND
					`ktbic`.`inquiry_course_id` = :inquiry_course_id AND
					`ktbic`.`course_id` = :course_id
				GROUP BY
					`ktbic`.`id`
			";
			
			$aSql = array(
				'week' => $aRowData['week'],
				'teacher_id' => $aRowData['teacher_id'],
				'inquiry_course_id' => $aRowData['inquiry_course_id'],
				'course_id' => $aRowData['course_id'],
			);
			
			$oMatchingAllocations = $oDB->getCollection($sSql, $aSql);

			// Wenn passende Zuweisungen gefunden
			if(count($oMatchingAllocations) > 0)
			{
				// Tage durchgehen & Anwesenheit verteilen
				foreach($aDays as $iDay => $sDay)
				{	
					// Anwsenheit pro Wochentag
					$fAttendanceByDay = (float)$aRowData[$sDay];
					
					#if($fAttendanceByDay > 0)
					#{
						foreach($oMatchingAllocations as $aAllocation)
						{
							$sBlockDays = $aAllocation['days'];
							$aBlockDays	= explode(',', $sBlockDays);
							
							if(!in_array($iDay, $aBlockDays))
							{
								// Falls die Anwsenheit in 2 geteilt wird anhand der Tage, z.B. Block1 => Mo-Mi, Block2 => Do,Fr
								// und für alle Tage was in der Anwesenheit eingetragen ist
								continue;
							}
							
							// Gesamte Dauer eines Blocks
							$fAllocationDuration = (float)$aAllocation['allocation_duration'];
							
							if($fAttendanceByDay > $fAllocationDuration)
							{
								// Wenn Anwesenheit größer als insgesamt Lektionsdauer eines Blocks, 
								// dann wurde eine Anwesenheit eingetragen die über mehrere Blöcke geht...
								$fAttendanceAdd = $fAllocationDuration;
							}
							else
							{
								// Wenn Anwesenheit kleiner oder gleich Lektionsdauer ist, 
								// dann wurde eine Anwesenheit nur für ein Block eingetragen
								$fAttendanceAdd = $fAttendanceByDay;
							}
							
							if(!isset($aAttendanceInsert[$aRowData['id']]))
							{
								$aAttendanceInsert[$aRowData['id']] = array();
							}
							
							if(!isset($aAttendanceInsert[$aRowData['id']][$aAllocation['allocation_id']]))
							{
								$aAttendanceInsert[$aRowData['id']][$aAllocation['allocation_id']] = array();
							}
							
							if(!isset($aAttendanceInsert[$aRowData['id']][$aAllocation['allocation_id']][$sDay]))
							{
								$aAttendanceInsert[$aRowData['id']][$aAllocation['allocation_id']][$sDay] = array();
							}
							
							//Replace
							$aAttendanceInsert[$aRowData['id']][$aAllocation['allocation_id']][$sDay] = $fAttendanceAdd;


							$fAttendanceByDay -= $fAttendanceAdd;
							
							if($fAttendanceByDay <= 0)
							{
								// alles verteilt, Schleife abbrechen
								break;
							}
						}
					#}
				}
			}
		}

		foreach($aAttendanceInsert as $iAttendanceId => $aAttendanceByAllocation)
		{
			$iCounterByAttendanceId = 1;
			
			foreach($aAttendanceByAllocation as $iAllocationId => $aAttendanceByDays)
			{
				$aInsert = array(
					'allocation_id' => $iAllocationId,
				);
				
				foreach($aAttendanceByDays as $sDay => $fAttendance)
				{
					$aInsert[$sDay] = $fAttendance;
				}
				
				if($iCounterByAttendanceId > 1)
				{
					$sSql = '
						SELECT
							*
						FROM
							`kolumbus_tuition_attendance`
						WHERE
							`id` = :attendance_id
					';
					
					$aRow = (array)DB::getQueryRow($sSql, array(
						'attendance_id' => $iAttendanceId
					));
					
					$aInsert['week']	= $aRow['week'];
					$aInsert['comment'] = $aRow['comment'];
					$aInsert['score']	= $aRow['score'];
					
					$rRes = DB::insertData('kolumbus_tuition_attendance', $aInsert);
				}
				else
				{
					$sWhere = ' id = '.$iAttendanceId;

					$rRes = DB::updateData('kolumbus_tuition_attendance', $aInsert, $sWhere);
				}
				
				if($rRes === false)
				{
					__pout($aInsert); 
					__pout($iAttendanceId); 
					__pout($iAllocationId); 
					__pout("failed to add!");
				}

				$iCounterByAttendanceId++;
			}
		}
		
		// Unnötige Spalten entfernen
		$sSql = '
			ALTER TABLE
				`kolumbus_tuition_attendance`
			DROP `inquiry_course_id`,
			DROP `course_id`,
			DROP `teacher_id`,
			DROP `inquiry_id`,
			DROP `week`
		';
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout('couldnt drop columns!'); 
		}

		return true;
	}
}