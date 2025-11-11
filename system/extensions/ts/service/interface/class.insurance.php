<?php


interface Ext_TS_Service_Interface_Insurance {

	public function getInsurance();

	public function getInfo($iSchoolId, $sLanguage, $aData = null);

	public function getUntil();

}