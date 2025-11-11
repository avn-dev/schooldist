<?php

class Ext_Gui2_View_Autocomplete_Test extends Ext_Gui2_View_Autocomplete_Abstract {

	public function getOption($aSaveField, $sValue) {
		$aOptions = $this->getOptions('', array(), $aSaveField);

		return $aOptions[$sValue];

	}

	public function getOptions($sInput, $aSelectedIds, $aSaveField) {

		$aOptions = array();
		$aOptions[0] = '';
		$aOptions['1'] = 'Option 1';
		$aOptions['2'] = 'Option 2';
		$aOptions['3'] = 'Option 3';
		$aOptions['4'] = 'Option 4';
		$aOptions['5'] = 'Option 5';

		return $aOptions;

	}

}