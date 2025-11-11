<?php

class Ext_Thebing_Pickup_Service_Gui2_Data extends Ext_Thebing_Gui2_Data {
	
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {
		
		if(
			$sError == 'JOURNEY_TRANSFER_FOUND'
		){
			$sMessage = $this->t('Es existieren noch Verknüpfungen zu Buchungen. Bitte überprüfen sie die Gültigkeit des Eintrages!');
		}else{
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}	
	
}