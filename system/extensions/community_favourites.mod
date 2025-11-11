<?

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if($_VARS['task'] == "delete") {

	db_query($db_data['module'],"DELETE FROM community_favourites WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' AND id = '".$_VARS['idFavourite']."' ");

}

$buffer_output 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'output');
$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

$cache = "";

$res_favouriten = db_query($db_data['module'],"SELECT * FROM community_favourites WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' ");
while($my_favouriten = get_data($res_favouriten)) {
	$buffer = $buffer_entry;
	$buffer = str_replace("<#link#>",		makelink($my_favouriten['idPage']),$buffer);
	$buffer = str_replace("<#track#>",		getPageTrack($my_favouriten['idPage']," &raquo; ",1),$buffer);
	$buffer = str_replace("<#idFavourite#>",$my_favouriten['id'],$buffer);

	$buffer = str_replace("<#name#>",$my_buddy['nickname'],$buffer);
	$buffer = str_replace("<#idBuddy#>",$my['id'],$buffer);

	$cache .= $buffer;
}

echo \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);

?>