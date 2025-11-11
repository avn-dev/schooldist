<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

use TsStatistic\Generator\Tool\Groupings\Course\Course as GroupingCourse;

class Course extends GroupingCourse {

	public function getSelectFieldForId() {
		return "`ktc_items`.`id`";
	}

	public function getSelectFieldForLabel() {
		$sInterfaceLanguage = \System::getInterfaceLanguage();
		return "`ktc_items`.`name_{$sInterfaceLanguage}`";
	}

	public function getJoinParts() {
		// Nicht den normalen Part einbauen
		return [];
	}

	public function getJoinPartsAdditions() {
		return [
			'JOIN_ITEMS' => " AND
				`kidvi`.`type` = 'course'
			",
			// Das MUSS über die Items gehen, da die Kursbuchung verändert werden kann!
			"JOIN_ITEMS_JOINS" => " /* INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`id` = `kidvi`.`type_id` */ INNER JOIN
				`kolumbus_tuition_courses` `ktc_items` ON
					`ktc_items`.`id` = `kidvi`.`type_object_id` INNER JOIN
				`ts_tuition_coursecategories` `ktcc_items` ON
					`ktcc_items`.`id` = `ktc_items`.`category_id`
			"
		];
	}

}
