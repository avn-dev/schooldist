<?php

require_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess("modules_admin");

$sSearch = $_VARS['term'];
$iTable = (int)$_VARS['idTable'];

$oCustomerDB = new Ext_CustomerDB_DB($iTable);

$aCustomers = $oCustomerDB->searchCustomers($sSearch);

$array = [];

foreach((array)$aCustomers as $aCustomer) {
	$array[] = $aCustomer['nickname'].", ".$aCustomer['email']." (ID: ".$aCustomer['id'].")";
}

echo json_encode($array);
