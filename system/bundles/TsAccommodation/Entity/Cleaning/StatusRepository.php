<?php

namespace TsAccommodation\Entity\Cleaning;

class StatusRepository extends \WDBasic_Repository {

	public function getLastStatus(int $roomId, int $bed, array $exceptedIds = []): ?Status {

		$where = "";
		if (!empty($exceptedIds)) {
			$where .= " AND `id` NOT IN (:excepted_ids) ";
		}

		$sql = "
            SELECT
                *
            FROM
                #table
            WHERE
                `room_id` = :room_id AND
                `bed` = :bed AND
                `active` = 1 
            	".$where."
            ORDER BY 
                `date` DESC 
            LIMIT 1                   
        ";

		$row = (array)\DB::getQueryRow($sql, [
			'table' => $this->_oEntity->getTableName(),
			'room_id' => $roomId,
			'bed' => $bed,
			'excepted_ids' => $exceptedIds,
		]);

		if(!empty($row)) {
			return $this->_getEntity($row);
		}

		return null;

	}

}
