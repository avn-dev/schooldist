<?php

namespace TsStatistic\Generator\Tool\Groupings\Revenue;

use TsStatistic\Generator\Tool\Groupings\AbstractGrouping;

/**
 * Diese Grupperierung kann nur mit einer entsprechenden Spalte funktionieren,
 * da es mehrere Provider zu einem Item geben kann.
 */
class AccommodationProvider extends AbstractGrouping {

	public function getTitle() {
		return self::t('Unterkunftsanbieter');
	}

	public function getSelectFieldForId() {
		return "`cdb4`.`id`";
	}

	public function getSelectFieldForLabel() {
		return "`cdb4`.`ext_33`";
	}

	public function getJoinPartsAdditions() {
		return [
			'JOIN_ITEMS' => " AND
				`kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks')
			",
			// Das MUSS über die Items gehen, da die Unterkunftsbuchung verändert werden kann!
			"JOIN_ITEMS_JOINS" => " INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kidvi`.`type_id` INNER JOIN
				`kolumbus_accommodations_allocations` `kaa` ON
					`kaa`.`inquiry_accommodation_id` = `ts_ija`.`id` AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 INNER JOIN
				`kolumbus_rooms` `kr` ON
					`kr`.`id` = `kaa`.`room_id` INNER JOIN
				`customer_db_4` `cdb4` ON
					`cdb4`.`id` = `kr`.`accommodation_id`
			"
		];
	}

	public function getColumnColor() {
		return 'service';
	}

}
