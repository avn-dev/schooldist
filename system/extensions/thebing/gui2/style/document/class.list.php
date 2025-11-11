<?php

class Ext_Thebing_Gui2_Style_Document_List extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if(
			!empty($aRowData['id']) &&
			$aRowData['is_last_document']
		) {
			$sColor = Ext_Thebing_Util::getColor('marked');
			return 'background-color: '.$sColor . ';';
		}

		return '';

	}

}
