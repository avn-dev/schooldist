<?php

class Ext_Thebing_Gui2_Format_Contract_ItemName extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		if($aResultData['item'] == 'teacher') {

			$oItem = Ext_Thebing_Teacher::getInstance($aResultData['item_id']);
			$mValue = $oItem->name;

		} elseif($aResultData['item'] == 'accommodation') {

			$oItem = Ext_Thebing_Accommodation::getInstance($aResultData['item_id']);
			$mValue = $oItem->name;

		}

		return $mValue;

	}

}
