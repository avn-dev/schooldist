#!/usr/bin/php
<?
/*
 * Created on 05.05.2006
 */

// Wenn �ber Browser ge�ffnet, dann abbrechen:
if($_SERVER['REMOTE_ADDR'])
{
 die("Dieses Script must be startet from the command line!");
}






include(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
include(\Util::getDocumentRoot()."system/legacy/admin/extensions/customer_db/customer_db_class.inc.php");



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

$config->period_1=intval($config->period_1);
$config->period_2=intval($config->period_2);

$config->idDatabase=intval($config->idDatabase);
$config->StatusColumn=intval($config->StatusColumn);
$DateColumn="created";#intval($config->DateColumn);

// Pr�fe, ob alle notwendigen Angaben vorhanden sind
if(0==$config->idDatabase OR 0==$config->StatusColumn)
{
    die("Wichtige Parameter fehlen! Script abgebrochen!");
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
$res=get_data(db_query("SELECT name, field_nr FROM customer_db_definition WHERE id=".$config->StatusColumn));
if(0<$res['field_nr'])
{
    $StatusColumn="ext_".$res['field_nr'];
}
else
{
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

    $remind_counter=0;


    while($my=get_data($rResult))
    {
        // sende Mail
		$config->email_message = str_replace("<#nickname#>", $my["nickname"],$config->email_message);
		$config->email_message = str_replace("<#email#>", $my["email"],$config->email_message);


		// Ersetze m�gliche ext-Tags
        $counter=0; # Notfall-Abbruch-Bedingung
		while(strpos($config->email_message, "<#ext_")!==FALSE)
		{
		    $parts=explode("<#ext_",$config->email_message);
		    $number=intval($parts[1]);
            $config->email_message = str_replace("<#ext_$number#>", $my["ext_$number"],$config->email_message);

            $counter++;
            if($counter>250) {break;}
        }

        wdmail($my["email"], $config->email_subject, $config->email_message, $system_data['mail_from']);#

        // vermerke Mailstatus
        $update="UPDATE customer_db_".$config->idDatabase." SET $StatusColumn=1 WHERE id=".$my['id'];
        #echo $update."<br>";
        db_query($update);
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
    $zeitraum_2=date("YmdHis",time()-24*60*60*$config->period_2);

    $query = "SELECT * FROM customer_db_".$config->idDatabase." WHERE active=1 AND ";
    $query.= "$StatusColumn = 1 AND ";
    $query.= "$DateColumn < '$zeitraum_2'";

    #echo "<br>$query<br>";

    $rResult=db_query($query);

    $delete_counter=0;

    while($my=get_data($rResult))
    {
        // sende Mail

		$config->email_delete_message = str_replace("<#nickname#>", $my["nickname"],$config->email_delete_message);
		$config->email_delete_message = str_replace("<#email#>", $my["email"],$config->email_delete_message);

		// Ersetze m�gliche ext-Tags
        $counter=0; # Notfall-Abbruch-Bedingung
		while(strpos($config->email_delete_message, "<#ext_")!==FALSE)
		{
		    $parts=explode("<#ext_",$config->email_delete_message);
		    $number=intval($parts[1]);
            $config->email_delete_message = str_replace("<#ext_$number#>", $my["ext_$number"],$config->email_delete_message);

            $counter++;
            if($counter>250) {break;}
        }

        wdmail($my["email"], $config->email_delete_subject, $config->email_delete_message, $system_data['mail_from']);#

        // deaktiviere User
        $update="UPDATE customer_db_".$config->idDatabase." SET active=0 WHERE id=".$my['id'];
        #echo $update."<br>";
        db_query($update);
        $delete_counter++;
    }

}

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////


#echo "<br>";
#echo "Es wurden $remind_counter User erinnert.";
#echo "<br>";
#echo "Es wurden $delete_counter User gel�scht.";


?>
