<?php
/*
 * Created on 16.10.2006
 *
 * templategesteuerte Detailanzeige der Bildergalerie v2
 */

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$oSmarty = new \Cms\Service\Smarty();

if($oConfig->gallery_id) {
	$_VARS['gallery_id'] = $oConfig->gallery_id;
} else {
	$_VARS['gallery_id'] = (int)$_VARS['gallery_id'];
}

// Titel und Beschreibung ersetzen
$sSQL = "SELECT * FROM `gallery2_list` WHERE `active`='1' AND `id`='" . intval($_VARS['gallery_id']) . "'";
$rRes = db_query($sSQL);
$aMy = get_data($rRes);

////////////////////////////////////////////
////////////////////////////////////////////
// Preview Bereich
////////////////////////////////////////////
////////////////////////////////////////////

$per_page = $oConfig->repeat_x * $oConfig->repeat_y;

// Lade Galeriebilder

// Bl�ttern
$iStart = intval($_VARS['start']);

$sLimit="LIMIT ".(int)$iStart.",".(int)$per_page;

// Erzeuge die Bl�ttern-Links
$all=get_data(db_query("SELECT COUNT(*) AS anzahl FROM gallery2_items i " .
				"WHERE i.list_id=".intval($_VARS['gallery_id'])." AND i.active=1 "));

$iBack = 0;
$iNext = 0; 

if($iStart > 0) {

	$diff = intval($_VARS['start']) - $per_page;
	if(0<$diff) {
		$iBack = $diff;
	} else {
		$iBack = 0;
	}
}

if($per_page < ($all['anzahl']-$iStart)) {
	$iNext = $iStart + $per_page;
}

// Lade die "Navi"


$sLoadGallery = "SELECT i.id, i.name FROM gallery2_items i " .
				"WHERE i.list_id=".intval($_VARS['gallery_id'])." AND i.active=1 ORDER BY i.position ".$sLimit;#

#echo $sLoadGallery.$sLimit;

$rGallery=db_query($sLoadGallery);

$aPictures = array();
while($aItem = get_data($rGallery)) {
	$sSql = "SELECT f.path, f.filename, f.size_id FROM gallery2_files f WHERE f.item_id = ".(int)$aItem['id']."";
	$aImages = DB::getQueryData($sSql);
	foreach((array)$aImages as $aImage) {
		$aPictures[$aItem['id']]['images'][$aImage['size_id']] = $aImage['path'].$aImage['filename'];
	}
	$aPictures[$aItem['id']]['name'] = $aItem['name'];
}

$aPictures = array_values($aPictures);

$sOutput='';
$position = 0;
$aRows = array();
for($y=1;$y<=$oConfig->repeat_y;$y++) {

	for($x=1;$x<=$oConfig->repeat_x;$x++) {

		if($aPictures[$position]) {
			
			$aRows[$y][$x] = array();
			$aRows[$y][$x]['name'] = $aPictures[$position]['name'];

			foreach((array)$aPictures[$position]['images'] as $iSize=>$sImage) {
				$aRows[$y][$x]['image'][$iSize] = $sImage;
			}

		} else {

			break 2;

		}
		
		$position++;

	}

}

$oSmarty->assign('iPerPage', $per_page);
$oSmarty->assign('iBack', $iBack);
$oSmarty->assign('iNext', $iNext);
$oSmarty->assign('iStart', $iStart);

$oSmarty->assign('aRows', $aRows);
$oSmarty->assign('iGallery', (int)$_VARS['gallery_id']);
$oSmarty->assign('sName', $aMy['name']);
$oSmarty->assign('sDescription', $aMy['description']);

$oSmarty->displayExtension($element_data)

?>