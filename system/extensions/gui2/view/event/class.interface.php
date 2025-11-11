<?php

interface Ext_Gui2_View_Event_Interface {

	public function __get($sOption);

	public function __set($sOption, $mValue);

	public function getEvent($mValue, $oColumn, $aResultData);

	public function getFunction($mValue, $oColumn, $aResultData);

}