<?php


class Ext_TC_System_Checks_Vat_AllocationAddress extends \GlobalChecks {

	public function getTitle() {
		return "Vat Allocations";
	}

	public function getDescription() {
		return "Assign default addressee to vat allocations";
	}

	public function executeCheck() {

		$sql = "
			SELECT 
				`vra`.`id` 
			FROM 
				`tc_vat_rates_allocations` `vra` LEFT JOIN
				`tc_vat_rates_allocations_address_types` `vraa` ON 
					`vraa`.`allocation_id` = `vra`.`id` 
			WHERE
				`vra`.`active` = 1 AND
				 `vraa`.`allocation_id` IS NULL 
		";

		$allocations = \DB::getQueryCol($sql);

		if(!empty($allocations)) {

			$types = ['private', 'company'];

			foreach($allocations as $allocationId) {
				foreach ($types as $type) {
					\DB::insertData('tc_vat_rates_allocations_address_types', [
						'allocation_id' => $allocationId,
						'type' => $type
					]);
				}
			}

		}

		return true;
	}

}
