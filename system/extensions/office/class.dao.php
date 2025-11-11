<?php

include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

class Ext_Office_Dao extends classExtensionDao_Office {

	public function __construct() {
		$oOffice = new classExtension_Office;
		$aConfigData = $oOffice->getConfigData();

		parent::__construct($aConfigData);
	}
}