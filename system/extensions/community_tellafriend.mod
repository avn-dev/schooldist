<?
/*******************************************************************************
* CMS																			
* Mark Koopmann																	
* PHP-Datei: community_tellafriend.mod													
********************************************************************************
* Tell-a-friend Modul															
*																				
* Erstellungsdatum: 01.03.2005													
*			   von: Michael Merkens												
*																				
*  letzte �nderung: Einbau der mySQL Eintr�ge/Kommentare/Checks/Blacklist/Template/Bugs beseitigen				
*				Am: 14.03.2005													
*			 durch: Michael Merkens												
*******************************************************************************/

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
$tf_check = 1;

if(!$config->tf_db_table) {
	$tf_idPage = (int)$_VARS['tf_idPage'];
	$arrItemData = getPageData($tf_idPage);
} else {
	$tf_idPage = (int)$_VARS['tf_idItem'];
	$arrItemData = get_data(db_query("SELECT `".$config->tf_db_field_text."` as title FROM `".$config->tf_db_table."` WHERE `".$config->tf_db_field_id."` = '".$tf_idPage."'"));
	$arrItemData['url'] = str_replace("#id#",$tf_idPage,$config->tf_url);
}

# Daten der idPage holen (Titel, URL...):
$tf_action = $_VARS['tf_action'];

$tf_usertext = $_VARS['tf_usertext'];
# Wenn 'tf_action = send', dann auslesen von wem die Mail kommt und an wen sie geht.

if($tf_action == "send") {

	$tf_from_name	=	$_VARS['tf_from_name'];
	$tf_from_mail	=	$_VARS['tf_from_mail'];
	$tf_to_name		=	$_VARS['tf_to_name'];
	$tf_to_mail		=	$_VARS['tf_to_mail'];
	$ip				=	$_SERVER['REMOTE_ADDR'];
	$errors			= 	"";
	$error_anzahl	=	0;

	// ueberpruefen der Daten:
	if(!checkEmailMx($tf_from_mail)) {
		$errors .= $config->tf_from_mail_error;
		$error_anzahl++;
	}
	if(!checkEmailMx($tf_to_mail)) {
		$errors .= $config->tf_to_mail_error;
		$error_anzahl++;
	}

	if ($tf_check == 1) {
		$tf_blacklisted = count_rows(db_query($db_data['module'],"SELECT id FROM community_tellafriend_blacklist WHERE (email LIKE '".$tf_from_mail."' OR email LIKE '".$tf_to_mail."') AND confirm = '1' LIMIT 1"));
		$tf_asend = count_rows(db_query($db_data['module'],"SELECT id FROM community_tellafriend_log WHERE to_mail LIKE '".$tf_to_mail."' AND idPage = '".intval($tf_idPage)."' LIMIT 1"));

		#asend 4 absender	
		if ($tf_asend != 0 ) {
			#$errors[$my_val['id']] = "alreadysend";
			echo $errors .= $config->tf_alreadysend;
		}
		if ($tf_blacklisted != 0) {
			#$errors[$my_val['id']] = "blacklisted";
			$errors .= $config->tf_bl_inblacklist;
		}
	}

	if($errors == "") {

		$domain = $system_data['domain'];

		# Festlegen des Absenders, Empf�ngers und des Textes:
		$to = $tf_to_name." <".$tf_to_mail.">";
		$from = $tf_from_name." <".$tf_from_mail.">";
		$subject = $config->tf_subject; # Von Domain oder von Sender empfohlen
		$body = $config->tf_body;
		$header  = "From:".$from."\n";
		$header .= "Reply-To: ".$from."\n";
		# Wenn der Absender eine Kopie will, dann trage in als bcc ein.
		if($_VARS['tf_copy'] == 1) {$header .= "Bcc: ".$from."\n";}
		#$header .= "X-Mailer: webDynamics CMS\n";
		$header .= "X-Sender-IP: ".$ip."\n"; # Die IP des Absenders in den Header der Mail einf�gen

		$blacklist = $domain.$_SERVER['PHP_SELF'].'?tf_action=blacklist';

		$subject = str_replace("<#tf_idPage#>", \Util::convertHtmlEntities($tf_idPage), $subject);
		$subject = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $subject);
		$subject = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $subject);
		$subject = str_replace("<#domain#>", \Util::convertHtmlEntities($system_data['domain']), $subject);
		$subject = str_replace("<#recipient#>", \Util::convertHtmlEntities($tf_to_name), $subject);
		$subject = str_replace("<#sender#>", \Util::convertHtmlEntities($tf_from_name), $subject);
		$subject = str_replace("<#usertext#>", \Util::convertHtmlEntities($tf_usertext), $subject);
		$subject = str_replace("<#url#>", \Util::convertHtmlEntities($url), $subject);

		$body = str_replace("<#tf_idPage#>", \Util::convertHtmlEntities($tf_idPage), $body);
		$body = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $body);
		$body = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $body);
		$body = str_replace("<#domain#>", \Util::convertHtmlEntities($system_data['domain']), $body);
		$body = str_replace("<#sender#>", \Util::convertHtmlEntities($tf_from_name), $body);
		$body = str_replace("<#usertext#>", \Util::convertHtmlEntities($tf_usertext), $body);
		$body = str_replace("<#url#>", \Util::convertHtmlEntities($arrItemData['url']), $body);
		$body = str_replace("<#recipient#>", \Util::convertHtmlEntities($tf_to_name), $body);
		$body = str_replace("<#enter_blacklist#>",\Util::convertHtmlEntities($blacklist), $body);
 
		// Manipultation des Inhaltes durchf�hren
		$objPageProcessor = new \Cms\Service\PageProcessor();
		$objPageProcessor->content = $subject;
		$objPageProcessor->postprocess();
		$subject = $objPageProcessor->content;
		$objPageProcessor->content = $body;
		$objPageProcessor->postprocess();
		$body = $objPageProcessor->content;

		# Mail abschicken:
		wdmail($to, $subject, $body, $header);

		# Log schreiben:
		db_query($db_data['module'],"INSERT INTO community_tellafriend_log (from_mail, from_name, to_mail, to_name, ip, idPage) VALUES ('$tf_from_mail', '$tf_from_name', '$tf_to_mail', '$tf_to_name', '$ip', '$tf_idPage')");
		#####get_data();

		# Seite das es erfolgreich versendet wurde:
		# [test ob richtig versendet wurde noch einbauen, evt irgendwo Fehler speichern/ausgeben]

		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"success");

		# Ersetzten der Platzhalter:
		$buffer = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $buffer);
		$buffer = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $buffer);
		$buffer = str_replace("<#tf_to_name#>", \Util::convertHtmlEntities($tf_to_name), $buffer);
		$buffer = str_replace("<#tf_to_mail#>", \Util::convertHtmlEntities($tf_to_mail), $buffer);

	} else {
		$tf_action = false;
		//$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"error");
		//$buffer = str_replace("<#message#>",$errors,$buffer);
	}

	#$errors
		# Forumular definieren:
