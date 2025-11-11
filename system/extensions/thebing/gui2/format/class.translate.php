<?php

class Ext_Thebing_Gui2_Format_Translate extends Ext_Gui2_View_Format_Abstract {

	protected $_sDescriptionPart;

	public function __construct($sDescriptionPart)
	{
		$this->_sDescriptionPart = $sDescriptionPart;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		return L10N::t($mValue, $this->_sDescriptionPart);
	}

}
?>
