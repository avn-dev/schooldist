<?php


class Ext_Thebing_Gui2_Format_Accommodation_Confirmed extends Ext_Gui2_View_Format_Abstract {

	protected $_sType = '';

	public function __construct($sType = '') {
		$this->_sType = $sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Wenn keine Unterkunft zugewiesen ist, dann leer lassen
		#if(empty($aResultData['active_accommodation_allocations'])) {
			#return '';
		#}
		#__uout($aResultData, 'Mehmet');

		if(
			$this->_sType == 'accommodation_customer_agency_confirmed'
		){
			if(
				!empty($aResultData['inactive_customer_agency_confirmed'])
			){
				$aConfirmed			= explode(',',$aResultData['inactive_customer_agency_confirmed']);
				$sLastConfirmed		= reset($aConfirmed);
				$oDate				= new WDDate($sLastConfirmed, WDDate::DB_TIMESTAMP);
				$mValue				= $oDate->get(WDDate::TIMESTAMP);
			}
		}

		if(
			$this->_sType == 'accommodation_accommodation_confirmed'
		){
			if(
				!empty($aResultData['inactive_accommodation_confirmed'])
			){
				$aConfirmed			= explode(',',$aResultData['inactive_accommodation_confirmed']);
				$sLastConfirmed		= reset($aConfirmed);
				$oDate				= new WDDate($sLastConfirmed, WDDate::DB_TIMESTAMP);
				$mValue				= $oDate->get(WDDate::TIMESTAMP);
			}
		}

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date_Time();

		$sContent = '';

		if(!empty($mValue) && $mValue != '0000-00-00 00:00:00') {
			$sContent = $oFormatDate->format($mValue);
		}

		return $sContent;

	}

}