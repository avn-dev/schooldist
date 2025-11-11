<?php

class Ext_TS_Pickup_Gui2_Icon_Visible extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return bool
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(empty($aSelectedIds)) {
			if(
				$oElement->action == 'confirmPickupRequest' ||
				$oElement->action == 'confirmPickupProvider' ||
				$oElement->action == 'confirmPickupAccommodationProvider' ||
				$oElement->action == 'confirmPickupCustomer'
			) {
				return true;
			}
			if(
				$oElement->action == 'deletePickupRequestConfirmation' ||
				$oElement->action == 'deletePickupProviderConfirmation' ||
				$oElement->action == 'deletePickupAccommodationProviderConfirmation' ||
				$oElement->action == 'deletePickupCustomerConfirmation'
			) {
				return false;
			}
		}

		$aRow = reset($aRowData);
		$iId = (int)$aRow['inquiry_transfer_id'];

		$oJourneyTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iId);

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  

		if(
			$oElement->action == 'confirmPickupRequest' ||
			$oElement->action == 'deletePickupRequestConfirmation'
		) {

			$aProviderRequests = $oJourneyTransfer->getProviderRequests();

			if($oElement->action == 'confirmPickupRequest') {
				if(!empty($aProviderRequests)) {
					return false;
				}
			}

			if($oElement->action == 'deletePickupRequestConfirmation') {
				if(empty($aProviderRequests)) {
					return false;
				}
			}

		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  

		if($oElement->action == 'confirmPickupProvider') {
			if($oJourneyTransfer->provider_confirmed > 0) {
				return false;
			}
		}

		if($oElement->action == 'deletePickupProviderConfirmation') {
			if($oJourneyTransfer->provider_confirmed == 0) {
				return false;
			}
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  

		if($oElement->action == 'confirmPickupAccommodationProvider') {
			if($oJourneyTransfer->accommodation_confirmed > 0) {
				return false;
			}
		}

		if($oElement->action == 'deletePickupAccommodationProviderConfirmation') {
			if($oJourneyTransfer->accommodation_confirmed == 0) {
				return false;
			}
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  

		if($oElement->action == 'confirmPickupCustomer') {
			if($oJourneyTransfer->customer_agency_confirmed > 0) {
				return false;
			}
		}

		if($oElement->action == 'deletePickupCustomerConfirmation') {
			if($oJourneyTransfer->customer_agency_confirmed == 0) {
				return false;
			}
		}
				
		return true;
	}
	
}