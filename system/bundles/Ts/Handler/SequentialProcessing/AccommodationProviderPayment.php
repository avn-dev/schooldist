<?php

namespace Ts\Handler\SequentialProcessing;

use \Core\Handler\SequentialProcessing\TypeHandler;

class AccommodationProviderPayment extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute($oAllocation) {

		$oPaymentRepository = \Ts\Entity\AccommodationProvider\Payment::getRepository();

		// Alle Zuweisung der Kombination berücksichtigen!
		$oAccommodationAllocationCombination = new \Ts\Helper\Accommodation\AllocationCombination($oAllocation);

		$aAllocationIds = $oAccommodationAllocationCombination->getAllocationIds();

		$aCriteria = [
			'accommodation_allocation_id' => (array)$aAllocationIds
		];

		/* @var \Ts\Entity\AccommodationProvider\Payment[] $aPayments */
		$aPayments = $oPaymentRepository->findBy($aCriteria);

		foreach($aPayments as $oPayment) {
			$oPayment->delete();
		}

		$aSkipAllocationIds = [];
		foreach($oAccommodationAllocationCombination as $oCombinationAllocation) {

			\Core\Facade\SequentialProcessing::remove('ts/accommodation-provider-payment', $oCombinationAllocation);

			// Überspringen wenn nicht aktiv oder schon bearbeitet
			if(
				$oCombinationAllocation->active != 1 ||
				$oCombinationAllocation->status != 0 ||
				in_array($oCombinationAllocation->id, $aSkipAllocationIds)
			) {
				continue;
			}

			$oCombinationAllocationCombination = new \Ts\Helper\Accommodation\AllocationCombination($oCombinationAllocation);

			$aSkipAllocationIds += $oCombinationAllocationCombination->getAllocationIds();

			$oPaymentGenerator = new \Ts\Generator\AccommodationProvider\PaymentGenerator($oCombinationAllocationCombination);
			$oPaymentGenerator->run();

		}

	}

	/**
	 * @inheritdoc
	 */
	public function check($oObject) {
		return $oObject instanceof \Ext_Thebing_Accommodation_Allocation;
	}

}
