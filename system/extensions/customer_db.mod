<?
//////////////////////////////////////////////////////
//  Hauptmodul f?r die customer_db Funktionalit?t   //
//////////////////////////////////////////////////////

// Auslesen, bzw erzeugen der Form_ID ( global! )
// Diese ID ist n?tig, falls mehrere gleiche Formulare auf
// einer Seite sind
global $Form_ID;

if($Form_ID)
{
    $Form_ID++;
}
else
{
    $Form_ID=1;
}

////////////////////////////////////////



$element_id=$element_data["id"];

global $user_data,$db_config;
global $aCDB_CheckFields;
global $aCDB_CheckFieldTypes;

// reset
$aCDB_CheckFields = array();
$aCDB_CheckFieldTypes = array();

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared) {
    require_once(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}

// Die per POST ?bergebenen Variablen werden in das globale
// Array SESSION ?bertragen
foreach((array)$_POST['customer_db_fields'] as $key=>$val) {
	// Wenn Feld Upload
	if($_FILES['customer_db_'.$val]) {
		$key = 'customer_db_'.$val;
		$val = $_FILES['customer_db_'.$val];
		if (is_file($val['tmp_name'])) {
			if(!is_dir(\Util::getDocumentRoot()."system/extensions/customer_db")) {
				@chmod(\Util::getDocumentRoot()."system/extensions",$system_data['chmod_mode_dir']);
				@mkdir(\Util::getDocumentRoot()."system/extensions",$system_data['chmod_mode_dir']);
				@chmod(\Util::getDocumentRoot()."system/extensions/customer_db",$system_data['chmod_mode_dir']);
				@mkdir(\Util::getDocumentRoot()."system/extensions/customer_db",$system_data['chmod_mode_dir']);
			}
			$number = substr($key,strrpos($key,"_")+1,strlen($key));

			$my_field = DB::getQueryRow("SELECT `ext_".$number."` FROM ".$user_data['table']." WHERE id = '".$user_data['id']."'");
			$aImages = explode("|",$my_field['ext_'.$number]);
			$my_field['ext_'.$number] = $aImages[0];

			$ext = strtolower(substr($val['name'],strrpos($val['name'],".")+1,3));

			// TODO: Beschr?nkungen aus Definition Table auslesen
			$my_info = DB::getQueryRow(("SELECT additional FROM customer_db_definition WHERE field_nr = '".$number."' AND db_nr = '".$user_data['idTable']."' LIMIT 1"));
			$aAdditional = unserialize($my_info['additional']);
			$aExtensions 	= ($aAdditional['extensions'])?$aAdditional['extensions']:array("jpg","gif","png");
			$aInfo['iSize'] = ($aAdditional['size'])?$aAdditional['size']:300;

			// Abfrage auf Extension und Gr??e
			if(in_array($ext,$aExtensions) && $val['size'] < ($aInfo['iSize']*1024)) {
				$sValue = $user_data['idTable']."_".$user_data['id']."_".$number."_".time().".".$ext;
				$_SESSION[$key] = $sValue;
				$key = str_replace("customer_db_","customer_db_save_",$key);
				if($aAdditional['publish']) {
					$_SESSION[$key] = $sValue;
				} else {
					$_SESSION[$key] = $my_field['ext_'.$number]."|".$sValue;
				}
				@copy($val['tmp_name'],\Util::getDocumentRoot()."system/extensions/customer_db/".$sValue);
				@chmod(\Util::getDocumentRoot()."system/extensions/customer_db/".$sValue,0777);
			} else {
				unset($_SESSION[$key]);
				$key = str_replace("customer_db_","customer_db_save_",$key);
				unset($_SESSION[$key]);
				$session_data['error'] = "customer_db_file";
			}
			unset($_FILES[$key]);
	    }
	} else {
		$_SESSION['customer_db_'.$val]		= htmlspecialchars($_POST['customer_db_'.$val]);
		$_SESSION['customer_db_save_'.$val]	= htmlspecialchars($_POST['customer_db_'.$val]);
	}
}

// laden der Daten aus der Datenbank
// wenn user eingeloggt
// UND wenn Daten noch nicht hochgeladen

if($user_data["id"] AND $user_data["table"] AND $_SESSION["customer_db_1"]!=$user_data["email"]) {
    // lade Daten aus DB
    load_customer_data($user_data['table'],$user_data["id"]);
}

$template = $element_data["content"];
$objTemplate = new \Cms\Helper\Template($element_data["page_id"], $element_data["id"], $template,'','','');
?>

<form name="customer_db_data_form_<?=$Form_ID?>" enctype="multipart/form-data" method="POST" action="">
<input type="hidden" name="customer_returnurl" value="<?=$_SERVER['PHP_SELF']?>" />
<input type="hidden" name="customer_task" value="save" />

<?
global $parent_config;
$parent_config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$table_number=substr($parent_config->table_name,strrpos($parent_config->table_name,"_")+1,strlen($parent_config->table_name));

$db_config = DB::getQueryRow(("SELECT db_name, db_encode_pw FROM customer_db_config WHERE id = '".$table_number."'"));

$login    = \Cms\Service\PageParser::checkForBlock($template, 'LOGIN');
$message  = \Cms\Service\PageParser::checkForBlock($template, 'MESSAGE');

echo $objTemplate->block_pre;
$objTemplate->parse();
echo $objTemplate->block_post;

$template = \Cms\Service\PageParser::replaceBlock($template, 'LOGIN',   $login);
$template = \Cms\Service\PageParser::replaceBlock($template, 'MESSAGE', $message);

foreach($_POST as $key=>$val) {
    if ( isset($_SESSION[$key]) AND strpos($key,"customer_db_")===0 ) {
        $val = htmlspecialchars($val);
    }
}

echo "\n<input type=\"hidden\" name=\"table_number\" value=\"".$table_number."\">";
echo "</form>";

?>

<script>
function form_check_script_<?=$Form_ID?>()
{
<?
  if($Form_ID==1) 
  {
	  function jsToDoIfEmpty($sElement,$sPara="required_missing=1;") 
	  {
		  // Muss noch in der Administration eingestellt werden k?nnen, was passiert...
	    return "$sElement.style.backgroundColor='ffccaa';".$sPara."";
	  }
	  function jsToDoIfNotEmpty($sElement) 
	  {   // Muss noch in der Administration eingestellt werden k?nnen, was passiert...
	    return "$sElement.style.backgroundColor='ffffff';";
	  }
  }
                // in $aCDB_CheckFields stehen die default Werte!
                $count =                0;
                $sCheckscript = '';
                #var_dump($aCDB_CheckFieldTypes);
                if($aCDB_CheckFieldTypes) {
                    foreach($aCDB_CheckFieldTypes as $key => $value) {
                        switch ($value)
                        {
                            case "TEXT" :
                            {
                                $count++;
                                $sCheckscript .=    "if(document.customer_db_data_form_$Form_ID.$key.value == '' ".
                                                    "|| document.customer_db_data_form_$Form_ID.$key.value == '$aCDB_CheckFields[$key]')".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n ".
                                                    "else {".jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n";
                                break;
                            }
							case "PASSWORD" :
                            {
								//eklige lösung, aufbessern
                                $sql="SELECT required FROM customer_db_definition WHERE name = 'password' AND db_nr = '1' AND active = 1";
                                $aSQLTemp = DB::getQueryRow($sql);
                                if($aSQLTemp['required']){
                                	$sTemp=jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key");

                                }
								//eklige lösung ende

                                $count++;
                                $sCheckscript .=    "if(document.customer_db_data_form_$Form_ID.$key.value == '')".
													"{".$sTemp."}\n ".
                                                    "else if(document.customer_db_data_form_$Form_ID.".$key.".value != document.customer_db_data_form_$Form_ID.".$key."_check.value)".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.".$key."_check","password_check=1;")."}\n ".
                                                    "else {".jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.".$key."_check")."}\n";
                                break;
                            }
                            case "INTEGER" :
                            {
                                #if ( $aCDB_CheckFields[$key]=="" OR $aCDB_CheckFields[$key]==$$key OR !is_numeric($aCDB_CheckFields[$key])  )
                                $count++;
                                $sCheckscript .=    "if(document.customer_db_data_form_$Form_ID.$key.value == '' ".
                                                    "|| document.customer_db_data_form_$Form_ID.$key.value == '$aCDB_CheckFields[$key]'".
                                                    "|| isNaN(parseInt(document.customer_db_data_form_$Form_ID.$key.value)))".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key")."} else {\n ".
                                                    //"\ndocument.customer_db_data_form_$Form_ID.$key.value=parseInt(document.customer_db_data_form_$Form_ID.$key.value);\n".
                                                        jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n";
                                break;
                            }
                            {
                                #if ($aCDB_CheckFields[$key]=="" OR $aCDB_CheckFields[$key]==$$key)
                                $count++;
                                break;
                            }
                            case "image" :
                            {
                                $count++;
                                break;
                            }
                            case "Datei" :
                            {
                                #if ($aCDB_CheckFields[$key]=="" OR $aCDB_CheckFields[$key]==$$key)
                                #TODO!!!
                                $count++;
                                break;
                            }
                            case "ProtectedDatei" :
                            {
                                #if ($aCDB_CheckFields[$key]=="" OR $aCDB_CheckFields[$key]==$$key)
                                #TODO!!!
                                $count++;
                                break;
                            }
                            case "Checkbox" :
                            {
                                #if ($aCDB_CheckFields[$key]=="" OR $aCDB_CheckFields[$key]==$$key OR $aCDB_CheckFields[$key]!=1)
                                /*
                                $sCheckscript .=    "if(document.customer_db_data_form_$Form_ID.$key.value.checked == 'checked')".
                                                    "{alert(\"y\");".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key.value")."}\n ".
                                                    "else {alert(\"x\");".jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.$key.value")."}\n";
                                */
                                $sCheckscript .=    "if(!document.customer_db_data_form_$Form_ID.$key.checked)".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n ".
                                                    "else {".jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n";#document.customer_db_data_form_$Form_ID.$key.style.backgroundColor='cccccc';required_missing=1;
                                                    
                                
                                
                                #$sCheckscript .=    "alert(document.customer_db_data_form_$Form_ID.$key.checked);alert(\"zblag!\");\n";#customer_db_data_form_$Form_ID.
                                $count++;
                                break;
                            }
                            case "Select Field" :
                                $sCheckscript .=    "if(document.customer_db_data_form_".$Form_ID.".".$key.".options[document.customer_db_data_form_".$Form_ID.".".$key.".selectedIndex].value == 0)".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_".$Form_ID.".".$key."")."}\n ".
                                                    "else {".jsToDoIfNotEmpty("document.customer_db_data_form_".$Form_ID.".".$key."")."}\n";
                                $count++;
                                break;
                            case "Kategoriefeld" :
                                $sCheckscript .=    "if(document.customer_db_data_form_$Form_ID.$key.selectedIndex == 1)".
                                                    "{".jsToDoIfEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n ".
                                                    "else {".jsToDoIfNotEmpty("document.customer_db_data_form_$Form_ID.$key")."}\n";
                                $count++;
                                break;
                            default :
                                #echo "Feld nicht erkannt!";
                                break;
                        }
                    }
                }

            ?>
  required_missing	= 0;
	password_check		= 0;
	
	

	<?=$sCheckscript?>

<?

$parent_config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(!$parent_config->error_password) {
	$parent_config->error_password = "Die Passwörter stimmen nicht überein. Bitte kontrollieren Sie Ihre Angaben! ";
}
if(!$parent_config->error_required) {
	$parent_config->error_required = "Die nötigen Pflichtfelder wurden nicht vollständig ausgefüllt! Bitte füllen Sie diese nun aus! ";
}

?>

  if( required_missing > 0 ) {
    // setze alle fehlenden auf neuen Style!
    alert("<?=$parent_config->error_required?>");
    return 0;
	}
  else if( password_check > 0 ) {
    alert("<?=$parent_config->error_password?>");
    return 0;
  }
  else {
    return 1;
  }
}
</script>