<?


/* Start Funktionen */
if (is_file(\Util::getDocumentRoot().'system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php')) {
	require_once(\Util::getDocumentRoot().'system/legacy/admin/extensions/customer_db/customer_db_functions.inc.php');
}
/* Ende Funktionen */

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

class Ext_Community_Forum {
	
	protected $_iForum;
	protected $_iPerPage;
	public $iTotal;
	protected $_iTopic = 0;
	
	public function __construct($oConfig) {
		$this->_iForum = $oConfig->forum_id;
		$this->_iPerPage = $oConfig->perpage;
	}

	public function getEntries($iParent=0, $iOffset=0, $sDirection='DESC') {

		if($sDirection != 'DESC') {
			$sDirection = 'ASC';
		}
		
		$sSql = "
				SELECT 
					SQL_CALC_FOUND_ROWS	
					cfd.*, 
					COUNT(cfdp.id) `answers`, 
					UNIX_TIMESTAMP(cfd.created) `created` 
				FROM 
					community_forum_data cfd LEFT OUTER JOIN
					community_forum_data cfdp ON
						cfd.id = cfdp.parent_id
				WHERE 
					cfd.forum_id = :forum_id AND
					cfd.parent_id = :parent_id AND 
					cfd.active = 1
				GROUP BY
					cfd.id
				ORDER BY 
					cfd.created ".$sDirection."
				LIMIT
					:offset, :limit
				";
		$aSql = array();
		$aSql['parent_id'] = (int)$iParent;
		$aSql['forum_id'] = (int)$this->_iForum;
		$aSql['offset'] = (int)$iOffset;
		$aSql['limit'] = (int)$this->_iPerPage;
		$aEntries = DB::getPreparedQueryData($sSql, $aSql);
		
		$sSql = "SELECT FOUND_ROWS() `count`";
		$aCount = DB::getQueryData($sSql);
		$this->iTotal = $aCount[0]['count'];

		return $aEntries;

	}

