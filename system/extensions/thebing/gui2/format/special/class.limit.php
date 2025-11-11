<?
class Ext_Thebing_Gui2_Format_Special_Limit extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aLimitTypes = Ext_Thebing_School_Special::getLimitTypes();

		$sReturn = '';
		if($mValue == 2){
			// Anzahl verfügbarer Specials anzeigen
			$sReturn = (int)$aResultData['limit'];
		}else{
			$sReturn = $aLimitTypes[$mValue];
		}
		//$sReturn =
		return $sReturn;
	}

}
