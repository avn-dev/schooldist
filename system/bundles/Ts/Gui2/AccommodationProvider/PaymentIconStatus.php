<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentIconStatus extends \Ext_Gui2_View_Icon_Abstract {

	/**
	 * {@inheritdoc}
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->action == 'accommodation_payment') {

			// Wenn nichts ausgewählt ist kann auch nichts bezahlt werden
			if(count($aSelectedIds) < 1) {
				return 0;
			}

			$oPaymentCategoryRepository = $this->getPaymentCategoryRepository();
			$oCostCategoryRepository = $this->getCostCategoryRepository();

			// Alle ausgewählten Einträge brauchen eine Abrechnungs- und eine Kostenkategorie
			foreach($aRowData as $aRow) {

				$oAccommodationAllocation = \Ext_Thebing_Accommodation_Allocation::getInstance($aRow['accommodation_allocation_id']);
				$oAccommodationProvider = $oAccommodationAllocation->getAccommodationProvider();

				if(!($oAccommodationProvider instanceof \Ext_Thebing_Accommodation)) {
					return 0;
				}

				$dAllocationFrom = new \DateTime($oAccommodationAllocation->from);

				$oPaymentCategory = $oPaymentCategoryRepository->findByProvider($oAccommodationProvider, $dAllocationFrom);

				if(
					empty($oPaymentCategory) ||
					(
						$oPaymentCategory instanceof \Ts\Entity\AccommodationProvider\Payment\Category &&
						$oPaymentCategory->active == 0
					)
				) {
					return 0;
				}

				$oCostCategory = $oCostCategoryRepository->findByProvider($oAccommodationProvider, $dAllocationFrom);

				if(
					empty($oCostCategory) ||
					(
						$oCostCategory instanceof \Ext_Thebing_Accommodation_Cost_Category &&
						$oCostCategory->active == 0
					)
				) {
					return 0;
				}

			}

		}

		return 1;

	}

	/**
	 * @return \Ts\Entity\AccommodationProvider\Payment\CategoryRepository
	 */
	private function getPaymentCategoryRepository() {
		return \Ts\Entity\AccommodationProvider\Payment\Category::getRepository();
	}

	/**
	 * @return \Ext_Thebing_Accommodation_Cost_CategoryRepository
	 */
	private function getCostCategoryRepository() {
		return \Ext_Thebing_Accommodation_Cost_Category::getRepository();
	}

}
