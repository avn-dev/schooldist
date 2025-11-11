<?php

class Ext_Thebing_Examination_Gui2_Format_Empty extends Ext_Gui2_View_Format_Abstract {

	private $sFormatClass;

	public function __construct($sFormatClass) {
		$this->sFormatClass = $sFormatClass;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($mValue !== null) {
			$oFormat = new $this->sFormatClass();
			return $oFormat->format($mValue, $oColumn, $aResultData);
		}

		return '';

	}

}