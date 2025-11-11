<?php

namespace TsTuition\Entity\Course;

class ProgramRepository extends \WDBasic_Repository {

	public function hasJourneyCourses(int $programId): bool {

		$sql = "
			SELECT
				`ts_ijc`.`id`
			FROM
				`ts_inquiries_journeys_courses` `ts_ijc` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1
			WHERE
				`ts_ijc`.`active` = 1 AND
				`ts_ijc`.`program_id` = :id
			LIMIT 1
		";

		$firstResult = (array)\DB::getQueryRow($sql, ['id' => $programId]);

		return !empty($firstResult);
	}

}
