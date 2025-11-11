<?
function last_updates_getUpdates($iNum)
/**
 *Desc:   Diese Funktion ermittelt die letzten X updates und gibt deren Beschreibung als Array zur�ck
 *Param:  $iNum: Anzahl der zu ermittelnden Updates
 *Return: Array mit den BEschreibungstexten der X letzten Updates  
**/
{
  // zun�chst einmal die Sub-Routinen f�r den XML-Parser

function last_updates_handleElement($parser, $data) {
	global $aUpdate,$iLastVersion,$sLastName,$iVersion;
	if($iLastVersion > $iVersion) {
	
		if(preg_match("/[a-z1-9]/i",$data)){
			// Handling f�r singul�re Tags
			if($sLastName == "MESSAGE"){
				$aUpdate[$iLastVersion][$sLastName] = $data;
			}
			// Handling f�r mehrfach erlaubte Tags
			if($sLastName == "SQL"){
				$aUpdate[$iLastVersion]['QUERIES'][] = $data;
			}
				
			if($sLastName == "FILE"){
				$aUpdate[$iLastVersion]['FILES'][] = $data;
			}
		}elseif($sLastName == "DATE"){
			$aUpdate[$iLastVersion]['DATE'] = $data;
		}
	}
}
  
  function last_updates_startElement($parser, $name, $attrs) 
  {
  	global $aUpdate,$iLastVersion,$sLastName,$iVersion;
  
  	if($attrs['VERSION']>$iVersion) {
  		$iLastVersion = $attrs['VERSION'];
  	}
  	$sLastName = $name;
  }
  
  function last_updates_endElement($parser, $name) 
  {
  
  }
  
  function last_updates_defaultElement($parser, $name) 
  {
  
  }
  
  
  // Einstellungen und glabale Variablen
  global $aUpdate,$sLastName,$iLastVersion,$system_data, $iVersion;
  $aUpdate = array();
  $sLastName = false;
  $iLastVersion = false;
  $sConfigserver = $system_data['update_server'];
  $asReturn = array();

  // hoffendlich ist das System up to date, sonst erhalten wir mehr als $iNum updates ;-)
	$iVersion = $system_data['version'] -0.001*$iNum;
  //echo "$iVersion::".$system_data['version'].'<br />';
	// Update Informationen holen
	$sContent = "";
	$sParameter = "action=check&version=".$iVersion."&key=".$system_data['license']."&host=".$_SERVER['HTTP_HOST'];
	$fp = fsockopen($sConfigserver, 80, $errno, $errstr, 10);
	if (!$fp) 
  {
		//besser keine korekte Meldung, dass gibt nur ein schlechtes Image :-)
    //echo "Der Updateserver konnte nicht erreicht werden: $errstr ($errno)<br />\n";
		echo "Derzeit sind keine aktuellen Updates verf&uuml;gbar.";
		return ;
	} 
  else 
  {
		fputs ($fp, "POST /update.php?".$sParameter." HTTP/1.0\r\nUser-Agent: webDynamics Update Service\r\nHost: ".$system_data['update_server']."\r\n\r\n");
		while (!feof($fp)) 
    {
			$sContent .= fgets($fp,128);
		}
		fclose($fp);
	}
	$sContent = strstr($sContent,"<?xml");
	$xml_parser = xml_parser_create();
	xml_set_element_handler($xml_parser, "last_updates_startElement", "last_updates_endElement");
	xml_set_character_data_handler($xml_parser, "last_updates_handleElement");
	xml_set_default_handler($xml_parser, "last_updates_defaultElement");
	xml_parse($xml_parser, $sContent);
	xml_parser_free($xml_parser);

	// Updates auslesen
	$iNumUpdates=null;
	foreach($aUpdate as $fVersion=>$elem) {
    	$asReturn[$fVersion]=array('message'=>$elem['MESSAGE'],'date'=>$elem['DATE']);
    	++$iNumUpdates;
    	if($iNumUpdates>$iNum)
    		array_shift($asReturn);
	}
	return $asReturn;
}

/**
 *Desc: Function Main
 **/

$asUpdates=array_reverse(last_updates_getUpdates(20));
$sTemplate=$element_data['content'];

$sRow = checkForBlock($sTemplate,'row');
$sVInfos='';
foreach($asUpdates as $fVersion=>$aData)
{
  $sVInfos.=str_replace(
    array('<#version#>', '<#message#>',     '<#date#>'), 
    array($fVersion,     $aData['message'], $aData['date']),
    $sRow);
}

echo replaceBlock($sTemplate, 'row', $sVInfos);

?>
