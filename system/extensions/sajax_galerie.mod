<?php
include(\Util::getDocumentRoot()."system/includes/sajax.inc.php");

if(!function_exists('getEvent'))
{
 function getEvent($id)
 {
  $id = intval($id);
  $sEmptyFlyer = "media/template/spacer.gif";
  $sError = "/".$sEmptyFlyer."|Es is ein Fehler aufgetreten|<p>Es wurde versucht, eine ung&uuml;ltige Event-Id aufzurufen. Bitte versuchen Sie es erneut.</p>";
  if ($id > 0)
  {
  $sQuery = "SELECT * FROM eventmodul_events WHERE id='$id' LIMIT 1";
  $rRes = db_query($sQuery);
  $aData = get_data($rRes);
  if ($aData)
    {
      $sFlyer = $aData['teaser_url'];
      if ($sFlyer == "")
      {
        $sFlyer = $sEmptyFlyer;
      }
      $sTitle = $aData['title'];
      $sText = $aData['text'];

      $sResult = "/".$sFlyer."|".$sTitle."|".$sText;
    }
    else
    {
      $sResult = $sError;
    }
  }
  else
  {
    $sResult = $sError;
  }
  return $sResult;
 }
}


$trennzeichen="|M|";

if(!function_exists('getBildURL')){
 function getBildURL($id, $sizeid){
 	$sSQL="SELECT f.path, f.filename " .
			"FROM `gallery2_items` i, gallery2_files f " .
			"WHERE i.id = f.item_id " .
			"AND f.size_id = '".$sizeid."' " .
			"AND i.active = '1' " .
			"AND i.id = '" .$id."'";
	$tURL=get_data(db_query($sSQL));
	return $tURL['path'].$tURL['filename'];
 }
}
if(!function_exists('getGalerieInfos')){
 function getGalerieInfos($gid){
 	global $trennzeichen;
 	$sSQL="SELECT name, description, date" .
 			"FROM gallery2_list " .
 			"WHERE id = ".$gid.
 			"AND active = '1'";
 	$tInfos=get_data(db_query($sSQL));
 	return $tInfos['name'].$trennzeichen.$tInfos['description'].$trennzeichen.$tInfos['date'];
 }
}
/*
if(!function_exists('getLowestId')){
 function getLowestId($gid){
 	$sSQL="SELECT MIN(id) as id " .
 			"FROM `gallery2_items` " .
 			"WHERE list_id='".$gid."'";
 	$tID=get_data(db_query($sSQL));
 	return $tID['id'];
 }
}
*/


if(!function_exists('showGallery')){
 function showGallery($gid, $startitem){
	$sSQL="SELECT COUNT(*) as count " .
			"FROM gallery2_items " .
			"WHERE list_id='".$gid."'";
	$countertmp=get_data(db_query($sSQL));
	$maxcount=$countertmp['count'];

	#echo $sSQL."<br>";
	#echo $maxcount;
	$sSQL="SELECT id " .
			"FROM gallery2_items " .
			"WHERE list_id='".$gid."' " .
			"LIMIT ".$startitem.", 11";
	$tshowGalerie=db_query($sSQL);
	$zaehler=1;
	$tempString="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" height=308 width=276><tr>";
	while($tshowGalerieRow =get_data($tshowGalerie)){
		$tempString.="<td height=77 width=92><img src=\"".getBildURL($tshowGalerieRow['id'], "2")."\" height=68 width=90 onclick=\"x_getBildURL(".$tshowGalerieRow['id'].",2, getBildURL_cb)\"></td>";
		if($zaehler%3==0){$tempString.="</tr><tr>";}
		$zaehler++;
	}

	for($i=$zaehler; $i<=11; $i++){
		$tempString.= "<td height=77 width=92>$nbsp</td>";
		if($i%3==0){$tempString.= "</tr><tr>";}
	}

	$tempString.="<td><input type=\"button\" value=\"-\"";
	if($startitem>9){$tempString.=" onclick=\"previous(".$gid.")\"";}
	$tempString.="><input type=\"button\" value=\"+\"";
	if($startitem<$maxcount-11){$tempString.=" onclick=\"next(".$gid.")\"";}
	$tempString.="></td></tr></table>";
	return $tempString;
 }
}

#echo showGallery(3,1);

sajax_init();
sajax_export("getEvent");
sajax_export("getBildURL");
sajax_export("getGalerieInfos");
sajax_export("showGallery");
sajax_handle_client_request();
?>
<script type="text/javascript">
<!--
<?php
sajax_show_javascript();
?>
-->
</script>

