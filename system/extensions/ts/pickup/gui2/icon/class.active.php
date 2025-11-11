<?php

class Ext_TS_Pickup_Gui2_Icon_Active extends Ext_Thebing_Gui2_Icon_Inbox {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return bool|int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->task === 'export_csv') {
			return true;
		}

		if(empty($aSelectedIds)) {
			return false;
		}

		if(
			$oElement->action === 'confirmPickupProvider' ||
			$oElement->action === 'confirmPickupCustomer' ||
			$oElement->action === 'confirmPickupAccommodationProvider' ||
			$oElement->action === 'communication' && (
				$oElement->additional === 'transfer_provider_confirm' ||
				$oElement->additional === 'transfer_customer_agency_information' ||
				$oElement->additional === 'transfer_customer_accommodation_information'
			)
		) {
			foreach($aRowData as $aRow) {
				// Prüfen ob überhaupt schon Provider zugewiesen wurde
				if(empty($aRow['provider_id'])) {
					return false;
				}

				// Icons für Unterkunft haben noch Prüfung nach Start/Ende und vorhandenen Zuweisungen
				if(
					$oElement->action === 'confirmPickupAccommodationProvider' ||
					$oElement->additional === 'transfer_customer_accommodation_information'
				) {
//					if(
//						$aRow['end_type'] !== 'accommodation' &&
//						$aRow['start_type'] !== 'accommodation'
//					) {
//						return false;
//					}

					if(empty($aRow['accommodation_info'])) {
						return false;
					}
				}
			}

			return true;
		}

		return parent::getStatus($aSelectedIds, $aRowData, $oElement);

	}
	
}