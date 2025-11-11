<?php

use Ts\Helper\Accommodation\AllocationCombination;

class Ext_Thebing_Accommodation_RoomRepository extends WDBasic_Repository {
	
	public function getAllActive() {

		$sSql = "
			SELECT
				`kr`.*
			FROM 
				`kolumbus_rooms` `kr` JOIN
				`customer_db_4` `cdb4` ON
					`kr`.`accommodation_id` = `cdb4`.`id` AND
					`cdb4`.`active` = 1 AND
					(
						`cdb4`.`valid_until` >= CURDATE() OR
						`cdb4`.`valid_until` = '0000-00-00'
					)
			WHERE
				`kr`.`active` = 1
				
			ORDER BY
				`kr`.`accommodation_id`,
				`kr`.`position`
			";
		
		$aResults = \DB::getQueryRows($sSql, $aSql);

		$aEntities = [];
		if(!empty($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}
	
}