	public function printtopics($my_discussions, $no_child, $sDirection='DESC') {
		// $temp     = die generierte Ausgabe, wird immer weiter gef�llt mit jedem Funktionsaufruf
		// $buffer   = Vorlage f�r jeden Eintrag
		// $fo_start = Erster Post der in der �bersicht angezeigt werden soll, beginnend ab 1
		global $profile_url, $db_data, $temp, $i, $buffer, $aVars, $aVarsPlus, $sSelect, $fo_start;
	
		// FIXIT: $id_user_email sollte bestimmt im folgenden if gesetzt werden.
		$id_user_email = 0;
		if ($my_discussions['name'] == "" && $my_discussions['idUser'] > 0 && $my_discussions['tblUser'] > 0) {
			$sQuery     = "SELECT ".$sSelect." db.email, db.nickname, db.active FROM customer_db_".$my_discussions['tblUser']." db WHERE id = '".$my_discussions['idUser']."' LIMIT 1";
	
			$my_user    = get_data(db_query($sQuery));
			$sUserName  = $my_user['nickname'];
			$sUserEmail = $my_user['email'];
		} else {
			$sUserEmail = $my_discussions['email'];
			$sUserName  = $my_discussions['name'];
		}
	
		if (!$my_discussions['subject']) {
			$my_discussions['subject'] = "...";
		}
	
		$rowclass		= ($i%2) ? "first" : "second";
		$topicrowclass	= ($this->_iTopic % 2) ? "first" : "second";
		$created      = strftime("%x %X", $my_discussions['created']);
		$email_buffer = \Cms\Service\PageParser::checkForBlock($buffer, "email");
	
		$spacer = "";
		$iWidth = 0;
		if ($my_discussions['parent_id'] > 0) {
			$iWidth = $my_discussions['level'] * 16;
			$spacer = '<img src="/media/spacer.gif" width="'.$iWidth.'" height="16" align="absmiddle" border="0" />';
			$cache = \Cms\Service\PageParser::checkForBlock($buffer, "reply_loop");
		} else {
			$cache = \Cms\Service\PageParser::checkForBlock($buffer, "topic_loop");
			$this->_iTopic++;
		}

		$sbuffer = str_replace("<#author#>", $sUserName, $cache);
		if ($id_user_email) {
			$email_buffer = str_replace("<#address#>", $sUserEmail, $email_buffer);
		} else {
			$email_buffer = "";
		}

		$sbuffer = \Cms\Service\PageParser::replaceBlock($sbuffer, "email", $email_buffer);
		$sbuffer = str_replace("<#user_id#>", $my_discussions['user_id'], $sbuffer);
		$sbuffer = str_replace("<#rowclass#>", $rowclass, $sbuffer);
		$sbuffer = str_replace("<#topicrowclass#>", $topicrowclass, $sbuffer);
		$sbuffer = str_replace("<#topic_id#>", $my_discussions['id'], $sbuffer);
		$sbuffer = str_replace("<#spacer#>", $spacer ,$sbuffer);
		$sbuffer = str_replace("<#spacer_width#>", $iWidth ,$sbuffer);
		$sbuffer = str_replace("<#subject#>", $my_discussions['subject'], $sbuffer);
		$sbuffer = str_replace("<#text#>", nl2br($my_discussions['text']), $sbuffer);
		$sbuffer = str_replace("<#answers#>", $my_discussions['answers'], $sbuffer);
		$sbuffer = str_replace("<#created#>", $created, $sbuffer);
		$sbuffer = str_replace("<#start#>", $fo_start, $sbuffer);
		$temp   .= $sbuffer;

		foreach ((array)$aVars as $val) {
			$sVar = "<#".$val[2]."#>";
			if ($val[1][1] == "Select Field") $val[1][1] = "select";
			$content = getFieldOutput($val[1][1], $my_user[$val[1][0]], $val[3], $val[1]);
			$temp = str_replace($sVar, $content, $temp);
		}
	
		foreach ((array)$aVarsPlus as $val) {
			$sVar = "<#".$val[0]."#>";
			if ($val[1][0] == "created") {
				$sType = "timestamp";
			} else {
				$sType = "text";
			}
			$content = getFieldOutput($sType, $my_discussions[$val[1][0]], 0, $val[1]);
			$temp = str_replace($sVar, $content, $temp);
		}
	
		if ($no_child != 1) {
			$aEntries = $this->getEntries($my_discussions['id'], 0, $sDirection);
			$i++;
			if (!empty($aEntries)) {
				foreach((array)$aEntries as $my_discussions) {
					$this->printtopics($my_discussions, 0, $sDirection);
				}
			}
		}
	}
	
}

$oForum = new Ext_Community_Forum($config);


if ($config->useforCustomers) {

	if (!$_VARS['id']) {
		$_VARS['id'] = $user_data['id'];
		$_VARS['idTable'] = $user_data['idTable'];
	}
	if (!$_VARS['idTable']) {
		$_VARS['idTable'] = $config->table_id;
	}

	$my_user = get_data(db_query("SELECT id, nickname, email FROM customer_db_".$_VARS['idTable']." WHERE id = '".$_VARS['id']."'"));
	$my_forum = get_data(db_query("SELECT id FROM community_forum_init WHERE name = '".$my_user['nickname']."|".$_VARS['id']."|".$_VARS['idTable']."'"));
	if ($my_user['id'] > 0 && $my_forum['id'] < 1) {
		db_query("INSERT INTO community_forum_init SET name = '".$my_user['nickname']."|".$_VARS['id']."|".$_VARS['idTable']."', notify = '".$my_user['email']."', access = '".$config->access."', blacklist = '".$config->blacklist."'");
	}
	$element_data['content'] = str_replace("<#id#>",$_VARS['id'],$element_data['content']);
	$element_data['content'] = str_replace("<#idTable#>",$_VARS['idTable'],$element_data['content']);
	$element_data['content'] = str_replace("<#nickname#>",$my_user['nickname'],$element_data['content']);
	$config->forum_id = $my_forum['id'];

}

