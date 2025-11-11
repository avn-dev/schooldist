<?php


class Ext_Thebing_Gui2_Style_Accounting_Overview_Print extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aRowData){

        $sStyle = '';
        
		$sPrinted = Ext_Thebing_Util::getColor('good');
		$sOldPrinted = Ext_Thebing_Util::getColor('neutral');

        $oPrintSuccess  = new DateTime($aRowData['print_success_original']);
        $oChanged       = new DateTime($aRowData['changed_original']);

        if(
            isset($aRowData['print_success_original']) &&    
            $oChanged > $oPrintSuccess 
        ){
            $sStyle = 'background-color: '.$sOldPrinted.';';
        } else if(isset($aRowData['print_success_original'])){
            $sStyle = 'background-color: '.$sPrinted.';';
        }
       
		return $sStyle;

	}


}