<?php



class Ext_Thebing_Gui2_Icon_Accommodation_Feedback_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		$iSelectedId = reset($aSelectedIds);

		$bShow = 0;
		
		if($iSelectedId > 0){
			$bShow = 1;
		}

		return $bShow;
	}
}