$discussions_id 		= (int)$_VARS['discussions_id'];
$discussions_action 	= $_VARS['discussions_action'];
$discussions_name 		= $_VARS['discussions_name'];
$discussions_email 		= $_VARS['discussions_email'];
$discussions_topic 		= $_VARS['discussions_topic'];
$discussions_text 		= $_VARS['discussions_text'];
$discussions_sendreply 	= $_VARS['discussions_sendreply'];
$discussions_parent_id 	= (int)$_VARS['discussions_parent_id'];

global $SmiliesTbl;
$SmiliesTbl = Array(
	":\)"	=> array("icon_smile.gif", "19", "19"),
	":-\)"	=> array("icon_smile.gif", "19", "19"),
	":D"	=> array("icon_smile_big.gif", "19", "19"),
	":o"	=> array("icon_smile_shock.gif", "19", "19"),
	":\("	=> array("icon_smile_sad.gif", "19", "19"),
	";\)"	=> array("icon_smile_wink.gif", "19", "19"),
	":p"	=> array("icon_smile_tongue.gif", "19", "19"),
	"8\)"	=> array("icon_smile_cool.gif", "19", "19"),
	":\["	=> array("icon_smile_blush.gif", "19", "19"),
	":@"	=> array("icon_smile_evil.gif", "19", "19")
);

function Check4Smilies($string) {
	global $SmiliesTbl;
	while(list($key, $prop) = each($SmiliesTbl)) {
		$string = ereg_replace($key, " <IMG SRC=\"/media/extensions/community/$prop[0]\" ALT=\"".str_replace("\"","&quot;", stripslashes($key))."\"> ", $string);
	}
	return $string;
}


function cleanUserInput($string) {
	$string = strip_tags($string);
	$string = preg_replace("/(http:\/\/)(.*?\..*?\..*?(?=\/))(.*?)(?=\s|<br\s*\/?>)/ims", "<a href=\"\\1\\2\\3\" target=_blank>\\2</a>", $string);
	$a_string_exploded = explode(" ", $string);
	foreach ($a_string_exploded as $word) {
		if (strlen($word) <= 50 or stristr($word, "http:\/\/") == FALSE) {
			$s_text_modified .= $word." ";
		} else {
			$s_text_modified .= chunk_split($word, 50, " ");
		}
	}
	return $s_text_modified;
}

function checkBlacklist($text, $blacklist) {
	$arrElements = explode(",", $blacklist);
	foreach ($arrElements as $strElement) {
		$strElement = trim($strElement);
		if ($strElement != '') {
			$text = eregi_replace($strElement, str_pad("", strlen($strElement), "*"), $text);
		}
	}
	return $text;
}

/*
 * Start des eigentlichen Skripts
 */
global $temp, $i, $buffer, $fo_start, $aVars, $aVarsPlus, $sSelect;

$my_element['content'] = $element_data['content'];
$element_id = $element_data['id'];
$page_id = $element_data['page_id'];
$user_id = $user_data['id'];
$fo_start = (!$_VARS['fo_start']) ? 1 : $_VARS['fo_start'];

$discussions_init = get_data(db_query("SELECT * FROM community_forum_init WHERE id = '".$config->forum_id."'"));


