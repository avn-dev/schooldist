<?php
/*
 * Created on 04.09.2006
 *
 * hier werden zusatzaufwände erfasst
 */
 
require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

Admin_Html::loadAdminHeader();

Access_Backend::checkAccess("office");

//print_r($_VARS);
$scale = array("Wochen" => "Wochen", "Monate" => "Monate");

/* AUSLESEN DER DATEN */

// Positionen
$aArticles = array(0 => "");
if ($_VARS['type'] == "offer" or $_VARS['type'] == "confirmation" or $_VARS['type'] == "account" or $_VARS['type'] == "contract") {
	$rArticle = DB::getQueryRows("SELECT * FROM office_articles");
	foreach($rArticle as $aArticle) {
		 $aArticles[$aArticle['id']] = $aArticle['number']." ".$aArticle['product']." (".$aArticle['price']." &euro;)";
		 $aArticlesDetails[$aArticle['id']] = $aArticle;
	}
}

// Firma
$aCustomers = array(0 => '-');
$rCustomer = DB::getQueryRows("SELECT id, ext_1, ext_2, ext_3, ext_4, ext_5, ext_6 FROM customer_db_".$database." WHERE active = 1");
foreach($rCustomer as $aCustomer) {
	$aCustomers[$aCustomer['id']] = $aCustomer['ext_1'];
	$aCustomersFull[$aCustomer['id']] = $aCustomer;
}

// Bearbeiter
$rEditor = DB::getQueryRows("SELECT id, firstname, lastname FROM system_user WHERE active = 1 ORDER BY lastname");
foreach($rEditor as $aEditor) {
	$aEditors[$aEditor['id']] = $aEditor['lastname'].", ".$aEditor['firstname'];
}



?>
<h1>Notizen zu Zusatzaufwänden</h1>
