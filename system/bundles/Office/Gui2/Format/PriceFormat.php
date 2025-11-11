<?php

namespace Office\Gui2\Format;

class PriceFormat extends \Ext_Gui2_View_Format_Float {

	protected $iDecPoint	= ',';
	protected $iThausendSep = '.';
	
	/**
	 * Setzt die Einstellung zum Umwandel
	 * @param type $sDecimal
	 * @param type $iThousand 
	 */
	public function __construct() {
		
	}

	public function format($fAmount, &$oColumn = null, &$aResultData = null){
		$fAmount = parent::format($fAmount, $oColumn, $aResultData);
		return $fAmount;
	}

}
