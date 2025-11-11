<?
class Ext_Thebing_Gui2_Format_Special_Amounttype extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aAmountTypes = Ext_Thebing_School_Special::getAmountTypes();


		return $aAmountTypes[$mValue];
	}

}
