<?php

interface Ext_Gui2_View_Format_Interface {

	public function __get($sOption);

	public function __set($sOption, $mValue);

	public function format($mValue, &$oColumn = null, &$aResultData = null);

	public function align(&$oColumn = null);

	public function convert($mValue, &$oColumn = null, &$aResultData = null);

	public function get($mValue, &$oColumn = null, &$aResultData = null);

	public function getTitle(&$oColumn = null, &$aResultData = null);
	
	public function getMVCTitle(&$oColumn = null, &$aResultData = null);

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null);
	
}