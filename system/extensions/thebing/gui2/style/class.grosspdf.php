<?php

class Ext_Thebing_Gui2_Style_GrossPdf extends Ext_Gui2_View_Style_Abstract implements Ext_Gui2_View_Style_Index_Interface {

    public function getStyle($mValue, &$oColumn, &$aResultData){
        
    }
    
	public function getIndexStyle($mValue, &$oColumn, &$aResultData){

		if(
			isset($aResultData['id']) && 
			$aResultData['id'] > 0 
		){
		 
			$iMailCount = $aResultData['kidv_gross_sent'] ?? 0;

			$iPdfNet = $aResultData['pdf_gross'] ?? 0; 
			if(isset($aResultData['agency_invoice_settings'])) {
				$iPdfNet = $aResultData['agency_invoice_settings'];
			}
			
			if(
				empty($iMailCount) ||
				$iMailCount == '0000-00-00 00:00:00'
			) {
				$iMailCount = 0;
			} else {
				$iMailCount = 1;
			}

			if(
				isset($aResultData['agency_id']) &&
				$aResultData['agency_id'] > 0
			) {
				if($iPdfNet == 1 && $iMailCount > 0){
					return 'background-color: #CCFFAA; ';
				} else if($iPdfNet == 1){
					return 'background-color: #FFCCAA; ';
				} else {
					return '';
				}
			}else{
				if($iMailCount > 0) {
					return 'background-color: #CCFFAA; ';
				} else {
					return '';
				}
			}
			
		}else{
			return '';
		}
	}
	
}
