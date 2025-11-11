<?
class Ext_Thebing_Gui2_Style_Pdf extends Ext_Gui2_View_Style_Abstract {

	protected $_sField = 'pdf';

	public function  __construct($sField) {
		$this->_sField = $sField;
	}

	public function getStyle($mValue, &$oColumn, &$aResultData){

		if(
			isset($aResultData['id']) && 
			$aResultData['id'] > 0 
		){
		 
			$sPDF = $aResultData[$this->_sField];

			if(!empty($sPDF)) {
				return 'background-color: #CCFFAA; ';
			} else {

			}
			
		}

		return '';

	}
	
}
