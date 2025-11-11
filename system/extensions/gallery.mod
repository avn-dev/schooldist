<?php

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(!$config->widthImage) $config->widthImage = "800";
if(!$config->heightImage) $config->heightImage = "600";
if(!$config->widthThumb) $config->widthThumb = "140";
if(!$config->heightThumb) $config->heightThumb = "140";
if(!$config->numberpp) $config->numberpp = "9";
if(!$config->colspanpp) $config->colspanpp = "3";
if ($config->order_by) {
	$sOrderBy = " ORDER BY `gd`.`".$config->order_by.'` '.$config->direction;
}

$upload_dir = $system_data['upload_dir'];
$page_id = $element_data['page_id'];
$element_id = $element_data['id'];

$my_element['content'] = $element_data['content'];

$ga_start 	= $_VARS['ga_start'];
$ga_keyword = $_VARS['ga_keyword'];
$ga_action 	= $_VARS['ga_action'];
$ga_id 		= $_VARS['ga_id'];

if(0<intval($_VARS['gallery_id'])) {
	$gallery_id = intval($_VARS['gallery_id']);	
} else {
	$gallery_id = $config->gallery_id;	
}

$aGallery = DB::getRowData('gallery_init', $gallery_id);

if(!$ga_start) {
	$ga_start = 1;
}

