<?php

class Ext_TS_Inquiry_Contact_TravellerRepository extends WDBasic_Repository {

	/**
	 * @param string $sLastname
	 * @param string $sNumber
	 * @return Ext_TS_Inquiry_Contact_Traveller|null
	 */
	public function findOneByLastnameAndNumber($sLastname, $sNumber) {

		$sSql = "
			SELECT
				`tc_c`.*
			FROM
				`tc_contacts` `tc_c` INNER JOIN
				`tc_contacts_numbers` `tc_cn` ON 
					`tc_cn`.`contact_id` = `tc_c`.`id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`contact_id` = `tc_cn`.`contact_id` AND
					`ts_itc`.`type` = 'traveller'
			WHERE
				`tc_c`.`lastname` = :lastname AND
				`tc_cn`.`number` = :number
			LIMIT
				1
		";

		$aRow = DB::getQueryRow($sSql, [
			'lastname' => $sLastname,
			'number' => $sNumber
		]);

		if(empty($aRow)) {
			return null;
		}

		return $this->_oEntity->getObjectFromArray($aRow);

	}
}
