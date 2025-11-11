<?php


class Ext_Thebing_Gui2_Format_Transfer extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Fallback, falls nur ein Teil übertragen wird (explode per "_")
		if($mValue == 'arr') {
			$mValue = 'arr_dep';
		}

		$transferList = \Ext_Thebing_Data::getTransferList();
		return $transferList[$mValue] ?? $mValue;
	}

}