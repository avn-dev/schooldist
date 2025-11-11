<?php

namespace TsActivities\Entity\Activity;

class ProviderRepository extends \WDBasic_Repository {

	/**
	 * @return array
	 */
	public function getProviderSelectList() {

		$sSql = "
			SELECT 
				`ts_actp`.`id`,
				CONCAT_WS(', ', `tc_c`.`lastname`, `tc_c`.`firstname`) `name`
			FROM 
			  	`ts_activities_providers` `ts_actp` LEFT JOIN 
			  	`tc_contacts` `tc_c` ON 
			  		`ts_actp`.`contact_id` = `tc_c`.`id` 
			WHERE 
				`ts_actp`.`active` = 1 AND 
				(
					`ts_actp`.`valid_until` = 0000-00-00 OR 
					`ts_actp`.`valid_until` = NOW()
				)
			ORDER BY
				`tc_c`.`lastname`, 
				`tc_c`.`firstname`
		";

		$aResult = \DB::getQueryPairs($sSql);

		return $aResult;
	}

}
