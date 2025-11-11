<?php

class Ext_Gui2_View_Style_Indexrow extends Ext_Gui2_View_Style_Row {

    public function getStyle($mValue, &$oColumn, &$aRowData){
		return $aRowData['gui_row_style'];
	}

}