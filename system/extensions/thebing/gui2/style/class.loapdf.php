<?
class Ext_Thebing_Gui2_Style_LoaPdf extends Ext_Gui2_View_Style_Abstract implements Ext_Gui2_View_Style_Index_Interface {

    public function getStyle($mValue, &$oColumn, &$aResultData){
        
    }

	public function getIndexStyle($mValue, &$oColumn, &$aResultData){

		if(
			isset($aResultData['id']) && 
			$aResultData['id'] > 0 
		) {
			
			$iMailCount = $aResultData['kidv_loa_sent'] ?? 0;
						
			if(
				empty($iMailCount) ||
				$iMailCount == '0000-00-00 00:00:00'
			) {
				$iMailCount = 0;
			} else {
				$iMailCount = 1;
			}

			$iPdfLoa = $aResultData['pdf_loa'] ?? 0;
			// Index
			if(isset($aResultData['pdf_loa_agency'])) {
				$iPdfLoa = $aResultData['pdf_loa_agency'];
			}
			
			if(
				isset($aResultData['agency_id']) &&
				$aResultData['agency_id'] > 0
			) {
					
				if($iPdfLoa == 1 && $iMailCount > 0){
					return 'background-color: #CCFFAA; ';
				} else if($iPdfLoa == 1){
					return 'background-color: #FFCCAA; ';
				} else {
					return '';
				}
			} else {

				if($iMailCount > 0){
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


