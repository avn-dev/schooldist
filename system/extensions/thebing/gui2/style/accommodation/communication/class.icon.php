<?php

class Ext_Thebing_Gui2_Style_Accommodation_Communication_Icon extends Ext_Gui2_View_Style_Abstract {

	protected $_sType = '';

	public function __construct($sType = '') {
		$this->_sType = $sType;
	}

	public function getStyle($mValue, &$oColumn, &$aResultData) {

		$sStyle = '';

		if($mValue == 0){
			// Komplett Neu (noch kein Datum im Feld)

			if(
				$this->_sType == 'accommodation_canceled' &&
				$aResultData['accommodation_confirmed'] == 0
			){
				return '';
			}

			if(
				$this->_sType == 'customer_agency_canceled' &&
				$aResultData['customer_agency_confirmed'] == 0
			){
				return '';
			}

			// Wenn Unterkunft ODER Kunde abgesagt wurde kann keiner mehr bestätigt werden
			if(
				(
					$this->_sType == 'accommodation_confirmed' ||
					$this->_sType == 'customer_agency_confirmed'
				) && (
					$aResultData['accommodation_canceled'] != 0 ||
					$aResultData['customer_agency_canceled'] != 0
				)
			){
				return '';
			}

			// "Alte" Unterkünfte können niemals bestätigt werden
			if(
				(
					$this->_sType == 'accommodation_confirmed' ||
					$this->_sType == 'customer_agency_confirmed'
				) &&
				$aResultData['active'] != 1
			){
				return '';
			}


			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('bad').'; ';
		}elseif(
			$mValue < $aResultData['allocation_changed']
		){
			// Wurde nach Bestätigen verändert
			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('neutral').'; ';
		}else{
			// Alles OK
			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('good').'; ';
		}

		return $sStyle;

	}


}
