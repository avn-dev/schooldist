<?php

namespace Ts\Entity\AccommodationProvider;

use Ext_Thebing_Accommodation_Allocation as Allocation;
use Ts\Helper\Accommodation\AllocationCombination;

class PaymentRepository extends \WDBasic_Repository {
	
	/**
	 * 
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
				`accommodation_allocation_id` IN (:accommodation_allocation_ids)
			ORDER BY
				`until` DESC
			LIMIT 1
			";
		$aSql = array(
			'table' => $sTable,
			'accommodation_allocation_ids' => $oAllocationCombination->getAllocationIds()
		);
		
		$aResult = \DB::getQueryRow($sSql, $aSql);
		
		$oEntity = null;
		if(is_array($aResult)) {
			$oEntity = $this->_getEntity($aResult);
		}

		return $oEntity;	
		
	}

}