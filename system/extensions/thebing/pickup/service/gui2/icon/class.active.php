<?php

class Ext_Thebing_Pickup_Service_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

	/**
	 * Objekt darf nicht gelöscht werden, wenn eine Verknüpfung zu einer Buchung
	 * und/oder einer Bezahlung besteht
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param object $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->task == 'deleteRow') {			

			$aData = reset($aRowData);
			
			$bCheckInquiryRelation = $this->_checkInquiryRelation($aSelectedIds, $aData, $oElement);
			if($bCheckInquiryRelation) {
				return 0;
			}

		}
		
		return 1;
	}
	
	/**
	 * Prüft, ob eine Verbindung zu einer Buchung besteht
	 *
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param object $oElement
	 * @return boolean
	 */
	final protected function _checkInquiryRelation(&$aSelectedIds, &$aRowData, &$oElement) {
				
		$oObject = $this->_getObject($aRowData);
		
		if(!empty($oObject)) {
			$aJourneyTransfers = $this->_getJourneyTransfers($oObject);

			if(!empty($aJourneyTransfers)) {
				return true;
			}
		}
		
		return false;
	}	
	
	/**
	 * Holt alle Journey-Transfers für das jeweilige Objektes
	 *
	 * @param $oObject
	 * @return array
	 */
	protected function _getJourneyTransfers($oObject) {		
		$aJourneyTransfers = array();
		return $aJourneyTransfers;
	}
	
	/**
	 * Holt das zugehörige Objekt
	 *
	 * @param array $aRowData
	 * @return null
	 */
	protected function _getObject($aRowData) {
		return null;
	}
	
}