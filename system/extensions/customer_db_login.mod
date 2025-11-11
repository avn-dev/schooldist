<?
//////////////////////////////////////////////////////
// Hauptmodul f�r die customer-login Funktionalit�t //
//////////////////////////////////////////////////////


global $aLoginMod;
global $_VARS;
/*
var_dump($aLoginMod);
var_dump($_POST);
var_dump($_VARS);
*/

// Auslesen, bzw erzeugen der Form_ID ( global! )
// Diese ID ist n�tig, falls mehrere gleiche Formulare auf
// einer Seite sind
global $Form_ID;

if($Form_ID)
{$Form_ID++;}
else
{$Form_ID=1;}


//$template = $my_element["content"];
//$template = new template_class($page_id, $element, $template,'','','');// 'begin_loop', 'end_loop', repeat_block);
$template = $element_data["content"];
$template = new \Cms\Helper\Template($element_data["page_id"], $element_data["id"], $template,'','','');

global $parent_config;
//$parent_config = new \Cms\Helper\ExtensionConfig($page_id, $element);
$parent_config = new \Cms\Helper\ExtensionConfig($element_data["page_id"], $element_data['id']);

//var_dump($parent_config);

if($_VARS["logintarget"])
{
    $destination_url=$_VARS["logintarget"];
}
else
{
    $destination_url=$parent_config->url;
}

if($_VARS['loginfailed'])
{
    echo $_VARS['loginfailed'];
}


                                                                                //
echo "<form name=\"customer_db_login_form_$Form_ID\" method=\"POST\" action=\"".$destination_url."\">";

global $db_data;

//echo "<br>".$db_data['module'].",$config->table_name,$customer_login_0,$customer_login_1)<br>";

if ($_POST["customer_login_0"] OR $_POST["customer_login_1"])
{
    if (check_customer_password($db_data['module'],$parent_config->table_name,$customer_login_0,$customer_login_1))
    {
        echo stripslashes($parent_config->success_msg)."<br>";
        //echo "Jetzt nach $config->url weiterleiten!<br>";
?>
<script>
  document.customer_db_login_form_<?=$Form_ID?>.action="<?=$parent_config->url?>";
  document.customer_db_login_form_<?=$Form_ID?>.submit();
  
</script>
<?

    }
    else
    {
        echo stripslashes($parent_config->fail_msg)."<br>";
    }
}

/*
$aLoginMod["page_id"]=$page_id;
$aLoginMod["element_id"]=$element;
*/

$login    = \Cms\Service\PageParser::checkForBlock($template, 'LOGIN');
$message  = \Cms\Service\PageParser::checkForBlock($template, 'MESSAGE');


echo $template->block_pre;
$template->parse();
echo $template->block_post;

$template = \Cms\Service\PageParser::replaceBlock($template, 'LOGIN',   $login);
$template = \Cms\Service\PageParser::replaceBlock($template, 'MESSAGE', $message);

//echo $template;


//echo "\n<input type='hidden' name = 'parent_page_id' value = '$page_id'>";
//echo "<br>parent_page_id : ".$page_id;


// globales array absuchen
// hidden fields erstellen
// $_POST benutzen
// global $fields_array;

/*
foreach($_POST as $key=>$val)
{
  if (!isset($fields_array[$key]))
  {
    $val = htmlspecialchars($val);
  	echo "\n<input type='hidden' name = '$key' value = '$val'>";
  	$key="";
  	$val="";
  }
}
*/

//var_dump($aLoginMod);

$pos=strrpos($parent_config->table_name,"_")+1;
$table_number = substr ( $parent_config->table_name , $pos , strlen($parent_config->table_name) );
//$table_number = substr($parent_config->table_name,12,13);
echo "\n<input type='hidden' name='destination_url' value='".$parent_config->url."'>";
echo "\n<input type='hidden' name='table_number' value='".$table_number."'>";
echo "\n<input type='hidden' name='loginmodul' value='1'>";


echo "</form>";

// echo "<br>###login script ende###<br>";
?>