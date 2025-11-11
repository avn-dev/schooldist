<?
// customer_db_data
// Mini Modul um eine Seite zur�ck zu bl�ttern

global $shop_inc;


if($SHOP_DEBUG) echo "<br>----SHOP_PAGE_BACK.MOD----<br>";


/*Die "SHOP-VARS-Stripties-Zeile" ;-) */global $SHOP_DEBUG; if($SHOP_DEBUG) {echo "<table>";$vars = get_defined_vars();foreach($vars as $key=>$val){echo "<tr><td valign=top><b>$key</b></TD><Td><pre>";print_r($$key);echo"</pre></td></tr>\n";}echo "</table>";}

// Einstellungen aus der separaten Konfigurationstabelle holen

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

global $aLoginMod;
$parent_config = new \Cms\Helper\ExtensionConfig($aLoginMod['page_id'], $aLoginMod['element_id'], $aLoginMod['content_id'], $aLoginMod['language']);

$template = $element_data['template'];

// Bedingte Verzweigungen in Scriptform bringen:
global $SHOP_ORDER_FIELD_CLASSES;

for($j=1; $j<4; $j++){
   $var = 'con_data_field_'.$j; $cond_field = $config->$var;
   $var = 'condition'.$j;       $cond_value = (int)$config->$var-1;
   $var = 'back_con_url_'.$j;   $cond_url   = htmlspecialchars($config->$var);
                                $cond_class = $SHOP_ORDER_FIELD_CLASSES[$cond_field];
   if(($cond_class == 'select' || $cond_class =='checkbox' || $cond_class == 'vradio'|| $cond_class == 'hradio')&& $cond_url&& $cond_value>-1){
      switch($cond_class){
         case 'select':
            $condition[$j] =  "if(document.customer_db_data_form.SHOP_CUSTOMER_$cond_field.selectedIndex == '$cond_value') ";
            break;
         case 'checkbox':
            $condition[$j] =  "if(document.customer_db_data_form.SHOP_CUSTOMER_$cond_field.checked == '$cond_value') ";
            break;
         case 'vradio':
         case 'hradio':
            $condition[$j] =  "if(document.customer_db_data_form.SHOP_CUSTOMER_$cond_field"."[$cond_value].checked == true) ";
            break;
      }
   $condition[$j] .= "url = '$cond_url';";//"alert ('TREFFER: $j'); else alert('$j nicht');";
   }
}

if(is_numeric($config->url)) {
	$oPage = Cms\Entity\Page::getInstance(intval($config->url));
	$config->url = $oPage->getLink($page_data['language']);
}

// Script, dass �berpr�ft, wohin gesprungen werden soll:
$scriptname = "checkBackURL_".rand();


// URL muss aus der Haupt-config stammen!!
// genauso button-look!
?>
<script>
  function <?=$scriptname?>(){
     var url ='<?=$config->url;?>';
     <?=$condition[1];?>
     <?=$condition[2];?>
     <?=$condition[3];?>
     return url;
  }
</script>
<?

// Links einf�gen


// diverse global Variablen :
global $Form_ID;
global $aCDB_CheckFields;
global $aCDB_CheckFieldTypes;

if ($template) {
	$template = str_replace('<#LINK_ANFANG#>',"<a href=\"javascript:goWithoutCheck($scriptname());\">", $template);
	$template = str_replace('<#LINK_ENDE#>',  "</a>",                                                $template);
} else {

    $destination = $config->url;

    if ($config->button_look == "Button") {
		$template = "<input type=\"button\" value=\"".$config->button_name."\" ".$config->tagatt." name=\"$config->button_name\" onClick=\"if(form_check_script_$Form_ID()){document.customer_db_data_form_$Form_ID.action = '$destination';document.customer_db_data_form_$Form_ID.submit();}\">";
  	} elseif ($config->button_look == "image") {
  		if(strpos($config->button_image, "imgbuilder") !== false) {
  			$strImage = $config->button_image;
  		} else {
  			$strImage = "/media/".$config->button_image;
  		}
  		$template = "<input type=\"image\" src=\"".$strImage."\" value=\"$config->button_name\" ".$config->tagatt." name=\"$config->button_name\" onClick=\"if(form_check_script_$Form_ID()){document.customer_db_data_form_$Form_ID.action = '$destination';document.customer_db_data_form_$Form_ID.submit();} else { return false; }\" border=\"0\">";
	}

}

echo $template;

?>