<?
class Ext_Thebing_Gui2_Format_Link extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sReturn = '<a href="'.$this->link.'" onclick="'.$this->onClick.'">'.$mValue.'</a>';

		return $sReturn;

	}

}
