<?
class Ext_Gui2_View_Format_Gender extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aSelection = array();
		$aSelection[0] = L10N::t('Male');
		$aSelection[1] = L10N::t('Female');

		$mValue = (string)$aSelection[$mValue];

		return $mValue;

	}

}