#		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form");
		# Ersetzten der Platzhalter:
#		$buffer = str_replace("<#tf_idPage#>",$_VARS['tf_idPage'],$buffer);
#		$buffer = str_replace("<#title#>",$arrItemData['title'],$buffer)#}

}

if ($tf_action == "blacklist") { # Blacklist Funktion
	$tf_ip				= $_SERVER['REMOTE_ADDR'];
	$tf_blacklist_email = $_VARS['tf_blacklist_email'];
	$tf_bl_hash			= $_VARS['tf_bl_hash'];

	if (empty($tf_blacklist_email)) { # Wenn die VAR leer ist, dass Formuar zum eintragen ausgeben
		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"blacklist_entry");
	} elseif (!empty($tf_blacklist_email)) { #SUCCESSFULL & UNSUCCESSFULL
		if (!empty($tf_bl_hash)) {
					db_query($db_data['module'],"UPDATE community_tellafriend_blacklist SET time = NOW( ) , confirm = '1' WHERE hash = '$tf_bl_hash' AND email = '$tf_blacklist_email' LIMIT 1");
					$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"blacklist_entry_successful");
		} else {
##			$errors = array();
			if(!preg_match("/^([a-z0-9\._-]*)@([a-z0-9\.-]{2,66})\.([a-z]{2,6})$/i",$tf_blacklist_email)) {
				$errors[$my_val['id']] = "tf_blacklist_email";
				die("E-Mail Adresse ungültig");
			}
			$tf_mail_bekommen = count_rows(db_query($db_data['module'],"SELECT id FROM community_tellafriend_log WHERE to_mail LIKE '$tf_blacklist_email' OR from_mail LIKE '$tf_blacklist_email' LIMIT 1")); # User muss mindestens eine Mail bekommen oder versendet haben!
			if ($tf_mail_bekommen == 0) {
				$errors .= $config->tf_bl_no_logentry;
			} # error = noch keine mail versendet/bekommen
			$tf_bl_drin = count_rows(db_query($db_data['module'],"SELECT id FROM community_tellafriend_blacklist WHERE email LIKE '$tf_blacklist_email' AND confim = '1' LIMIT 1")); # User darf noch nicht drinstehen!
			if ($tf_bl_drin == 1) {
				$errors .= $config->tf_bl_already_cc;
			} # error = schon in der Blacklist & confirmed
			$tf_bl_drin2 = count_rows(db_query($db_data['module'],"SELECT id FROM community_tellafriend_blacklist WHERE email LIKE '$tf_blacklist_email' AND confim = '0' LIMIT 1")); # User darf noch nicht drinstehen!
			if ($tf_bl_drin2 == 1) {
				$errors .= $config->tf_bl_already_nc;
			} # error = schon in der Blacklist, aber noch nicht confirmed
			if(count($errors) == 0) {
				$tf_bl_hash = \Util::generateRandomString('9'); #hash erstellen
				db_query($db_data['module'],"INSERT INTO community_tellafriend_blacklist (email, ip, hash) VALUES ('$tf_blacklist_email', '$tf_ip', '$tf_bl_hash')");
				$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"blacklist_entry_ok");	
				# checken ob schon vorhanden
				
				
				# MAIL:
				# Da $arrItemData['domain'] noch nicht geht, temporaer:
				#$domain = "v4.p32.de";
				$system_data['mail_from'];
				
				# Festlegen des Absenders, Empf�ngers und des Textes:
				$to = $tf_blacklist_email;
				$from = $system_data['mail_from'];
				$subject = $config->tf_bl_subject; # Von Domain oder von Sender empfohlen
				$body = $config->tf_bl_body; #$tf_blacklist_text."\n\n Um sich auszutragen benutzen sie bitte folgende URL: ".$system_data['domain'].$_SERVER['PHP_SELF']."?=tf_action=blacklist&tf_blacklist_email=$tf_blacklist_email&tf_bl_hash=$tf_bl_hash";
				$header  = $from."\n";
				#		$header .= "Reply-To: ".$from."\n";
				$header .= "X-Sender-IP: ".$tf_ip."\n"; # Die IP des Absenders in den Header der Mail einf�gen
				
				$subject = str_replace("<#tf_idPage#>", \Util::convertHtmlEntities($tf_idPage), $subject);
				$subject = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $subject);
				$subject = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $subject);
				$subject = str_replace("<#domain#>", \Util::convertHtmlEntities($system_data['domain']), $subject);
				$subject = str_replace("<#recipient#>", \Util::convertHtmlEntities($tf_to_name), $subject);
				$subject = str_replace("<#sender#>", \Util::convertHtmlEntities($tf_from_name), $subject);
				$subject = str_replace("<#url#>", \Util::convertHtmlEntities($url), $subject);
				
				$blacklistURL = $system_data['domain'].$_SERVER['PHP_SELF']."?=tf_action=blacklist&tf_blacklist_email=$tf_blacklist_email&tf_bl_hash=$tf_bl_hash";
				
				$body = str_replace("<#tf_idPage#>", \Util::convertHtmlEntities($tf_idPage), $body);
				$body = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $body);
				$body = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $body);
				$body = str_replace("<#domain#>", \Util::convertHtmlEntities($system_data['domain']), $body);
				$body = str_replace("<#sender#>", \Util::convertHtmlEntities($tf_from_name), $body);
				$body = str_replace("<#url#>", \Util::convertHtmlEntities($arrItemData['url']), $body);
				$body = str_replace("<#recipient#>", \Util::convertHtmlEntities($tf_to_name), $body);
				$body = str_replace("<#blacklistURL#>", \Util::convertHtmlEntities($blacklistURL), $body);
				
				# Mail abschicken:
				wdmail($to, $subject, $body, $header);

			} else { 
				die ($config->tf_bl_no_logentry); 
			} 
		} 
	}
} elseif(!$tf_action) {

# Wenn 'tf_action != send && != blacklist' dann das Formular ausgeben:

#if ($error_anzahl == 1)
#$istsind = "ist folgender";
#elseif ($error_anzahl == 2)
#$istsind = "sind folgende";

#echo $error_anzahl;

#if ($errors!=FALSE)
#$errors = "<b>Es ".$istsind." Fehler aufgetreten:</b><br/></br/>".$errors;

	# Check ob geblacklisted (from & to) und check ob die idPage schon mal an den Empf�nger empfohlen wurde:

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"form");
	# Ersetzten der Platzhalter:
	$buffer = str_replace("<#tf_idPage#>", \Util::convertHtmlEntities($tf_idPage), $buffer);
	$buffer = str_replace("<#title#>", \Util::convertHtmlEntities($arrItemData['title']), $buffer);
	$buffer = str_replace("<#track#>", \Util::convertHtmlEntities($arrItemData['track']), $buffer);
	$buffer = str_replace("<#domain#>", \Util::convertHtmlEntities($system_data['domain']), $buffer);
	$buffer = str_replace("<#recipient#>", \Util::convertHtmlEntities($system_data['domain']), $buffer);
	$buffer = str_replace("<#errors#>", \Util::convertHtmlEntities($errors), $buffer);
	$buffer = str_replace("<#tf_from_name#>", \Util::convertHtmlEntities($tf_from_name), $buffer);
	$buffer = str_replace("<#tf_from_mail#>", \Util::convertHtmlEntities($tf_from_mail), $buffer);
	$buffer = str_replace("<#tf_to_name#>", \Util::convertHtmlEntities($tf_to_name), $buffer);
	$buffer = str_replace("<#tf_to_mail#>", \Util::convertHtmlEntities($tf_to_mail), $buffer);
	$buffer = str_replace("<#tf_usertext#>", \Util::convertHtmlEntities($tf_usertext), $buffer);

}

# Sichergehen, dass kein Platzhalter angezeigt wird:
$pos=0;
while($pos = strpos($buffer,'<#',$pos)) {
	$end = strpos($buffer,'#>',$pos);
	$var = substr($buffer, $pos+2, $end-$pos-2);
	$buffer = substr($buffer, 0, $pos)  .   \Util::convertHtmlEntities($$var)  .  substr($buffer, $end+2);
}

echo $buffer;

?>