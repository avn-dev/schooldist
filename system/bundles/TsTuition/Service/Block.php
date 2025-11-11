<?php

namespace TsTuition\Service;

use TsTuition\Entity\Course\Program\Service;
use Carbon\Carbon;

/**
 * Class Block
 *
 * Baut ein Array mit den Daten der Blöcke der Lehrer in einer Woche zusammen und berechnet die wöchentlichen/täglichen Lektionen und Stunden
 *
 * @package TsTuition\Service
 */
class Block {

	private $aBlocks = [];

	/**
	 * @param \Ext_Thebing_Teacher $oTeacher
	 */
	public function addTeacher(\Ext_Thebing_Teacher $oTeacher) {
		$sTeacherName = $oTeacher->lastname.', '.$oTeacher->firstname;
		$this->aBlocks[$oTeacher->id]['teacher'] = $oTeacher;
		$this->aBlocks[$oTeacher->id]['teacher_schools'] = array_map(function($a) { return (int)$a; }, $oTeacher->schools);
		$this->aBlocks[$oTeacher->id]['teacher_name'] = $sTeacherName;
		$this->aBlocks[$oTeacher->id]['teacher_course_categories'] = array_map(function($a) { return (int)$a; }, $oTeacher->course_categories);
		$this->aBlocks[$oTeacher->id]['teacher_levels'] = array_map(function($a) { return (int)$a; }, $oTeacher->levels);
		$this->aBlocks[$oTeacher->id]['teacher_course_languages'] = array_map(function($a) { return (int)$a; }, $oTeacher->course_languages);
	}

