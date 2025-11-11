<?php

/* Start Funktionen */

include_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");

/* Ende Funktionen */

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$buffer_gast 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'gast');

$buffer_output 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'output');
$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

DB::executeQuery("DELETE FROM community_whoisonline WHERE UNIX_TIMESTAMP(changed) < (UNIX_TIMESTAMP(NOW()) - ".System::d('whoisonline_valid', 600).")");

if($config->oneself) {
	$res_wio = DB::getQueryRows("SELECT idUser,tblUser,idPage,UNIX_TIMESTAMP(changed) as changed FROM community_whoisonline ORDER BY changed DESC");
} else {
	$res_wio = DB::getQueryRows("SELECT idUser,tblUser,idPage,UNIX_TIMESTAMP(changed) as changed FROM community_whoisonline WHERE !(idUser = '".(int)$user_data['id']."' AND tblUser = '".(int)$user_data['idTable']."') ORDER BY changed DESC");
}

$aVars = array();
$aDbls = array();
$sSelect = "";
$pos=0;
$i=0;
while($pos = strpos($buffer_entry,'<#',$pos)) {
	$end = strpos($buffer_entry,'#>',$pos);
	$var = substr($buffer_entry, $pos+2, $end-$pos-2);
	$info = explode(":",$var);
	if(!in_array($info[1],$aDbls) && $info[0] == "wio") {
		$number = (strstr($info[1],"ext_"))?substr($info[1],strpos($info[1],"_")+1):$info[1];
		if(is_numeric($number)) {
			$query = "SELECT id,type FROM customer_db_definition WHERE field_nr = '".$number."' AND db_nr = '".$user_data['idTable']."' AND active = 1";
		} else {
			$query = "SELECT id,type FROM customer_db_definition WHERE name = '".$number."' AND db_nr = '".$user_data['idTable']."' AND active = 1";
		}
		$my_field = DB::getQueryRow($query);
		$aVars[$i][0] = $info;
		$info[0] = $info[1];
		$info[1] = $my_field['type'];
		$aVars[$i][1] = $info;
		$aVars[$i][2] = $var;
		$aVars[$i][3] = $my_field['id'];
		$aDbls[] = $info[0];
		$sSelect .= "db.".$info[0].",";
		$i++;
	}
	$pos++;
}

$cache = "";

foreach($res_wio as $my_wio) {
	$buffer = $buffer_entry;
	if($my_wio['idUser']>0 && $my_wio['tblUser']>0) {
		$my_user = DB::getQueryRow("SELECT ".$sSelect." db.active FROM customer_db_".(int)$my_wio['tblUser']." db WHERE id = '".(int)$my_wio['idUser']."' LIMIT 1");
	} else {
		$my_user = array("nickname"=>$buffer_gast);
	}

	foreach($aVars as $val) {
		$sVar = "<#".$val[2]."#>";
		if($val[1][1] == "Select Field") $val[1][1] = "select";
		$content = getFieldOutput($val[1][1], $my_user[$val[1][0]], $val[3], $val[1]);
		$buffer = str_replace($sVar,$content,$buffer);
	}

	$buffer = str_replace("<#name#>",$my_user['nickname'],$buffer);
	$buffer = str_replace("<#idUser#>",$my_wio['idUser'],$buffer);
	$buffer = str_replace("<#tblUser#>",$my_wio['tblUser'],$buffer);
	$buffer = str_replace("<#message#>",getPageData($my_wio['idPage'],"message"),$buffer);
	$buffer = str_replace("<#page#>",getPageTrack($my_wio['idPage']),$buffer);
	$buffer = str_replace("<#time#>",strftime($config->ftime,$my_wio['changed']),$buffer);

	$cache .= $buffer;
}

echo \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);
