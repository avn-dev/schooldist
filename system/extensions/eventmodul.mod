<?php
/*
 * Created on 13.11.2006
 * @author: Bastian Hübner
 *
 * Dieses Modul zeigt eine übersicht der Veranstaltungen
 * einer ausgewählten Woche an
 *
 * Folgende Blöcke kann / muss es geben:
 * Monatsbuttons
 * Wochenbuttons
 * Tagesslots (mit Bild, Titel, Beschreibung, xyz)
 *
 *
 */


// DEBUG
#var_dump($_REQUEST);










////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
// Template holen
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////


// Lies Template aus
$sTemplate = $element_data['content'];


////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////









////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
// Funktionen
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////

if(!function_exists("translateMonth"))
{
  function translateMonth($iMonth)
  {
       $iMonth=intval($iMonth);

       switch ($iMonth) {
      case "1": $ret= "Januar";
        break;
      case "2": $ret= "Februar";
        break;
      case "3": $ret= "M�rz";
        break;
      case "4": $ret= "April";
        break;
      case "5": $ret= "Mai";
        break;
      case "6": $ret= "Juni";
        break;
      case "7": $ret= "Juli";
        break;
      case "8": $ret= "August";
        break;
      case "9": $ret= "September";
        break;
      case "10": $ret= "Oktober";
        break;
      case "11": $ret= "November";
        break;
      case "12": $ret= "Dezember";
        break;

      default:
        break;
    }

    return $ret;
  }
}


if(!function_exists("getWeeksInMonth"))
{
  function getWeeksInMonth($iMonth,$iYear)
  {
    $iDaysInMonth=date("t",mktime(0,0,0,$iMonth,1,$iYear));
    // Bestimme erste Woche

    $tsCounter=mktime(12,0,0,$iMonth,1,$iYear);
    $Sunday=1-date("w",$tsCounter);

    $aWeeksInMonth[]=mktime(12,0,0,$iMonth,$Sunday,$iYear);

    $Sunday=intval(date("d",$aWeeksInMonth[0]+604800));


    while($Sunday<$iDaysInMonth)
    {
      $aWeeksInMonth[]=mktime(12,0,0,$iMonth,$Sunday,$iYear);
      $Sunday+=7;
    }


    return $aWeeksInMonth;
  }
}



function get_tag_variable($tag, $template){
  $startposi=strpos(" ".$template, "<#".$tag.":");
  if(!$startposi){return false;}
  $startposi+=strlen("<#".$tag.":")-1;
  for($i=$startposi; $i<strlen($template); $i++)
  {
    if(substr($template, $i, 2)=="#>")
    {
      $return['wert']=substr($template, $startposi, $i-$startposi);
      break;
    }
  }
  $return['template']=str_replace("<#".$tag.":".$return['wert']."#>", "", $template);
  return $return;
}



function checkforblockmitvariable($template,$block_name) {
  if(@strstr($template,$block_name)) {
    $len  = strlen($block_name) + 3;
    $pos  = strpos($template,("<#".$block_name.":"));
    if($pos)
    {
      for($i=$pos+$len; $i<strlen($template); $i++)
      {
        if(substr($template, $i, 2)=="#>")
        {
          $vari=substr($template, $pos+$len, $i-($pos+$len));
          $startpos=$i+2;
          break;
        }
      }
    $end  = strpos($template,"<#/".$block_name."#>",$startpos);
    $code[0] = substr($template, $startpos, ($end-$startpos));
    $code[1] = $vari;
    }
  }
  return $pos?$code:false;
}


function replaceblockmitvariable($template,$block_name,$replace = "") {
  if(!(strpos($template,$block_name)===false)) {
    $len  = strlen($block_name) + 3;
    $pos  = strpos($template,"<#".$block_name.":");
    $end  = strpos($template,"<#/".$block_name."#>", $pos);

    if($pos&&$end){
      $template = substr($template, 0, $pos)  .  $replace  .  substr($template, $end+$len+2);
    }
  }
  return $template;
}

////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////














////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
// Bearbeite Bl�cke
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////
// Monatsbuttons
////////////////////////////////////////////////////////////

