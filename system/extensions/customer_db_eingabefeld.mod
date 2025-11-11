<?

////////////////////////////////////////////////////
/////////////// Modul Eingabefeld //////////////////
////////////////////////////////////////////////////

// Dieses Modul erzeugt ein generisches Eingabefeld
// dessen Daten danach in ein zugeh�riges
// Datenbankfeld geschrieben werden ( mittels
// der Klasse shop_config

////////////////////////////////////////////////////

global $db_data,$user_data;
$db_module = $db_data['module'];

global $_VARS;
global $Form_ID;
global $iCDB_ElementID;
$iCDB_ElementID = intval($iCDB_ElementID)+1;

global $db_class_is_declared;
if (!$db_class_is_declared) {
  	require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
	if(is_file(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/tree_db_functions.inc.php")) {
		require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/tree_db_functions.inc.php"); // F�r's Kat-feld
	}
}

global $parent_config;

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$table_name = $parent_config->table_name;

$pos=strrpos($table_name,"_");
$identifier=substr($table_name,0,$pos+1);
$table_number=substr($table_name,$pos+1,strlen($table_name));
$definition_table=$identifier."definition";
$value_table=$identifier."values";
$tree_table="tree_db_1";

$arrFieldData = getCustomerFieldData($config->field_name,intval($table_number));

$field_type = $arrFieldData["type"];
$field_id	= $arrFieldData["id"];
$required	= $arrFieldData["required"];

$sValue = false;

if(is_numeric($config->field_name)) {
	$sValue = $user_data['data']['ext_'.$config->field_name];
} else {
	$sValue = $user_data['data'][$config->field_name];
}

$tmp= "customer_db_".addslashes($config->field_name);
$tmp=str_replace(" ","_",$tmp);

if(isset($_SESSION[$tmp])) {
	$default	= $_SESSION[$tmp];
} else {
    $selector	= $config->selected_option;
    $default	= $config->default_value;
}

$config->field_name = addslashes($config->field_name);

$sType					= $field_type;
$idForm 				= $Form_ID;
$idElement 				= $iCDB_ElementID;
$idField				= $field_id;
$sFieldName				= $config->field_name;
$aConfig				= $config;
$aConfig->idDatabase 	= $table_number;
$aConfig->additional 	= unserialize($arrFieldData['additional']);

if($sValue == "")
{
  if(isset($_VARS['customer_db_'.$config->field_name]))
  {
	  $sValue = $_VARS['customer_db_'.$config->field_name];
  }
  else
  {
	  $sValue = $default;
  }
}

echo getFieldInput($sType, $idForm, $idElement, $idField, $sFieldName, $sValue, $aConfig, $field_type);

global $aCDB_CheckFields;
global $aCDB_CheckFieldTypes;

if($aConfig->check_passwd == 1) {
    // erzeuge Javascript Schnipsel!
    $aCDB_CheckFields["customer_db_".$config->field_name.""] = $config->default_value;
    $aCDB_CheckFieldTypes["customer_db_".$config->field_name.""] = $field_type;
}

if($required) {
    // erzeuge Javascript Schnipsel!
    $aCDB_CheckFields["customer_db_".$config->field_name.""] = $config->default_value;
    $aCDB_CheckFieldTypes["customer_db_".$config->field_name.""] = $field_type;
}

?>
