<?php

// Speichern Modul der customer_db_
// Pr�ft, ob ein Eintrag zu entsprechendem Login vorliegt, und wenn, pr�ft, ob dieser
// g�ltig eingelogt ist.

#die("STIRB!!");
/**/



// Dirty hack!
unset($_SESSION['customer_db_save_FLAG_UPGRADE']);



global $user_data,$db_config;
global $Form_ID;
global $parent_config;

global $db_class_is_declared;
if (!$db_class_is_declared)
{
    require (\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");
}

global $customer_db_functions_is_declared;
if(!$customer_db_functions_is_declared)
{
    include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php");
}




$this_site="www.gbg24.de";

$SQL_handler = new customer_db;
$config = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);

$pos=strrpos($config->table_name,"_");
$identifier=substr($config->table_name,0,$pos+1);
$table_number=substr($config->table_name,$pos+1,strlen($config->table_name));
$definition_table=$identifier."definition";
$value_table=$identifier."values";
$tree_table="tree_db_1";


/*
echo "<br><br>Session:<br>";
var_dump($_SESSION);

var_dump($user_data);
echo "u_f: ".$_SESSION["customer_db_FLAG_UPGRADE"]."!";
if($_SESSION["customer_db_FLAG_UPGRADE"]=="1") echo "<br>ZBLAG!!";


die();
*/

// Erzeuge Auswertungsmatrix:

if($user_data['login']) $bLogin=TRUE;
else $bLogin=FALSE;

if($_SESSION["customer_db_FLAG_UPGRADE"]=="1") $bUpgrade=TRUE;
else $bUpgrade=FALSE;

if(intval($_SESSION["customer_db_36"])>0) $bPaket=TRUE;
else $bPaket=FALSE;



#echo "<br>Matrix:<br> L : $bLogin | U: $bUpgrade | P: $bPaket<br>";


if(($bLogin AND $bUpgrade AND $bPaket) OR  ( !$bLogin AND !$bUpgrade AND $bPaket ) )
$Status="send_mail";

elseif($bLogin AND !$bUpgrade AND !$bPaket)
$Status="save";
else
die("Fehlerhafte Daten beim Speichern!");

#die($Status);




function new_costs_mail($reciever,$new_costs,$cause,$offener_betrag)
{
    if(!$reciever)
    $reciever="pk@germanbusinessguide.com";
    $subject="Rechnung - German Business Guide";

    $complete_costs=$new_costs+$offener_betrag;

    #echo "<br><br>neue Mail an user wg neuer Kosten:<br>";
    $body= "Neue Kosten : $new_costs EUR\n";
    $body.= "Für : $cause\n";
    $body.= "Ausserdem noch unbezahlt : $offener_betrag EUR\n";
    $body.= "Summe offener Beträge : $complete_costs EUR\n";
    $body.= "\n\nHave a nice day.";



    wdmail($reciever,$subject,$body,"From: \"German Business Guide\" <Rechnung@GermanBusinessGuide.com>\r\nReply-To: Rechnung@GermanBusinessGuide.com\r\nBCC:huebner@plan-i.de\r\n");

}


function add_to_current_data($id,&$aKeys,&$aValues)
{
    if(!intval($id))
    {
        echo ("invalid ID - please reload");
        return -1;
    }
    else
    {
#    echo $id."<br>";
#    echo "<br>keys:<br>";
#    var_dump($aKeys);
#    echo "<br><br>vals:<br>";
#    var_dump($aValues);



    if(is_array($aKeys))
    foreach($aKeys as $key => $value)
    {
        if($value=="ext_35")
        {
            $old_data=get_data(db_query("SELECT ext_35 as anzahl, ext_62 as offener_betrag FROM customer_db_1 WHERE id=$id"));

            #echo "neu: ".intval($aValues[$key])." | alt: ".intval($old_data['anzahl']);

            $new_booked=intval($aValues[$key]);

            if($new_booked==10000)
                $new_costs=150;
            elseif($new_booked==5000)
                $new_costs=85;
            elseif($new_booked==1000)
                $new_costs=20;
            else
                $new_costs=0;

            $aValues[$key]=$new_booked+intval($old_data['anzahl']);

            $aValues[]=$new_costs+floatval($old_data['offener_betrag']);
            $aKeys[]="ext_62";

            #echo "neu: ".$new_costs." | alt: ".$old_data['offener_betrag'];

            new_costs_mail($old_data['email'],$new_costs,"Buchung von weiteren ".$new_booked." St�ck Bannerwerbung" ,$old_data['offener_betrag']);

            #echo "<br>zu speichern : ".$aValues[$key];
        }
    }

/*
    echo "<br><br><br>keys danach:<br>";
    var_dump($aKeys);
    echo "<br><br>vals danach:<br>";
    var_dump($aValues);
    die("nich gespeichert!");
 */

    }
}



