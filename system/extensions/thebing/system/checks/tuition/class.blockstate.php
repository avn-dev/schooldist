<?php

class Ext_Thebing_System_Checks_Tuition_BlockState extends GlobalChecks {

	public function getTitle() {
		return 'Class overview: Teacher availability and qualification check';
	}

	public function getDescription() {
		return 'Sets data for 2019 and ongoing.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Util::backupTable('kolumbus_tuition_blocks');

		$aTeachers = $this->getTeachers();

		$sSql = "
			SELECT
				`ktb`.`id`,
			    `ktb`.`teacher_id`,
			   	`ktb`.`level_id`,
				`ktb`.`changed`,
			    `ktt`.`from`,
			   	`ktt`.`until`,
			   	GROUP_CONCAT(DISTINCT `ktbd`.`day`) `days`,
			   	GROUP_CONCAT(DISTINCT `ktc`.`category_id`) `course_categories`,
			    GROUP_CONCAT(DISTINCT `kab`.`id`) `absence`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
				    `ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
				    `ktt`.`id` = `ktb`.`template_id` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` AND
					`ktcl`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_classes_courses` `ktclc` ON
				    `ktclc`.`class_id` = `ktcl`.`id` LEFT JOIN
				`kolumbus_tuition_courses` `ktc` ON
				    `ktc`.`id` = `ktclc`.`course_id` LEFT JOIN
				`kolumbus_absence` `kab` ON
					`kab`.`item` = 'teacher' AND
					`kab`.`item_id` = `ktb`.`teacher_id` AND
					`kab`.`active` = 1 AND
					`kab`.`from` <= `ktb`.`week` + INTERVAL 6 DAY AND
					`kab`.`until` >= `ktb`.`week`
			WHERE
			    YEAR(`ktb`.`week`) >= 2019 AND
			    `ktb`.`teacher_id` != 0 AND
				`ktb`.`active` = 1 AND
			  	`ktb`.`state` IS NULL
			GROUP BY
				`ktb`.`id`
		";

		$aBlocks = (array)DB::getQueryRows($sSql);
		foreach($aBlocks as $aBlock) {

			if(!isset($aTeachers[$aBlock['teacher_id']])) {
				$this->logError('Teacher missing for block: '.$aBlock['teacher_id']);
				continue;
			}

			$aTeacher = $aTeachers[$aBlock['teacher_id']];
			$aDays = explode(',', $aBlock['days']);
			$aCourseCategories = explode(',', $aBlock['course_categories']);
			$iState = null;

			// Lehrer ist abwesend
			if(!empty($aBlock['absence'])) {
				$iState |= Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE;
			}

			foreach($aDays as $iDay) {

				if(!isset($aTeacher['availability'][$iDay])) {
					// Tag nicht verfügbar
					$iState |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY;
					break;
				}

				$cCreateDate = function($sTime) {
					$dDate = new DateTime();
					list($iHour, $iMinutes, $iSeconds) = explode(':', $sTime);
					$dDate->setTime($iHour, $iMinutes, $iSeconds);
					return $dDate;
				};

				$dBlockFrom = $cCreateDate($aBlock['from']);
				$dBlockUntil = $cCreateDate($aBlock['until']);
				$dTeacherFrom = $cCreateDate($aTeacher['availability'][$iDay][0]);
				$dTeacherUntil = $cCreateDate($aTeacher['availability'][$iDay][1]);

				if(
					$dTeacherFrom > $dBlockFrom ||
					$dTeacherUntil < $dBlockUntil
				) {
					// Zeit nicht verfügbar
					$iState |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY;
					break;
				}

			}

			if(
				!empty($aBlock['level_id']) &&
				!in_array($aBlock['level_id'], $aTeacher['levels'])
			) {
				// Lehrer unterrichtet Level nicht
				$iState |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION;
			} else {

				// Lehrer unterrichtet Kurskategorie nicht
				foreach($aCourseCategories as $iCourseCategoryId) {
					if(!in_array($iCourseCategoryId, $aTeacher['courses'])) {
						$iState |= Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION;
						break;
					}
				}

			}

			if($iState !== null) {

				DB::updateData('kolumbus_tuition_blocks', [
					'state' => $iState,
					'changed' => $aBlock['changed']
				], ['id' => $aBlock['id']]);

				$this->logInfo(vsprintf('Updated block %d state to %d (%d %d %d)', [
					$aBlock['id'],
					$iState,
					$iState & Ext_Thebing_School_Tuition_Block::STATE_TEACHER_ABSENCE,
					$iState & Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_AVAILABILITY,
					$iState & Ext_Thebing_School_Tuition_Block::STATE_INVALID_TEACHER_QUALIFICATION
				]));

			}

		}

		return true;

	}

	private function getTeachers() {

		$sSql = "
			SELECT
				`kt`.`id`,
				GROUP_CONCAT(DISTINCT `ktco`.`course_id`) `courses`,
				GROUP_CONCAT(DISTINCT `ktle`.`level_id`) `levels`,
				GROUP_CONCAT(DISTINCT CONCAT(`kts`.`idDay`, ',', `kts`.`timeFrom`, ',', `kts`.`timeTo`) SEPARATOR ';') `schedule`
			FROM
				`ts_teachers` `kt` LEFT JOIN
				`kolumbus_teacher_courses` `ktco` ON
					`ktco`.`teacher_id` = `kt`.`id` LEFT JOIN
				`kolumbus_teacher_levels` `ktle` ON
					`ktle`.`teacher_id` = `kt`.`id` LEFT JOIN
				`kolumbus_teacher_schedule` `kts` ON
					`kts`.`idTeacher` = `kt`.`id` AND
					`kts`.`active` = 1
			GROUP BY
				`kt`.`id`
		";

		$aTeachers = (array)DB::getQueryRowsAssoc($sSql);
		foreach($aTeachers as &$aTeacher) {
			$aTeacher['courses'] = explode(',', $aTeacher['courses']);
			$aTeacher['levels'] = explode(',', $aTeacher['levels']);
			$aTeacher['availability'] = [];
			foreach(explode(';', $aTeacher['schedule']) as $sSchedule) {
				list($iDay, $sFrom, $sUntil) = explode(',', $sSchedule);
				$aTeacher['availability'][$iDay] = [$sFrom, $sUntil];
			}
		}

		return $aTeachers;

	}

}
