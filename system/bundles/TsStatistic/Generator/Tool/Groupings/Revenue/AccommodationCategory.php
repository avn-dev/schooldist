<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

use TsStatistic\Generator\Tool\Groupings\Accommodation\Category;

class AccommodationCategory extends Category {

	public function getJoinParts() {
		// Nicht den normalen Part einbauen
		return [];
	}

	public function getJoinPartsAdditions() {
		return [
			'JOIN_ITEMS' => " AND
				`kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks', 'additional_accommodation')
			",
			// Das MUSS über die Items gehen, da die Unterkunftsbuchung verändert werden kann!
			"JOIN_ITEMS_JOINS" => " INNER JOIN
				`kolumbus_accommodations_categories` `kac` ON
					`kac`.`id` = IF(
						`kidvi`.`type` = 'additional_accommodation',
						`kidvi`.`type_parent_object_id`,
						`kidvi`.`type_object_id`
					)
			"
		];
	}

}
