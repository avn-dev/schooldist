<?

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$buffer_output 	= $element_data['content'];
$buffer_entry 	= \Cms\Service\PageParser::checkForBlock($buffer_output,'entry');

$cache = "";

if(!$config->ftime) 		$config->ftime = "%x %X";
if(!$config->show) 			$config->show = 10;
if(!$config->startlevel) 	$config->startlevel = 1;
if(!$config->seperator) 	$config->seperator = " &raquo; ";

$sMenue = "";
if(!$config->includehidden) {
	$sMenue = " AND p.menue = 1 ";
}

$rChanges = db_query($db_data['module'],"SELECT p.id, MAX(l.time) as lasttime, p.path, p.file, p.title FROM system_logs l, cms_pages p WHERE l.`page_id` != '0' AND l.`page_id` = p.`id` AND p.active = 1 AND p.element = 'page' AND p.path LIKE '".$config->startpath."%' ".$sMenue." GROUP BY page_id ORDER BY lasttime DESC LIMIT ".$config->show."");

while($aChanges = get_data($rChanges)) {
	$buffer = $buffer_entry;
	$buffer = str_replace("<#link#>",		makelink($aChanges['id']),$buffer);
	$buffer = str_replace("<#track#>",		getPageTrack($aChanges['id'],$config->seperator,$config->startlevel),$buffer);

	$buffer = str_replace("<#title#>",		$aChanges['title'],$buffer);
	$buffer = str_replace("<#date#>",		strftime($config->ftime, $aChanges['lasttime']),$buffer);

	$cache .= $buffer;
}

echo \Cms\Service\PageParser::replaceBlock($buffer_output,"entry",$cache);

?>