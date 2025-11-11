<?php

class Ext_Thebing_Gui2_Format_Storno_Fee extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oFee = Ext_Thebing_Cancellation_Fee::getInstance($aResultData['id']);
		$aDynamicAmount = (array)$oFee->getDynamicAmount();
		$aDynamicAmount = reset($aDynamicAmount);
		$aFeeTypes = Ext_Thebing_Util::getStornoTypeOptions();

		$mValue = $aDynamicAmount['amount'];

		if($aDynamicAmount['kind'] == 1) {
			$sName = $mValue . ' %';
		} elseif($aDynamicAmount['kind'] == 2) {
			$sName = $mValue . ' ' . $aFeeTypes[$aDynamicAmount['kind']];
		} else {
			$sName = '';
		}

		return $sName;
	}

	public function align(&$oColumn = null){
		return 'right';
	}

}