// Erstellen eines neuen Beitrags oder L�schen eines Bestehenden Beitrags
if ($discussions_action == "save") {
	
	// rechte checken
	if(
		(
			!$user_data['id'] ||
			!$user_data['idTable']
		) && 
		$config->access == "private"
	) {
		unset($discussions_text);
	}

	// Wenn Nachrichtentext
	if ($discussions_text) {

		$discussions_topic = cleanUserInput($discussions_topic);
		$discussions_text = cleanUserInput($discussions_text);

		// Wenn Antwort
		if ($discussions_parent_id > 0) {
			$my_discussions = get_data(db_query("SELECT * FROM community_forum_data WHERE id = ".(int)$discussions_parent_id.""));
			$discussions_level = $my_discussions['level'] + 1;
			// Wenn Benachrichtigungsemail bei Antwort
			if ($my_discussions['sendreply'] > 0) {
				// Wenn registrierter User
				if ($my_discussions['idUser'] > 0) {
					$my_user = get_data(db_query("SELECT * FROM customer_db_".(int)$my_discussions['tblUser']." db WHERE id = ".(int)$my_discussions['idUser']." LIMIT 1"));
					$sUserName = $my_user['nickname'];
					$sUserEmail = $my_user['email'];
				} else {
					$sUserEmail = $my_discussions['email'];
					$sUserName = $my_discussions['name'];
				}
				wdmail($sUserEmail, "Neue Antwort auf Ihren Beitrag im Forum ".$discussions_init['name']." auf ".$system_data['project_name']."", "Auf Ihren Beitrag \"".$my_discussions['subject']."\" wurde geantwortet.\n\nKlicken Sie hier, um zu dem Forum zu gelangen:\n".$system_data['domain'].$_SERVER['PHP_SELF']."\n\nDiese Nachricht wurde automatisch erstellt.");
			}
		}

		// Wenn Benachritigung aktiviert
		if ($discussions_init['notify'] != "") {
			
			$strLink = $system_data['domain'].$_SERVER['PHP_SELF'];

			if($_VARS['id'] && $_VARS['idTable']) {
				$strLink .= '?idTable='.$_VARS['idTable'].'&id='.$_VARS['id'].'';
			}

			$strSubject = $config->email_subject;
			$strContent = $config->email_body;
			$strSubject = str_replace("<#title#>", $discussions_init['name'], $strSubject);
			$strSubject = str_replace("<#subject#>,", $discussions_topic, $strSubject);
			$strSubject = str_replace("<#link#>", $strLink, $strSubject);
			$strContent = str_replace("<#title#>", $discussions_init['name'], $strContent);
			$strContent = str_replace("<#subject#>,", $discussions_topic, $strContent);
			$strContent = str_replace("<#link#>", $strLink, $strContent);

			wdmail($discussions_init['notify'], $strSubject, $strContent);

		}
		$discussions_text = Check4Smilies($discussions_text);
		$query = "
			INSERT INTO
				community_forum_data
			SET
				forum_id = '".$config->forum_id."',
				idUser = '".$user_data['id']."',
				tblUser = '".$user_data['idTable']."',
				parent_id = '".$discussions_parent_id."',
				name = '".\DB::escapeQueryString($discussions_name)."',
				email = '".\DB::escapeQueryString($discussions_email)."',
				created = NOW(),
				subject = '".\DB::escapeQueryString($discussions_topic)."',
				text = '".\DB::escapeQueryString($discussions_text)."',
				sendreply = '".$discussions_sendreply."',
				level = '".$discussions_level."',
				active = 1
			";
		if($discussions_init['blacklist'] != "") {
			$query = checkBlacklist($query, $discussions_init['blacklist']);
		}
		db_query($query);
	}

	$discussions_action = "list";

} elseif($discussions_action == "delete") {

	if(
		$user_data['id'] > 0 &&
		$_VARS['id'] == $user_data['id'] && 
		$user_data['idTable'] > 0 &&
		$_VARS['idTable'] == $user_data['idTable']
	) {
		$sQuery = "DELETE FROM community_forum_data WHERE id = '".$_VARS['idEntry']."' AND forum_id = '".$config->forum_id."'";
		db_query($sQuery);
	}

	$discussions_action = "list";

}

if (!$discussions_action) {
	$discussions_action = "list";
}
$buffer = \Cms\Service\PageParser::checkForBlock($my_element['content'], $discussions_action);

