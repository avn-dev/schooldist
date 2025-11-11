<?php

class Ext_Thebing_Pickup_Airport_Gui2_Icon_Active extends Ext_Thebing_Pickup_Service_Gui2_Icon_Active {

	/**
	 * Holt den aktuellen Reiseort
	 *
	 * @param array $aRowData
	 * @return Ext_TS_Transfer_Location
	 */
	protected function _getObject($aRowData) {

		$iAirportId = (int) $aRowData['id'];
		$oAirport = Ext_TS_Transfer_Location::getInstance($iAirportId);
		
		return $oAirport;
	}
	
	/**
	 * Holt alle Journey-Transfers für das jeweilige Objektes
	 *
	 * @param Ext_TS_Transfer_Location $oAirport
	 * @return array
	 */
	protected function _getJourneyTransfers($oAirport) {		
		$aJourneyTransfers = $oAirport->getInquiryJourneyTransfers();
		return $aJourneyTransfers;
	}
	
}