$i=0;
if($_SESSION)
{
	foreach ($_SESSION as $key => $value)
	{
	    // pr�fen, ob variable zu dem Bereich customer_db_ geh�ren
	    if(strpos($key,"customer_db_save_")===0)
	    {
	        if($key=="customer_db_save_email")
	        {
	            $aKeys[$i]="email";
	            $aValues[$i]=$value;
	        #}
	        #elseif($key=="customer_db_save_nickname")
	        #{
	            $i++;
	            $aKeys[$i]="nickname";
	            $aValues[$i]=$value;
	        }
	        elseif($key=="customer_db_save_password" AND strlen($value)<32)
	        {
	            $aKeys[$i]="password";
				if($db_config['db_encode_pw']) {
		            $aValues[$i] = md5($value);
				} else {
		            $aValues[$i] = $value;
				}
	        }
	        elseif($key=="customer_db_save_password" AND !strlen($value)<32)
	        {
	            $aKeys[$i]="password";
	            $aValues[$i]=$value;
	        }
	        else
	        {
	            $loc_number=substr($key,strrpos($key,"_")+1,strlen($key));


	            // Nicht erneut hochgeladene Bilder != Bild l�schen
	            if($loc_number==38 OR $loc_number==39 OR $loc_number==33 )
	            {
                    if($value=='') continue;
                }


	            $aKeys[$i]="ext_".$loc_number;
	            $aValues[$i]=$value;

	            // semi-Dirty-Hack
	            if($loc_number==56)
	            {
                    $FLAG_kat=$value;
                }

	            if($loc_number==57)
	            {
                    $FLAG_banner=$value;
                    if($_REQUEST['FLAG_banner_any']=="on" OR $_SESSION['customer_db_57']=='on') $FLAG_banner="|ANY|";
                }


	        }
			#unset($_SESSION[$key]);
	        $i++;
	    }
	}
}


foreach($aKeys as $key => $value)
{
    $ZBLAG[$value]=$aValues[$key];
}



if($_SESSION AND $user_data['login'])
{
    // speichere alle Daten
    if($aKeys)
    {
        add_to_current_data($user_data['data']['id'],$aKeys,$aValues);


        save_customer_data($ZBLAG,0);
    }
    $new_id=$user_data['data']['id'];

}
elseif($_SESSION)
{
    // pr�fe, ob nick oder mail bereits in DB vorhanden
	$user_data['table'] = $parent_config->table_name;

#	#if($_SESSION['customer_db_email'] && !$_SESSION['customer_db_nickname']) {
#		$_SESSION['customer_db_nickname'] = $_SESSION['customer_db_email'];
#		#$_SESSION['customer_db_save_nickname'] = $_SESSION['customer_db_email'];
#	}
#	if($_SESSION['customer_db_nickname'] && !$_SESSION['customer_db_email']) {
#		$_SESSION['customer_db_email'] = $_SESSION['customer_db_nickname'];
#		$_SESSION['customer_db_save_email'] = $_SESSION['customer_db_nickname'];
#	}

    // Erzeuge query auf nick
    $mail_allowed=check_unique($_SESSION['customer_db_email'],	"email",	$parent_config->table_name);
    // erzeuge query auf mail
    $nick_allowed=TRUE;#check_unique($_SESSION['customer_db_nickname'],"nickname",	$parent_config->table_name);

    if($mail_allowed AND $nick_allowed)
    {
		if(!$_SESSION['customer_db_save_password']) {
			$_SESSION['customer_db_save_password'] = \Util::generateRandomString(6);
		}
        //speichere die Daten
        $new_id=save_customer_data($ZBLAG,1);
/*
        echo "<br><br>neue ID: ".$new_id."<br><br>";
        var_dump($aKeys);
        echo "<br>";echo "<br>";echo "<br>";
        var_dump($aValues);
        echo "<br>";echo "<br>";echo "<br>";
        echo mysql_error();
*/
    } else {
        if(!$mail_allowed)
        {
            echo "<br>Die Mail-Adresse ".$_SESSION['customer_db_email']." ist bereits angemeldet!<br>";
        }
        if(!$nick_allowed)
        {
            echo "<br>Der Nickname ".$_SESSION['customer_db_nickname']." ist bereits vergeben!<br>";
        }
    }

}



