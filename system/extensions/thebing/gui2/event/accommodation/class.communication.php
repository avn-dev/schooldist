<?php

class Ext_Thebing_Gui2_Event_Accommodation_Communication extends Ext_Gui2_View_Event_Abstract {

	protected $_sType = '';

	public function __construct($sType) {
		$this->_sType = $sType;
	}

	public function getEvent($mValue, $oColumn, $aResultData){

		$sEvent = 'click';

		// Icon nur anzeigen wenn bereits bestätigt worden ist
		if(
			$this->_sType == 'accommodation_canceled' &&
			$aResultData['accommodation_confirmed'] == 0
		){
			$sEvent = '';
		}

		if(
			$this->_sType == 'customer_agency_canceled' &&
			$aResultData['customer_agency_confirmed'] == 0
		){
			$sEvent = '';
		}

		// Kunde Agentur darf nur bestätigt werden wenn nicht der Unterkunft schon abgesagt wurde
		if(
			$this->_sType == 'customer_agency_confirmed' &&
			$aResultData['accommodation_canceled'] != 0
		){
			$sEvent = '';
		}

		// Unterkunft darf nur bestätigt werden wenn nicht dem Kunde Agentur schon abgesagt wurde
		if(
			$this->_sType == 'accommodation_confirmed' &&
			$aResultData['customer_agency_canceled'] != 0
		){
			$sEvent = '';
		}

		// Event nur anzeigen zum "bestätigen" wenn es KEINE alte Familie ist
		if(
			(
				$this->_sType == 'customer_agency_confirmed' ||
				$this->_sType == 'accommodation_confirmed'
			) &&
				$aResultData['active'] != 1
		){
			$sEvent = ''; 
		}

		// Prüfen ob Event gesetzt werden darf
		return $sEvent;
	}

	public function getFunction($mValue, $oColumn, $aResultData){

		// Passende Communikationsapplication
		$aApplication = '';

		switch($oColumn->db_column){
			case 'accommodation_confirmed':
				$aApplication = 'accommodation_communication_history_accommodation_confirmed';
				break;
			case 'accommodation_canceled':
				$aApplication = 'accommodation_communication_history_accommodation_canceled';
				break;
			case 'customer_agency_confirmed':
				$aApplication = 'accommodation_communication_history_customer_confirmed';
				break;
			case 'customer_agency_canceled':
				$aApplication = 'accommodation_communication_history_customer_canceled';
				break;
			default:
		}

		/*
		$aFunction = array();
		$aFunction['name'] = 'openCommunicationDialog';

		$aArgs = array();
		$aArgs[] = 'communication';
		$aArgs[] = $aApplication;
		$aArgs[] = 'openDialog';

		$aFunction['args'] = $aArgs;
		*/
		
		$aFunction = array();
		if(
			$aApplication == 'accommodation_communication_history_accommodation_confirmed' ||
			$aApplication == 'accommodation_communication_history_customer_confirmed'
		){
			$aFunction['name'] = 'openCommunicationDialog';
		}else{
			$aFunction['name'] = 'confirmCancelCommunicationDialog';
		}
		
		$aArgs = array();
		$aArgs[] = 'communication';
		$aArgs[] = $aApplication;
		$aArgs[] = 'request';

		$aFunction['args'] = $aArgs;
		
		
		
		return $aFunction;
	}

}