if($ga_action == "detail") {

	$buffer = \Cms\Service\PageParser::checkForBlock($my_element['content'],'detail');
	
	$buffer_pages 	= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'pages_detail');
	$buffer_backlink = \Cms\Service\PageParser::checkForBlock($buffer_pages, 'backlink');
	$buffer_forwardlink = \Cms\Service\PageParser::checkForBlock($buffer_pages, 'forwardlink');

	$buffer_backlink=str_replace("<#gallery_id#>", $gallery_id, $buffer_backlink);
	$buffer_forwardlink=str_replace("<#gallery_id#>", $gallery_id, $buffer_forwardlink);

	if($_VARS['entry_id'] > 0) {

		$arrBack = array();
		$arrNext = array();
		$arrEntry = DB::getQueryRow("SELECT * from gallery_data `gd` WHERE id = ".(int)$_VARS['entry_id']." ");
		$_VARS['ga_id'] = $arrEntry['image'];
		
		$resGallery = DB::getQueryRows("SELECT * from gallery_data `gd` WHERE gallery_id = '".$arrEntry['gallery_id']."' AND active = 1 ".$sOrderBy);
		foreach($resGallery as $arrGallery) {
			if($arrGallery['id'] == $_VARS['entry_id']) {
				$arrNext = $arrGallery;
				break;
			}
			$arrBack = $arrGallery;
		}

		// neuen Parameter an Links anh�ngen

		if($arrBack['id'])
			$ga_backlink = str_replace("<#back_entryid#>",$arrBack['id'],$buffer_backlink);
		if($arrNext['id'])
			$ga_forwardlink = str_replace("<#next_entryid#>",$arrNext['id'],$buffer_forwardlink);

		$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"backlink",$ga_backlink);
		$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"forwardlink",$ga_forwardlink);

		$buffer = str_replace("<#displayPages#>",$buffer_pages,$buffer);
		
		$buffer = str_replace("<#back_fileid#>",$my_media['id'],$buffer);
		$buffer = str_replace("<#entryid#>",$my['id'],$buffer);
	}

	$my_media = DB::getQueryRow("SELECT folder,file,id,description,extension FROM cms_media WHERE id = ".(int)$_VARS['ga_id']." ");

	$sFile = \Util::getDocumentRoot()."storage/public/".$my_media['folder']."".$my_media['file']."";
	$sName = strtolower("detail_".$my_media['id']."_".$config->widthImage."_".$config->heightImage.".".$my_media['extension']."");
	$sDetail = \Util::getDocumentRoot()."system/extensions/gallery/".$sName;

	if(!is_file($sDetail)) {
		\Core\Helper\Image::saveResizeImage($sFile,$sDetail,$config->widthImage,$config->heightImage, $my_media['extension']);
	}

	$size = @getimagesize($sDetail);
	$width = $size[0];
	$height = $size[1];
	$folder = $my_media['folder'];
	$image = $sName;
	$file = $my_media['file'];
	$fileid = $my_media['id'];
	$description = $my_media['description'];
	DB::executeQuery("UPDATE gallery_data SET views = views+1 WHERE gallery_id = ".(int)$gallery_id." AND image = ".(int)$ga_id."");

} else {

	$ga_number = \Cms\Service\PageParser::checkForBlock($element_data['content'],'numberpp');
	$ga_cols = \Cms\Service\PageParser::checkForBlock($element_data['content'],'colspp');
	if(!$ga_number) $ga_number = $config->numberpp;
	if(!$ga_cols) $ga_cols = $config->colspanpp;

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'gallery');

	$buffer_pages 	= \Cms\Service\PageParser::checkForBlock($element_data['content'],'pages');
	$buffer_backlink = \Cms\Service\PageParser::checkForBlock($buffer_pages,'backlink');
	$buffer_forwardlink = \Cms\Service\PageParser::checkForBlock($buffer_pages,'forwardlink');

	$buffer_row = \Cms\Service\PageParser::checkForBlock($element_data['content'],'row');
	$buffer_col = \Cms\Service\PageParser::checkForBlock($element_data['content'],'col');

	$sSql = "SELECT * FROM gallery_data WHERE keywords LIKE :search AND gallery_id = ".(int)$gallery_id." AND active = 1";
	$aSql = array(
		'search'=>'%'.$ga_keyword.'%'
	);
	$res = (array)DB::executePreparedQuery($sSql, $aSql);
	$total = count($res);

	if(($ga_start+$ga_number-1) < $total) {
		$ga_end = ($ga_start+$ga_number-1);
	} else {
		$ga_end = $total;
	}
	
	$buffer_backlink=str_replace("<#gallery_id#>",$gallery_id,$buffer_backlink);
	$buffer_forwardlink=str_replace("<#gallery_id#>",$gallery_id,$buffer_forwardlink);

	if($ga_start > 1) {
		$ga_backlink = str_replace("<#ga_startb#>",($ga_start-$ga_number),$buffer_backlink);
	}
	if(($ga_start+$ga_number) <= $total) {
		$ga_forwardlink = str_replace("<#ga_startf#>",($ga_start+$ga_number),$buffer_forwardlink);
	}

	$buffer_pages = str_replace("<#start#>",(int)$ga_start,$buffer_pages);
	$buffer_pages = str_replace("<#end#>",$ga_end,$buffer_pages);
	$buffer_pages = str_replace("<#total#>",$total,$buffer_pages);
	$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"backlink",$ga_backlink);
	$buffer_pages = \Cms\Service\PageParser::replaceBlock($buffer_pages,"forwardlink",$ga_forwardlink);

	$buffer = str_replace("<#displayPages#>",$buffer_pages,$buffer);
	
	$sSql = "
		SELECT 
			`gd`.* ,
			`sm`.`id` `media_id`,
			`sm`.`folder` `media_folder`,
			`sm`.`file` `media_file`,
			`sm`.`extension` `media_extension`
		FROM 
			`gallery_data` `gd` JOIN
			`cms_media` `sm` ON
				`gd`.`image` = `sm`.`id`
		WHERE 
			`gd`.`keywords` LIKE :search AND 
			`gd`.`gallery_id` = :gallery_id AND 
			`gd`.`active` = 1 
		".$sOrderBy." 
		LIMIT ".($ga_start-1).",$ga_number";
	$aSql = array(
		'gallery_id'=>(int)$gallery_id,
		'search'=>'%'.$ga_keyword.'%'
	);

	$res = DB::executePreparedQuery($sSql, $aSql);
	
	$cache = "";
	$i=1;
	$j=1;
	foreach($res as $my) {

		$sFile = \Util::getDocumentRoot()."storage/public/".$my['media_folder']."".$my['media_file']."";
		$sName = strtolower("thumb_".$my['media_id']."_".filemtime($sFile)."_".$config->widthThumb."_".$config->heightThumb.".".$my['media_extension']."");
		$sThumb = \Util::getDocumentRoot()."storage/public/gallery/".$sName;
	
		$sFileLarge = \Util::getDocumentRoot()."storage/public/".$my['media_folder']."".$my['media_file']."";
		$sNameLarge = strtolower("detail_".$my['media_id']."_".filemtime($sFileLarge)."_".$config->widthImage."_".$config->heightImage.".".$my['media_extension']."");
		$sDetail = \Util::getDocumentRoot()."storage/public/gallery/".$sNameLarge;

		if(!is_file($sDetail)) {
			\Core\Helper\Image::saveResizeImage($sFileLarge, $sDetail, $config->widthImage, $config->heightImage);
		}

		if(!is_file($sThumb)) {
			\Core\Helper\Image::saveResizeImage($sFile, $sThumb, $config->widthThumb, $config->heightThumb);
		}

		if(!is_file($sThumb)) {
			DB::executeQuery("DELETE FROM gallery_data WHERE id = ".(int)$my['id']." LIMIT 1");
			continue;
		}

		$temp = $buffer_col;
		$temp_row = $buffer_row;
		$temp = str_replace("<#file#>",$sName,$temp);
		$temp = str_replace("<#file_large#>",$sNameLarge,$temp);
		$temp = str_replace("<#folder#>", $my['media_folder'],$temp);
		$temp = str_replace("<#fileid#>", $my['media_id'],$temp);
		$temp = str_replace("<#id#>",$my['image'],$temp);
		$temp = str_replace("<#entryid#>",$my['id'],$temp);
		$temp = str_replace("<#description#>",$my['description'],$temp);

		$cache_row .= $temp;
		if(($i == $ga_cols) || ($j+$ga_start-1) == $total) {
			$cache .= \Cms\Service\PageParser::replaceBlock($temp_row,"col",$cache_row);
			$cache_row = "";
			$i=0;
		}

		$i++;
		$j++;
	}
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer,"row",$cache);
}

$PHP_SELF = $_SERVER['PHP_SELF'];

$buffer = str_replace('<#title#>', $aGallery['name'], $buffer);

$pos=0;
while($pos = strpos($buffer,'<#',$pos)) {
	$end = strpos($buffer,'#>',$pos);
	$var = substr($buffer, $pos+2, $end-$pos-2);
	$buffer = substr($buffer, 0, $pos)  .  $$var  .  substr($buffer, $end+2);
}

echo $buffer;
