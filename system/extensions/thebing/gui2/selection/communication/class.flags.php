<?
class Ext_Thebing_Gui2_Selection_Communication_Flags extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		global $_VARS, $user_data;
		
		$aApplications = $oWDBasic->applications;

		$aFlags = Ext_Thebing_Communication::getFlags($aApplications);
		return $aFlags; 
	}
}
?>
