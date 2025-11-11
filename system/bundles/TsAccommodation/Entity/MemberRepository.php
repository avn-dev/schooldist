<?php

namespace TsAccommodation\Entity;

class MemberRepository extends \WDBasic_Repository {

	/**
	 * @param \Ext_Thebing_Accommodation $oAccommodation
	 * @param integer $iAge
	 * @return array $aEntities
	 */
	public function findByAge(\Ext_Thebing_Accommodation $oAccommodation, $iAge) {

		$sSql = "
			SELECT 
				`tc_c`.* 
			FROM
				`tc_contacts` `tc_c` JOIN
				`ts_accommodation_providers_to_contacts` `ts_aptc` ON 
					`tc_c`.`id` = `ts_aptc`.`contact_id`
		  	WHERE
				`tc_c`.`active` = 1 AND
				`ts_aptc`.`accommodation_provider_id` = :accommodation_provider_id AND 	
				getAge(`tc_c`.`birthday`) >= :age
		";
		$aSql = [
			'accommodation_provider_id' => $oAccommodation->id,
			'age' => $iAge
		];

		$aContacts = \DB::getQueryRows($sSql, $aSql);

		$aEntities = array();
		if(is_array($aContacts)) {
			$aEntities = $this->_getEntities($aContacts);
		}

		return $aEntities;

	}

	public function getAllActive() {

		$sSql = "
			SELECT
				`tc_c`.*
			FROM 
				`tc_contacts` `tc_c` JOIN
				`ts_accommodation_providers_to_contacts` `ts_aptc` ON
					`tc_c`.`id` = `ts_aptc`.`contact_id` JOIN
				`customer_db_4` `cdb4` ON
					`ts_aptc`.`accommodation_provider_id` = `cdb4`.`id` AND
					`cdb4`.`active` = 1 AND
					(
						`cdb4`.`valid_until` >= CURDATE() OR
						`cdb4`.`valid_until` = '0000-00-00'
					)
			WHERE
				`tc_c`.`active` = 1				
			ORDER BY
				`cdb4`.`id`
			";
		
		$aResults = \DB::getQueryRows($sSql);

		$aEntities = [];
		if(!empty($aResults)) {
			$aEntities = $this->_getEntities($aResults);
		}

		return $aEntities;
	}
	
}
