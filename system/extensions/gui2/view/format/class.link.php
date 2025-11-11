<?
class Ext_Gui2_View_Format_Link extends Ext_Gui2_View_Format_Abstract {



	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sLinkName = $mValue;

		$sTitle = '';

		$mValue = '<a href="'.$mValue.'" title="'.$sTitle.'">'.$sLinkName.'</a>';

		return $mValue;

	}

}
