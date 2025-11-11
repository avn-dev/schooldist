<?

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if($_VARS['task'] == "delete") {

	db_query($db_data['module'],"DELETE FROM community_blacklist WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' AND id = '".$_VARS['idEntry']."' ");

}

$buffer_output 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'output');
$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

$cache = "";

$res = db_query($db_data['module'],"SELECT id, idBlacklist, tblBlacklist FROM community_blacklist WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."'");

while($my = get_data($res)) {
	$buffer = $buffer_entry;

	$my_buddy = get_data(db_query($db_data['module'],"SELECT c.id,c.nickname,UNIX_TIMESTAMP(w.changed) as lastaction FROM customer_db_".$my['tblBlacklist']." AS c LEFT OUTER JOIN community_whoisonline AS w ON c.id = w.idUser WHERE c.id = '".$my['idBlacklist']."'"));

	$buffer = str_replace("<#name#>",$my_buddy['nickname'],$buffer);
	$buffer = str_replace("<#idEntry#>",$my['id'],$buffer);
	$buffer = str_replace("<#id#>",$my_buddy['id'],$buffer);
	$buffer = str_replace("<#idUser#>",$my_buddy['id'],$buffer);
	$buffer = str_replace("<#tblUser#>",$my['tblBlacklist'],$buffer);
	$buffer = str_replace("<#onlinestatus#>",(($my_buddy['lastaction']>0)?"yes":"no"),$buffer);

	$cache .= $buffer;
}

echo \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);
 
?>