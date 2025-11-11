<?

$sBaseDir = substr($GLOBALS['argv'][0],0,- strlen(basename($GLOBALS['argv'][0])));

if($sBaseDir)
{
  //für die command-Line!
  chdir($sBaseDir);
}

include(\Util::getDocumentRoot()."system/legacy/admin/includes/config.inc.php");
include(\Util::getDocumentRoot()."system/legacy/admin/includes/functions.inc.php");
include_once(\Util::getDocumentRoot()."system/includes/autoload.inc.php");
include(\Util::getDocumentRoot()."system/legacy/admin/includes/dbconnect.inc.php");

function sendTicketReminder($IDUser, $IDTicket, $sTo, $sShortText, $sText, $iDate, $iDue)
// $IDUser:   Nummer des Benutzers in der tabelle system_users
// $IDTicket: Nummer des Tickets in der Tabeklle ticket_ticket
// $sTo:      'user' =>    an den User selbst schicken
//            'control' => an die zuständigen Contoluser
//            'admin' =>   an die Aministratoren
{
  $sBetreff = "Ticket-Reminder (".date("d-m-Y H:i").") $sShortText";
  $sHeader =  "FROM: Ticket-Master <theMaster@haustein.plan-i.de>";

  $res = db_query("SELECT `firstname`,`lastname` FROM `system_user` WHERE `id` = '$IDUser'");
  $aData = get_data($res);

  $sBody =    "
Bitte kümmern Sie sich um das folgende Ticket:

Kurztext:                $sShortText
Eingetragen am:          ".date("d-m-Y H:i", $iDate)."
Abgelaufen am:           ".date("d-m-Y H:i", $iDue)."
Zuständiger Mitarbeiter: $aData[firstname] $aData[lastname]

Text:                    
$sText
";


	if(!$IDUser && $sTo=='user') {
    	$sTo='admin';
    	$sBetreff = "USER-ID fehlt! => " . $sBetreff;
	}
  
	if($sTo=='user') {
		$aIDUser = array($IDUser);
		DB::executeQuery("UPDATE ticket_tickets SET last_user_reminder = UNIX_TIMESTAMP() WHERE id = '$IDTicket'");
	} else if($sTo=='control') {
		$aIDUser = array(3,5);
		$sBetreff = "CONTROL => " . $sBetreff;
		DB::executeQuery("UPDATE ticket_tickets SET last_control_reminder = UNIX_TIMESTAMP() WHERE id = '$IDTicket'");
	} else if($sTo=='admin') {
	$aIDUser = array(5);
		$sBetreff = "ADMIN => " . $sBetreff;
	} else {
		echo "Bitte angeben wo das Ticket hin soll!! (\$sTo=='user' wird nun angenommen!)\n\n";
		$aIDUser = array($IDUser);
		$sBetreff = "NO RECEPIENT => " . $sBetreff;
		DB::executeQuery("UPDATE ticket_tickets SET last_user_reminder = UNIX_TIMESTAMP() WHERE id = '$IDTicket'");
	}
  
  
	foreach((array)$aIDUser as $k=>$v) {
		$res = db_query("SELECT `email` FROM `system_user` WHERE `id` = '$v'");
		$aData = get_data($res);
		wdmail($aData['email'], $sBetreff, $sBody);
	}

}





// Mahnung nach Überfälligkeit (alle 15 Minuten)

$res = db_query("SELECT *
FROM `ticket_tickets`
WHERE `due` < UNIX_TIMESTAMP()
AND `done` < '100'
AND `last_user_reminder` < ( UNIX_TIMESTAMP() - 15 * 60)
AND `active` = '1'
");

while($my = get_data($res)) {
	sendTicketReminder($my['user'], $my['id'], 'user', $my['short_text'], $my['text'],$my['date'],$my['due']);
}





// Control-Nachricht nach 8 Stunden Überfälligkeit (alle 4 Stunden)

$res = db_query("SELECT *
FROM `ticket_tickets`
WHERE `due` < ( UNIX_TIMESTAMP() - 8 * 60 * 60 )
AND `done` < '100'
AND `last_control_reminder` < ( UNIX_TIMESTAMP() - 4 * 60 * 60 )
AND `active` = '1'
");

while($my = get_data($res)) {
	sendTicketReminder($my['user'], $my['id'], 'control', $my['short_text'], $my['text'],$my['date'],$my['due']);
}
