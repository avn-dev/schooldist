<?php

/**
 * Class Ext_Thebing_AgencyRepository
 */
class Ext_Thebing_AccommodationRepository extends WDBasic_Repository {

	/**
	 * @param string $number
	 */
	public function findByNumber(string $number) {

		$sql = "
			SELECT
				`cdb`.*
			FROM
				#table `cdb` INNER JOIN
				`ts_accommodations_numbers` `ts_an` ON 
					`ts_an`.`accommodation_id` = `cdb`.`id` AND 
					`ts_an`.`number` = :number
			WHERE
				`cdb`.`active` = 1
		";

		$row = (array)\DB::getQueryRow($sql, [
			'table' => $this->_oEntity->getTableName(),
			'number' => $number,
		]);

		if(!empty($row)) {
			return $this->_getEntity($row);
		}

		return null;
	}

}
