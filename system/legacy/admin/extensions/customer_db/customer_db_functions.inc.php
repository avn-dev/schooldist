<?php
////////////////////////////////////////////////
////////////   Beginn Funktionen   /////////////
////////////////////////////////////////////////
 
global $customer_db_functions_is_declared;
$customer_db_functions_is_declared=1;

function getCustomerDbs() {
	global $db_data;
	$res = DB::getQueryRows("SELECT id,db_name FROM customer_db_config WHERE active=1 ORDER BY id");
	$aDbs = array();
	foreach($res as $my) {
		$aDbs[$my['id']] = $my['db_name'];
	}
	return $aDbs;
}

function edit_path($db_name,$SQL_handler,$definition_table,$value_table,$selected_table, $selected_field) {

  $SQL_handler->SQL_get_id($db_name,$definition_table,$selected_table,$selected_field);

  $option_name="definition_id";
  $option=$SQL_handler->result[1];

  $SQL_handler->SQL_get_row_by_option($db_name, $value_table, $selected_table, $option_name, $option);

  $result=$SQL_handler->result[1];

  for ($i=0;$i<count($result);$i++)
  {
    $current_display[$i]= $result[$i]["display"];
    //$current_value[$i]= $result[$i]["value"];
  }
  echo "<br>";
  echo "<input type=\"hidden\" name=\"save_path\" value=\"$current_display[0]\">";//$path=$current_display[0];
 // var_dump($current_display);echo "<br>";
 // var_dump($current_value);echo "<br>";


?>
  Verzeichnis, in dem zugehörige Dateien abgelegt werden sollen : <br><br>

  <table border=0 class=table cellpadding=4 cellspacing=0>
  <tr>
  <td>
  Verzeichnis
  </td>
  <td>
  <input type=text name=save_path value=<?=$current_display[0]?>>
  </td>
  </tr>

  </table> <br>
  <input type="hidden" name="old_selected_option" value="<?=$current_display[0]?>">
 <?

  return TRUE;


} // Ende function edit_path

