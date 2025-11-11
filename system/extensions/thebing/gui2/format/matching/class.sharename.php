<?

class Ext_Thebing_Gui2_Format_Matching_ShareName extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$aBack = array();
		$sBack = '';
		$aValue = explode('{||}',$mValue);
		
		foreach((array)$aValue as $aData){
			$aNames = explode('{|}',$aData);
			
			$aBack[] = Ext_Thebing_Gui2_Format_CustomerName::manually_format($aNames[0], $aNames[1]);
		}
		
		$sBack = implode('<br/>', $aBack);
		
		return $sBack;

	}


}