$sMonthSQL="SELECT * FROM eventmodul_released_months WHERE active=1 AND (year>= '".date("Y")."' AND month >= '".date("n")."') " .
    " OR (year> '".date("Y")."') ORDER BY year, month ASC LIMIT 0,3";

#echo "<br>Query:<br>".$sMonthSQL."<br>";

$rMonth=db_query($sMonthSQL);

$sBlockMonth 	= checkForBlock($sTemplate, "month");
$sMonthReplacer = '';


while($my_month = get_data($rMonth))
{
  $tmpMonth = $sBlockMonth;
  $sMonthName = translateMonth($my_month['month']);

  $aTmp=getWeeksInMonth($my_month['month'],$my_month['year']);
  #mktime(0,0,0,$my_month['month'],1,$my_month['year'])
  $tmpMonth = str_replace("<#destination_time#>",$aTmp[0],$tmpMonth);
  $tmpMonth=str_replace("<#thisMonth#>",$my_month['month'],$tmpMonth);
  $tmpMonth=str_replace("<#thisYear#>",$my_month['year'],$tmpMonth);
  $sMonthReplacer.=str_replace("<#name#>",$sMonthName,$tmpMonth);

}




$sTemplate 		= \Cms\Service\PageParser::replaceBlock( $sTemplate, "month", $sMonthReplacer);


////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////







////////////////////////////////////////////////////////////
// Wochenbuttons
////////////////////////////////////////////////////////////

// Pr�fe, ob destination_time �bergeben wurde, sonst default: aktueller Monat, Jahr
$iThisMonth=intval($_REQUEST['thisMonth']);
$iThisYear=intval($_REQUEST['thisYear']);

if(!$iThisMonth) $iThisMonth=date("n");
if(!$iThisYear) $iThisYear=date("Y");







if(intval($_REQUEST['destination_time'])>0)
{
  $destination_time=intval($_REQUEST['destination_time']);
}
else
{
  $result=get_tag_variable("start", $sTemplate);
  if($result){
      $sTemplate=$result['template'];

      if($result['wert']=="now")
      {
        $destination_time=time();
      }elseif($result['wert']=="today"){
      	$destination_time=substr(time(), 0, 8)."000000";
      }else{
        $destination_time = strtotimestamp($result['wert']);
      }
  }
  else
  {
    #letzter Sonntag
    $tag=date("d")-date("w");
    $destination_time=mktime(12,0,0,date("m"),$tag,date("Y"));
  }
}



#echo "<br>destination_time:$destination_time<br><br>";


$sBlockWeek = checkForBlock($sTemplate, "week");


$aWeeksInMonth = getWeeksInMonth($iThisMonth,$iThisYear);

$sWeekReplacer = '';
foreach($aWeeksInMonth as $key => $value)
{
  $tmpWeek = $sBlockWeek;
  $tmpWeek = str_replace("<#destination_time#>",$value,$tmpWeek);

  $tmpWeek=str_replace("<#thisMonth#>",$iThisMonth,$tmpWeek);
  $tmpWeek=str_replace("<#thisYear#>",$iThisYear,$tmpWeek);


  #echo "<br>$destination_time:".date("d.m.y",$destination_time)."==$value:".date("d.m.y",$value);

  #echo "<br>$destination_time==$value OR ($destination_time>$value AND $destination_time<$value+604800<hr><br>";

  #if($destination_time==$value OR ($destination_time>$value AND $destination_time<$value+604800)) #$_REQUEST['destination_time']
  if(date("d.m.y",$destination_time)==date("d.m.y",$value))
  {
    #aktiv
    //$pre="<span class='active_link'>";
    //$post="</span>";
    // Zus�tzlich ein "aktiv-tag" anbieten
    $tmpWeek=str_replace("<#active_link#>", "active_link", $tmpWeek);
  }
  else
  {
    #inaktiv
    //$pre="";
    //$post="";
    // Zus�tzlich ein !"aktiv-tag" anbieten
    $tmpWeek=str_replace("<#active_link#>", "inactive_link", $tmpWeek);

  }

  //$sWeekReplacer .= str_replace("<#name#>",$pre.date("d.m.",$value+86400)." - ".date("d.m.",$value+604800).$post,$tmpWeek); # 6 * 24 * 60 * 60 = 518400 #"\n".
  // Kein HTML mehr in der Ausgabe
  $sWeekReplacer .= str_replace("<#name#>",date("d.m.",$value+86400)." - ".date("d.m.",$value+604800),$tmpWeek); # 6 * 24 * 60 * 60 = 518400 #"\n".
  #+86400
  #604800
}