function check_unique($value,$field_name,$table_name) {

    $customer_db_functions_is_declared = true;

    if($value && $field_name && $table_name) {
        $query = "SELECT id FROM `".$table_name."` WHERE `".$field_name."` = '".$value."'";
        $result = count(DB::getQueryRows($query)??[]);
        if ($result == 0) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * @param array $aData
 * @param bool $bNew
 * @param int $iDemandConfirm
 * @param string $sTable
 * @param int $iId
 * @return boolean
 */
function save_customer_data($aData, $bNew=true, $iDemandConfirm=0, $sTable=null, $iId=null) {

	$aBlock = array();

    if(!$bNew) {

        $query="UPDATE ".$sTable." SET ";
        foreach($aData as $sName=>$sValue) {
			if($sName && !in_array($sName,$aBlock)) {
				if(
					strstr($sName,"ext_") && 
					preg_match("/ext_[0-9]+/", $sName) !== 1
				) {
					continue;
				}
	            $query.=" `".$sName."` = '".\DB::escapeQueryString($sValue)."',";
				$aBlock[] = $sName;
			}
        }
        $query.= " changed_by = ".$iId.", active=1 WHERE id = ".$iId;
    } else {

        $query="INSERT INTO ".$sTable." SET ";
		
		$aData['access_code'] = \Util::generateRandomString(32);
		
		foreach($aData as $sName=>$sValue) {
			if($sName && !in_array($sName,$aBlock)) {
				if(strstr($sName,"ext_") && preg_match("/ext_[0-9]+/", $sName) !== 1) {
					continue;
				}
	            $query.=" `".$sName."` = '".\DB::escapeQueryString($sValue)."',";
				$aBlock[] = $sName;
			}
        }
        $query.= " created = NOW(), changed_by = 0, ";

        // iDemandConfirm gibt an, ob ein OPT-IN vorliegt, so dass ein Eintrag erst
        // bestätigt werden muss, bevor er auf active=1 gesetzt wird
        // Dabei ist 2==OPT-IN; 1==normaler Eintrag
        if(2==$iDemandConfirm)
        {
            $query.= "active = 0";
        }
        else
        {
            $query.= "active = 1";
        }
    }

	$objDb = DB::getDefaultConnection();
	try {
		$resSql = $objDb->query($query);
	} catch (Exception $e) {
		__out($query);
		error("Error save_customer_data: \n".$query, 1, 0, 1);
		$resSql = false;
	}
	if(!$resSql) {
		return false;
	} else {
		if(!$bNew) {
			return true;
		} else {
			return $objDb->getInsertID();
		}
	}

} // Ende function save_customer_data()

function prepareSaveCustomerData($aInput,$idTable,$sPrefix="customer_db_save_") {
	global $db_data;
	$i=0;
	$aConfig = DB::getQueryRow("SELECT * FROM customer_db_config WHERE id = '".$idTable."'");

	if($aInput) {
		foreach ($aInput as $key => $value) {
		    // prüfen, ob variable zu dem Bereich customer_db_ gehören
		    if(strpos($key,$sPrefix)===0) {
		        if($key == $sPrefix."email") {
					$aData["email"]	= $value;
		        } elseif($key == $sPrefix."nickname") {
					$aData["nickname"] = $value;
		        } elseif($key == $sPrefix."password") {
					if($value) {
						$sPlainPw = $value;
						if($aConfig['db_encode_pw']) {
				            $value = md5($value);
						}
						$aData["password"] = $value;
					}
		        } elseif($key == $sPrefix."groups") {
					$aData["groups"] = "|".implode("|",$value)."|";
	        	} else {
		            $sFieldId = str_replace($sPrefix,"",$key);
					$sFieldId = str_replace("ext_","",$sFieldId);
					$sType = getCustomerFieldType($sFieldId,$idTable);
					switch($sType) {
						case "timestamp":
							$value = strtotimestamp($value,1);
						break;
					}
					if(is_numeric($sFieldId)) {
						$aData["ext_".$sFieldId] = $value;
					} else {
						$aData[$sFieldId] = $value;
					}
		        }
				unset($aInput[$key]);
		        $i++;
		    }
		}
	}
	return $aData;
}

function load_customer_data($table_name, $id)
{
    global $db_data;

	$aReturn = array();
	
	$idDatabase = substr($table_name,strrpos($table_name,"_")+1);
	$aFields = CustomerDb\Helper\Functions::getCustomerFields($idDatabase);

	$sSelect = "";
    foreach($aFields as $k=>$v) {
		if(is_numeric($k)) {
			$query = "SELECT id, type FROM customer_db_definition WHERE field_nr = '".$k."' AND db_nr = '".$idDatabase."' AND active = 1";
		} else {
			$query = "SELECT id, type FROM customer_db_definition WHERE name = '".$k."' AND db_nr = '".$idDatabase."' AND active = 1";
		}
		$my_field = DB::getQueryRow($query);
		$aFieldData['id'] = $my_field['id'];
		$aFieldData['type'] = $my_field['type'];
		$aFieldData['name'] = ((is_numeric($k))?"ext_".$k:$k);
		if($my_field['type'] == "timestamp") {
			$sSelect .= "UNIX_TIMESTAMP(`".$aFieldData['name']."`) as ".$aFieldData['name'].", ";
		} else {
			$sSelect .= "`".$aFieldData['name']."` as ".$aFieldData['name'].", ";
		}
	}

	$strSql = "SELECT ".$sSelect." active FROM $table_name WHERE id = '$id' ";#AND active=1
    $arrCustomer = DB::getQueryRow($strSql);

    if (!empty($arrCustomer)) {
		foreach($arrCustomer as $strKey => $strValue) {
			if(!is_numeric($strKey)) {
				$intNumber = str_replace("ext_", "", $strKey);
				$strTmp = "customer_db_".$intNumber;
				$aReturn[$strTmp] = $strValue;	
			}
        }
    }

	return $aReturn;
} // ende


// Funktion für die Ausgabe der Felder in den Modulen

function getFieldOutput($sType, $sValue, $iDefinition, $aAdditional, $aConfig=array()) {
	global $_SERVER, $db_data;
	switch($sType) {
		case "image":
			$aValue = explode("|",$sValue);
			$sValue = $aValue[0];
		 	$sFile = \Util::getDocumentRoot()."system/extensions/customer_db/".$sValue;
			// Wenn Datei vorhanden
			if(is_file($sFile)) {
				// Wenn Größenvorgabe
				if($aAdditional[2] > 0 && $aAdditional[3] > 0) {
				 	$url = "/system/extensions/customer_db/".$aAdditional[2]."_".$aAdditional[3]."_".$sValue;
				} else {
					$url = "/system/extensions/customer_db/".$sValue;
				}
				$sDetail = \Util::getDocumentRoot().$url;
			} else {
				$sFile = \Util::getDocumentRoot()."system/extensions/customer_db/default.gif";
			 	$url = "/system/extensions/customer_db/".$aAdditional[2]."_".$aAdditional[3]."_default.gif";
				$sDetail = \Util::getDocumentRoot().$url;
			}
		 	if(!is_file($sDetail)) {
				saveResizeImage($sFile,$sDetail,$aAdditional[2],$aAdditional[3]);
			}
		 	if(!is_file($sDetail)) {
				$url = "/system/extensions/customer_db/default.gif";
			}
			$content = $url;
			break;
		case "Checkbox":
			$my_value = DB::getQueryRow("SELECT display FROM customer_db_values WHERE definition_id = '".$iDefinition."' AND value = '".$sValue."' AND active = 1");
			$content = $my_value['display'];
			if($my_value['display']) $content .= $aAdditional[2];
			break;
		case "groups":
			$aGroups = preg_split("/\|/",$sValue,-1,PREG_SPLIT_NO_EMPTY);
			
			$aGroupOutput = array();
			foreach((array)$aGroups as $iGroup) {
				$aGroup = DB::getQueryRow("SELECT name FROM customer_groups WHERE id = '".(int)$iGroup."' LIMIT 1");
				$aGroupOutput[] = $aGroup['name'];
			}
			$content = implode(", ", $aGroupOutput);

			break;
		case "timestamp":
			if(!$aAdditional[2]) $aAdditional[2] = "%x %X";
			if($sValue) {
				$sTemp = $sValue;
				$aTemp = DB::getQueryRow("SELECT UNIX_TIMESTAMP('".$sValue."') as unixtime");
				$sValue = $aTemp['unixtime'];
				if(!$sValue) $sValue = $sTemp;
				$content = strftime($aAdditional[2],$sValue);
			} else {
				$content = "";
			}
			break;
		case "Radio-Button":
		case "Select Field":
		case "select":
			$my_value = DB::getQueryRow("SELECT display FROM customer_db_values WHERE definition_id = '".$iDefinition."' AND value = '".$sValue."' AND active = 1");
			$content = $my_value['display'];
			break;
			
		case "Datei":
			$aValue = explode("|",$sValue);
			$sValue = $aValue[1];
			if($sValue) {
			 	$content = "/system/extensions/customer_db/".$sValue;
			}
			break;
			
		case "ProtectedDatei":
			$aValue = explode("|",$sValue);
			$sValue = $aValue[1];
			if($sValue) {
			 	$content = "/system/extensions/customer_db/secure/".$sValue;
			}
			break;

		case "reference":
			$sQuery = "SELECT `".$aConfig['db_field_text']."` display FROM `".$aConfig['db_table']."` WHERE `".$aConfig['db_field_value']."` = '".$sValue."'";
	    	$arrValue = DB::getQueryRow($sQuery);
			$content = $arrValue['display'];
			break;
        default:
			$content = nl2br($sValue);
			break;
	}
	return $content;
}

function getFieldInput($sType, $idForm, $idElement, $idField, $sFieldName, $sValue, $aConfig, $field_type) {

	$output = "";
	
	switch($sType) {
	    case "TEXT":
			if(!$sValue && $aConfig->default_value) {
				$sValue == $aConfig->default_value;
			}
			if($aConfig->textarea) {
		        $output .= "<textarea class=\"form-control\" id=\"cdb_field_" . $idForm . "_" . $idElement . "\" name=\"customer_db_" . $sFieldName . "\" OnFocus = ' if ( this.value == \"" . $aConfig->default_value . '\" ) { this.value = \"\"; } \' "' . stripslashes($aConfig->default_class) . ">" . $sValue . "</textarea>";
			} else {
		        $output .= "<input class=\"form-control\" type=text id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" value=\"".$sValue."\" OnFocus = ' if ( this.value == \"".$aConfig->default_value."\" ) { this.value = \"\"; }; ' ".stripslashes($aConfig->default_class).">";
			}
	        break;

	    case "timestamp":
			if(!$aConfig->timeformat) $aConfig->timeformat = "%x %X";
			if($sValue) {
				$sTemp = $sValue;
				if(0 && strlen($sTemp) == 19) {
					$sValue = strtotimestamp($sValue);
				} else {
					$aTemp = DB::getQueryRow("SELECT UNIX_TIMESTAMP('".$sValue."') as unixtime");
					$sValue = $aTemp['unixtime'];
				}
				if(!$sValue) $sValue = $sTemp;
				$sValue = strftime($aConfig->timeformat,$sValue);
			} else {
				$sValue = $aConfig->default_value;
			}
	        $output .= "<input type=text id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" value=\"".$sValue."\" OnFocus = \" if ( this.value == '".$aConfig->default_value."' ) { this.value = ''; } \" ".stripslashes($aConfig->default_class).">";
	        break;

	    case "PASSWORD":
	        $output .= "<input class=\"form-control\" type=\"password\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."".(($aConfig->check_passwd==1)?"_check":"")."\" value=\"\" ".stripslashes($aConfig->default_class).">";
	        break;

	    case "INTEGER":
	        $output .= "<input type=text id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" value=\"".$sValue."\" OnFocus = \" if ( this.value == '".$aConfig->default_value."' ) { this.value = ''; } \" ".stripslashes($aConfig->default_class).">";
	        break;

	    case "Select Field":
	        $res_values = DB::getQueryRows("SELECT value,display FROM customer_db_values WHERE `definition_id` = '".$idField."' AND active = 1 ORDER BY value");

	        $output .= "<select class=\"form-control\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\"  ".stripslashes($aConfig->default_class).">";
	        foreach($res_values as $my_values) {
	            $output .= "<option value='".$my_values['value']."' ".(($my_values['value'] == $sValue)?"selected":"").">".$my_values['display']."</option>";
	        }

	        $output .= "</select>";
	        break;

	    case "groups":
	        $aGroups = CustomerDb\Helper\Functions::getCustomerGroups($aConfig->idDatabase);
			$aValue = preg_split("/\|/",$sValue,-1,PREG_SPLIT_NO_EMPTY);
			rsort($aValue);
			$intGroups = count($aGroups);
			$intGroups = min($intGroups, 5);
	        $output .= "<select class=\"form-control\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."[]\"  ".stripslashes($aConfig->default_class)." multiple size=\"".(int)$intGroups."\">";
	        foreach($aGroups as $k=>$v) {
	            $output .= "<option value='".$k."' ".((in_array($k,$aValue))?"selected":"").">".$v."</option>";
	        }
	        $output .= "</select>";
	        break;

	    case "image":
	        $output .= "<input type=\"file\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" ".stripslashes($aConfig->default_class).">";
	        break;

	    case "Datei":
	        $output .= "<input type=\"file\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" ".stripslashes($aConfig->default_class).">";
	        break;
	        
	    case "ProtectedDatei":
	        $output .= "<input type=\"file\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" ".stripslashes($aConfig->default_class).">";
	        break;	        

	    case "Radio-Button":
	        $output .= "<input type=\"radio\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" value=\"".$aConfig->default_value."\" ".stripslashes($aConfig->default_class)." ".(($aConfig->default_value == $sValue)?"checked":"").">";
	        break;

	    case "Checkbox":
	        $output .= "<input type=checkbox id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\" value=\"1\" ".(($sValue)?"checked=checked":"")." ".stripslashes($aConfig->default_class)." >";#OnClick='alert(document.getElementById(\"cdb_field_".$idForm."_".$idElement."\").checked)'
	        break;

	    case "reference":
	    	$sQuery = "SELECT `".$aConfig->additional['db_field_value']."` value, `".$aConfig->additional['db_field_text']."` display FROM `".$aConfig->additional['db_table']."` ".$aConfig->additional['db_query']."";
			$res_values = DB::getQueryRows($sQuery);
			if($aConfig->additional['multiple']) {
	        	$output .= "<select class=\"form-control\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."[]\" multiple size='5' ".stripslashes($aConfig->default_class).">";
			} else {
				$output .= "<select class=\"form-control\" id=\"cdb_field_".$idForm."_".$idElement."\" name=\"customer_db_".$sFieldName."\"  ".stripslashes($aConfig->default_class).">";
				$sValue = "|".$sValue."|";
			}
	        foreach($res_values as $my_values) {
	            $output .= "<option value='".$my_values['value']."' ".((strpos($sValue, "|".$my_values['value']."|")!==false)?"selected":"").">".$my_values['display']."</option>";
	        }

	        $output .= "</select>";
	        break;

	    case "Kategoriefeld":
	        global $cat_number;
	        if(!intval($cat_number)) {
	            $cat_number=1;
	        } else {
	            $cat_number++;
	        }
	        $max_depth= DB::getQueryRow("SELECT depth FROM $tree_table WHERE tree_number=".intval($config->default_value)." AND active=1 GROUP BY depth DESC");

	        $result= DB::getQueryRow("SELECT * FROM $tree_table WHERE depth=0 AND name='Wurzel' AND tree_number=".intval($config->default_value));

	        $output .= "<input type=\"hidden\" name=\"customer_db_".$config->field_name."\" value=\"".$result['ID']."\">";

	        $output .= "<iframe width=600 height=50 scrolling='no' marginheight='0' marginwidth='0' frameborder='0'";
	        $output .= "id='".$cat_number."_0' src='/admin/extensions/customer_db/category.ifr.php?db_name=$db_module&tree_table=$tree_table&selected_tree=$config->default_value&Form_ID=$Form_ID&id=".$result['ID']."&field_name=customer_db_".$config->field_name."&frame_id=0&cat_number=$cat_number'>";
	        $output .= "</iframe>";

	        for($frame_number=1;$frame_number<$max_depth['depth'];$frame_number++) {
	            $output .= "<br>";
	            $output .= "<iframe width=600 height=50 scrolling='no' marginheight='0' marginwidth='0' frameborder='0'";
	            $output .= "id='".$cat_number."_".$frame_number."' src=''>";
	            $output .= "</iframe>";
	        }

	        ## unsichtbares Frame, das die letzte Unterebene 'auffängt'
	        $output .= "<iframe style='visibility:hidden' width=1 height=1 scrolling='no' marginheight='0' marginwidth='0' frameborder='0'";
	        $output .= "id='".$cat_number."_".$frame_number."' src=''>";
	        $output .= "</iframe>";

	        break;

		default :
	    	if(!$field_type) {
				$field_type="No Type specified!";
			} else {
				$output .= "unknown field type in Database! Type : $field_type";
			}
	    	$required=0;
			break;
	} // Ende switch-case
	$output .=  "<input type=\"hidden\" name=\"customer_db_fields[]\" value=\"".$sFieldName."\">";

	return $output;
}

function getCustomerFieldType($sFieldId,$idTable) {

	if(is_numeric($sFieldId)) {
		$sQuery = "SELECT id,type FROM customer_db_definition WHERE field_nr = '".$sFieldId."' AND db_nr = '".$idTable."' AND active = 1";
	} else {
		$sQuery = "SELECT id,type FROM customer_db_definition WHERE name = '".$sFieldId."' AND db_nr = '".$idTable."' AND active = 1";
	}
	$aField = DB::getQueryRow($sQuery);

	return $aField['type'];
}

function getCustomerFieldData($sFieldId,$idTable) {

	if(is_numeric($sFieldId)) {
		$sQuery = "SELECT id,type,additional,required FROM customer_db_definition WHERE field_nr = '".$sFieldId."' AND db_nr = '".$idTable."' AND active = 1";
	} else {
		$sQuery = "SELECT id,type,additional,required FROM customer_db_definition WHERE name = '".$sFieldId."' AND db_nr = '".$idTable."' AND active = 1";
	}
	
	$aField = DB::getQueryRow($sQuery);

	return $aField;
}

function parseCustomerTemplate($template,&$aVars,&$aVarsPlus,&$sSelect,$sItem,$idTable) {
	global $db_data;

	$aDbls = array();
	$pos=0;
	$i=0;
	while($pos = strpos($template,'<#',$pos)) {
		$end = strpos($template,'#>',$pos);
		$var = substr($template, $pos+2, $end-$pos-2);
		$info = explode(":",$var);

		if(!in_array($info[1],$aDbls) && $info[0] == $sItem) {
			$number = (strstr($info[1],"ext_"))?substr($info[1],strpos($info[1],"_")+1):$info[1];
			if(is_numeric($number)) {
				$query = "SELECT id,type FROM customer_db_definition WHERE field_nr = '".$number."' AND db_nr = '".(int)$idTable."' AND active = 1";
			} else {
				$query = "SELECT id,type FROM customer_db_definition WHERE name = '".$number."' AND db_nr = '".(int)$idTable."' AND active = 1";
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
		} elseif(!strstr($info[0],"if")) {
			$aVarsPlus[] = array($var,$info);
		}
		$pos++;
	}

}

function fReplaceTags($email_message, $aData, $sPlainPw) {
	
	$email_message = \Cms\Service\ReplaceVars::execute($email_message);

	$aData['activate_User'] = $aData['activate_link'];
	$aData['delete_User'] = $aData['delete_link'];
	$aData['password'] = $sPlainPw;

	// Ersetze mögliche ext-Tags
	foreach((array)$aData as $sKey=>$mValue) {
		$email_message = str_replace("<#".$sKey."#>",	$mValue, $email_message);
		$email_message = str_replace("{".$sKey."}",		$mValue, $email_message);
	}

	return $email_message;	

}