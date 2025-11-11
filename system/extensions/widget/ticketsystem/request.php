<?
include_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");


//header('Cache-Control: no-cache, must-revalidate');
//header('Content-type: application/json');

$oWidget = new Ext_Widget_Ticketsystem();
$oWidget->setProject($_VARS['iProject']);
$oWidget->setStatus($_VARS['sStatus']);
$oWidget->setTicket($_VARS['iTicket']);
$sJson = $oWidget->getJSON();
echo $_GET["callback"].'('.$sJson.')';




