<?php

interface Ext_Gui2_View_Autocomplete_Interface {

	public function getOption($aSaveField, $sValue);

	public function getOptions($sInput, $aSelectedIds, $aSaveField);

	public function printOptions($sInput, $aSelectedIds, $aSaveField);

}