$sTemplate 		= \Cms\Service\PageParser::replaceBlock( $sTemplate, "week", $sWeekReplacer);


////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////









////////////////////////////////////////////////////////////
// Limit auslesen
////////////////////////////////////////////////////////////

/*
$result=get_tag_variable("limit", $sTemplate);
if($result)
{
  $sTemplate=$result['template'];
  if($result['wert']>0){
    $sLimit = " LIMIT ".intval($result['wert']);
  }
}
unset($result);
*/



if(strpos($sTemplate, "<#activegallery#>")>0){
  $sTemplate=str_replace("<#activegallery#>", "", $sTemplate);
  $hatgalerie=" AND hat_galerie='1'";
}





$resultLimitStart=get_tag_variable("limitstart", $sTemplate);
if($resultLimitStart){
  $sTemplate=$resultLimitStart['template'];
}

$resultLimit=get_tag_variable("limit", $sTemplate);
if($resultLimit){
  $sTemplate=$resultLimit['template'];
}

if($_REQUEST['startgal']){
  $resultLimitStart['wert']=intval($_REQUEST['startgal']);
}

if($resultLimitStart['wert']>=0||$resultLimit['wert']>0){
  $sLimit=" LIMIT ";
  if($resultLimitStart['wert']>0){
    $sLimit.=$resultLimitStart['wert'];
  }else{
    $sLimit.="0";
  }
  $sLimit.=", 999999";
}

$resultOrder=get_tag_variable("order", $sTemplate);
if($resultOrder){
  $sTemplate=$resultOrder['template'];
}

if($resultOrder['wert']=="DESC"){
  $sOrder="DESC";
}

$sSQL="SELECT *, UNIX_TIMESTAMP(date) as datum FROM eventmodul_events WHERE active=1 ORDER BY date";
$aSQLResultRes=db_query($sSQL);


while($aSQLResultTemp=get_data($aSQLResultRes))
{
$aSQLResult[]=$aSQLResultTemp;
}


$startevent=$_REQUEST['startevent'];
if(!$_REQUEST['startevent']){
  for($i=0; $i<count($aSQLResult); $i++){
    if($aSQLResult[$i]['datum']>$destination_time){
      $startevent=$i;
      break;
    }
  }

}
else{
  if($startevent==-1){
    $startevent="0";
    $sLimit=" LIMIT ".$_REQUEST['limit'];
  }
  $destination_time=$aSQLResult[$startevent]['datum'];
  #echo $destination_time;
}


#echo $startevent.": ";
if($startevent-30>=0){
  $sTemplate=str_replace("<#gprev30#>",($startevent-30),$sTemplate);
}else{
  if($_REQUEST['limit']){
    $sTemplate=str_replace("<#gprev30#>", "-1&limit=".$_REQUEST['limit'], $sTemplate);
  }else{
    $sTemplate=str_replace("<#gprev30#>", "-1&limit=".$startevent, $sTemplate);
  }
  #$sLimit=$startevent;
}

if($startevent+30<count($aSQLResult)){
  if($_REQUEST['limit']){
    $sTemplate=str_replace("<#gnext30#>", ($_REQUEST['limit']), $sTemplate);
  }else{
    $sTemplate=str_replace("<#gnext30#>", ($startevent+30), $sTemplate);
  }
  #echo "kleiner max";
}else{
  $sTemplate=str_replace("<#gnext30#>", $startevent, $sTemplate);
  #$sLimit=count($aSQLResult)-$startevent;
}


////////////////////////////////////////////////////////////
// Eventanzeige
////////////////////////////////////////////////////////////

