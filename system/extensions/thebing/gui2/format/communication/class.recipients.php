<?php

class Ext_Thebing_Gui2_Format_Communication_Recipients extends Ext_Gui2_View_Format_Abstract {

	protected $_sType = 'to';

	public function  __construct($sType='to') {
		$this->_sType = $sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aRecipients = json_decode($mValue, true);

		$sType = $this->_sType;
		$sTo = implode(", ", (array)$aRecipients[$sType]);

		return $sTo;

	}

}
