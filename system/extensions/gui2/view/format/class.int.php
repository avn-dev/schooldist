<?php


class Ext_Gui2_View_Format_Int extends Ext_Gui2_View_Format_Abstract {

	protected $iThausendSep = ' ';

	public function format($fAmount, &$oColumn = null, &$aResultData = null){
		
		if(
			empty($fAmount)
		) {
			return 0;
		}
		
		if(!is_numeric($fAmount)) {
			return $fAmount;
		}
		
		// Formatieren
		$mAmount = number_format($fAmount, 0, '', $this->iThausendSep);

		return $mAmount;

	}

	public function align(&$oColumn = null){
		return 'right';
	}

}