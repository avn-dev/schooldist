<?php

$config = 	new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
$config->news_id;

//Datenbankabfrage f�r die Grundeinstellungen der News
$aInit =	get_data(db_query($db_data['module'],"SELECT * FROM news_init WHERE id = ".$config->news_id.""));
$sSQL =		"SELECT * FROM news_options WHERE active = '1' AND (news_id= '".$aInit['id']."' OR news_id= '0')";
$rResData =	db_query($sSQL);


$i = 		"0";
while($aMyData=get_data($rResData))
{
  $aTag[$i][0]=$aMyData['id'];
  $aTag[$i][1]=$aMyData['tagname'];
  $aTag[$i][2]=$aMyData['type'];
  $i++;
}

$sSQL =		"SELECT * FROM news_data WHERE active = '1' AND news_id= '".$aInit['id']."' ORDER BY field_6 DESC, field_1 DESC";
$rResData=	db_query($sSQL);


$sBlockbuffer =	\Cms\Service\PageParser::checkForBlock($element_data['content'], "news");

$zaehler=0;
$test=0;
while($aMyData=get_data($rResData))
{
  $test++;
  // Falls die maximal Anzahl der News noch nicht erreicht wurde...
  if($zaehler<$aInit['max'])
  {
    $sTimestamp=strtotimestamp(substr($aMyData['field_1'], 6, 2).".".substr($aMyData['field_1'], 4, 2).".".substr($aMyData['field_1'], 0, 4)." ".substr($aMyData['field_1'], 8, 2).":".substr($aMyData['field_1'], 10, 2).":".substr($aMyData['field_1'], 12, 2));
    // Falls es eine TopNews ist...
    if($aMyData['field_6']=="1")
    {
		$sIfTop="$sIfTopBuffer";
	    $sIfNotTop="";
	    // Falls ein Verfallsdatum angegeben ist...
	    if($aMyData['field_5']>0)
	    {
	    	$sTimestampExpire=strtotimestamp(substr($aMyData['field_5'], 6, 2).".".substr($aMyData['field_5'], 4, 2).".".substr($aMyData['field_5'], 0, 4)." ".substr($aMyData['field_5'], 8, 2).":".substr($aMyData['field_5'], 10, 2).":".substr($aMyData['field_5'], 12, 2));
	    	// Falls das Verfallsdatum abgelaufen ist wird zum nächsten gesprungen.
		    if($sTimestampExpire<=time())
		    {
		    	continue;
		    }
	    }
	    // Falls kein Verfallsdatum angegeben ist ...
	    else
	    {
	    	// Falls die Anzeigezeit überschritten ist...
		    if(time()>=$sTimestamp+86400*$aInit['expire'])
		    {
		    	continue;
		    }
	    }
    }
    // Falls es keine TopNews ist...
    else
    {
      $sIfTop =		"";
      $sIfNotTop =	"$sIfNotTopBuffer";
      // Falls die Anzeigezeit überschritten ist...
      if(time()>=$sTimestamp+86400*$aInit['expire'])
      {
      		// Falls die Mindestanzahl der News überschritten ist...
		    if($zaehler>=$aInit['min'])
		    {
		    	break;
		    }
      }
    }
  }
  // Falls die Maximale Anzahl der News erreicht ist...
  else
  {
    break;
  }


  $sBlockBufferTemp = $sBlockbuffer;

  $sBlockBufferTemp=\Cms\Service\PageParser::replaceBlock($sBlockBufferTemp, "if:top", $sIfTop);
  $sBlockBufferTemp=\Cms\Service\PageParser::replaceBlock($sBlockBufferTemp, "ifnot:top", $sIfNotTop);


  for($i=0; $i<count($aTag); $i++)
  {
    $sBlockBufferTemp= str_replace("<#field_".$aTag[$i][0]."#>", $aMyData["field_".$aTag[$i][0]], $sBlockBufferTemp);
    if($aTag[$i][2]=="image"){
    	$aBildInfoTemp=getimagesize(\Util::getDocumentRoot()."media/".$aMyData["field_".$aTag[$i][0]]);
    	$sBlockBufferTemp = str_replace("<#field_".$aTag[$i][0]."_height#>", $aBildInfoTemp[1], $sBlockBufferTemp);
    	$sBlockBufferTemp = str_replace("<#field_".$aTag[$i][0]."_width#>", $aBildInfoTemp[0], $sBlockBufferTemp);
    	$sBlockBufferTemp = str_replace("<#".$aTag[$i][1]."_height#>", $bildinfotemp[1], $sBlockBufferTemp);
    	$sBlockBufferTemp = str_replace("<#".$aTag[$i][1]."_width#>", $bildinfotemp[0], $sBlockBufferTemp);
    }
    if($aTag[$i][1]){$sBlockBufferTemp= str_replace("<#".$aTag[$i][1]."#>", $aMyData["field_".$aTag[$i][0]], $sBlockBufferTemp);}
  }

  $sBlockBufferTemp= str_replace("<#date#>",	substr($aMyData["field_1"],6,2).".".substr($aMyData["field_1"],4,2).".".substr($aMyData["field_1"],0,4), $sBlockBufferTemp);
  $sBlockBufferTemp= str_replace("<#time#>",	substr($aMyData["field_1"],8,2).":".substr($aMyData["field_1"],10,2), $sBlockBufferTemp);
  $sBlockBufferTemp= str_replace("<#counter#>",	$zaehler, $sBlockBufferTemp);
  $sBlockBuffer.=$sBlockBufferTemp;

  $zaehler++;
}

$sOutput=\Cms\Service\PageParser::replaceBlock($element_data['content'],"news", $sBlockBuffer);

$sOutput = str_replace("<#newsname#>",$aInit['name'],$sOutput);

echo $sOutput;
?>
