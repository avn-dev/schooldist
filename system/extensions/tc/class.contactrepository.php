<?php

class Ext_TC_ContactRepository extends \WDBasic_Repository {

	/**
	 * @param string|array $systemTypes
	 * @return array
	 */
	public function findBySystemType($systemTypes): array {

		if (!is_array($systemTypes)) {
			$systemTypes = [$systemTypes];
		}

		$sql = "
			SELECT
				`tc_c`.*
			FROM
				`tc_contacts` `tc_c` INNER JOIN
				`tc_contacts_to_system_types` `tc_ctst` ON
				    `tc_ctst`.`contact_id` = `tc_c`.`id` INNER JOIN
				`tc_system_type_mapping_to_system_types` `tc_stmtst` ON 
					`tc_stmtst`.`mapping_id` = `tc_ctst`.`mapping_id` AND
				    `tc_stmtst`.`type` IN (:type)
			WHERE	
				`tc_c`.`active` = 1
			GROUP BY 
				`tc_c`.`id`
		";

		$rows = (array)\DB::getPreparedQueryData($sql, ['type' => $systemTypes]);

		return $this->_getEntities($rows);
	}


}