	/**
	 * @param \Ext_Thebing_Teacher $oTeacher
	 * @param int $iBlockDay
	 * @param array $aBlock
	 */
	public function addBlock(\Ext_Thebing_Teacher $oTeacher, int $iBlockDay, array $aBlock) {

		$this->addTeacher($oTeacher);

		if(isset($this->aBlocks[$oTeacher->id]['weekly_lessons'])) {
			$this->aBlocks[$oTeacher->id]['weekly_lessons'] += $aBlock['lessons'];
		} else {
			$this->aBlocks[$oTeacher->id]['weekly_lessons'] = $aBlock['lessons'];
		}

		if(!isset($this->aBlocks[$oTeacher->id]['weekly_hours'])) {
			$this->aBlocks[$oTeacher->id]['weekly_hours'] = 0;
		}

		$this->aBlocks[$oTeacher->id]['weekly_hours'] += ($aBlock['lessons'] * $aBlock['lesson_duration']) / 60;

		// Wert berechnen
		$timeFrom = new Carbon($aBlock['time_from']);
		$timeUntil = new Carbon($aBlock['time_until']);
		// diffInHours() gibt nur die Stundenanzahl ohne Kommastellen, deswegen diffInMinutes()/60.
		$singleClassDuration = $timeFrom->diffInMinutes($timeUntil)/60;

		if(!isset($this->aBlocks[$oTeacher->id]['weekly_class_duration'])) {
			$this->aBlocks[$oTeacher->id]['weekly_class_duration'] = 0;
		}
		$this->aBlocks[$oTeacher->id]['weekly_class_duration'] += $singleClassDuration;

		if(isset($this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_lessons'])) {
			$this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_lessons'] += $aBlock['lessons'];
		} else {
			$this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_lessons'] = $aBlock['lessons'];
		}

		if(!isset($this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_hours'])) {
			$this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_hours'] = 0;
		}

		$this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['daily_hours'] += ($aBlock['lessons'] * $aBlock['lesson_duration']) / 60;

		$this->aBlocks[$oTeacher->id]['days'][$iBlockDay]['blocks'][] = $aBlock;

	}

	/**
	 * @return array
	 */
	public function getBlocks() {
		return $this->aBlocks;
	}

	/**
	 * @param string $sWeekFrom
	 *
	 * @return array $aBlocksData
	 */
	public function getWeekBlocksQueryData(string $sWeekFrom, array $schoolIds) {

		$sSql = "
			SELECT 
			   	`ktb`.`id`,
			   	`ktb`.`teacher_id`,
			   	`ktt`.`lessons`,
			   	`ktcl`.`lesson_duration`,
			   	DATE_FORMAT(`ktt`.`from`,'%H:%i') `time_from`,
			   	DATE_FORMAT(`ktt`.`until`, '%H:%i')  `time_until`,
			   	`ktcl`.`name` `class`,
			   	`ktl`.`name_short` `level`,
			   	`ktco`.`code` `class_color`,
			   	GROUP_CONCAT(DISTINCT `kr`.`name` ORDER BY `kr`.`position` SEPARATOR ', ') `room`,
			   	`ktbd`.`day` `block_day`,
			   	getRealDateFromTuitionWeek(
					`ktb`.`week`,
					`ktbd`.`day`,
					:course_startday
				) `block_date`,
				GROUP_CONCAT(DISTINCT CONCAT_WS('{#}', `ktbst`.`teacher_id`, `ktbst`.`lessons`, DATE_FORMAT(`ktbst`.`from`,'%H:%i'), DATE_FORMAT(`ktbst`.`until`,'%H:%i'))  ORDER BY `ktbst`.`day`, `ktbst`.`from` SEPARATOR '{|}') `substitute_teachers`,
				(
					/* TODO @MP: Was soll die Redundanz hier mit Ext_Thebing_Tuition_Class? */
					SELECT
						COUNT(DISTINCT `cdb1`.`id`)
					FROM
					    `kolumbus_tuition_blocks_inquiries_courses` `ktbic2` INNER JOIN
						`kolumbus_tuition_blocks` `ktb2` ON
							`ktb2`.`id` = `ktbic2`.`block_id` INNER JOIN
						`ts_inquiries_journeys_courses` `kic` ON
						   	`kic`.`id` = `ktbic2`.`inquiry_course_id` AND 
						   	`kic`.`active` = 1 AND
							`kic`.`visible` = 1 INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `kic`.`journey_id` AND
							`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
						`ts_inquiries` `ki` ON
							`ki`.`id` = `ts_i_j`.`inquiry_id` AND
							`ki`.`canceled` <= 0 INNER JOIN
						`ts_inquiries_to_contacts` `ts_i_to_c` ON
							`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
							`ts_i_to_c`.`type` = 'traveller' INNER JOIN
						`tc_contacts` `cdb1` ON
							`cdb1`.`id` = `ts_i_to_c`.`contact_id`
					WHERE
						`ktbic2`.`active` = 1 AND
						`ktb2`.`class_id` = `ktcl`.`id` AND
						`ktb2`.`week` = :week_from
				) `count_students`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
				    `ktt`.`id` = `ktb`.`template_id` LEFT JOIN
			 	`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
			 	    `ktbic`.`block_id` = `ktb`.`id` AND
				 	`ktbic`.`active` = 1 LEFT JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON 
					`ts_tcps`.`id` = `ktbic`.`program_service_id` AND 
					`ts_tcps`.`type` = '".Service::TYPE_COURSE."' LEFT JOIN
				`kolumbus_tuition_courses` `ktc` ON
				    `ktc`.`id` = `ts_tcps`.`type_id` AND
				    `ktc`.`active` = 1 INNER JOIN
			 	`kolumbus_tuition_classes` `ktcl` ON
			 	    `ktcl`.`id` = `ktb`.`class_id` AND
			 	    `ktcl`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_colors` `ktco` ON
					`ktco`.`id` = `ktcl`.`color_id` AND
					`ktco`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
					`ktb`.`id` = `ktbtr`.`block_id` LEFT JOIN
			 	`kolumbus_classroom` `kr` ON
			 	    `kr`.`id` = `ktbtr`.`room_id` AND
			 	    `kr`.`active` = 1 LEFT JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktb`.`teacher_id` AND
					`kt`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktbst`.`block_id` = `ktb`.`id` AND
					`ktbst`.`day` = `ktbd`.`day` AND
					`ktbst`.`active` = 1 LEFT JOIN
				`ts_tuition_levels` `ktl` ON
					`ktl`.`id` = `ktb`.`level_id`
			WHERE
		  		`ktb`.`school_id` IN (:school_ids) AND
			  	`ktb`.`week` = :week_from AND
			  	`ktb`.`active` = 1      
			GROUP BY 
			 	`ktb`.`id`,
			 	`block_day`
			ORDER BY
		 		`block_day`,
			 	`time_from`;
			";

		$aSql = [
			'week_from' => $sWeekFrom,
			'school_ids' => $schoolIds,
			'course_startday' => \Ext_Thebing_School::getInstance(reset($schoolIds))->course_startday
		];

		$aBlocksData = \DB::getQueryRows($sSql, $aSql);

		if(empty($aBlocksData)) {
			return [];
		}

		return $aBlocksData;
	}

	/**
	 * @param string $sWeekFrom
	 * @param string $sWeekUntil
	 *
	 * @return array $aTargetLessons
	 */
	public function getTargetLessons(string $sWeekFrom, string $sWeekUntil, array $schoolIds) {

		$weekFromObject = new Carbon($sWeekFrom);
		$weekUntilObject = new Carbon($sWeekUntil);

		$sSql = "
			SELECT
			   	`teacher_id` `salary_teacher_id`,
				SUM(`lessons`) `lessons`
			FROM
				`kolumbus_teacher_salary` `kts`
			WHERE
				`kts`.`lessons_period` = 'week' AND
				`kts`.`active` = 1 AND
				`kts`.`valid_from` <= :week_until AND (
				`kts`.`valid_until` = '0000-00-00' OR
				`kts`.`valid_until` >= :week_from
				) AND
			    `kts`.`school_id` IN (:school_ids)
			GROUP BY
				`salary_teacher_id`
		";

		$aSql = [
			'school_ids' => $schoolIds,
			'week_from' => $weekFromObject->toDateString(),
			'week_until' => $weekUntilObject->toDateString(),
		];

		$aRows = (array)\DB::getQueryRows($sSql, $aSql);

		$aTargetLessons = [];

		foreach($aRows as $aRow) {
			$aTargetLessons[$aRow['salary_teacher_id']] = $aRow['lessons'];
		}

		return $aTargetLessons;
	}

	/**
	 * @param array $aDays
	 *
	 * @return array $aAbsenceData
	 */
	public function getTeachersAbsence(array $aDays, array $schoolIds) {

		$sSql = "
			SELECT
				`ka`.`from`,
				`ka`.`until`,
			   	`ka`.`comment`,
			   	`ka`.`item_id` `teacher_id`,
				`kac`.`name` `category_name`,   
				`kac`.`color`
			FROM 
				`kolumbus_absence` `ka` INNER JOIN
				`kolumbus_absence_categories` `kac` ON
					`kac`.`id` = `ka`.`category_id` AND 
					`kac`.`active` = 1
			WHERE 
				`ka`.`school_id` IN (:school_ids) AND
				`ka`.`item` = 'teacher' AND 
				`ka`.`active` = 1
			";

		$aSql = [
			'school_ids' => $schoolIds
		];

		$aRows = (array)\DB::getQueryRows($sSql, $aSql);

		$aAbsenceData = [];

		foreach($aRows as $aRow) {

			$dAbsenceFrom = new \DateTime($aRow['from']);
			$dAbsenceUntil = new \DateTime($aRow['until']);

			foreach($aDays as $iDay => $dDay) {

				if(
					$dDay >= $dAbsenceFrom &&
					$dDay <= $dAbsenceUntil
				) {
					$aAbsenceData[$aRow['teacher_id']][$iDay] = $aRow;
				}

			}

		}

		return $aAbsenceData;
	}

}
