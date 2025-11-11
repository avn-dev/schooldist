<?php

////////////////////////////////////////////////////
/////////////// Modul Eingabefeld //////////////////
////////////////////////////////////////////////////

// Dieses Modul erzeugt ein generisches Eingabefeld
// dessen Daten danach in ein zugeh�riges
// Datenbankfeld geschrieben werden ( mittels
// der Klasse shop_config

////////////////////////////////////////////////////

global $db_data;
$db_module=$db_data[module];
global $DOCUMENT_ROOT;
//global $parent_element_id;
//global $parent_page_id;
//echo "<br>parent_element_id : ".$parent_element_id;
//echo "<br>parent_page_id : ".$parent_page_id;
//echo"<br>config_class($page_id, $element_id)<br>";
global $db_class_is_declared;
if (!$db_class_is_declared)
{
  //include ($DOCUMENT_ROOT."admin/includes/main.inc.php");
  require ($DOCUMENT_ROOT."admin/extensions/customer_db/customer_db_class.inc.php");
}

global $aLoginMod;
//var_dump($aLoginMod);

//echo "<br>config_class($aLoginMod[page_id], $aLoginMod[element_id])<br>";

//$parent_config = new config_class($aLoginMod[page_id], $aLoginMod[element_id]);

global $parent_config;
//var_dump($parent_config);


$config = new config_class($page_id, $element);

//echo"<br><br>";
//var_dump($parent_config);
//echo"<br><br>";
//if (!$SQL_handler)
$SQL_handler = new customer_db;

$table_name=$parent_config->table_name;

$pos=strrpos($table_name,"_");
$identifier=substr($table_name,0,$pos+1);
$table_number=substr($table_name,$pos+1,strlen($table_name));
$aLoginMod[table_number]=$table_number;

$definition_table=$identifier."definition";
$value_table=$identifier."values";

// field_type laden
//echo "<br>SQL_get_row_by_name($db_module, $definition_table, $table_name, $config->field_name)<br>";

$option=$config->field_name;
$option_name="field_nr";

$SQL_handler->SQL_get_row_by_option($db_module, $definition_table, $table_name,$option_name,$option );
$result=$SQL_handler->result[1];
$field_type=$result[0]["type"];


//echo "<br><br>";
//var_dump($result);
//echo "<br><br>field_type: ".$field_type."<br>";


// neuen Wert in hiddenfield schreiben

switch($field_type)
{
 case "TEXT":
     echo "<input class=txt type=text name=\"customer_login_$config->field_name\" value=\"$config->default_value\" style= 'width=$config->width px'>";
     break;


 case "PASSWORD":
    echo "<input class=txt type=password name=\"customer_login_$config->field_name\" style= 'width=$config->width px'>";
 	break;

 default :
    echo "unknown field type in Database!";
} // Ende switch-case

//echo "<input type=hidden name=\"$config->field_name\" value=\"".$$config->field_name."\">";
//echo "<br>eingabefeld script ende<br>";

?>