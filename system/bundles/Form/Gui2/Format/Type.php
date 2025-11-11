<?php

namespace Form\Gui2\Format;

class Type extends \Ext_Gui2_View_Format_Abstract {

	private $aOptions = [];

	public function __construct() {
		
		$this->aOptions = \Form\Gui2\Data\Fields::getTypes();
		
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		return $this->aOptions[$mValue];
	}
}