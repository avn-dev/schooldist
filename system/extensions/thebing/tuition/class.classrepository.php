<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Ext_Thebing_Tuition_ClassRepository extends WDBasic_Repository
{
	/**
	 * Prüfen, ob diese Klasse aktive Zuweisungen in der Klassenplanung hat
	 *
	 * @param Ext_Thebing_Tuition_Class $class
	 * @return bool
	 */
	public function hasTuitionAllocation(Ext_Thebing_Tuition_Class $class)
	{
		$sql = "
			SELECT
				`ktbic`.`id`
			FROM
			    `kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN 
			    `kolumbus_tuition_blocks` `ktb` ON 
			    	`ktb`.`id` = `ktbic`.`block_id` AND
			    	`ktb`.`class_id` = :class_id AND
			    	`ktb`.`active` = 1
			WHERE
			    `ktbic`.`active` = 1
			LIMIT 1
		";

		$firstAllocation = DB::getQueryOne($sql, ['class_id' => $class->id]);

		return !empty($firstAllocation);
	}

	/**
	 * @return Collection
	 */
	public function findOnlineBookableBySchoolAndPeriodAndLanguage(int $schoolId, CarbonInterface $from, CarbonInterface $until, array $courseIds, int $courseLanguageId = null): Collection
	{
		$sql = "
			SELECT
			    ktcl.*
			FROM
			    kolumbus_tuition_classes ktcl INNER JOIN
				kolumbus_tuition_courses ktc ON
					ktc.id = ktcl.online_bookable_as_course
			WHERE
			    ktcl.active = 1 AND
			    ktcl.school_id = :school_id AND
				ktcl.online_bookable_as_course IS NOT NULL AND
				ktcl.online_bookable_as_course IN (:courses) AND
				ktcl.start_week <= :until AND
				ktcl.start_week + INTERVAL ktcl.weeks WEEK >= :from AND
				(:language_id IS NULL OR ktcl.courselanguage_id = :language_id) AND
				ktc.active = 1
			ORDER BY
			    ktc.position
		";

		$result = collect(\DB::getQueryRows($sql, [
			'school_id' => $schoolId,
			'from' => $from,
			'until' => $until,
			'courses' => $courseIds,
			'language_id' => $courseLanguageId
		]));

		return $result->map(fn(array $c) => Ext_Thebing_Tuition_Class::getObjectFromArray($c));
	}

	public function getFreeBlocksForWeek(Ext_Thebing_Tuition_Class $class, CarbonInterface $week, string $iso): array
	{
		$sql = "
			SELECT
			    ktb.id block_id,
				ts_tl.id level_id,
				ts_tl.name_{$iso} level_name,
				ts_tl.position level_position,
				GROUP_CONCAT(DISTINCT ktbd.day) days_list,
				ktt.from,
				ktt.until,
				ktt.lessons,
				ktcl.lesson_duration,
				GROUP_CONCAT(DISTINCT ktcr.id) room_ids,
				MIN(ktc.maximum_students) max_students_course,
				MIN(ktcr.max_students) max_students_room,
				(
					/* Rest weg gelassen, weil das mittlerweile kaskadiert gelöscht wird */
					SELECT
						COUNT(id)
					FROM
						kolumbus_tuition_blocks_inquiries_courses
					WHERE
						block_id = ktb.id AND
						active = 1
				) students
			FROM
				kolumbus_tuition_blocks ktb INNER JOIN
				kolumbus_tuition_classes ktcl ON
					ktcl.id = ktb.class_id INNER JOIN
				kolumbus_tuition_classes_courses ktclc ON
				    ktclc.class_id = ktcl.id INNER JOIN
				kolumbus_tuition_courses ktc ON
					ktc.id = ktclc.course_id INNER JOIN
				kolumbus_tuition_blocks_days ktbd ON
					ktbd.block_id = ktb.id INNER JOIN
				kolumbus_tuition_templates ktt ON
					ktt.id = ktb.template_id LEFT JOIN
				ts_tuition_levels ts_tl ON
					ts_tl.id = ktb.level_id LEFT JOIN
				kolumbus_tuition_blocks_to_rooms ktbtr ON
					ktbtr.block_id = ktb.id LEFT JOIN
				kolumbus_classroom ktcr ON
					ktcr.id = ktbtr.room_id
			WHERE
				ktb.class_id = :class_id AND
				ktb.week = :week AND
				ktb.active = 1
			GROUP BY
				ktb.id
			HAVING
				(max_students_course < 1 OR students < max_students_course) AND
				(max_students_room IS NULL OR students < max_students_room)
		";

		return (array)\DB::getQueryRows($sql, [
			'class_id' => $class->id,
			'week' => $week->toDateString()
		]);
	}
}
