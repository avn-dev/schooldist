<?php

class Ext_Thebing_Tuition_LevelGroupRepository extends WDBasic_Repository
{
	public function findUsedBySchools(array $schools)
	{
		$schoolIds = array_map(fn ($school) => $school->id, $schools);

		$sql = "
				SELECT
					`ts_tcl`.*
				FROM
				    `ts_tuition_courselanguages` `ts_tcl` INNER JOIN
				    `ts_tuition_courses_to_courselanguages` `ts_tctc` ON 
				    	`ts_tctc`.`courselanguage_id` = `ts_tcl`.`id` INNER JOIN
				    `kolumbus_tuition_courses` `ktc` ON
				    	`ktc`.`id` = `ts_tctc`.`course_id` AND
				    	`ktc`.`school_id` IN (:school_ids) AND
				    	`ktc`.`active` = 1
				WHERE
				    `ts_tcl`.`active` = 1
				GROUP BY 
				    `ts_tcl`.`id`
			";

		// Kurssprachen die einem Kurs der Schule zugewiesen sind
		$entries = (array)\DB::getQueryRows($sql, ['school_ids' => $schoolIds]);

		return $this->_getEntities($entries);
	}
}
