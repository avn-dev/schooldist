<?

/* Start Funktionen */

include_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");

/* Ende Funktionen */

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

// VARS zwischenspeichern
$aTempVars = $_VARS;

if($config->defaulttask) {
	$_VARS['task'] = $config->defaulttask;
}

if($_VARS['task'] == "detail") {

	if(!$_VARS['id']) $_VARS['id'] = $user_data['id'];

	$buffer_output 	= $element_data['content'];

	$aVars = array();
	$sSelect = "";
	$pos=0;
	while($pos = strpos($buffer_output,'<#',$pos)) {
		$end = strpos($buffer_output,'#>',$pos);
		$var = substr($buffer_output, $pos+2, $end-$pos-2);
		$info = explode(":",$var);
		if($info[0] == "detail") {
			$aVars[] = $info;
			$sSelect .= "c.";
			if(is_numeric($info[1]))
				$sSelect .= "ext_";
			$sSelect .= $info[1].",";
		}
		$pos++;
	}

	// Profilcounter einen hoch setzen
	if($_COOKIE['customer_db_detail_'.$_VARS['id']] != 1) {
		// Aufruf protokollieren
		if($config->logViews) {
			db_query($db_data['system'], "INSERT INTO customer_db_list_log SET idUser = '".$user_data['id']."', tblUser = '".$user_data['idTable']."', idProfile = '".$_VARS['id']."', tblProfile = '".$config->idTable."', idPage = '".$page_data['id']."' ");
		}
		db_query($db_data['system'],"UPDATE customer_db_".$config->idTable." SET changed = changed, views = views + 1 WHERE id = '".$_VARS['id']."' AND active = 1");
		setcookie('customer_db_detail_'.$_VARS['id'], "1", time()+86400, "/");
	}

	if($system_data['whoisonline_active']) {
		$sQuery = "SELECT ".$sSelect." c.active, UNIX_TIMESTAMP(w.changed) as lastaction FROM customer_db_".$config->idTable." c LEFT OUTER JOIN community_whoisonline w ON c.id = w.idUser WHERE c.id = '".$_VARS['id']."' AND c.active = 1 ";
	} else {
		$sQuery = "SELECT ".$sSelect." c.active FROM customer_db_".$config->idTable." c WHERE c.id = '".$_VARS['id']."' AND c.active = 1 ";
	}
	$res_list = db_query($db_data['system'],$sQuery);
	$my_list = get_data($res_list);

	$buffer_output = str_replace("<#onlinestatus#>",(($my_list['lastaction']>0)?"1":"0"),$buffer_output);

	foreach($aVars as $val) {
		$my_field = getCustomerFieldData($val[1],$config->idTable);
		if($val[1] == "groups") {
			$aGroups = explode("|",$my_list[$val[1]]);
			rsort($aGroups);
			$my_list[$val[1]] = $aGroups[0];
		}
		$sVar = "<#";
		foreach($val as $e) {
			$sVar .= $e.":";
		}
		$sVar = substr($sVar,0,strlen($sVar)-1)."#>";
		if(is_numeric($val[1])) $val[1] = "ext_".$val[1];
		if($my_field['type'] == "Select Field") $my_field['type'] = "select";
		$aAdditional = unserialize($my_field['additional']);

		$content = getFieldOutput($my_field['type'], $my_list[$val[1]], $my_field['id'], $val, $aAdditional);

		$buffer_output = str_replace($sVar,$content,$buffer_output);
	}

	if($_VARS['id'] == $user_data['id'] && $config->idTable == $user_data['idTable']) {
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"if:isUser",\Cms\Service\PageParser::checkForBlock($buffer_output,"if:isUser"));
	} else {
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"if:isUser","");
	}

	$buffer = $buffer_output;

} elseif($_VARS['task'] == "logs") {

	if($config->bShowMyVisits) $_VARS['id'] = $user_data['id'];

	if(!$config->bShowUser || ($user_data['id'] == $_VARS['id'] && $user_data['idTable'] == $config->idTable)) {
		$buffer_output	= $element_data['content'];
		$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

		$aVars = array();
		$aVarsPlus = array();
		$aDbls = array();
		$sSelect = "";
		$pos=0;
		$i=0;
		while($pos = strpos($buffer_entry,'<#',$pos)) {
			$end = strpos($buffer_entry,'#>',$pos);
			$var = substr($buffer_entry, $pos+2, $end-$pos-2);
			$info = explode(":",$var);
			if(!in_array($info[1],$aDbls) && $info[0] == "logs") {
				$number = (strstr($info[1],"ext_"))?substr($info[1],strpos($info[1],"_")+1):$info[1];
				$my_field = getCustomerFieldData($number,$config->idTable);
				$aVars[$i][0] = $info;
				$info[0] = $info[1];
				$info[1] = $my_field['type'];
				$aVars[$i][1] = $info;
				$aVars[$i][2] = $var;
				$aVars[$i][3] = $my_field['id'];
				$aDbls[] = $info[0];
				$sSelect .= "db.".$info[0].",";
				$i++;
			} else {
				$aVarsPlus[] = array($var,$info);
			}
			$pos++;
		}

		$cache = "";
		$res_logs = db_query($db_data['system'], "SELECT ".$sSelect." UNIX_TIMESTAMP(ll.changed) as changed FROM customer_db_list_log ll, customer_db_".$config->idTable." db WHERE ll.idUser = db.id AND ll.idUser != '".$_VARS['id']."' AND ll.idProfile = '".$_VARS['id']."' AND ll.tblProfile = '".$config->idTable."' GROUP BY ll.idUser ORDER BY ll.changed DESC LIMIT ".$config->iEntries."");
		while($my_logs = get_data($res_logs)) {
			$buffer = $buffer_entry;
			foreach($aVars as $val) {
				$sVar = "<#".$val[2]."#>";
				if($val[1][1] == "Select Field") $val[1][1] = "select";
				$content = getFieldOutput($val[1][1], $my_logs[$val[1][0]], $val[3], $val[1]);
				$buffer = str_replace($sVar,$content,$buffer);
			}
			foreach($aVarsPlus as $val) {
				$sVar = "<#".$val[0]."#>";
				if($val[1][0] == "changed") {
					$sType = "timestamp";
				} else {
					$sType = "text";
				}
				$content = getFieldOutput($sType, $my_logs[$val[1][0]], 0, $val[1]);
				$buffer = str_replace($sVar,$content,$buffer);
			}
			$cache .= $buffer;
		}

		$buffer = \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);

		$count_logs = count_rows(db_query($db_data['system'], "SELECT id FROM customer_db_list_log WHERE idUser != '".$_VARS['id']."' AND idProfile = '".$_VARS['id']."' AND tblProfile = '".$config->idTable."'"));
		$buffer = str_replace("<#count#>",$count_logs,$buffer);
		$buffer = str_replace("<#id#>",$_VARS['id'],$buffer);

	} else {
		$buffer = "";
	}

} elseif($_VARS['task'] == "search") {

	$buffer_output 	= $element_data['content'];

	$aVars = array();
	$sSelect = "";
	$pos=0;
	while($pos = strpos($buffer_output,'<#',$pos)) {
		$end = strpos($buffer_output,'#>',$pos);
		$var = substr($buffer_output, $pos+2, $end-$pos-2);
		$info = explode(":",$var);
		if(count($info)>1) {
			$aVars[] = $info;
			$sSelect .= $info[0].",";
		}
		$pos++;
	}

	foreach($aVars as $val) {
		$my_field = getCustomerFieldData($val[1],$config->idTable);
		$sVar = "<#";
		foreach($val as $e) {
			$sVar .= $e.":";
		}
		$sVar = substr($sVar,0,strlen($sVar)-1)."#>";
		if(is_numeric($val[1])) {
			$sField = "ext_".$val[1];
		} else {
			$sField = $val[1];
		}
		switch($my_field['type']) {
			case "Checkbox":
				$content = "<input type=\"checkbox\" name=\"search_".$sField."\" value=\"1\" ".$val[2].">";
				break;
			case "image":
				$content = "<input type=\"checkbox\" name=\"search_".$sField."\" value=\"1\" ".$val[2].">";
				break;
			case "Select Field":
				$content = "<select name=\"search_".$sField."\" ".$val[2].">";
				$res_options = db_query($db_data['module'],"SELECT display, value FROM customer_db_values WHERE definition_id = '".$my_field['id']."' AND active = 1 ORDER BY value");
				while($my_options = get_data($res_options)) {
					$content .= "<option value=\"".$my_options['value']."\" ".(($my_options['value']==$_VARS['search_'.$sField.''])?"selected":"").">".$my_options['display']."</option>";
				}
				$content .= "</select>";
				break;
			default:
				$content = "<input type=\"text\" name=\"search_".$sField."\" value=\"".$_VARS['search_'.$sField.'']."\" ".$val[2].">";
				break;
		}
		$content .= "<input type=\"hidden\" name=\"searchmode_".$sField."\" value=\"".$val[3]."\">";
		$buffer_output = str_replace($sVar,$content,$buffer_output);
	}

	$buffer = $buffer_output;

} else {

	$buffer_output 	= $element_data['content'];
	$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

	$buffer_pages 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'pages');
	$buffer_output 	= \Cms\Service\PageParser::replaceBlock($buffer_output,'pages','');

	$buffer_backlink = \Cms\Service\PageParser::checkForBlock($buffer_pages,'backlink');
	$buffer_forwardlink = \Cms\Service\PageParser::checkForBlock($buffer_pages,'forwardlink');

	$where = "";

	foreach((array)$config->aFilter as $k=>$v) {
		if(is_numeric($v[0])) $v[0] = "ext_".$v[0];
		$_VARS['search'] = 1;
		$_VARS['search_'.$v[0]] = $v[1];
		$_VARS['searchmode_'.$v[0]][] = $v[2];
	}

	if($_VARS['search'] == 1) {
		$sParameter = "";
		foreach($_VARS as $key=>$val) {
			if($par = strstr($key,"search")) {
				if(!is_array($val)) {
					$aVal = array();
					$aVal[0] = $val;
					$sAdd = "";
				} else {
					$aVal = $val;
					$sAdd = "[]";
				}
				foreach($aVal AS $k=>$v) {
					$v = \DB::escapeQueryString($v);
					$sParameter .= $key.$sAdd."=".$v."&";
					if($par = strstr($key,"search_")) {
						switch($_VARS['searchmode_'.substr($key,7)][$k]) {
							case "1":
								$where .= " `".substr($key,7)."` LIKE '%".$v."%' ";
								break;
							case "2":
								$where .= " `".substr($key,7)."` LIKE '".$v."%' ";
								break;
							case "3":
								$where .= " `".substr($key,7)."` LIKE '%".$v."' ";
								break;
							case "4":
								$where .= " `".substr($key,7)."` LIKE '".$v."' ";
								break;
							case "5":
								$where .= " `".substr($key,7)."` = '".$v."' ";
								break;
							case "6":
								$where .= " `".substr($key,7)."` > '".$v."' ";
								break;
							case "7":
								$where .= " `".substr($key,7)."` < '".$v."' ";
								break;
							case "8":
								$where .= " `".substr($key,7)."` != '' ";
								break;
							case "9":
								if($v=='0') $v = 0;
								$where .= " (`".substr($key,7)."` = '".$v."' OR '".$v."' = 0) ";
								break;
							case "10":
								$where .= " `".substr($key,7)."` > SUBDATE(NOW(),INTERVAL ".intval($v)." DAY) ";
								break;
							default:
								$where .= " `".substr($key,7)."` LIKE '%".$v."%' ";
								break;
						}

						if($_VARS['searchlogic'] == 'OR') {
							$where .= " OR ";
						} else {
							$where .= " AND ";
						}

					}
				}
			}
		}
	}

	if($system_data['whoisonline_active']) {
		$query = "SELECT c.id FROM customer_db_".$config->idTable." AS c LEFT OUTER JOIN community_whoisonline AS w ON c.id = w.idUser WHERE ".$where." active = 1";
	} else {
		$query = "SELECT c.id FROM customer_db_".$config->idTable." AS c WHERE ".$where." active = 1";
	}
	if($_VARS['debug'] == 1) {
		echo $query;
	}
	$res_list = db_query($db_data['system'],$query);

	$total = count_rows($res_list);

	if($total > 0) {

		$cd_start = $_VARS['cd_start'];

		if(!$cd_start) $cd_start = 1;
		$cd_number = $config->iEntries;

		if(($cd_start+$cd_number-1) < $total)
			$cd_end = ($cd_start+$cd_number-1);
		else 
			$cd_end = $total;

		if($cd_start > 1)
			$cd_backlink = str_replace("<#cd_startb#>",($cd_start-$cd_number),$buffer_backlink);
		if(($cd_start+$cd_number) <= $total)
			$cd_forwardlink = str_replace("<#cd_startf#>",($cd_start+$cd_number),$buffer_forwardlink);

		$buffer_pages = str_replace("<#start#>",$cd_start,$buffer_pages);
		$buffer_pages = str_replace("<#end#>",$cd_end,$buffer_pages);
		$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"backlink",$cd_backlink);
		$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"forwardlink",$cd_forwardlink);

		$buffer_output = str_replace("<#displayPages#>",$buffer_pages,$buffer_output);
		$buffer_output = str_replace("<#cd_parameter#>",$sParameter,$buffer_output);

		$aVars = array();
		$aDbls = array();
		$sSelect = "";
		$pos=0;
		$i=0;
		while($pos = strpos($buffer_entry,'<#',$pos)) {
			$end = strpos($buffer_entry,'#>',$pos);
			$var = substr($buffer_entry, $pos+2, $end-$pos-2);
			$info = explode(":",$var);
			if(!in_array($info[1],$aDbls) && $info[0] == "list") {
				$number = str_replace("ext_","",$info[1]);
				$info[1] = (is_numeric($info[1]))?"ext_".$info[1]:$info[1];
				$my_field = getCustomerFieldData($number,$config->idTable);
				$aVars[$i][0] = $info;
				$info[0] = $info[1];
				$info[1] = $my_field['type'];
				$aVars[$i][1] = $info;
				$aVars[$i][2] = $var;
				$aVars[$i][3] = $my_field['id'];
				$aDbls[] = $info[0];
				$sSelect .= "c.".$info[0].",";
				$i++;
			}
			$pos++;
		}

		if(is_numeric($config->iSort)) {
			$sSort = "ext_".$config->iSort;
		} else {
			$sSort = $config->iSort;
		}

		if($system_data['whoisonline_active']) {
			$query = "SELECT ".$sSelect." c.active,UNIX_TIMESTAMP(w.changed) as lastaction FROM customer_db_".$config->idTable." AS c LEFT OUTER JOIN community_whoisonline AS w ON c.id = w.idUser WHERE ".$where." active = 1 ORDER BY c.".$sSort." ".$config->sSortDir." LIMIT ".($cd_start-1).",".$cd_number." ";
		} else {
			$query = "SELECT ".$sSelect." c.active FROM customer_db_".$config->idTable." AS c WHERE ".$where." active = 1 ORDER BY c.".$sSort." ".$config->sSortDir." LIMIT ".($cd_start-1).",".$cd_number." ";
		}
		$res_list = db_query($db_data['system'],$query);

		$cache = "";

		while($my_list = get_data($res_list)) {

			$buffer = $buffer_entry;

			foreach($aVars as $val) {
				$sVar = "<#".$val[2]."#>";
				if($val[1][1] == "Select Field") $val[1][1] = "select";
				$content = getFieldOutput($val[1][1], $my_list[$val[1][0]], $val[3], $val[1]);
				$buffer = str_replace($sVar,$content,$buffer);
			}

			$cache .= $buffer;
		}
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"noentry","");
	// Wenn keine Eintr�ge vorhanden
	} else {
		$temp = \Cms\Service\PageParser::checkForBlock($buffer_output,"noentry");
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"noentry",$temp);
		$buffer_output = \Cms\Service\PageParser::replaceBlock($buffer_output,"entry","");
	}

	$buffer = $buffer_output;
}

$pos=0;
while($pos = strpos($buffer,'<#',$pos)) {
      $end = strpos($buffer,'#>',$pos);
      $var = substr($buffer, $pos+2, $end-$pos-2);
	  if($_VARS[$var]) $$var = $_VARS[$var];
      $buffer = substr($buffer, 0, $pos)  .  $$var  .  substr($buffer, $end+2);
   }

echo $buffer;

$_VARS = $aTempVars;
