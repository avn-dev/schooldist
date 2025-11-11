<?php

include_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/multi.inc.html");

if(class_exists('\\Cms\\Helper\\ExtensionConfig')) {
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
} else {
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
}

if($config->norequest) {
	$aTempVars = $_VARS;
	$_VARS = array();
}

if(
	$config->filter &&
	is_array($config->filter)
) {

	if(!isset($_VARS['search'])) {
		$_VARS['search'] = array();
	}

	foreach($config->filter as $aFilter) {
		$_VARS['search_'.$aFilter['field']][] = $aFilter['compare'];
		//$_VARS['searchmode_'.$aFilter['field']][] = 5;
	}

}

if($config->use_smarty == 1) {

	include(\Util::getDocumentRoot()."system/extensions/multi/smarty.php");

} else {

	global $PHP_SELF,$upload_dir;

	$mu_start = $_VARS['mu_start'];

	if(!$mu_start) $mu_start = 1;

	$load_page_id = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'load_page_id');
	$load_element_id = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'load_element_id');

	if($load_page_id) 		$page_id = $load_page_id;
	if($load_element_id) 	$element = $load_element_id;

	$mu_number = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'mu_number');
	if(!$mu_number) $mu_number = $config->show;
	if(!$mu_number) $mu_number = 10;
	
	if(!$_VARS['mu_action']) {
		$my_init = DB::getQueryRow("SELECT view FROM multi_init WHERE id = '".$config->multi_id."'");
		list($_VARS['mu_action']) = explode("|", $my_init['view']);
	}
	
	// search
	if($_VARS['mu_action'] == "search") {
	
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'search');
	
		$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_text");
		$elem_code['smalltext'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_smalltext");
		$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_select");
		$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_textarea");
		$res_init = DB::getQueryRows("SELECT * FROM multi_init,multi_fields WHERE multi_init.id = multi_fields.multi_id AND multi_init.id = '".$config->multi_id."' AND multi_fields.display LIKE '%search%' ORDER BY multi_fields.position");

		$temp_buffer = "";

		foreach($res_init as $my_init) {
			$i = $my_init['field_id'];
			$s_buffer = $elem_code[$my_init["type"]];
			if($_VARS["option_value_$i"])
				$val = $_VARS["option_value_$i"];
			else
				$val = $my_init["value"];
			$s_buffer = str_replace("<#value#>",$val,$s_buffer);
			$s_buffer = str_replace("<#title#>",$my_init["name"],$s_buffer);
			$s_buffer = str_replace("<#option#>",$my_init["options"],$s_buffer);
			if($my_init["type"] == "select") {
				$s_buffer = str_replace("<#name#>",'search_'.$i.'',$s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer,"optionlist");
				$temp = "";
				$arr = preg_split("/\|/",$my_init["value"]);
				foreach($arr as $elem)
					$temp .= str_replace("<#option_value#>",$elem,str_replace("<#select#>",(($elem==$_VARS["option_value_$i"])?"selected":""),$t_buffer));
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer,"optionlist",$temp);
			} else {
				$s_buffer = str_replace("<#name#>","search_".$i,$s_buffer);
			}
			$temp_buffer .= $s_buffer;
		}
		$buffer = str_replace("<#elements#>",$temp_buffer,$buffer);
	
	} elseif($_VARS['mu_action'] == "add") {
	
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'add');
	
		$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_text");
		$elem_code['smalltext'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_smalltext");
		$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_select");
		$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_textarea");
		$elem_code['html'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_html");
		$elem_code['email'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_email");
		$elem_code['image'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_image");
		$elem_code['date'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_date");
		$elem_code['web'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_web");
		$elem_code['reference'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_reference");
		$elem_code['multiselect'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form_multiselect");
		$res_init = DB::getQueryRows("SELECT * FROM multi_init,multi_fields WHERE multi_init.id = multi_fields.multi_id AND multi_init.id = '".$config->multi_id."' ORDER BY multi_fields.position");
	
		$temp_buffer = "";
	
		foreach($res_init as $my_init) {
			$i = $my_init['id'];
			$s_buffer = $elem_code[$my_init["type"]];
			if($_VARS["option_value_$i"])
				$val = $_VARS["option_value_$i"];
			else
				$val = $my_init["value"];
			$s_buffer = str_replace("<#value#>",$val,$s_buffer);
			$s_buffer = str_replace("<#title#>",$my_init["name"],$s_buffer);
			$s_buffer = str_replace("<#option#>",$my_init["options"],$s_buffer);
			if($my_init["type"] == "select") {
				$s_buffer = str_replace("<#name#>",'option_'.$i.'',$s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer,"optionlist");
				$temp = "";
				$arr = preg_split("/\|/",$my_init["value"]);
				foreach($arr as $elem)
					$temp .= str_replace("<#option_value#>",$elem,str_replace("<#select#>",(($elem==$_VARS["option_value_$i"])?"selected":""),$t_buffer));
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer,"optionlist",$temp);
			} elseif($my_init["type"] == "multiselect") {
				$s_buffer = str_replace("<#name#>",'option_'.$i.'',$s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer,"optionlist");
				$temp = "";
				$arr = preg_split("/\|/",$my_init["value"]);
				foreach($arr as $elem)
					$temp .= str_replace("<#option_value#>",$elem,str_replace("<#select#>",(($elem==$_VARS["option_value_$i"])?"selected":""),$t_buffer));
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer,"optionlist",$temp);
			} elseif($my_init["type"] == "reference") {
				$s_buffer = str_replace("<#name#>",'option_'.$i.'',$s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer,"optionlist");
				$temp = "";
	      		$res_ref = DB::getQueryRows("SELECT * FROM multi_data WHERE field_id = '".$my_init["value"]."'");
	      		foreach($res_ref as $my_ref) {
					$temp .= str_replace("<#option_value#>",$my_ref['value'],str_replace("<#select#>",(($my_ref['value']==$_VARS["option_value_$i"])?"selected":""),$t_buffer));
	      		}
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer,"optionlist",$temp);
			} else {
				$s_buffer = str_replace("<#name#>","option_$i",$s_buffer);
			}
			$temp_buffer .= $s_buffer;
		}
		$buffer = str_replace("<#elements#>",$temp_buffer,$buffer);
	
	} elseif($_VARS['mu_action'] == "save") {
	
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'save');
	
		$multi_array = DB::getQueryRow("SELECT * FROM multi_init WHERE id = '".$config->multi_id."'");
		$multi_id = $multi_array['id'];
	
		$set_entry = "SET multi_id = '$multi_id', name = '".$_POST['entry_name']."'";
		DB::executeQuery("INSERT INTO multi_entry $set_entry");
		$entry_id = get_insert_id();
		$multi_abfrage2 = DB::getQueryRows("SELECT * FROM multi_fields WHERE multi_id = '$multi_id' ORDER BY position");
		foreach($multi_abfrage2 as $multi_array2) {
			$value = "";
			if($multi_array2['type'] == "image" || $multi_array2['type'] == "downlaod") {
				save($_FILES["option_".$multi_array2['id']]['tmp_name'], \Util::getDocumentRoot().$upload_dir,"multi_".$_FILES["option_".$multi_array2['id']]["name"]);
				$_POST["option_".$multi_array2['id']] = "multi_".$_FILES["option_".$multi_array2['id']]["name"];
			}
			if(is_array($_POST["option_".$multi_array2['id']])) {
				foreach($_POST["option_".$multi_array2['id']] as $elem)
					$value .= "|".$elem;
				$value = substr($value,1);
		    } else {
				$value = $_POST["option_".$multi_array2['id']];
		    }
			DB::executeQuery("INSERT INTO multi_data SET value = '".$value."', field_id = '".$multi_array2['id']."', multi_id = '$multi_id', entry_id = '$entry_id'");
		}
	
		message("Ein neuer Eintrag im Multidatenmodul auf der Seite ".$page_data['title']."! Bitte schalten Sie diesen unter ".$system_data['domain']."/admin/ aktiv.");
	
	} elseif($_VARS['mu_action'] == "list") {
	
		$where = "";
	
		if($_VARS['idShowCat'] > 0) {
			$config->showcat = $_VARS['idShowCat'];
		}
	
		$config->showcat;
		$config->sortbycat;
	
		$buffer_pages = \Cms\Service\PageParser::checkForBlock($element_data['content'],'pages');
	
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],$_VARS['mu_action']);
	
		$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"text");
		$elem_code['date'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"date");
		$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"select");
		$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"textarea");
		$elem_code['html'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"html");
		$elem_code['image'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"image");
		$elem_code['email'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"email");
		$elem_code['web'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"web");
		$elem_code['download'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"download");
		$elem_code['reference'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"reference");
		$elem_code['multiselect'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"multiselect");
	 	$replace = \Cms\Service\PageParser::checkForBlock($elem_code['multiselect'],'replace');
	 	$elem_code['multiselect'] = \Cms\Service\PageParser::replaceBlock($elem_code['multiselect'],'replace','');
		$result_buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'result');
		$noresult_buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'noresult');
		$buffer_backlink = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'backlink');
		$buffer_forwardlink = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'forwardlink');
	
		$i=0;
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
					$sParameter .= $key.$sAdd."=".$v."&";
					if($par = strstr($key,"search_")) {

						if(is_numeric(substr($key,7)))
						{
							$sCol = "field_"  .substr($key, 7);
						}
						else
						{
							$sCol = substr($key, 7);
						}

						switch($_VARS['searchmode_'.substr($key,7)][$k]) {
							case "1":
								$where .= " (`".$sCol."` LIKE '%".$v."%') OR ";
								break;
							case "2":
								$where .= " (`".$sCol."` LIKE '".$v."%') OR ";
								break;
							case "3":
								$where .= " (`".$sCol."` LIKE '%".$v."') OR ";
								break;
							case "4":
								$where .= " (`".$sCol."` LIKE '".$v."') OR ";
								break;
							case "5":
								$where .= " (`".$sCol."` = '".$v."') OR ";
								break;
							case "6":
								$where .= " (`".$sCol."` >= ".intval($v).") OR ";
								break;
							case "7":
								$where .= " (`".$sCol."` < ".intval($v).") OR ";
								break;
							case "8":
								$where .= " (`".$sCol."` != '') OR ";
								break;
							case "9":
								if($v=='0') $v = 0;
								$where .= " (`".$sCol."` = '".$v."' OR '".$v."' = 0) OR ";
								break;
							default:
								$where .= " (`".$sCol."` LIKE '%".$v."%') OR ";
								break;
						}
						$i++;
					}
				}
			}
		}
		$where = substr($where,0,-3);
		if(!$where) $where = "1";
	
		$strSelect = '';
	
		$aFieldData = array();
		$strSql = "
					SELECT 
						multi_init.id as multi_id, 
						multi_fields.field_id as field_id,  
						multi_fields.type as type,  
						multi_fields.options as options
					FROM  
						multi_init JOIN
						multi_fields ON
							multi_init.id = multi_fields.multi_id
					WHERE 
						multi_init.id = '".$config->multi_id."' AND  
						multi_fields.display LIKE '%list%'  
					ORDER BY  
						multi_fields.position";
		$rFields = DB::getQueryRows($strSql);
		foreach($rFields as $aFields) {
			$aFieldData[$aFields['field_id']] = $aFields;
			switch($aFields['type']) {
				case "date":
					$strSelect .= 'UNIX_TIMESTAMP(e.field_'.$aFields['field_id'].') field_'.$aFields['field_id'].', ';
					break;
				default:
					$strSelect .= 'e.field_'.$aFields['field_id'].', ';
					break;
			}
		}
	
		// Query Zusatz f�r Volltextsuche
		if($_VARS['mu_keyword']) {
			$sWhereFulltext = "(";
			$mu_i=0;
			foreach($aFieldData as $aFields) {
				$sWhereFulltext .= "(field_".$aFields['field_id']." LIKE '%".$_VARS['mu_keyword']."%') OR ";
			}
			$sWhereFulltext .= " 0) ";
		}
		if(!$sWhereFulltext) $sWhereFulltext = "1";
	
		$arr = array();
		$sOrderBy = "";
	
		if ($config->random == 1) {
			$sOrderBy = "RAND()";
		} else {
			if($config->sortbycat > 0) {
				$sOrderBy = "c.position, c.name, ";
			}
			foreach((array)$config->sortby_field as $k=>$v) {
				if($v > 0) {
					$sOrderBy .= "e.field_".$v." ".$config->sortby_dir[$k].",";
				}
				else if($v == 'id')
				{
					$sOrderBy .= "e.`".$v."` ".$config->sortby_dir[$k].",";
				}
			}
			$sOrderBy .= "e.position";
		}
	
		$sWhere = "";
		if($config->showcat > 0) {
			$sWhere .= " AND e.category_id = '".intval($config->showcat)."' ";
		}

		$sValidQuery = Ext_Multi::getValidQueryPart($config->select_mode);
	
		$sQuery = "
			SELECT 
				".$strSelect."
				e.id,
				e.created,
				e.title,
				e.validfrom,
				e.validuntil,
				e.position,
				c.id category_id,
				c.name category_name,
				GROUP_CONCAT(CONCAT(`ma_0`.`customer_db_id`, '_', `ma_0`.`customer_group_id`) SEPARATOR '|') AS `access_0`,
				GROUP_CONCAT(CONCAT(`ma_1`.`customer_db_id`, '_', `ma_1`.`customer_group_id`) SEPARATOR '|') AS `access_1`
			FROM 
				`multi_table_" . $config->multi_id . "` AS `e`	LEFT OUTER JOIN 
				`multi_categories` AS `c`							ON
					`e`.`category_id` = `c`.`id`				LEFT OUTER JOIN
				`multi_access` AS `ma_0`							ON
					`e`.`id` = `ma_0`.`multi_entry_id` AND
					`ma_0`.`access` = 0							LEFT OUTER JOIN
				`multi_access` AS `ma_1`							ON
					`e`.`id` = `ma_1`.`multi_entry_id` AND
					`ma_1`.`access` = 1
			WHERE 
				(".$where.") AND 
				(".$sWhereFulltext.") AND 
				(".$sValidQuery.") 
				".$sWhere." 
			GROUP BY
				`e`.`id`
			ORDER BY 
				".$sOrderBy."
		";
		$rList = DB::getQueryRows($sQuery);
		foreach($rList as $aList) {

			if(!Ext_Multi_Access::checkAccess($aList['access_0'], $aList['access_1'])) {
				continue;
			}

			$arr[] = $aList;
		}

		$buffer_row = \Cms\Service\PageParser::checkForBlock($element_data['content'],'row');
		$buffer_separator = \Cms\Service\PageParser::checkForBlock($element_data['content'],'separator');
		$buffer_row_elem = \Cms\Service\PageParser::checkForBlock($element_data['content'],'row_elem');
	
		$total = count($arr);
	
		// Wenn kein Ergebnis
		if($total < 1) {
	
			$buffer = str_replace("<#elem#>",$noresult_buffer,$buffer);
	
		// Wenn Ergebnis
		} else {
	
			$mu_backlink = "";
	
			if(($mu_start+$mu_number-1) < $total)
				$mu_end = ($mu_start+$mu_number-1);
			else 
				$mu_end = $total;
			if($mu_start > 1)
				$mu_backlink = str_replace("<#mu_startb#>",($mu_start-$mu_number),$buffer_backlink);
			if(($mu_start+$mu_number-1) < $total)
				$mu_forwardlink = str_replace("<#mu_startf#>",($mu_start+$mu_number),$buffer_forwardlink);
	
			$buffer_pages = str_replace("<#start#>",$mu_start,$buffer_pages);
			$buffer_pages = str_replace("<#end#>",$mu_end,$buffer_pages);
			$buffer_pages = str_replace("<#total#>",$total,$buffer_pages);
			$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"backlink",$mu_backlink);
			$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"forwardlink",$mu_forwardlink);
	
			$buffer = str_replace("<#displayPages#>",$buffer_pages,$buffer);
			$buffer = str_replace("<#mu_variables#>",$sParameter,$buffer);
			$buffer = str_replace("<#mu_keyword#>",$_VARS['mu_keyword'],$buffer);
	
			$aVars = array();
			$aDbls = array();
			$sSelect = "";
			$pos=0;
			while($pos = strpos($buffer_row,'<#',$pos)) {
				$end = strpos($buffer_row,'#>',$pos);
				$var = substr($buffer_row, $pos+2, $end-$pos-2);
				$info = explode(":",$var);
				if(!in_array($var,$aDbls) && $info[0] == "list") {
					$aTemp = array();
					$aTemp[0] = $info;
					$aTemp[1] = $var;
					$aVars[$info[1]][] = $aTemp;
					$aDbls[] = $var;
				}
				$pos++;
			}
	
			$iCurrentCat = -1;
	
			$cache = "";
			$sOutput = "";
			$cat_buffer = "";
			$inner_c = "";
			$inner_t = "";
			$inner_r = "";
			$i=1;
			$j=1;
	
			if(!$config->sortbycat) {
				$sOutput = "";
				$cat_buffer = $result_buffer;
				$cache = "";
				$cat_buffer = str_replace("<#category#>","",$cat_buffer);
			}
	
			// Alle Eintr�ge durchgehen
			for($c = ($mu_start-1); $c < $mu_end; $c++) {
	
				$elem = $arr[$c]['id'];
				$title = $arr[$c]['title'];
				$iCat = $arr[$c]['category_id'];
	
				$inner_r = $buffer_row;
				$inner_r = str_replace("<#title#>",$title,$inner_r);
				$inner_c = "";
				// Wenn neue Kategorie
				if($config->sortbycat > 0 && $iCurrentCat != $iCat) {
					// Kategorieinfos holen
					$aCat = DB::getQueryRow("SELECT * FROM multi_categories WHERE id = '".intval($iCat)."'");
					if($iCurrentCat != -1)
						$sOutput .= \Cms\Service\PageParser::replaceBlock($cat_buffer,"row",$cache);
					$cat_buffer = $result_buffer;
					$cache = "";
					$cat_buffer = str_replace("<#category#>",$aCat['name'],$cat_buffer);
					$cat_buffer = str_replace("<#idCategory#>",$aCat['id'],$cat_buffer);
					$cat_buffer = str_replace("<#category_id#>",$aCat['id'],$cat_buffer);
					$iCurrentCat = $iCat;
				}
	
				// Jedes Feld durchgehen
				foreach($aFieldData as $idField=>$aField) {
					$my['field_id'] = $idField;
					$my['value'] = $arr[$c]['field_'.$idField];
					$my['type'] = $aField['type'];
					$my['options'] = $aField['options'];
					// Geht jedes Vorkommen des Feldes durch
					foreach((array)$aVars[$my['field_id']] as $aVar) {
						$sValue = $my['value'];
						$sVar = "<#".$aVar[1]."#>";
						$bIsSpacer = false;
	
						if($my['type'] == "image") {
							
							if($aVar[0][2] == 'original') {
	
								$sValue = $sValue;
	
							} else {
	
								$sDir = \Util::getDocumentRoot()."system/extensions/multi/";
								if(!is_dir($sDir)) {
									@mkdir($sDir,$system_data['chmod_mode_dir']);
									@chmod($sDir,$system_data['chmod_mode_dir']);
								}
								$sFile = \Util::getDocumentRoot()."media/".$sValue;
								$iW = $aVar[0][2];
								$iH = $aVar[0][3];
								if(is_file($sFile) && $iW > 0 && $iH > 0) {
									$iFileMTime = @filemtime($sFile);
									$url = "/system/extensions/multi/".$iW."_".$iH."_".$iFileMTime."_".str_replace("/","_",$sValue);
								} elseif(is_file($sFile)) {
								 	$url = "/media/".$sValue;
								} else {
									$sFile = \Util::getDocumentRoot()."system/extensions/multi/default.gif";
								 	$url = "/system/extensions/multi/".$iW."_".$iH."_default.gif";
									$bIsSpacer = true;
								}
								$sDetail = \Util::getDocumentRoot().$url;
							 	if(!is_file($sDetail) && $iW > 0 && $iH > 0) {
									saveResizeImage($sFile,$sDetail,$iW,$iH);
								}

							 	if(!is_file($sDetail)) {
									$url = "";
								}
								$sValue = $url;

							}
	
						}
	
						if($my['type'] == "table") {
	
							$sTable = $my['options'];
							$arrContent = unserialize($sValue);
							$arrRows = \Cms\Service\PageParser::getBlockContentAll($sTable,"row");
							$sRows = "";
							foreach((array)$arrContent as $row) {
								$sRowBuffer = $arrRows[$row[0]][1];
								for($i=1;$i<count($row);$i++) {
									$sRowBuffer = str_replace("#wd:cell:".$i."#",nl2br($row[$i]),$sRowBuffer);
								}
								$sRows .= $sRowBuffer;
							}
							$sValue = \Cms\Service\PageParser::replaceBlockContent($sTable,"content",$sRows);
	
						}
	
						if($my['type'] == "download") {
							$sFile = "/".$sValue;
							$sName 	= substr($sFile,(strrpos($sFile,"/")+1));
							$sLink = "/system/applications/download.php?multi_data_id=".$my['multi_data_id']."&targetname=".urlencode($sName)."&filepath=".urlencode($sValue);
							$sExt 	= substr($sValue,(strrpos($sValue,".")+1));
							$sSize 	= Util::formatFilesize(filesize(\Util::getDocumentRoot()."media/".$sValue));
							$inner_r = str_replace("<#list:".$my['field_id'].":link#>",$sLink,$inner_r);
							$inner_r = str_replace("<#list:".$my['field_id'].":name#>",$sName,$inner_r);
							$inner_r = str_replace("<#list:".$my['field_id'].":ext#>",$sExt,$inner_r);
							$inner_r = str_replace("<#list:".$my['field_id'].":size#>",$sSize,$inner_r);
						}
	
						if($my['type'] == "date") {
							$strFormat = $aVar[0][2];
							if(!$strFormat) {
								$strFormat = "%x";
							}
							$sValue = strftime($strFormat, $sValue);
						}
	
				    	if($my['type'] != "html" && $my['type'] != "table") {
							switch($aVar[0][2]) {
								case "sprintf":
									$sValue = sprintf($aVar[0][3], $sValue);
									break;
								case "wrap":
									$objImgBuilder = new imgBuilder;
									$aTemp = $objImgBuilder->determineWordWrap($sValue, $aVar[0][3], 0, $aVar[0][4], $aVar[0][5], $aVar[0][6], $aVar[0][7]);
									$sValue = $aTemp['wrap'];
									break;
								case "trunc":
									if ($aVar[0][3] == 0)
										$sValue = '';
								    if (strlen($sValue) > $aVar[0][3]) {
										$sValue = preg_replace('/\s+?(\S+)?$/', '', substr($sValue, 0, $aVar[0][3]+1));
										$sValue = substr($sValue, 0, $aVar[0][3]) . '...';
									} else {
										$sValue = $sValue;
									}
									$inner_r = str_replace($sVar,$sValue,$inner_r);
									break;
								default:
									$sValue = $sValue;
									break;
							}
							$sValue = str_replace("|", $replace, $sValue);
							$sValue = nl2br($sValue);
						}
	
						$inner_r = str_replace($sVar, $sValue, $inner_r);
	
					} // Ende foreach
	
					$varname = 'tpl_value_'.$my['field_id'];
					$$varname = $my['value'];
	
					$varname = 'tpl_name_'.$my['field_id'];
					$$varname = $my['name'];
	
					$inner_t = $buffer_row_elem;
					$inner_t = str_replace("<#option#>",$my['options'],$inner_t);
					$inner_t = str_replace("<#value#>",$elem_code[$my['type']],$inner_t);
					$inner_t = str_replace("<#value#>",$my['value'],$inner_t);
					$inner_t = str_replace("<#name#>",$my['name'],$inner_t);

					$inner_t = str_replace("<#category_id#>", $my['category_id'], $inner_t);

					if($my['value'] && !$bIsSpacer) {
						$inner_c .= $inner_t;
					}
					$inner_r = str_replace("<#field_".$my['field_id']."#>",$my['value'],$inner_r);
	
				}
				$temp = str_replace("<#elements#>",$inner_c,$inner_r);
	
				$pos=0;
				while($pos = strpos($temp,'<#tpl_',$pos)) {
			      $end = strpos($temp,'#>',$pos);
				  $var = substr($temp, $pos+2, $end-$pos-2);
			      $temp = substr($temp, 0, $pos)  .  $$var  .  substr($temp, $end+2);
				}
	
				$temp = str_replace("<#category_name#>", $arr[$c]['category_name'], $temp);
				$temp = str_replace("<#category_id#>", $arr[$c]['category_id'], $temp);
				$temp = str_replace("<#mu_id#>", $elem, $temp);
				$cache .= $temp;
	
				$i++;
				$j++;
				
				// Separator nach jedem Eintrag einf�gen au�er beim letzten
				if(($c + 1) < $mu_end) {
					$cache .= $buffer_separator;
				}
	
			}
			$sOutput .= \Cms\Service\PageParser::replaceBlock($cat_buffer,"row",$cache);
	
			$sOutput = \Cms\Service\PageParser::replaceBlock($sOutput,"separator","");
	
			$buffer = str_replace("<#elem#>",$sOutput,$buffer);
	
		}
	
	//short oder long
	} else {
	
		$inner_c = "";
	
		$my_entry = DB::getQueryRow("SELECT id, name, category_id FROM multi_entry WHERE id = '".intval($_VARS['mu_id'])."'");
		$title = $my_entry['name'];
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],$_VARS['mu_action']);

		// id des aktuellen eintrages
		$buffer = str_replace('<#id#>', $my_entry['id'], $buffer); 
		$buffer = str_replace('<#mu_id#>', $my_entry['id'], $buffer); 
		$buffer = str_replace("<#idCategory#>", $my_entry['category_id'], $buffer);
		$buffer = str_replace("<#category_id#>", $my_entry['category_id'], $buffer);
	
		$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"text");
		$elem_code['date'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"date");
		$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"select");
		$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"textarea");
		$elem_code['image'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"image");
		$elem_code['email'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"email");
		$elem_code['web'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"web");
		$elem_code['download'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"download");
		$elem_code['multiselect'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"multiselect");
	 	$replace = \Cms\Service\PageParser::checkForBlock($elem_code['multiselect'],'replace');
	 	$elem_code['multiselect'] = \Cms\Service\PageParser::replaceBlock($elem_code['multiselect'],'replace','');
		$elem_code['reference'] = \Cms\Service\PageParser::checkForBlock($element_data['content'],"reference");
		
		$strSql = "
					SELECT 
						multi_fields.field_id as field_id,
						multi_data.value as value,
						multi_fields.type as type,
						multi_fields.name as name,
						multi_fields.options as options,
						GROUP_CONCAT(CONCAT(`ma_0`.`customer_db_id`, '_', `ma_0`.`customer_group_id`) SEPARATOR '|') AS `access_0`,
						GROUP_CONCAT(CONCAT(`ma_1`.`customer_db_id`, '_', `ma_1`.`customer_group_id`) SEPARATOR '|') AS `access_1`
					FROM 
						multi_data JOIN
						multi_fields ON
							multi_fields.multi_id = multi_data.multi_id LEFT OUTER JOIN
						`multi_access` AS `ma_0`							ON
							multi_data.entry_id = `ma_0`.`multi_entry_id`		AND
							`ma_0`.`access` = 0							LEFT OUTER JOIN
						`multi_access` AS `ma_1`							ON
							multi_data.entry_id = `ma_1`.`multi_entry_id`		AND
							`ma_1`.`access` = 1
					WHERE 
						multi_data.entry_id = ".intval($_VARS['mu_id'])." AND 
						multi_fields.field_id = multi_data.field_id AND 
						multi_fields.display LIKE '%detail%'
					GROUP BY
						multi_data.entry_id
					ORDER BY 
						multi_fields.position";
		$res = (array)DB::getQueryRows($strSql);
		$inner_r = \Cms\Service\PageParser::checkForBlock($buffer,"field");

		$aVars = array();
		$aDbls = array();
		$sSelect = "";
		$pos=0;
		while($pos = strpos($buffer,'<#',$pos)) {
			$end = strpos($buffer,'#>',$pos);
			$var = substr($buffer, $pos+2, $end-$pos-2);
			$info = explode(":",$var);
			if(!in_array($info[1],$aDbls) && $info[0] == "detail") {
				$aVars[$info[1]][0] = $info;
				$aVars[$info[1]][1] = $var;
			}
			$pos++;
		}
		foreach($res as $my) {
			
			if(!Ext_Multi_Access::checkAccess($my['access_0'], $my['access_1'])) {
				continue;
			}
			
			$bIsSpacer = false;
			$sVar = "<#".$aVars[$my['field_id']][1]."#>";
	
			if($my['type'] == "image") {
											
				if($aVars[$my['field_id']][0][2] == 'original') {

					$my['value'] = $sValue;

				} else {
	
					$sDir = \Util::getDocumentRoot()."system/extensions/multi/";
					if(!is_dir($sDir)) {
						@mkdir($sDir,$system_data['chmod_mode_dir']);
						@chmod($sDir,$system_data['chmod_mode_dir']);
					}
					$sFile = \Util::getDocumentRoot()."media/".$my['value'];
					$iW = $aVars[$my['field_id']][0][2];
					$iH = $aVars[$my['field_id']][0][3];
					if(is_file($sFile) && $iW > 0 && $iH > 0) {
						$iFileMTime = @filemtime($sFile);
						$url = "/system/extensions/multi/".$iW."_".$iH."_".$iFileMTime."_".str_replace("/","_",$my['value']);
					} elseif(is_file($sFile)) {
					 	$url = "/media/".$my['value'];
					} else {
						$sFile = \Util::getDocumentRoot()."system/extensions/multi/default.gif";
					 	$url = "/system/extensions/multi/".$iW."_".$iH."_default.gif";
						$bIsSpacer = true;
					}
					$sDetail = \Util::getDocumentRoot().$url;
				 	if(!is_file($sDetail) && $iW > 0 && $iH > 0) {
						saveResizeImage($sFile, $sDetail, $iW, $iH);
					}
		
				 	if(!is_file($sDetail)) {
						$url = "";
					}
					$my['value'] = $url;
					
				}

			}

			if($my['type'] == "table") {
	
				$sTable = $my['options'];
				$arrContent = unserialize($my['value']);
				$arrRows = \Cms\Service\PageParser::getBlockContentAll($sTable,"row");
				$sRows = "";
				$c=0;
				foreach((array)$arrContent as $row) {
					$sRowBuffer = $arrRows[$row[0]][1];
					for($i=1;$i<count($row);$i++) {
						$sRowBuffer = str_replace("#wd:cell:".$i."#",nl2br($row[$i]),$sRowBuffer);
					}
					$sRows .= $sRowBuffer;
					$c++;
				}
				$my['value'] = \Cms\Service\PageParser::replaceBlockContent($sTable,"content",$sRows);
	
			}
	
			/*
			if($my['type'] == "date") {
				$strFormat = $aVars[$my['field_id']][0][2];
				if(!$strFormat) {
					$strFormat = "%x";
				}
				$my['value'] = strftime($strFormat, $my['value']);
			}
			*/
	
	    	if($my['type'] != "html" && $my['type'] != "table")
				$my['value'] = nl2br(str_replace("|",$replace,$my['value']));
	
			$varname = 'tpl_value_'.$my['field_id'];
			$$varname = $my['value'];
	
			$varname = 'tpl_name_'.$my['field_id'];
			$$varname = $my['name'];
	
			$inner_t = $inner_r;
			$inner_t = str_replace("<#option#>",$my['options'],$inner_t);
			$inner_t = str_replace("<#value#>",$elem_code[$my['type']],$inner_t);
			$inner_t = str_replace("<#value#>",$my['value'],$inner_t);
			$inner_t = str_replace("<#name#>",$my['name'],$inner_t);
			if($my['value'] && !$bIsSpacer) {
				$inner_c .= $inner_t;
			}
			$buffer = str_replace("<#field_".$my['field_id']."#>",$my['value'],$buffer);
			$buffer = str_replace($sVar,$my['value'],$buffer);
		}
		$cache = $inner_c;
	
		$pos=0;
		while($pos = strpos($cache,'<#tpl_',$pos)) {
	      $end = strpos($cache,'#>',$pos);
		  $var = substr($cache, $pos+2, $end-$pos-2);
	      $cache = substr($cache, 0, $pos)  .  $$var  .  substr($cache, $end+2);
		}
	
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer,"field",$cache);
	
	}
	
	$i=0;
	$pos=0;
	while($i < 1000 && $pos = strpos($buffer,'<#',$pos)) {
		$end = strpos($buffer,'#>',$pos);
		$var = substr($buffer, $pos+2, $end-$pos-2);
		$buffer = substr($buffer, 0, $pos)  .  $$var  .  substr($buffer, $end+2);
		$i++;
	}
	
	echo $buffer;

}

if($config->norequest) {
	$_VARS = $aTempVars;
}