$day=date("d")-date("w");
$last_sunday=mktime(12,0,0,date("m"),$day,date("Y"));




$sBlockParty =	checkForBlock($sTemplate, "party");
$sIfTopBuffer =	\Cms\Service\PageParser::checkForBlock($sBlockParty, "if:top");
$sIfNotTopBuffer =	\Cms\Service\PageParser::checkForBlock($sBlockParty, "ifnot:top");
$sIfGalleryBuffer = \Cms\Service\PageParser::checkForBlock($sBlockParty, "if:gallery");
$sIfNotGalleryBuffer = \Cms\Service\PageParser::checkForBlock($sBlockParty, "ifnot: gallery");

$result=get_tag_variable("end", $sTemplate);
if($result&&$result['wert']>0)
{
  $sTemplate=$result['template'];
  $destination_end = strtotimestamp($result['wert']);
}
else
{
  $destination_end=$destination_time+604800;
}
#echo "start:".date("d.m.Y", $destination_time)."<br>";
#echo "end:  ".date("d.m.Y", $destination_end)."<br>";
#echo "limit:".$sLimit."<br>";
#AND UNIX_TIMESTAMP(date)<".$destination_end."
$sSQL="SELECT *, UNIX_TIMESTAMP(date) as datum FROM eventmodul_events WHERE active=1 AND UNIX_TIMESTAMP(date)>=".$destination_time.$hatgalerie." AND UNIX_TIMESTAMP(date)<".$destination_end." ORDER BY date ".$sOrder.$sLimit;
#echo "<br>Query:<br>".$sSQL;



$tmpParty='';
$sPartyReplacer='';
$daycounter = 0;
$daycounterstring = "";

$rParty=db_query($sSQL);
$zaehler=0;
$galleryzaehler=$notgalleryzaehler=0;
$oddoreavencounter=1;


