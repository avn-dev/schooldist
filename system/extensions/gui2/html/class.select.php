<?php
 
class Ext_Gui2_Html_Select extends Ext_Gui2_Html_Abstract {

	protected $sStartTag	= '<select>';
	protected $sEndTag		= '</select>';
	public $bAllowReadOnly = true;
	
	public function addOption($mValue, $mText, bool $bSelected = false) {

		$oOption = new Ext_Gui2_Html_Option();
		$oOption->value = $mValue;

		if ($bSelected) {
			$oOption->selected = 'selected';
		}

		$oOption->setElement($mText);
		$this->setElement($oOption);

	}
	
}