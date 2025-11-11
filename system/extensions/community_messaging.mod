<?
 
/* 
 * active Zust�nde:
 * 0: nicht aktiv
 * 1: aktiv
 * 2: gel�scht vom Empf�nger
 * 3: gel�scht vom Absender
 */

if(!$user_data['idTable']) $user_data['idTable'] = 1;

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if($_VARS['action'] == "delete") {
	if(!is_array($_VARS['iMessage'])) {
		$aMessages = array($_VARS['iMessage']);
	} else {
		$aMessages = $_VARS['iMessage'];
	}
	foreach($aMessages as $_VARS['iMessage']) {
		$iActive = 0;
		// Aktuellen Status und Rolle des User herausfinden
		$my = get_data(db_query($db_data['module'],"SELECT active,tblUser,idUser,tblReceiver,idReceiver FROM community_messaging WHERE id = '".$_VARS['iMessage']."' AND ((idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable'].") OR (idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable'].")) AND active IN (1,2,3) LIMIT 1"));
		if($my['idUser'] == $user_data['id'] && $my['tblUser'] == $user_data['idTable'] && $my['idReceiver'] == $user_data['id'] && $my['tblReceiver'] == $user_data['idTable']) {
			// User ist Absender und Empf�nger
			$iActive = 0;
		} elseif($my['idUser'] == $user_data['id'] && $my['tblUser'] == $user_data['idTable']) {
			// User ist Absender
			if($my['active'] == 2) {
				$iActive = 0;
			} else {
				$iActive = 3;
			}
		} else {
			// User ist Empf�nger
			if($my['active'] == 3) {
				$iActive = 0;
			} else {
				$iActive = 2;
			}
		}
		if($iActive>0) {
			db_query($db_data['module'],"UPDATE community_messaging SET active = '".$iActive."', changed = changed WHERE id = '".$_VARS['iMessage']."' AND ((idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable'].") OR (idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable'].")) LIMIT 1");
		} else {
			// Wenn Absender und Empf�nger Nachricht gel�scht -> dann entg�ltig l�schen
			db_query($db_data['module'],"DELETE FROM community_messaging WHERE id = '".$_VARS['iMessage']."' AND ((idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable'].") OR (idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable'].")) LIMIT 1");
		}
	}
}

if($_VARS['task'] == "send") {
	$my_receiver = get_data(db_query($db_data['module'],"SELECT id,email, nickname FROM customer_db_".$user_data['idTable']." WHERE nickname LIKE '".$_VARS['sReceiver']."'"));
	if(!$my_receiver['id']) {
		$_VARS['sError']	= $config->error_nouser;
		$_VARS['task'] 		= "create";
	}

	if(!$_VARS['sReceiver'] || !$_VARS['sSubject'] || !$_VARS['sMessage']) {
		$_VARS['sError']	= $config->error_notcomplete;
		$_VARS['task'] 		= "create";
	}

}

if($_VARS['task'] == "create") {
	$buffer	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'create');

	if($_VARS['iResponse']) {
		$my = get_data(db_query($db_data['module'],"SELECT subject FROM community_messaging WHERE id = '".$_VARS['iResponse']."' AND ((idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable'].") OR (idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable'].")) AND active IN (1,2,3) LIMIT 1"));
		$_VARS['sSubject'] = "Re: ".$my['subject'];
	}

	$buffer = str_replace("<#sError#>",$_VARS['sError'],$buffer);
	$buffer = str_replace("<#sReceiver#>",$_VARS['sReceiver'],$buffer);
	$buffer = str_replace("<#sSubject#>",$_VARS['sSubject'],$buffer);
	$buffer = str_replace("<#sMessage#>",$_VARS['sMessage'],$buffer);

} elseif($_VARS['task'] == "detail") {

	$buffer	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'detail');

	$my = get_data(db_query($db_data['module'],"SELECT UNIX_TIMESTAMP(changed) as changed, UNIX_TIMESTAMP(received) as received,id,subject,message,tblUser,idUser,tblReceiver,idReceiver FROM community_messaging WHERE id = '".$_VARS['iMessage']."' AND ((idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable'].") OR (idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable'].")) AND active IN (1,2,3) LIMIT 1"));
	$my_sender 		= get_data(db_query($db_data['module'],"SELECT nickname FROM customer_db_".$my['tblUser']." WHERE id = '".$my['idUser']."'"));
	$my_receiver 	= get_data(db_query($db_data['module'],"SELECT nickname FROM customer_db_".$my['tblReceiver']." WHERE id = '".$my['idReceiver']."'"));

	$buffer = str_replace("<#id#>",$my['id'],$buffer);
	$buffer = str_replace("<#idSender#>",$my['idUser'],$buffer);
	$buffer = str_replace("<#tblSender#>",$my['tblUser'],$buffer);
	$buffer = str_replace("<#idReceiver#>",$my['idReceiver'],$buffer);
	$buffer = str_replace("<#tblReceiver#>",$my['tblReceiver'],$buffer);
	$buffer = str_replace("<#sSender#>",$my_sender['nickname'],$buffer);
	$buffer = str_replace("<#sReceiver#>",$my_receiver['nickname'],$buffer);
	$buffer = str_replace("<#sSubject#>",$my['subject'],$buffer);
	// Wenn Newsletter vom Admin
	if($my['idUser'] == 1) {
		if(!strpos($my['message'],"<br")) {
			$buffer = str_replace("<#sMessage#>",nl2br($my['message']),$buffer);
		} else {
			$buffer = str_replace("<#sMessage#>",$my['message'],$buffer);
		}
	} else {
		$buffer = str_replace("<#sMessage#>",nl2br(strip_tags($my['message'],"<a><b><p><i><strong>")),$buffer);
	}
	$buffer = str_replace("<#lasttask#>",$_VARS['lasttask'],$buffer);

	if($user_data['id'] == $my['idReceiver'] && $user_data['idTable'] == $my['tblReceiver']) {
		db_query($db_data['module'],"UPDATE community_messaging SET changed = changed, received = NOW() WHERE id = '".$_VARS['iMessage']."'");
	}

} elseif($_VARS['task'] == "send") {

	$buffer	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'send');

	$active = 1;
	if(!$user_data['blacklist']['recheck'][$user_data['idTable']."|".$my_receiver['id']]) {
		if($config->emailnotify) {
			$config->emailsubject = str_replace("#wd:receiver#",$my_receiver['nickname'],$config->emailsubject);
			$config->emailcontent = str_replace("#wd:receiver#",$my_receiver['nickname'],$config->emailcontent);
			$config->emailsubject = str_replace("#wd:sender#",$user_data['name'],$config->emailsubject);
			$config->emailcontent = str_replace("#wd:sender#",$user_data['name'],$config->emailcontent);
			wdmail($my_receiver['email'],$config->emailsubject,$config->emailcontent,$system_data['mail_from']);
		}
	} else {
		$active = 2;
	}

	db_query($db_data['module'],"INSERT INTO community_messaging SET idUser = '".$user_data['id']."', tblUser = '".$user_data['idTable']."', idReceiver = '".$my_receiver['id']."', tblReceiver = '".$user_data['idTable']."', subject = '".\DB::escapeQueryString($_VARS['sSubject'])."', message = '".\DB::escapeQueryString($_VARS['sMessage'])."', changed = NOW(), received = 0, active = ".$active." ");

} elseif($_VARS['task'] == "outbox") {

	$buffer	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'outbox');

	$buffer_new	= \Cms\Service\PageParser::checkForBlock($buffer,'row_new');
	$buffer_old	= \Cms\Service\PageParser::checkForBlock($buffer,'row_old');

	$cache = "";

	$res = db_query($db_data['module'],"SELECT UNIX_TIMESTAMP(changed) as changed, UNIX_TIMESTAMP(received) as received,id,subject,tblUser,idUser,tblReceiver,idReceiver FROM community_messaging WHERE idUser = ".$user_data['id']." AND tblUser = ".$user_data['idTable']." AND active IN (1,2) ORDER BY changed DESC");

	while($my = get_data($res)) {

		$my_receiver = get_data(db_query($db_data['module'],"SELECT nickname FROM customer_db_".$my['tblReceiver']." WHERE id = '".$my['idReceiver']."'"));

		if($my['received'] > $my['changed']) {
			$temp = $buffer_old;
		} else {
			$temp = $buffer_new;
		}

		$temp = str_replace("<#idSender#>",$my['idUser'],$temp);
		$temp = str_replace("<#tblSender#>",$my['tblUser'],$temp);
		$temp = str_replace("<#idReceiver#>",$my['idReceiver'],$temp);
		$temp = str_replace("<#tblReceiver#>",$my['tblReceiver'],$temp);

		$temp = str_replace("<#id#>",$my['id'],$temp);
		$temp = str_replace("<#subject#>",$my['subject'],$temp);
		$temp = str_replace("<#receiver#>",$my_receiver['nickname'],$temp);
		$temp = str_replace("<#changed#>",strftime($config->ftime,$my['changed']),$temp);

		$cache .= $temp;
	}

	$buffer	= \Cms\Service\PageParser::replaceBlock($buffer,"rows",$cache);

} else {

	$buffer	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'inbox');

	$buffer_new	= \Cms\Service\PageParser::checkForBlock($buffer,'row_new');
	$buffer_old	= \Cms\Service\PageParser::checkForBlock($buffer,'row_old');

	$cache = "";

	$res = db_query($db_data['module'],"SELECT UNIX_TIMESTAMP(changed) as changed, UNIX_TIMESTAMP(received) as received,id,subject,tblUser,idUser,tblReceiver,idReceiver FROM community_messaging WHERE idReceiver = ".$user_data['id']." AND tblReceiver = ".$user_data['idTable']." AND active IN (1,3) ORDER BY changed DESC");

	while($my = get_data($res)) {

		$my_sender = get_data(db_query($db_data['module'],"SELECT nickname FROM customer_db_".$my['tblUser']." WHERE id = '".$my['idUser']."'"));

		if($my['received'] > $my['changed']) {
			$temp = $buffer_old;
		} else {
			$temp = $buffer_new;
		}

		$temp = str_replace("<#idSender#>",$my['idUser'],$temp);
		$temp = str_replace("<#tblSender#>",$my['tblUser'],$temp);
		$temp = str_replace("<#idReceiver#>",$my['idReceiver'],$temp);
		$temp = str_replace("<#tblReceiver#>",$my['tblReceiver'],$temp);

		$temp = str_replace("<#id#>",$my['id'],$temp);
		$temp = str_replace("<#subject#>",$my['subject'],$temp);
		$temp = str_replace("<#sender#>",$my_sender['nickname'],$temp);
		$temp = str_replace("<#changed#>",strftime($config->ftime,$my['changed']),$temp);

		$cache .= $temp;
	}

	$buffer	= \Cms\Service\PageParser::replaceBlock($buffer,"rows",$cache);
}

$pos=0;
while($pos = strpos($buffer,'<#',$pos)) {
      $end = strpos($buffer,'#>',$pos);
      $var = substr($buffer, $pos+2, $end-$pos-2);
      $buffer = substr($buffer, 0, $pos)  .  $GLOBALS[$var]  .  substr($buffer, $end+2);
   }

echo $buffer;

?>