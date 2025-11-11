<?php

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(!$config->iShow) {
	$config->iShow = "20";
}

$page_limit = 20;

$oSite = $this->oPage->getJoinedObject('site');

if(isset($_VARS['searchString'])) {
	$searchstring = $_VARS['searchString'];
}

$buffer = $aElement['content'] = $element_data['content'];

$sform = \Cms\Service\PageParser::checkForBlock($aElement['content'], 'form');
$slist = \Cms\Service\PageParser::checkForBlock($aElement['content'], 'list');
$srow = \Cms\Service\PageParser::checkForBlock($slist, 'row');

if($searchstring) {

	$search = new \Search\Service\Search($oSite);

	$search->run( $searchstring );
	$search->orderby( "ps_score" );

	if(isset($_VARS['page'])) {
		$start = $_VARS['page'];
	} else {
		$start = 0;
	}

	$total = count($search->obj_list);

	$max = $search->obj_list[0]['ps_score'];
	
	$end = $start+$config->iShow;
	
	if($end > $total) $end = $total;

	$cache_row = "";
	for($i=$start;$i<($end);$i++) {

		$temp_row = $buffer_row;
		$elem = $search->obj_list[$i];

		$oPage = Cms\Entity\Page::getInstance($elem['page_id']);
		
		$ext = strtolower(substr($elem['url'], strrpos($elem['url'], ".")+1));
		
		$temp_row = str_replace('<#url#>',$elem['url'], $srow);
		$temp_row = str_replace('<#nr#>', $i, $temp_row);
		$temp_row = str_replace('<#title#>',$elem['title'], $temp_row);
		$temp_row = str_replace('<#desc#>',$elem['desc'], $temp_row);
		$temp_row = str_replace('<#score#>',intval($elem['ps_score']/$max*100), $temp_row);
		$temp_row = str_replace('<#track#>', $oPage->getTrack(), $temp_row);

		$cache_row .= $temp_row;
	}
 
	$buffer_list = \Cms\Service\PageParser::replaceBlock($slist,"row",$cache_row);

}

$sbacklink = \Cms\Service\PageParser::checkForBlock($aElement['content'], 'backlink');

if($start>0) {
	$temp_backlink = str_replace("<#back#>", "?page=".($start - $config->iShow)."&searchString=".$searchstring, $sbacklink);
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "backlink", $temp_backlink);
} else {
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "backlink", "");
}

$sforwardlink = \Cms\Service\PageParser::checkForBlock($aElement['content'], 'forwardlink');
if($end!=$total) {
	$temp_forwardlink = str_replace("<#next#>", "?page=".($start+$config->iShow)."&searchString=".$searchstring, $sforwardlink);
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "forwardlink", $temp_forwardlink);
} else {
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "forwardlink", "");
}

if($total > 0) {
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "list", $buffer_list);
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "no_results", "");
} elseif($searchstring) {
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "list", "");
	$sNoResults = \Cms\Service\PageParser::checkForBlock($buffer, 'no_results');
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "no_results", $sNoResults);
} else {
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "list", "");
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "no_results", "");
}

//form anpassen
$cache_form = $sform;

$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "form", $cache_form);

$buffer = str_replace("<#total#>", (int)$total, $buffer);
$buffer = str_replace("<#search#>", \Util::convertHtmlEntities($searchstring), $buffer);
$buffer = str_replace("<#PHP_SELF#>", $_SERVER['PHP_SELF'] , $buffer);

echo $buffer;
