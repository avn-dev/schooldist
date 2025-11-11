<?php
/*
 * Created on 16.10.2006
 * Die neue Galerieübersicht
 * Fährt für jede Galerie
 * Template:
 * #gallery# #/gallery#
 * headline
 * description
 * gallery_id
 * created
 * image : position : size_id
 */




// Lies Template aus
$sTemplate = $element_data['content'];

// Hole Galerie-Block
$sTemplateGallery = \Cms\Service\PageParser::checkForBlock($sTemplate,"gallery");

/* ================================================== */

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$iLimit = (int)$oConfig->galery2_pagination_limit;

$sLimit = "";

if($iLimit > 0)
{
	if(!isset($_VARS['page_nr']))
	{
		$iPageNr = 1;
	}
	else
	{
		$iPageNr = (int)$_VARS['page_nr'];
	}

	$iOffset = ($iPageNr - 1) * $iLimit;

	$sLimit = "LIMIT " . $iOffset . " , " . $iLimit;
}

/* ================================================== */

// Lade Galerien
$sLoadGallery = "SELECT SQL_CALC_FOUND_ROWS *,
				UNIX_TIMESTAMP(`created`) `created`,
				UNIX_TIMESTAMP(`modified`) `modified`,
				UNIX_TIMESTAMP(`date`) `date`
			FROM `gallery2_list` WHERE active=1 ORDER BY date DESC
			{LIMIT}
";
$sLoadGallery = str_replace('{LIMIT}', $sLimit, $sLoadGallery);
$rGallery = db_query($sLoadGallery);

/* ================================================== */

$iTotalCount = DB::getQueryOne('SELECT FOUND_ROWS() as `count`');

if($iPageNr == 1)
{
	$sPrev	= '';

	if($iTotalCount > 0)
	{
		$sStart	= 1;
	}
	else
	{
		$sStart	= 0;
	}
}
else
{
	$sPrev	= $iPageNr - 1;
	$sStart	= ($iPageNr-1) * $iLimit + 1;
}

if(($iPageNr * $iLimit) > $iTotalCount)
{
	$sEnd = $iTotalCount;
}
else
{
	$sEnd = $iPageNr * $iLimit;
}

if(($iPageNr * $iLimit) >= $iTotalCount)
{
	$sNext = '';
}
else
{
	$sNext = $iPageNr + 1;
}

/* ================================================== */

// Wiederhole Template f�r jede Galerie
$sOutput="";

while($my_gallery = get_data($rGallery))
{
	// hole Blockschnipsel
	$sTempBlock = $sTemplateGallery;

	// ersetze feste Tags
	$sTempBlock=str_replace("<#headline#>",		$my_gallery['name'],$sTempBlock);
	$sTempBlock=str_replace("<#description#>",	$my_gallery['description'],$sTempBlock);
	$sTempBlock=str_replace("<#gallery_id#>",	$my_gallery['id'],$sTempBlock);
	$sTempBlock=str_replace("<#date#>",			strftime("%x", $my_gallery['date']), $sTempBlock);

	// ersetze Image-Tags
	$parts = explode("<#image:",$sTempBlock);

	$replacor=$parts[0];

	if(is_array($parts))
	foreach($parts as $key => $value)
	{
		if($key==0) continue;

		$pieces=explode("#>",$value);
		$bits=explode(":",$pieces[0]);

		if(count($bits)==2)
		{
			// b-0 ist die Position
			// b-1 ist die size
			$sSQL="SELECT * FROM `gallery2_files` f LEFT JOIN gallery2_items i ON f.`item_id`=i.id " .
					"WHERE i.position=".$bits[0]." AND f.size_id=".$bits[1]." AND i.list_id=".$my_gallery['id']." AND i.active=1 LIMIT 0,1";

			$my=get_data(db_query($sSQL));

			if(!$my['filename'])
			{
				$my['path']="/media/";
				$my['filename']="spacer.gif";
			}

			$replacor.=$my['path'].$my['filename'];
		}
		else
		{
			$replacor.="<!-- error in image-tag: needs 2 parameters -->";
		}

		$replacor.=$pieces[1];

	}

	$sOutput.=$replacor;
}

$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate, "gallery", $sOutput);

/* ================================================== */

// Get pagination block(s)
while(($sPagination = \Cms\Service\PageParser::checkForBlock($sTemplate, "pagination")) != '')
{
	$sPagination = \Cms\Service\PageParser::checkForBlock($sTemplate, "pagination");

	$sPagination=str_replace("<#start#>",		$sStart,$sPagination);
	$sPagination=str_replace("<#end#>",			$sEnd,$sPagination);
	$sPagination=str_replace("<#total#>",		$iTotalCount, $sPagination);
	$sPagination=str_replace("<#prevlink#>",	$sPrev,$sPagination);
	$sPagination=str_replace("<#nextlink#>",	$sNext,$sPagination);

	$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate, "pagination", $sPagination);
}

/* ================================================== */

echo $sTemplate;

?>