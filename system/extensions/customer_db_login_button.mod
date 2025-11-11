<?
// customer_db_login_button
// Submodul, um die Login form abzuschicken

global $Form_ID;


global $shop_inc;
/*
if(!$shop_inc) {
        include($DOCUMENT_ROOT."/system/includes/main.inc.php");
	include($DOCUMENT_ROOT.'/system/extensions/shop/shop.inc');

	$shop_inc = 1;

}

*/
if($SHOP_DEBUG) echo "<br>----SHOP_PAGE_BACK.MOD----<br>";


/*Die "SHOP-VARS-Stripties-Zeile" ;-) */global $SHOP_DEBUG; if($SHOP_DEBUG) {echo "<table>";$vars = get_defined_vars();foreach($vars as $key=>$val){echo "<tr><td valign=top><b>$key</b></TD><Td><pre>";print_r($$key);echo"</pre></td></tr>\n";}echo "</table>";}

// Einstellungen aus der separaten Konfigurationstabelle holen


/*
? >
<form name="customer_db_login_button_form" method=post enctype="multipart/form-data" action="< ?=$PHP_SELF? >">

<input type='hidden' name="page_id" value=< ?=$page_id? >>
<input type='hidden' name="element_id" value=< ?=$element_id? >>
< ?
*/


//echo " config_class($page_id, $element);";


$config = new config_class($page_id, $element);

//global $aLoginMod;

//var_dump($aLoginMod);

//$aLoginMod[url]=$parent_config->url;
global $parent_config; //= new config_class($aLoginMod[page_id], $aLoginMod[element_id]);

#DEBUG var_dump( $aLoginMod);
#DEBUG echo "<br>##########<br><br>";
//var_dump($parent_config);



$template = $element_data[template];
//echo "<br>template : $template<br>config: <br>";
//var_dump($config);
//echo "<br>config_class($page_id, $element)<br>";

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


// Script, dass überprüft, wohin gesprungen werden soll:
$scriptname = "checkBackURL_".rand();


// URL muss aus der Haupt-config stammen!!
// genauso button-look!
?>
<script>
  function <?=$scriptname?>(){
     var url ='<?=$parent_config->url;?>';
     <?=$condition[1];?>
     <?=$condition[2];?>
     <?=$condition[3];?>
     return url;
  }
</script>
<?

// Links einfügen

if ($template)
{
$template = str_replace('<#LINK_ANFANG#>',"<a href=\"javascript:goWithoutCheck($scriptname());\">", $template);
$template = str_replace('<#LINK_ENDE#>',  "</a>",                                                $template);
}
else
{   echo "\n";
  	if ($config->button_look=="Button")                                                                             //OnClick=\"document.customer_db_login_form_$Form_ID.submit()\"
  	{                                                                                                                          //document.customer_db_login_form_$Form_ID.action=$destination_url;
        $template="<input class=\"btn\" type=\"submit\" value=\"$config->button_name\" name=\"$config->button_name\">";
  	}
	else
	{
  		$template="<a href=$PHP_SELF>$config->button_name</a>"; // \"javascript:goWithoutCheck($scriptname());\"
	}
    echo "\n";
  
}

//var_dump($config);



echo $template;

//echo "parent_login_page_id $parent_login_page_id";

//echo "<br>### login BUTTON script ende ###<br>";

?>