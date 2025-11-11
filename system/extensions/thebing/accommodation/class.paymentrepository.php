<?php

use Ts\Helper\Accommodation\AllocationCombination;

class Ext_Thebing_Accommodation_PaymentRepository extends WDBasic_Repository {
	
	/**
	 * @param AllocationCombination $oAllocationCombination
	 * @return Payment
	 */
	public function getLatestPaymentByAllocationCombination(AllocationCombination $oAllocationCombination) {

		$sTable = $this->_oEntity->getTableName();
		
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				`allocation_id` IN (:allocation_ids) AND
				`active` = 1
			ORDER BY
				`until` DESC
			LIMIT 1
			";
		$aSql = array(
			'table' => $sTable,
			'allocation_ids' => $oAllocationCombination->getAllocationIds()
		);

		$aResult = \DB::getQueryRow($sSql, $aSql);

		$oEntity = null;
		if(is_array($aResult)) {
			$oEntity = $this->_getEntity($aResult);
		}

		return $oEntity;
	}
	
}
