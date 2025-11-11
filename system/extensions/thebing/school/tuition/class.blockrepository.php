<?php

class Ext_Thebing_School_Tuition_BlockRepository extends WDBasic_Repository
{

	/**
	 * @param DateTimeInterface $startDate
	 * @param DateTimeInterface $endDate
	 * @param Ext_Thebing_School $school
	 * @param Ext_Thebing_Teacher|null $teacher
	 *
	 * @return array $aResult
	 */
	public function getTuitionBlocks(
		DateTimeInterface $startDate,
		DateTimeInterface $endDate,
		\Ext_Thebing_School $school,
		?\Ext_Thebing_Teacher $teacher = null
	): array
	{

		$where = " AND `ktb`.`school_id` = :school_id ";
		if(!empty($teacher)) {
			$where .= " AND (`ktbst`.`teacher_id` = :teacher_id OR `kt`.`id` = :teacher_id) ";
		}

		$sql = "
			SELECT
				`ktb`.`id`,
				`ktcl`.`name`,
				`ktt`.`from`,
				`ktt`.`until`,
				GROUP_CONCAT(`kr`.`name` SEPARATOR ', ') `room`,
				GROUP_CONCAT(`kr`.`id`) `room_ids`,
				`ktb`.`week`,
				`ktb`.`level_id`,
				`ktb`.`class_id`,
				`ktb`.`school_id`,
				`ktbd`.`day`,
			  	getRealDateFromTuitionWeek(
					`ktb`.`week`,
					`ktbd`.`day`,
					:course_startday
				) `date`
			FROM
				`kolumbus_tuition_blocks` `ktb`  LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON   
				   `ktbtr`.`block_id` = `ktb`.`id` INNER JOIN 
				`kolumbus_tuition_templates` `ktt` ON
					`ktb`.`template_id` = `ktt`.`id` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` AND
					`ktcl`.`active` = 1 LEFT JOIN
				`kolumbus_classroom` `kr` ON 
					`ktbtr`.`room_id` = `kr`.`id` LEFT JOIN 
				`ts_teachers` `kt` ON
					`ktb`.`teacher_id` = `kt`.`id` LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktb`.`id` = `ktbst`.`block_id` AND
					`ktbd`.`day` = `ktbst`.`day` AND
					`ktbst`.`active` = 1 
			WHERE
			  	`ktb`.`active` = 1 AND
				CONCAT(
					  getRealDateFromTuitionWeek(
						`ktb`.`week`,
						`ktbd`.`day`,
						:course_startday
					),
					' ',
					`ktt`.`until`
				) BETWEEN :start_date AND :end_date
				{$where}
			GROUP BY
				`ktb`.`id`,
				`ktbd`.`day`
			ORDER BY
				`date` DESC,
				`ktt`.`from` ASC,
				`ktcl`.`name` ASC
		";

		$sqlData = array(
			'start_date' => $startDate->format('Y-m-d H:i:s'),
			'end_date' => $endDate->format('Y-m-d H:i:s'),
			'course_startday' => $school->course_startday,
			'school_id' => $school->id
		);

		if (!empty($teacher)) {
			$sqlData['teacher_id'] = $teacher->id;
		}

		return (array)DB::getQueryRows($sql, $sqlData);
	}

}