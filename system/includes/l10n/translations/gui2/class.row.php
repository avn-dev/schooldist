<?php

class L10N_Translations_Gui2_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){
		if($aRowData['use'] != 1) {
			return 'color: #bbb;';
		}
	}

}
