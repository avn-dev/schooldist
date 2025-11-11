!/usr/bin/php<?#

function sendReport($strMessage, $bolDie=1) {
	global $system_data;

	wdmail("mk@plan-i.de", "firstclasslounge.de Cronjob", $strMessage . "\n\n" . print_r($GLOBALS, 1), $system_data['mail_from']);
	if($bolDie) {
		die($strMessage);
	}

}

ob_start();

/*
 * Created on 08.05.2006
*/

// if opened with browser -> abort
/**/
if(0 && $_SERVER['REMOTE_ADDR'])
{
    die("This Script must be startet from the command line!");
}

//$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__)."/../../";

include(\Util::getDocumentRoot()."system/includes/functions.inc.php");
include(\Util::getDocumentRoot()."system/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/includes/dbconnect.inc.php");

// auf Kundenwunsch:
$system_data['mail_from']	= 'FROM:registrierung@firstclasslounge.de';
$system_data['admin']		= 'registrierung@firstclasslounge.de';

// Umschalter:
// true: User wird gel�scht
// false: User wird verschoben
$switch_del2move = false;

$my_parent = get_data(db_query($db_data['system'],"SELECT * FROM cms_content WHERE page_id = '".$_VARS['page_id']."' AND content LIKE '%<#content".(str_pad($_VARS['element_id'], 3, "0", STR_PAD_LEFT))."#>%'"));
$aParent = new \Cms\Helper\ExtensionConfig($my_parent['page_id'], $my_parent['number']);

// Laden der vorhandenen Daten
$config = new \Cms\Helper\ExtensionConfig($_VARS['page_id'], $_VARS['element_id'], $_VARS['content_id']);


/*
    $config->email_message
	$config->email_subject


	$config->period_1
	$config->period_2

	$config->StatusColumn
	$config->DateColumn
*/

$config->period_1 = intval($config->period_1);
$config->period_2 = intval($config->period_2);

$config->idDatabase = intval($config->idDatabase);
$config->StatusColumn = intval($config->StatusColumn);
$DateColumn = "created";#intval($config->DateColumn);

if(!$config->destination_table) {
    $config->destination_table=7;
}

// Prfe, ob alle notwendigen Angaben vorhanden sind
if(0==$config->idDatabase OR 0==$config->StatusColumn) {
	sendReport("Wichtige Parameter fehlen! Script abgebrochen!");
}

global $system_data;

#var_dump($config);
#echo "<br><br>";


//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

















//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
// Lade die Daten zu den Parametern
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
$strSql = "SELECT name, field_nr FROM customer_db_definition WHERE id=".$config->StatusColumn;
$res = get_data(db_query($strSql));
if($strError = mysql_error()) {
	echo $strError;
	echo $strSql;
	echo "\n\n";
}

if(0 < $res['field_nr']) {
    $StatusColumn="ext_".$res['field_nr'];
} else {
    $StatusColumn=$res['name'];
}



//////////////////////////////////////////////////////
//////////////////////////////////////////////////////



















//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
// Erster Zeitabschnitt : Erinnerungsmail
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

if(0<$config->period_1)
{


    $zeitraum_1=date("YmdHis",time()-24*60*60*$config->period_1);


    $query = "SELECT * FROM customer_db_".$config->idDatabase." WHERE active=1 AND ";
    $query.= "$StatusColumn < 1 AND ";
    $query.= "$DateColumn < '$zeitraum_1'";

    #echo "<br>$query<br>";

    $rResult=db_query($query);
    if($strError = mysql_error()) {
    	echo $strError;
    	echo $query;
    	echo "\n\n";
    }
    $remind_counter=0;


    while($my=get_data($rResult))
    {
    	$tmp_email_message=$config->email_message;

        // sende Mail
		$tmp_email_message = str_replace("<#nickname#>", $my["nickname"],$tmp_email_message);
		$tmp_email_message = str_replace("<#email#>", $my["email"],$tmp_email_message);


		// Ersetze m�gliche ext-Tags
        $counter=0; # Notfall-Abbruch-Bedingung
		while(strpos($tmp_email_message, "<#ext_")!==FALSE)
		{
		    $parts=explode("<#ext_",$tmp_email_message);
		    $number=intval($parts[1]);
            $tmp_email_message = str_replace("<#ext_$number#>", $my["ext_$number"],$tmp_email_message);

            $counter++;
            if($counter>250) {break;}
        }


        wdmail($my["email"], $config->email_subject, $tmp_email_message, $system_data['mail_from']);#
        wdmail("mk@plan-i.de", $config->email_subject, "Email an ".$my["email"].":\n".$tmp_email_message, $system_data['mail_from']);#


        $admin_msg="Hallo Admin!\nFolgender User ist seit ".$config->period_1." Tagen registriert und wurde jetzt erinnert:\n
        Kundennummer: ".$my["id"]."
        Nickname: ".$my["nickname"]."
        Email: ".$my["email"];

        wdmail($system_data['admin'], "firstclasslounge.de - Kunde wurde erinnert", $admin_msg,$system_data['mail_from']);
        wdmail("mk@plan-i.de", "firstclasslounge.de - Kunde wurde erinnert", $admin_msg,$system_data['mail_from']);

        // vermerke Mailstatus
        $update="UPDATE customer_db_".$config->idDatabase." SET $StatusColumn=1 WHERE id=".$my['id'];
        #echo $update."<br>";
        db_query($update);
        if($strError = mysql_error()) {
        	echo $strError;
        	echo $update;
        	echo "\n\n";
        }
        $remind_counter++;

    }

}

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
