if(intval($new_id)>0)# AND $FLAG_kat)
{
    $kat_array=explode("|",$FLAG_kat);

    db_query("DELETE FROM Kat2User WHERE id_user=".$new_id);


    foreach($kat_array as $key => $value)
    {
        if($value=="") continue;

        $query="INSERT INTO Kat2User SET id_user=$new_id, id_kat=$value";
        #echo $query."<br>";
        db_query($query);
    }



    $banner_array=explode("|",$FLAG_banner);

    db_query("DELETE FROM BannerKats WHERE id_user=".$new_id);


    foreach($banner_array as $key => $value)
    {
        if($value=="") continue;

        $query="INSERT INTO BannerKats SET id_user='$new_id', id_kat='$value'";
        #echo $query."<br>";
        db_query($query);
    }

    #die($FLAG_banner);

}


#var_dump($_SESSION);

if($Status=="send_mail" AND intval($new_id)>0)
{
    $reciever=$_SESSION['customer_db_email'];

    if(!$reciever)
    $reciever="pk@GermanBusinessGuide.com";

    // Abschnitt f�r die Aktivierungs-Mail

    $activation_key=rand(100000, 999999);


    // 1. Query: setze neuen Account auf active=0

    $query="UPDATE customer_db_1 SET active=0 WHERE id=".intval($new_id);
    db_query($query);

    // 2. Query: Activation vorbereiten!
    db_query("INSERT INTO customer_db_activation SET id_user=".intval($new_id).", activation_key=$activation_key");


    $subject="Herzlich Wilkommen beim German Business Guide";

    if($_SESSION['customer_db_save_13']==2) $anrede="geehrte Frau";
    else $anrede="geehrter Herr";

    $body.="Sehr ".$anrede." ".$_SESSION['customer_db_save_16'];
    $body.=" ".$_SESSION['customer_db_save_14']." ".$_SESSION['customer_db_save_15'].",";
    $body.="\n\nHerzlich willkommen beim German Business Guide, Ihre Registrierung f�r:";
    $body.="\n".$_SESSION['customer_db_save_5'];
    $body.="\nwurde erfolgreich gespeichert.";

    $body.="\n\nUm Ihren Account entg�ltig zu aktivieren, klicken Sie bitte auf diesen Link:";
    $body.="\n".$this_site."/german/aktivierungs_seite.html?task=activate&activation_key=".$activation_key."&id_user=$new_id";

    $body.="\n\nIhre Zugangsdaten:\nE-Mail: ".$_SESSION['customer_db_save_email'];
    $body.="\nPasswort: ".$_SESSION['customer_db_save_password'];
    $body.="\nSie k�nnen sich nach der Aktivierung jederzeit mit Ihrer E-Mail-Adresse und Ihrem Passwort auf der Seite";
    $body.="\nhttp://www.germanbusinessguide.com";
    $body.="\neinloggen, um weitere Funktionen des Guide zu nutzen, oder um Ihre Daten einzusehen oder zu �ndern.";

    if($_SESSION['customer_db_save_36']==3)$Paket.= "Premium";
    if($_SESSION['customer_db_save_36']==2)$Paket.= "Comfort";
    if($_SESSION['customer_db_save_36']==1)$Paket.= "Basic";

    $body.="\n\n\nDas Paket, dass Sie f�r Ihre Firma gew�hlt haben ist : ".$Paket."\n";



// Hier Probemonat-Weiche

    if($_SESSION['customer_db_save_81']=='on' OR $_SESSION['customer_db_save_81']==1)
    {
        $body.="\nSie haben den unverbindlichen Probemonat gew�hlt, Sie k�nnen den GBG 30 Tage kostenlos und unverbindlich testen!
        Danach erhalten Sie eine Email und k�nnen entscheiden, ob Sie Ihr Paket bebehalten m�chten. Sollten Sie nicht auf diese Email reagieren,
        so werden Sie automatisch auf den Basic Eintrag umgebucht - es entstehen dann auf keinen Fall Kosten f�r Sie!\n
        HINWEIS: Wenn Sie allerdings w�hrend des Probemonats zus�tzliche Bannerwerbung buchen, so wird Ihnen diese nat�rlich in Rechnung gestellt.";

    }
    else
    {
        if($_SESSION['customer_db_save_36']>1)
        {
            $body.="\nDer Rechnungsbetrag f�r 12 Monate inclusive der ausgew�hlten Zusatzoptionen und des Freimonats betr�gt : ".$_SESSION['customer_db_save_62']." EUR";

            if($_SESSION['customer_db_save_73']==1)
            {
                $body.="\nWir werden diesen Betrag ";
                if($_SESSION['customer_db_save_40']==1) $Kred_type="Visa";
                if($_SESSION['customer_db_save_40']==2) $Kred_type="Mastercard";
                if($_SESSION['customer_db_save_40']==3) $Kred_type="American Express";

                $body.=" Ihrer '".$Kred_type."' Kreditkarte mit der Nummer ".$_SESSION['customer_db_save_41']." in Rechnung stellen.";
            }
            elseif($_SESSION['customer_db_save_73']==2)
            {
                $body.="\nWir werden diesen Betrag ";
                $body.="von Ihrem Konto\nNummer: ".$_SESSION['customer_db_save_46']."\nBLZ: ".$_SESSION['customer_db_save_47']."\nabbuchen.";
            }
            else
            {
                $body.="\nBitte �berweisen Sie diesen Betrag binnen 14 Tagen auf folgendes Konto:\n\n";
                $body.="Empf�nger:  German-Business-Guide\nKreditinstitut:  Commerzbank\nKto.-Nr.:  57 141 91\n";
                $body.="BLZ :  330 400 01\nVerwendungszweck : KdNr:".$new_id." + ".$_SESSION['customer_db_save_5'];
            }

            $body.="\nDie Kosten setzen sich wie folgt zusammen:";

            $body.="\nMonatlich : ".$_SESSION['customer_db_save_76']." EUR f�r Listung Typ $Paket";
            $body.="\nEinmalig : ".$_SESSION['customer_db_save_77']." EUR f�r Bannerwerbung";


        }
        else
        {
            $body.="\nDieses Paket ist komplett kostenlos.";
        }

    }




    $body.="\n\nMit freundlichen Gr��en\nIhr German Business Guide Team!";



    wdmail($reciever,$subject,$body,"From: \"German Business Guide\" <support@GermanBusinessGuide.com>\r\nReply-To: support@GermanBusinessGuide.com\r\nBCC:huebner@plan-i.de\r\n");


   #echo "<br>".$body;
/* TESTVERSION :
        $body ="########################################################\n";
        $body.="##### Diese Mail w�rde an einen neuen Kunden gehen #####\n";
        $body.="##### Die Adresse w�re : ".$aValues[5]."           #####\n";
        $body.="########################################################\n\n";

        $body.="\n\nAktivierungs-Link:\nwww.gbg.p16.de/german/aktivierungs_seite.html?task=activate&activation_key=".$activation_key."&id_user=$new_id\n\n";

        $body.="Sehr geehrter <Anrede><Titel><Vorname><Nachname>,".$_SESSION['customer_db_save_5'];
        $body.="\nHerzlich willkommen beim German Business Guide, Ihre Registrierung wurde erfolgreich gespeichert.";
        $body.="\n\nIhre Zugangsdaten:\nE-Mail:<email>\nPasswort: <passwort>";
        $body.="\nSie k�nnen sich ab jetzt jederzeit mit Ihrer E-Mail-Adresse und Ihrem Passwort auf der Seite";
        $body.="\nhttp://www.germanbusinessguide.de";
        $body.="\neinloggen, um weitere Funktionen des Guide zu nutzen, oder um Ihre Daten einzusehen oder zu �ndern.";
        $body.="\n\n\nDas Paket, dass Sie f�r Ihre Firma gew�hlt haben ist : <Paketauswahl>";
        $body.="\n<wenn Paketauswahl Medium oder Maxi :>";
        $body.="\n<Die Kosten f�r diesen Eintrag in H�he von [Rechnungsbetrag] werden [Zahlungsweise anzeigen] >";
        $body.="\n\nMit freundlichen Gr��en\nIhr German Business Guide Team!";
*/
        #if($aValues[4])


}

?>
