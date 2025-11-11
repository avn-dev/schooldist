<?
class Ext_Gui2_View_Format_Image extends Ext_Gui2_View_Format_Abstract {



	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sTitle = '';

		$sStyle = '';

		$mValue = '<img src="'.$mValue.'" style="'.$sStyle.'" title="'.$sTitle.'" alt="'.$sTitle.'" />';

		return $mValue;

	}

}
