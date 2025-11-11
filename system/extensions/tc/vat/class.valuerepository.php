<?php

class Ext_TC_Vat_ValueRepository extends WDBasic_Repository {

	public function findCurrentEntries(): array {
		return $this->findEntriesByDate(new \DateTime());
	}

	public function findEntriesByDate(\DateTime $date): array {

		$sql = "
				SELECT
					`tc_vrv`.*
				FROM
					`tc_vat_rates_values` `tc_vrv` INNER JOIN
					`tc_vat_rates` `tc_vr` ON
						`tc_vr`.`id` = `tc_vrv`.`rate_id` AND
						`tc_vr`.`active` = 1
				WHERE
					`tc_vrv`.`active` = 1 AND
					`tc_vrv`.`valid_from` <= :date AND
					(
						`tc_vrv`.`valid_until` >= :date OR
						`tc_vrv`.`valid_until` = '0000-00-00'
					)
			";

		$vatRates = (array) DB::getPreparedQueryData($sql, [
			'date' => $date->format('Y-m-d')
		]);

		return $this->_getEntities($vatRates);
	}

}
