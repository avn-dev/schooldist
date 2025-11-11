<?php

use Ts\Helper\Accommodation\AllocationCombination;

class Ext_Thebing_Accommodation_VisitRepository extends WDBasic_Repository {
	
	public function getAllActive() {

		$sSql = "
			SELECT
				`kav`.*
			FROM 
				`kolumbus_accommodation_visits` `kav` JOIN
				`customer_db_4` `cdb4` ON
					`kav`.`acc_id` = `cdb4`.`id` AND
					`cdb4`.`active` = 1 AND
					(
						`cdb4`.`valid_until` >= CURDATE() OR
						`cdb4`.`valid_until` = '0000-00-00'
					)
			WHERE
				`kav`.`active` = 1				
			ORDER BY
				`kav`.`acc_id`,
				`kav`.`date`
			";
		
		$aResults = \DB::getQueryRows($sSql, $aSql);

		$aEntities = [];
		if(!empty($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}
	
}