while($my_party=get_data($rParty))
{
  $zaehler++;
  if($zaehler<=$resultLimit['wert']){


    if($zaehler==1)
    {
      $ersteid=$my_party['id'];
    }
    $tmpParty=$sBlockParty;
    if($my_party['hat_galerie'])
    {
      $sIfGallery=$sIfGalleryBuffer;
      $sIfNotGallery="";
      $galleryzaehler++;
    }
    else
    {
      $sIfGallery="";
      $sIfNotGallery=$sIfNotGalleryBuffer;
      $notgalleryzaehler++;
    }

    if($my_party['ist_event']==1)
    {
      $sIfTop=$sIfTopBuffer;
        $sIfNotTop="";
    }
    else
    {
      $sIfTop="";
      $sIfNotTop=$sIfNotTopBuffer;
    }

    $temp=checkforblockmitvariable($tmpParty, "every");
    $temp2=checkforblockmitvariable($tmpParty, "everygallery");


    $tmpParty=\Cms\Service\PageParser::replaceBlock($tmpParty, "if:top", $sIfTop);
      $tmpParty=\Cms\Service\PageParser::replaceBlock($tmpParty, "ifnot:top", $sIfNotTop);
      $tmpParty=\Cms\Service\PageParser::replaceBlock($tmpParty, "if:gallery", $sIfGallery);
    $tmpParty=\Cms\Service\PageParser::replaceBlock($tmpParty, "ifnot:gallery", $sIfNotGallery);

    if($zaehler%$temp[1]==0&&$temp)
    {
      $tmpParty=replaceblockmitvariable($tmpParty, "every", $temp[0]);
      $oddoreavencounter++;
    }
    else
    {
      $tmpParty=replaceblockmitvariable($tmpParty, "every", "");
    }

    if($galleryzaehler%$temp2[1]==0&&$temp2)
    {
      $tmpParty=replaceblockmitvariable($tmpParty, "everygallery", $temp2[0]);
      $oddoreavencounter++;
    }
    else
    {
      $tmpParty=replaceblockmitvariable($tmpParty, "everygallery", "");
    }



    if($oddoreavencounter%2==0)
    {
      $tmpParty=str_replace("<#evenorodd#>", "even",$tmpParty);
    }
    else
    {
      $tmpParty=str_replace("<#evenorodd#>", "odd",$tmpParty);
    }

    if($my_party['hat_galerie']&&$my_party['gallery_id'])
    {
      #echo $my_party['gallery_id']."<br>";
      $tmpParty=str_replace("<#galleryid#>", $my_party['gallery_id'], $tmpParty);
    }
    else
    {
      $tmpParty=str_replace("<#galleryid#>", "", $tmpParty);
    }



    $tmpParty=str_replace("<#image#>",$my_party['teaser_url'],$tmpParty);
    $tmpParty=str_replace("<#title#>",$my_party['title'],$tmpParty);
    $tmpParty=str_replace("<#text#>",$my_party['text'],$tmpParty);
    $tmpParty=str_replace("<#preview#>",$my_party['preview'], $tmpParty);
    $tmpParty=str_replace("<#counter#>",$zaehler, $tmpParty);
    $tmpParty=str_replace("<#gallerycounter#>",$galleryzaehler, $tmpParty);
    $tmpParty=str_replace("<#notgallerycounter#>",$notgalleryzaehler, $tmpParty);
    $tmpParty=str_replace("<#id#>",$my_party['id'], $tmpParty);
    $tmpParty=str_replace("<#topevent_image#>", str_replace(".", "_teaser.", str_replace("images/", "images/teaser_small/", $my_party['teaser_url'])), $tmpParty);

    if (($my_party['datum']-$last_sunday)/86400%2 == 1)
    {
      $tmpParty=str_replace("<#odd_day_party#>","odd_day_party",$tmpParty);
      $daycounterstring = "_odd";
    }
    else
    {
      $tmpParty=str_replace("<#odd_day_party#>","even_day_party",$tmpParty);
      $daycounterstring = "";
    }
    $daycounter++;


    $tmpParty=str_replace("<#date#>",date("d.m.", $my_party['datum']),$tmpParty);
    $sGermanDay = date("D", $my_party['datum']);
    switch ($sGermanDay) {
      case "Mon":
        $sGermanDay = "Mo";
        break;
      case "Tue":
        $sGermanDay = "Di";
        break;
      case "Wed":
        $sGermanDay = "Mi";
        break;
      case "Thu":
        $sGermanDay = "Do";
        break;
      case "Fri":
        $sGermanDay = "Fr";
        break;
      case "Sat":
        $sGermanDay = "Sa";
        break;
      case "Sun":
        $sGermanDay = "So";
        break;
      default:
        $sGermanDay = "";
    }
    $tmpParty=str_replace("<#germanday#>",$sGermanDay,$tmpParty);
    $tmpParty=str_replace("<#time#>",date("H:i", $my_party['datum']),$tmpParty);
    $tmpParty=str_replace("<#day_image#>","/media/buttons/short_".date("l", $my_party['date']).$daycounterstring.".gif",$tmpParty);

    #if($sOrder=="DESC"){
    #$sPartyReplacer=$tmpParty.$sPartyReplacer;
    #}else{
    $sPartyReplacer.=$tmpParty;
    #}

  }

}

$sTemplate 		= \Cms\Service\PageParser::replaceBlock( $sTemplate, "party", $sPartyReplacer);
$sTemplate=str_replace("<#firstid#>",$ersteid, $sTemplate);

if($resultLimitStart['wert']+30<=$zaehler){
  $sTemplate=str_replace("<#next30#>",($resultLimitStart['wert']+30),$sTemplate);
}
else{
  $sTemplate=str_replace("<#next30#>",$resultLimitStart['wert'],$sTemplate);
}
if(($resultLimitStart['wert']-30)>0){
  $sTemplate=str_replace("<#prev30#>",($resultLimitStart['wert']-30),$sTemplate);
}else{
  $sTemplate=str_replace("<#prev30#>","0",$sTemplate);
}


$sTemplate=str_replace("<#woche_inc#>", (intval($destination_time)+86400*7), $sTemplate);
$sTemplate=str_replace("<#woche_dec#>", (intval($destination_time)-86400*7), $sTemplate);
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////












////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
// Ausgabe
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////

#Debug:
#echo "<br>Debug Ende!<hr><br>";

echo $sTemplate;

////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
?>
