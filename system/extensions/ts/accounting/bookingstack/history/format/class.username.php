<?php
class Ext_TS_Accounting_Bookingstack_History_Format_UserName extends Ext_Gui2_View_Format_UserName {
    
    public function format($mValue, &$oColumn = null, &$aResultData = null){

        if($mValue == \TsAccounting\Service\AutomationService::SYSTEM_CREATOR_ID) {
            return $this->oGui->t('System');
        }

        return parent::format($mValue, $oColumn, $aResultData);
	}
    
}
