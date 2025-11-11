<?php

namespace TsScreen\Entity;

class ScheduleRepository extends \WDBasic_Repository {

	public function getValidElement(Screen $screen, string $type=null) {
		
		$sqlParameter = [
			'screen_id' => (int)$screen->id,
			'date' => date('Y-m-d'),
			'time' => date('H:i:s')
		];
		
		$sqlQuery = "
			SELECT 
				* 
			FROM 
				`ts_screens_schedule` 
			WHERE 
				`active` = 1 AND
				`visible` = 1 AND
				`screen_id` = :screen_id AND
				(
					`date_from` IS NULL OR
					`date_from` <= :date OR
					`type` = 'roomplan'
				) AND
				(
					`date_to` IS NULL OR
					`date_to` >= :date OR
					`type` = 'roomplan'
				) AND
				(
					`time_from` IS NULL OR
					`time_from` <= :time OR
					`type` = 'roomplan'
				) AND
				(
					`time_to` IS NULL OR
					`time_to` >= :time OR
					`type` = 'roomplan'
				)
				";
		if($type !== null) {
			$sqlQuery .= " AND `type` = :type ";
			$sqlParameter['type'] = $type;
		}
				
		$result = \DB::getQueryRow($sqlQuery, $sqlParameter);

		$entity = array();
		if(!empty($result)) {
			$entity = $this->_getEntity($result);
		}

		return $entity;
	}

}