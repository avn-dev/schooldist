<?php
class Ext_TS_Accounting_Bookingstack_History_Format_Type extends Ext_Gui2_View_Format_Abstract {
    
    public function format($mValue, &$oColumn = null, &$aResultData = null){
		$mValue = $this->get($mValue, $oColumn, $aResultData);
        if($mValue == 'export'){
            return L10N::t('Export');
        } else if($mValue == 'clear'){
            return L10N::t('Bereinigung');
        }
        return '';
	}
    
}