//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
// Zweiter Zeitabschnitt : User entfernen
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

if(0<$config->period_2)
{
    unset($my);

    $zeitraum_2=date("YmdHis",time()-24*60*60*$config->period_2);

    $query = "SELECT * FROM customer_db_".$config->idDatabase." WHERE active=1 AND ";
    $query.= "$StatusColumn = 1 AND ";
    $query.= "$DateColumn < '$zeitraum_2'";

    #echo "<br>$query<br>";

    $rResult=db_query($query);
    if($strError = mysql_error()) {
    	echo $strError;
    	echo $query;
    	echo "\n\n";
    }

    $delete_counter=0;

    while($my=get_data($rResult))
    {
        // sende Mail
		$tmp_email_delete_message=$config->email_delete_message;
		$tmp_email_delete_message = str_replace("<#nickname#>", $my["nickname"],$tmp_email_delete_message);
		$tmp_email_delete_message = str_replace("<#email#>", $my["email"],$tmp_email_delete_message);

		// Ersetze m�gliche ext-Tags
        $counter=0; # Notfall-Abbruch-Bedingung
		while(strpos($tmp_email_delete_message, "<#ext_")!==FALSE)
		{
		    $parts=explode("<#ext_",$tmp_email_delete_message);
		    $number=intval($parts[1]);
            $tmp_email_delete_message = str_replace("<#ext_$number#>", $my["ext_$number"],$tmp_email_delete_message);

            $counter++;
            if($counter>250) {break;}
        }

        wdmail($my["email"], $config->email_delete_subject, $tmp_email_delete_message, $system_data['mail_from']);#
        wdmail("mk@plan-i.de", "firstclasslounge.de - " . $config->email_delete_subject, "Email an ".$my["email"].":\n".$tmp_email_delete_message, $system_data['mail_from']);#

        $admin_msg="Hallo Admin!\nFolgender User ist seit ".$config->period_2." Tagen registriert ohne sich anzumelden und wurde jetzt entfernt:\n
        Kundennummer: ".$my["id"]."
        Nickname: ".$my["nickname"]."
        Email: ".$my["email"]."
        DB_ID: ".$config->idDatabase."|".$config->destination_table;

        wdmail($system_data['admin'],"firstclasslounge.de - Kunde wurde gel�scht", $admin_msg,$system_data['mail_from']);
        wdmail("mk@plan-i.de","firstclasslounge.de - Kunde wurde gel�scht", $admin_msg,$system_data['mail_from']);

        if($switch_del2move)
        {
            // deaktiviere User
            $update="UPDATE customer_db_".$config->idDatabase." SET active=0 WHERE id=".$my['id'];
            echo $update."<br>";
            db_query($update);
			if($strError = mysql_error()) {
    			echo $strError;
    			echo $update;
    			echo "\n\n";
    		}
        }
        else
        {
            // verschiebe User in andere Tabelle
            $query_fields="SELECT * FROM customer_db_".$config->idDatabase." LIMIT 0,1";

            $sFieldList="nickname,email,";
            $res = db_query($query_fields);
			if($strError = mysql_error()) {
    			echo $strError;
    			echo $query_fields;
    			echo "\n\n";
    		}
            $my_field = get_data($res);
            foreach($my_field as $key => $value)
            {
                if(0===strpos($key,"ext_")) $sFieldList.=$key.",";
            }

            $sFieldList = substr($sFieldList,0,-1);

            $query="INSERT INTO customer_db_".$config->destination_table." ($sFieldList) SELECT $sFieldList FROM customer_db_".$config->idDatabase." WHERE id=".$my['id'];
            db_query($query);
			if($strError = mysql_error()) {
    			echo $strError;
    			echo $query;
    			echo "\n\n";
    		}

            $id = get_insert_id();
            db_query("UPDATE customer_db_".$config->destination_table." SET active=1 WHERE id=$id");
			if($strError = mysql_error()) {
    			echo $strError;
    			echo $query;
    			echo "\n\n";
    		}

            // Sicherheits-Check, damit nix verloren geht!
            if(0 < $id) {
            	$strSql = "DELETE FROM customer_db_".$config->idDatabase." WHERE id=".$my['id'];
                db_query($strSql);
				if($strError = mysql_error()) {
					echo $strError;
					echo $strSql;
					echo "\n\n";
				}
            } else {
                // Meldung, dass Problem!!!
                wdmail("mk@plan-i.de","firstclasslounge.de - Problem","Hoi!\nDie Verschiebung eines zu l�schenden Accounts (ID:".$my['id_user'].") ist fehlgeschlagen!!\n".$query,$system_data['mail_from']);
            }

        }
        $delete_counter++;
    }

}

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

$output="\n";
$output.="Es wurden $remind_counter User erinnert.";
$output.="\n\n";
$output.="Es wurden $delete_counter User gel�scht.";
$output.="\n\nZeitstempel: ".date("d.m.Y H:i:s");

$strOutput = ob_get_contents();

ob_end_flush();

wdmail("mk@plan-i.de","firstclasslounge.de - Cronjob", $output."\n\n".$strOutput, $system_data['mail_from']);

?>