if ($discussions_action == "list") {

	$aVars = array();
	$aVarsPlus = array();
	$sSelect = "";

	parseCustomerTemplate($buffer, $aVars, $aVarsPlus, $sSelect, "list", $user_data['idTable']);

	$sQuery = "SELECT id FROM community_forum_data WHERE forum_id = '".$config->forum_id."' AND parent_id = 0 AND active = 1 ORDER BY `created` DESC";
	$res = db_query($sQuery);
	$num_discussions = count_rows($res);

	$buffer_pages = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'pages');
	$buffer_backlink = \Cms\Service\PageParser::checkForBlock($buffer_pages, 'backlink');
	$buffer_forwardlink = \Cms\Service\PageParser::checkForBlock($buffer_pages, 'forwardlink');

	if (!$config->perpage) $config->perpage = 10000;
	$config->perpage = intval($config->perpage);

	if (($fo_start+$config->perpage-1) < $num_discussions) {
		$fo_end = intval($fo_start+$config->perpage-1);
	} else {
		$fo_end = intval($num_discussions);
	}

	if ($fo_start > 1) {
		$fo_backlink = str_replace("<#fo_startb#>", ($fo_start - $config->perpage), $buffer_backlink);
	}

	if (($fo_start + $config->perpage - 1) < $num_discussions) {
		$fo_forwardlink = str_replace("<#fo_startf#>",($fo_start + $config->perpage),$buffer_forwardlink);
	}

	$buffer_pages = str_replace("<#start#>", $fo_start, $buffer_pages);
	$buffer_pages = str_replace("<#end#>", $fo_end, $buffer_pages);
	$buffer_pages = str_replace("<#total#>", $num_discussions, $buffer_pages);
	$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages, "backlink", $fo_backlink);
	$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages, "forwardlink", $fo_forwardlink);

	$buffer = str_replace("<#displayPages#>", $buffer_pages, $buffer);

	// if current user is guestbook user
	if(
		$user_data['id'] > 0 && 
		$_VARS['id'] == $user_data['id'] && 
		$user_data['idTable'] > 0 && 
		$_VARS['idTable'] == $user_data['idTable']
	) {
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:isUser", \Cms\Service\PageParser::checkForBlock($buffer,"if:isUser"));
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:isNotUser", "");
	// if not
	} else {
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:isUser", "");
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:isNotUser", \Cms\Service\PageParser::checkForBlock($buffer,"if:isNotUser"));
	}

	// has right to post
	if (!$user_data['id'] && $config->access == "private") {
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:hasPostRight", "");
	} else {
		$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "if:hasPostRight", \Cms\Service\PageParser::checkForBlock($buffer,"if:hasPostRight"));
	}

	if ($num_discussions > 0) {
		$temp = "";
		$i=0;
		$aEntries = $oForum->getEntries(0, ($fo_start-1), 'DESC');
		foreach((array)$aEntries as $my_discussions) {
			$oForum->printtopics($my_discussions, 0, 'DESC');
		}
	} else {
		$temp = \Cms\Service\PageParser::checkForBlock($buffer, "noentry");
	}

	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "entry_loop", $temp);
	$buffer = str_replace("<#count#>", $num_discussions, $buffer);

} elseif($discussions_action == "post") {

	// rechte checken
	if(
		(
			!$user_data['id'] ||
			!$user_data['idTable']
		) && 
		$config->access == "private"
	) {
		$buffer = \Cms\Service\PageParser::checkForBlock($my_element['content'], "notpermitted");	
	}

	if(
		$discussions_parent_id > 0
	) {
		$my_discussions = get_data(db_query("SELECT subject FROM community_forum_data WHERE id = ".$discussions_parent_id.""));
		$buffer = str_replace("<#subject#>","Re: ".$my_discussions['subject'],$buffer);
	}

	$buffer = str_replace("<#discussions_parent_id#>", (int)$discussions_parent_id,$buffer);

} elseif($discussions_action == "detail") {

	// ID der Diskussion ("oberster Post" auf den sich der aktuelle Post bezieht) ermitteln.
	// Der ID der Diskussion steht danach in "$disc_id".
	$disc_id = $discussions_id;
	$res = db_query("SELECT *, UNIX_TIMESTAMP(created) as created FROM community_forum_data WHERE id = '$disc_id'");
	$my_discussions = get_data($res);

	while ($my_discussions['parent_id'] != 0) {
		$res = db_query("SELECT *, UNIX_TIMESTAMP(created) as created FROM community_forum_data WHERE id = '$disc_id'");
		$my_discussions = get_data($res);
		$disc_id = $my_discussions['parent_id'];
	} 

	$temp            = "";
	$i               = 0;
	$aVars           = array();
	$aVarsPlus       = array();
	$sSelect         = "";
	$buffer_complete = $buffer;
	$buffer          = \Cms\Service\PageParser::checkForBlock($buffer, "entry_loop");

	parseCustomerTemplate($buffer, $aVars, $aVarsPlus, $sSelect, "detail", $user_data['idTable']);
	$aVarsPlus = array();

	$oForum->printtopics($my_discussions, 0, 'ASC');

	$buffer = \Cms\Service\PageParser::replaceBlock($buffer_complete, "entry_loop", $temp);

	parseCustomerTemplate($buffer, $aVars, $aVarsPlus, $sSelect, "detail", $user_data['idTable']);
	$aVarsPlus = array();

	$my_discussions = get_data(db_query("SELECT *, UNIX_TIMESTAMP(created) as created FROM community_forum_data WHERE id = $discussions_id"));

	$created = strftime("%d. %B %Y %H:%M",$my_discussions['created']);

	if ($my_discussions['idUser'] > 0) {
		$my_user = get_data(db_query("SELECT ".$sSelect." db.email, db.nickname, db.active FROM customer_db_".$my_discussions['tblUser']." db WHERE id = '".$my_discussions['idUser']."' LIMIT 1"));
		$sUserName = $my_user['nickname'];
		$sUserEmail = $my_user['email'];

		foreach ((array)$aVars as $val) {
			$sVar = "<#".$val[2]."#>";
			if ($val[1][1] == "Select Field") $val[1][1] = "select";
			$content = getFieldOutput($val[1][1], $my_user[$val[1][0]], $val[3], $val[1]);
			$buffer = str_replace($sVar,$content,$buffer);
		}
	} else {
		$sUserEmail = $my_discussions['email'];
		$sUserName = $my_discussions['name'];
	}

	$buffer = str_replace("<#discussions_parent_id#>", (int)$my_discussions['id'], $buffer);
	$buffer = str_replace("<#author_email#>", $sUserEmail, $buffer);
	$buffer = str_replace("<#email#>", $sUserEmail, $buffer);
	$buffer = str_replace("<#author#>", $sUserName, $buffer);
	$buffer = str_replace("<#subject#>", $my_discussions['subject'], $buffer);
	$buffer = str_replace("<#created#>", $created, $buffer);
	$buffer = str_replace("<#text#>", nl2br($my_discussions['text']), $buffer);
	$buffer = str_replace("<#topic_id#>", (int)$my_discussions['id'], $buffer);
	$buffer = str_replace("<#start#>", $fo_start, $buffer);

}

$buffer = str_replace("<#PHP_SELF#>",$_SERVER['PHP_SELF'],$buffer);
$buffer = str_replace("<#start#>", $fo_start, $buffer);
$pos    =0;
while ($pos = strpos($buffer,'<#',$pos)) {
	$end    = strpos($buffer,'#>',$pos);
	$var    = substr($buffer, $pos+2, $end-$pos-2);
	$buffer = substr($buffer, 0, $pos).$$var.substr($buffer, $end+2);
}

echo $buffer;

?>