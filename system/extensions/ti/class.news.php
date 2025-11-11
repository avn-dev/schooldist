<?php

class Ext_TI_News extends Ext_TC_Basic {

	protected $_sTable = 'news';

	protected $_sDbConnectionName = 'thebing_update';

	public function  __construct($iDataID = 0) {

		// DB Connection zu thebing_update
		$this->_oDb = DB::createConnection('thebing_update', 'update.fidelo.com', 'thebing_update', 'inudu7ogup43', 'thebing_update');
		parent::__construct($iDataID);

	}

	public function __get($sName) {
		
		if(
			$sName == 'release' ||
			$sName == 'license'
		) {
			$mValue = json_decode($this->_aData[$sName], true);
		} else {
			$mValue = parent::__get($sName);
		}
		
		return $mValue;
		
	}
	
	public function __set($sName, $mValue) {
		if(
			$sName == 'release' ||
			$sName == 'license'
		) {
			$this->_aData[$sName] = json_encode($mValue);
		} else {
			parent::__set($sName, $mValue);
		}
	}
	
}