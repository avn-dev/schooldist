<?
class Ext_Thebing_Gui2_Format_FirstCourseName extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){
		$aTransfer 		= Ext_Thebing_Data::getTransferList();
		
		return $aTransfer[$mValue];

	}

}
