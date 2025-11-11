<?
class Ext_TC_User_Format_Group  extends Ext_TC_Gui2_Format { 

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oGroup = new Ext_TC_User_Group((int)$mValue);	
		
		return $oGroup->name; 

	}
}