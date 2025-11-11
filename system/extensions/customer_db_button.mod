<?
/////////////////////////////////////////////////////////////////////
// "customer_db_data" - Submodul
// Button, der auf eine weitere Seite weiterleitet
// mit der Option, Pflichtfelder zu prüfen
/////////////////////////////////////////////////////////////////////

global $parent_config;
global $SESSION;
global $Form_ID;
global $aCDB_CheckFields;
global $aCDB_CheckFieldTypes;
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
<form name="customer_db_login_button_form" method=post enctype="multipart/form-data" action="<?=$PHP_SELF? >">

<input type='hidden' name="page_id" value=<?=$page_id? >>
<input type='hidden' name="element_id" value=<?=$element_id? >>
< ?
*/

/*
echo "<br>element_data : ";
var_dump($element_data);

echo "<br>page_data : ";
var_dump($page_data);
*/

//echo "<br>new config_class($page_id, ".$element_data["number"].");<br>";
$config = new config_class($page_id, $element_data["number"]);

/*
echo "<br><hr>HIER:";
var_dump($config);
echo "<hr><br>";
*/

//$config = new config_class($page_id, $element_id);

//var_dump($config);


//$aLoginMod[url]=$parent_config->url;

/*
global $aLoginMod;
$parent_config = new config_class($aLoginMod[page_id], $aLoginMod[element_id]);
*/


#DEBUG var_dump( $aLoginMod);
#DEBUG echo "<br>##########<br><br>";
//var_dump($parent_config);



$template = $element_data[template];

/*
echo "<br>parent_config: <br>";
var_dump($parent_config);
*/
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
     var url ='<?=$config->url;?>';
     <?=$condition[1];?>
     <?=$condition[2];?>
     <?=$condition[3];?>
     return url;
  }
</script>



<?

    // if(pflichtfelderprüfen)
    // {
    //   prüfe Pflichtfelder, ob nicht leer oder defaulwert
    //   if(!OK)
    //     {
    //       alert();
    //     }
    //     else
    //     {
    //        document.customer_db_data_form.action=destination;
    //        document.customer_db_data_form.submit();
    //     }
    //   }
    //   else
    //   {
    //        document.customer_db_data_form.action=destination;
    //        document.customer_db_data_form.submit();
    //   }



    // else





// Links einfügen

if ($template)
{
$template = str_replace('<#LINK_ANFANG#>',"<a href=\"javascript:goWithoutCheck($scriptname());\">", $template);
$template = str_replace('<#LINK_ENDE#>',  "</a>",                                                $template);
}
else
{



    $destination=$config->url;
                                                                                                                         //Button_got_clicked($destination)
  	if ($config->button_look=="Button") {
		if($config->check_mandatory==0) {
			$template="<input class=\"btn\" type='button' value=\"$config->button_name\" name=\"$config->button_name\" OnClick='document.customer_db_data_form_$Form_ID.action=\"$destination\";document.customer_db_data_form_$Form_ID.submit();'>";
		} else {
	        $template="<input class=\"btn\" type='button' value=\"$config->button_name\" name=\"$config->button_name\" OnCLick='if(form_check_script_$Form_ID()){document.customer_db_data_form_$Form_ID.action=\"$destination\";document.customer_db_data_form_$Form_ID.submit();}'>";
		}
  	} else {
  		$template="<a href=$destination>$config->button_name</a>"; // \"javascript:goWithoutCheck($scriptname());\"
	}

  
}

//var_dump($config);



echo $template;

//echo "parent_login_page_id $parent_login_page_id";

//echo "<br>button script ende<br>";
?>
