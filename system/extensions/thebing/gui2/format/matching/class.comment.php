<?

class Ext_Thebing_Gui2_Format_Matching_Comment extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			!empty($mValue) &&
			$this->sFlexType == 'list' // beim export soll alles exportiert werden
		){
			$mValueShort = mb_substr($mValue, 0, 40);
			
			if(mb_strlen($mValueShort) < mb_strlen($mValue)){
				$mValue = $mValueShort . '...';
			}
		}
		
		
		return $mValue;
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {
	
		$aReturn = array();
		$aReturn['content'] = $aResultData['acc_comment'];
		$aReturn['name'] = '1';
		$aReturn['tooltip'] = true;

		return $aReturn;
		
	}

}
