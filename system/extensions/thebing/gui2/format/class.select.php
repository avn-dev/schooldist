<?php

/**
 * @see Ext_Gui2_View_Format_Selection
 * @deprecated
 */
class Ext_Thebing_Gui2_Format_Select extends Ext_Thebing_Gui2_Format_Format
{
	public $aSelectOptions;
	
	public function __construct($aSelectOptions=null) {
		if(!is_null($aSelectOptions)) {
			$this->aSelectOptions = $aSelectOptions;
		}
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if (
			is_array($this->aSelectOptions) &&
			isset($this->aSelectOptions[$mValue])
		) {
			return $this->aSelectOptions[$mValue];
		}

		return false;

	}

}