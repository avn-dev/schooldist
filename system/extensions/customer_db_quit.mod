<?

echo "zblag";

echo "<br><br>";
var_dump($_SESSION);





global $parent_config;
global $Form_ID;
global $aCDB_CheckFields;
global $aCDB_CheckFieldTypes;
global $shop_inc;

global $db_config;
global $Form_ID;
global $parent_config;
global $_VARS;


global $page_data;
global $element_data;


echo "<br>element_data : ";
var_dump($element_data);

echo "<br>page_data : ";
var_dump($page_data);
/**/




global $db_class_is_declared;
if (!$db_class_is_declared) {
    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
}

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared) {
    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}

$SQL_handler = new customer_db;
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);

echo "<br><hr>HIER:";
var_dump($config);
echo "<hr><br>";
#die("tue sterben!");







/*
global $user_data;

global $db_config;
global $Form_ID;
global $parent_config;
global $_VARS;

global $db_class_is_declared;
if (!$db_class_is_declared) {
    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
}

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared) {
    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}

#$SQL_handler = new customer_db;
// Konfiguration laden
global $element_data;
#$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);

*/
// erstelle richtigen Tabellen Namen
$pos = strrpos($parent_config->table_name,"_");
$identifier = substr($parent_config->table_name,0,$pos+1);
$table_name="customer_db_".intval($identifier);





$user_data['id']=61;
$_SESSION['customer_db_email']="huebner@plan-i.de";



if(1)# OR intval($user_data['id'])>0)#intval($_REQUEST['customer_db_78'])==1 AND 
{
	var_dump($config);
	echo "<br><br>";
	
    $new_id=intval($user_data['id']);

    #$result=db_query()
    $reciever=$_SESSION['customer_db_email'];

    #if(!$reciever) die("Kein Empf�nger!");
    $reciever="huebner@plan-i.de";


    // Abschnitt f�r die Aktivierungs-Mail

    $activation_key=rand(1000000, 9999999);


    // Query: De-Activation vorbereiten!
    db_query("INSERT INTO customer_db_activation SET id_user=".intval($new_id).", activation_key=$activation_key");


	//Hier die m�glichen Tags ersetzen
	
    $key= rand(100000, 999999); # einfach 6 Zahlen

    $query="INSERT INTO `customer_db_activation` (`activation_key`,`id_user`) VALUES ('$key','$idEntry')";
    db_query($query);

    $link="http://".$_SERVER['SERVER_NAME'].$config->email_link_destination."?task=deactivate&activation_key=$key&id_user=$idEntry";


	$config->body = str_replace("<#nickname#>", $aData["nickname"],$config->body);
	$config->body = str_replace("<#email#>", $aData["email"],$config->body);
	$config->body = str_replace("<#password#>", $sPlainPw, $config->body);
	$config->body = str_replace("<#link#>", $link,$config->body);


	// Ersetze m�gliche ext-Tags
    $counter=0; # Notfall-Abbruch-Bedingung
	while(strpos($config->body, "<#ext_")!==FALSE)
	{
	    $parts=explode("<#ext_",$config->body);
	    $number=intval($parts[1]);
        $config->body = str_replace("<#ext_$number#>", $aData["ext_$number"],$config->body);

        $counter++;
        if($counter>250) {break;}
    }

	echo "<br><br>";
    echo "wdmail(".$_SESSION['customer_db_email'].", $config->subject.$counter, $config->body, $config->from);";

	echo "<br><br>".$config->config_sent."<br><br>##zblag123";

/*
    if($_SESSION['customer_db_13']==2) $anrede="geehrte Frau";
    elseif($_SESSION['customer_db_13']==1) $anrede="geehrter Herr";

    $body.="Sehr ".$anrede." ".$_SESSION['customer_db_16'];
    $body.=" ".$_SESSION['customer_db_14']." ".$_SESSION['customer_db_15'].",";

    $body.="\nF�r Ihren Account wurde eine K�ndigung angefordert.";

    $body.="\n\nUm Ihren K�ndigung zu verifizieren, klicken Sie bitte auf diesen Link:";
    $body.="\nwww.gbg24.de/german/aktivierungs_seite.html?task=deactivate&activation_key=".$activation_key."&id_user=$new_id";

    $body.="\n\nSollten Sie gar nicht k�ndigen wollen, brauchen Sie nichts weiter zu tun, um Ihren Eintrag beizubehalten.";

    $body.="\n\nHave a nice day.\nIhr GBG-Team";


    $subject="K�ndigung beim German Business Guide";



    #echo "<br><br>Verschicke K�ndigungs-mail!";  \"German Business Guide\" <Rechnung@germanbusinessguide.com>
    wdmail($reciever, $subject, $body,"From: \"German Business Guide\" <Kuendigung@germanbusinessguide.com>\r\nReply-To: Kuendigung@germanbusinessguide.com\r\nBCC:huebner@plan-i.de\r\n"); # ."CC: pk@germanbusinessguide.de\r\n"
*/

}
elseif(intval($_REQUEST['customer_db_78'])!=1 AND intval($user_data['id'])>0)
{
    echo "Zum Kündigen müssen Sie die Checkbox aktiviert haben.<br><br>";
    echo "<button class='btn' OnClick='document.location.href=\"/german/my_gbg/kundigen.html\"'>&laquo; Zur�ck</button>";
}
else
{
    echo "Keine Status�nderung möglich.<br>Ihr GBG-Team";
}



?>
