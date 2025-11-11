<?php

class Ext_Thebing_Gui2_Format_Transfer_Info extends Ext_Gui2_View_Format_Abstract {

	protected $sView = '';
	
	public function __construct($sView = 'arrival'){
		$this->sView = $sView;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($aResultData[$this->sView . '_start_type'] == 'location'){
			$oProviderList = new Ext_TC_GUI2_Format_List('Ext_TS_Transfer_Location', $this->sView . '_start', 'short');
			$sProviderStart = $oProviderList->format($mValue, $oColumn, $aResultData);
		}elseif($aResultData[$this->sView . '_start_type'] == 'school'){
			$sProviderStart = L10N::t('School', 'Thebing » Transfer');
		}elseif($aResultData[$this->sView . '_start_type'] == 'accommodation'){
			$sProviderStart = L10N::t('Unterkunft', 'Thebing » Transfer');;
		}

		if($aResultData[$this->sView . '_end_type'] == 'location'){
			$oProviderList = new Ext_TC_GUI2_Format_List('Ext_TS_Transfer_Location', $this->sView . '_end', 'short');
			$sProviderEnd = $oProviderList->format($mValue, $oColumn, $aResultData);
		}elseif($aResultData[$this->sView . '_end_type'] == 'school'){
			$sProviderEnd = L10N::t('School', 'Thebing » Transfer');
		}elseif($aResultData[$this->sView . '_end_type'] == 'accommodation'){
			$sProviderEnd = L10N::t('Unterkunft', 'Thebing » Transfer');
		}

		$sName = '';
		
		if(!empty($sProviderStart)){
			$sName .= $sProviderStart;
		}

		if(!empty($sProviderEnd)){
			$sName .= ' - ';
			$sName .= $sProviderEnd;
		}

		if(
			$aResultData[$this->sView . '_date'] != '0000-00-00' &&
			$aResultData[$this->sView . '_date'] != ''
		){
			
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			$sDate		= $oDateFormat->format($aResultData[$this->sView . '_date'], $aResultData, $aResultData);
			$sWeekday		= Ext_Thebing_Util::getWeekDay(2, $aResultData[$this->sView . '_date'], true);
			$sName .= ' (' . $sWeekday . ' ' . $sDate . ')';

		}

		return $sName;

	}
	
}