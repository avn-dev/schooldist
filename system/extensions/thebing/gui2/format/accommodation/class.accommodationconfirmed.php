<?php

/**
 * Kommunidationsstatus in der History Liste der Gui
 */
class Ext_Thebing_Gui2_Format_Accommodation_AccommodationConfirmed extends Ext_Gui2_View_Format_Abstract {

	protected $_sType = '';

	public function __construct($sType) {
		$this->_sType = $sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		//$iAccommodationAllocationId = (int)$aResultData['id'];

		//$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAccommodationAllocationId);

		$mReturn = '';

		if($mValue > 0){
			// Datum anzeigen
			$oDateForamte = new Ext_Thebing_Gui2_Format_Date(true);
			$mReturn = $oDateForamte->format($mValue);
		}else{

			$bShowIcon = true;

			// Icon nur anzeigen wenn bereits bestätigt worden ist
			if(
				$this->_sType == 'accommodation_canceled' &&
				(
					$aResultData['accommodation_confirmed'] == 0// ||
					//$aResultData['status'] == 0
				)
			){
				$bShowIcon = false;
			}
			
			if(
				$this->_sType == 'customer_agency_canceled' &&
				(
					$aResultData['customer_agency_confirmed'] == 0 //||
					//$aResultData['status'] == 0
				)
			){
				$bShowIcon = false;
			}

			// Kunde Agentur darf nur bestätigt werden wenn nicht der Unterkunft schon abgesagt wurde
			if(
				$this->_sType == 'customer_agency_confirmed' &&
				$aResultData['accommodation_canceled'] != 0
			){
				$bShowIcon = false;
			}

			// Unterkunft darf nur bestätigt werden wenn nicht dem Kunde Agentur schon abgesagt wurde
			if(
				$this->_sType == 'accommodation_confirmed' &&
				$aResultData['customer_agency_canceled'] != 0
			){
				$bShowIcon = false;
			}

			// Icon nur anzeigen zum "bestätigen" wenn es KEINE alte Familie ist
			if(
				(
					$this->_sType == 'customer_agency_confirmed' ||
					$this->_sType == 'accommodation_confirmed'
				) &&
					$aResultData['active'] != 1
			){
				$bShowIcon = false;
			}

			if($bShowIcon){
				// Kommunidationsikon anzeigen
				$oImg = new Ext_Gui2_Html_I();
				$oImg->class = 'fa fa-envelope';
				$oImg->alt = L10N::t('Kommunikation');

				$mReturn = $oImg->generateHTML();
			}
		}

		return $mReturn;

	}

	public function align(&$oColumn = null){
		return 'center';
	}

}