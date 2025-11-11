<?php

namespace Ts\Gui2\Style\Special;

class CodeValidRowStyle extends \Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if($aRowData['valid'] != 1) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('red', 50).';';
		}
		elseif($aRowData['latest_use'] !== null) {
			return 'background-color: '.\Ext_Thebing_Util::getColor('orange', 50).';';
		}

	}

}