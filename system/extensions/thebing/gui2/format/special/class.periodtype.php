<?
class Ext_Thebing_Gui2_Format_Special_Periodtype extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aPeriodTypes = Ext_Thebing_School_Special::getPeriodeTypes();

		return $aPeriodTypes[$mValue];
	}

}
