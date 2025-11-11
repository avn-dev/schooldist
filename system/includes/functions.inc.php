<?php

/*
 * Funktion verarbeitet Fehlermeldungen
 *
 * @author 	MK
 * @param 	Fehlermeldung
 * @param 	E-Mail senden (1/0)
 * @param 	Fehlermeldung anzeigen (1/0)
 * @param 	priority (1/2/3)
 */

function error($mText, $sendmail=1, $showtxt=0, $prio=2) {

	Util::handleErrorMessage($mText, $sendmail, $showtxt, $prio); 
	
}

/**
 * 
 * @param string $sTo
 * @param string $sSubject
 * @param string $sMessage
 * @param string $sHeader
 * @param array $arrAttachments
 * @param string $strFrom
 * @param string $strReplyTo
 * @param string $strReturnPath
 * @return boolean
 */
function wdmail($sTo, $sSubject, $sMessage, $sHeader=false, $arrAttachments=false, $strFrom=false, $strReplyTo=false, $strReturnPath=false) {
	
	$oWDMail = new WDMail();
	
	$oWDMail->subject = $sSubject;
	$oWDMail->text = $sMessage;
	$oWDMail->header = $sHeader;
	$oWDMail->attachments = $arrAttachments;
	$oWDMail->from = $strFrom;
	$oWDMail->replyto = $strReplyTo;
	$oWDMail->returnpath = $strReturnPath;
	
	$bSuccess = $oWDMail->send($sTo);
	
	return $bSuccess;

}

/*
 * Wandelt Seiten ID in Pfad um
 *
 * @author 	MK
 * @param 	Seiten ID
 * @return	Pfad
 */

function idtopath($page_id, $strLanguage=false, $sExtension='html', $bWithLanguage=true) {
	global $system_data, $db_data;

	$objWebDynamicsDAO = new \Cms\Helper\Data;
	
	if(is_numeric($page_id) && $page_id > 0) {
	
		$arrPageData = $objWebDynamicsDAO->getPageData($page_id);
	
		$oSite = \Cms\Entity\Site::getInstance($arrPageData['site_id']);
		
		// Wenn der Internetauftritt Sprachordner deaktiviert hat
		if($oSite->no_language_folder == 1) {
			$bWithLanguage = false;
		}
		
		if(is_array($arrPageData)) {
			
			if(!$arrPageData['original_language'] && $strLanguage) {
				$arrPageData['original_language'] = $strLanguage;
			} elseif(!$arrPageData['original_language']) {
				$arrPageData['original_language'] = $system_data['arrLanguages'][0];
			}
	
			if(
				$arrPageData['path']=="" &&
				$arrPageData['file'] == ""
			) {
				$site_name = "/index.".$sExtension;
			} else {
				$site_name = "/";
				if($bWithLanguage) {
					$site_name .= $arrPageData['original_language']."/";
				}
				$site_name .= $arrPageData['path']."".$arrPageData['file'].".".$sExtension;
			}

			return $site_name;

		} else {
			return false;
		}

	} else {

		return false;

	}
}

function pathtoid($site_path, $sExtension='html') {
	global $db_data;

	$P = explode("/",$site_path);

	if($P[1] != "index.".$sExtension) {
		$language_name = $P[1];
		$language_name = trim($language_name);
		$T = $P[1];
		$T = substr($T,0,1).substr($T,1);
        $file_name = "";
        $i=2;
		$Q = count($P);
		$Q -= 1;
		if (strstr($P[$Q],".html") && $Q > 1) {
			$file_name = $P[$Q];
		}
		$path_name = "";
        while ($i < $Q) {
			if (!strstr($P[$i],".".$sExtension)) {
        		$path_name .= $P[$i]."/";
			}
    	    $i++;
        }
		$file_name = explode(".",$file_name);
		$file_name = $file_name[0];
		$sSql = "SELECT * FROM cms_pages WHERE path = '$path_name' AND file = '$file_name' AND language = '$language_name'";
	} else {
		$sSql = "SELECT * FROM cms_pages WHERE path = '' AND file = ''";
	}

	if($my_site = DB::getQueryRow($sSql)) {
		$page_id = $my_site['id'];
		return $page_id;
	} else {
		return false;
	}
}

/*
 * Ermittelt Browser Typ und OS
 *
 * @author 	MK
 * @param 	Browserkennung
 * @return	Array mit Browser Typ und OS
 */


function truncateText($sText, $iWidth, $iXPos, $iFont, $sFont, $iRows=0, $sSuffix="...") {
	$oImgBuilder = new imgBuilder;
	$aTemp = $oImgBuilder->determineWordWrap($sText, $iWidth, $iXPos, $iFont, $sFont, $iRows, $sSuffix);
	$sText = $aTemp['wrap'];
	if(empty($sText)) {
		$sText = $aTemp['text'];
	}
	return $sText;
}

function replacevars($buffer) {
	return \Cms\Service\ReplaceVars::execute($buffer);
}



function alert($text) {
	global $system_data;
	$system_data['error'] = L10N::t($text, 'Framework');
}

/**
 * 
 * @global type $objWebDynamics
 * @global Access_Backend $oAccessBackend
 * @param type $iPageId
 * @param type $sEntry
 * @param type $element_id
 * @param type $element_name
 * @param type $tbl_name
 * @param type $field_name
 * @param type $content
 */
function enterlog($iPageId, $sEntry, $element_id="", $element_name="", $tbl_name="", $field_name="", $content="") {
	
	Log::enterLog($iPageId, $sEntry, $element_id, $element_name, $tbl_name, $field_name, $content);
	
}

/**
 * @deprecated
 *
 * @param array|string $mRight
 * @param bool $iUserId
 * @return bool
 */
function hasRight($mRight, $iUserId=false) {

	if(!$iUserId) {
		
		$oAccess = Access::getInstance();
		
		$aRights = $oAccess->rights;

	} else {
		$oUser = User::getInstance($iUserId);
		$aRights = $oUser->getRights();
	}
	
	$aRight = array();
	$aRight['right'] = $mRight;
	$aRight['has_right'] = false;
	$aRight['return'] = false;

	\System::wd()->executeHook('has_right', $aRight);
	
	if(
		$aRight['has_right'] == true ||
		$aRight['return'] == true
	) {
		return (bool)$aRight['has_right'];
	}

	if($mRight == "") {
		return true;
	}

	if(!is_array($mRight))
	{
		$mRight = array($mRight);
	}

	foreach((array)$mRight as $sRight)
	{
		if( is_string($sRight) && isset($aRights[$sRight]) && $aRights[$sRight] == 1)
		{
			return true;
		}
	}

	return false;
}

function isRight($sRight) {
	$sQuery = "SELECT * FROM `system_rights` WHERE `right` = '".\DB::escapeQueryString($sRight)."'";
	$rResult = DB::executeQuery($sQuery);
	if (DB::numRows($rResult) > 0) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function getBrowserLanugage($allowed_languages, $default_language, $lang_variable = null, $strict_mode = false) {
	return getBrowserLanguage($allowed_languages, $default_language, $lang_variable = null, $strict_mode = false);
}

function getBrowserLanguage($allowed_languages, $default_language, $lang_variable = null, $strict_mode = false) {

	$sCurrentLanguage = Core\Helper\Agent::getBrowserLanguage($allowed_languages, $default_language, $lang_variable, $strict_mode);
       
	return $sCurrentLanguage;
}

// string mit microsekunden
function getmicrotime(){
	$floMicroSeconds = microtime(1);
	return $floMicroSeconds;
}

// string mit microsekunden
function getCurrentRuntime(){
	global $time_start;
	$floMicroSeconds = microtime(1);
	$floRuntime = $floMicroSeconds - $time_start;
	return $floRuntime;
}

// prÃ¼ft einzelne elemente auf zugriff
// parameter: besitzer und generelle freigabefunktion

function warrantychecker($owner, $authorized, $general) {
	global $user_data;

	$arrWarrant = \Util::decodeSerializeOrJson($authorized);

	if(
		$user_data['id'] > 0 &&
		(
			$owner == $user_data['id'] ||
			in_array($user_data['id'], (array)$arrWarrant['accept']) ||
			hasRight($general)
		) &&
		!in_array($user_data['id'], (array)$arrWarrant['deny'])
	) {
		return true;
	} else {
		return false;
	}
}

function getCleanFilename($sFile) {
	$sFile = Util::getCleanFilename($sFile);
	return $sFile;
}

function getCleanPath($sFile) {
	$sFile = Util::getCleanPath($sFile);
	return $sFile;
}

function replaceSpecialChars($strInput) {
	$strOutput = Util::replaceSpecialChars($strInput);
	return $strOutput;
}

//ersetzt alle irgendwie gearteten Sonderzeichen in einem String durch Linux-Systemkonforme Zeichen
function replace_specialchars($term) {
	$term = \Util::getCleanFileName($term);
	return $term;
}

//ersetzt alle irgendwie gearteten Sonderzeichen in einem String durch Linux-Systemkonforme Zeichen bis auf den Slash (um pfade zu Ã¼berprÃ¼fen und nicht zu zerstÃ¶ren
function replace_specialchars_light($term) {
	$term = getCleanPath($term);
	return $term;
}

//ersetzt alle irgendwie gearteten Sonderzeichen in einem String durch Linux-Systemkonforme Zeichen bis auf den Slash (um pfade zu Ã¼berprÃ¼fen und nicht zu zerstÃ¶ren
function replace_specialchars_path($term) {
	$term = getCleanPath($term);
	return $term;
}

function saveResizeImage($from, $to, $width="150", $height="150") {
	global $system_data,$_SERVER;

	mkdir(\Util::getDocumentRoot()."media/original",$system_data['chmod_mode_dir']);
	chmod(\Util::getDocumentRoot()."media/original",$system_data['chmod_mode_dir']);
	mkdir(\Util::getDocumentRoot()."media/temp",$system_data['chmod_mode_dir']);
	chmod(\Util::getDocumentRoot()."media/temp",$system_data['chmod_mode_dir']);

	$aFrom 	= pathinfo($from);
	$aTo 	= pathinfo($to);

	if(is_file($from)) {
		if (strlen($width) < 1) { $width = 0; }
		if (strlen($height) < 1) { $height = 0; }

		$arrSize = getimagesize($from);
		if(
			(
				$arrSize[0] == $width &&
				$arrSize[1] <= $height
			) || (
				$arrSize[0] <= $width &&
				$arrSize[1] == $height
			)
		) {
			
			copy($from, $to);
			
		} else {

			$cmd = $system_data['im_path']."convert -geometry ".intval($width)."x".intval($height)." '".$from."' '".$to."'";
			$returnVal = '';
			exec($cmd, $returnVal);
			
		}

		chmod($to, $system_data['chmod_mode_file']);

	}

}

// save files

function save($from, $path, $to, $dir="") {

	global $system_data,$upload_size_resize,$upload_size_height,$upload_size_width,$upload_size_format;

	$im_path = $system_data['im_path'];
	$temp_path = $path.$dir;

	Util::checkDir($path."original");
	Util::checkDir($path."temp");
	
	if(is_file($from)) {

	 	$to = replace_specialchars_path($to);

	 	$sDir = substr($temp_path.$to, 0, strrpos($temp_path.$to, '/'));
		$bDirectory = Util::checkDir($sDir);

		$ending_array = explode(".", $to);
		$extension = $ending_array[count($ending_array)-1];
		$to = substr($to,0,strrpos($to,".")).".".strtolower($extension);
    	$newname = substr($to,0,strrpos($to,".")).".".$upload_size_format;

		if($upload_size_resize) {

			if (strlen($upload_size_width) < 1) { $upload_size_width = 0; }
			if (strlen($upload_size_height) < 1) { $upload_size_height = 0; }
			copy($from,$path."temp/".$to);
			$cmd = $im_path."convert -geometry ".intval($upload_size_width)."x".intval($upload_size_height)." '".$path."temp/$to' '$temp_path$newname'";
			$returnVal='';
			exec($cmd, $returnVal);
			$cmd = $im_path."convert ".$path."temp/$to ".$path."original/$newname";
			exec($cmd, $returnVal);
			Util::changeFileMode($path."original/".$newname);
			$to = $newname;

		} else {

			$bCopy = copy($from, $temp_path.$to);
			
			if(getimagesize($from)) {
				copy($from, $path."original/".$to);
				Util::changeFileMode($path."original/".$to);
			}

		}

		Util::changeFileMode($temp_path.$to);

		if(is_file($temp_path.$to)) {
			return $to;	
		} else {
			return false;
		}

	} else {
		return false;
	}

}

// end save files



// end template parser


// erzeugt ein Extraformular und eine DHTML-Funktion um den Styleeditor per javascript aufrufen zu kÃ¯Â¿Â½nnen

function document_write_styleeditcaller() {
	global $styleeditcaller;
	if(!$styleeditcaller) {
		$styleeditcaller=true;
		?>	<form name="StyleEditCallForm" method="get" action="/admin/frame.html">
			<input type=hidden name=form_name>
			<input type=hidden name=field_name>
			<input type=hidden name=input_string>
			<input type=hidden name=action>
			<input type=hidden name=file value="styles_editor">
			</form>
		<script>
			function call_styleeditor(form_name, field_name, prewiev) {
				document.StyleEditCallForm.form_name.value = form_name;
				document.StyleEditCallForm.field_name.value = field_name;
				if(prewiev) document.StyleEditCallForm.action.value = "Vorschau_direkt";
				else		document.StyleEditCallForm.action.value = "";
				eval('document.StyleEditCallForm.input_string.value = '+form_name+'.'+field_name+'.value;');
				var style_win = window.open('about:blank', 'StyleEditWindow', 'location=no,menubar=no,resizable=no,status=no,toolbar=no');
				document.StyleEditCallForm.target='StyleEditWindow';
				document.StyleEditCallForm.submit();
			}
		</script>
		<?
	}
}

function changePosition($direction, $id, $table, $where="", $db='system', $positionCell='position', $idCell='id') {

	if($direction == "up") {

		$aItems = DB::getQueryRows("SELECT * FROM ".$table." ".$where." ORDER BY ".$positionCell." DESC, ".$idCell." DESC");
		$rows_position = count($aItems);
		$ii = $rows_position;
		while($my_position = current($aItems)) {

			$row_id = $my_position[$idCell];
			if($row_id == $id) {
				$ii_new = $ii - 1;
				DB::executeQuery("UPDATE ".$table." SET ".$positionCell." = '$ii_new' WHERE ".$idCell." = '$row_id'");
				$my_position = next($aItems);
				
				$row_id = $my_position[$idCell];
				DB::executeQuery("UPDATE ".$table." SET ".$positionCell." = '$ii' WHERE ".$idCell." = '$row_id'");
				
			} else {
				DB::executeQuery("UPDATE ".$table." SET ".$positionCell." = '$ii' WHERE ".$idCell." = '$row_id'");
			}
			$ii--;
			next($aItems);
		}

	} elseif ($direction == "down") {

		$strSql = "SELECT * FROM ".$table." ".$where." ORDER BY ".$positionCell." ASC, ".$idCell." ASC";
		$aItems = DB::getQueryRows($strSql);
		$ii = 1;
		foreach($aItems as $my_position) {
			$row_id = $my_position[$idCell];
			if($row_id == $id) {
				$ii_new = $ii + 1;
				
				$strSql = "UPDATE ".$table." SET ".$positionCell." = '$ii_new' WHERE ".$idCell." = '$row_id'";
				DB::executeQuery($strSql);
				
				$my_position = current($aItems);
				next($aItems);

				$row_id = $my_position[$idCell];

				$strSql = "UPDATE ".$table." SET ".$positionCell." = '$ii' WHERE ".$idCell." = '$row_id'";
				DB::executeQuery($strSql);
				$ii++;
			} else {
				$strSql = "UPDATE ".$table." SET ".$positionCell." = '$ii' WHERE ".$idCell." = '$row_id'";
				DB::executeQuery($strSql);
			}
			$ii++;
		}

	}
}

// setzt active = 2 und trÃ¤gt das ganze in die trash tabelle ein
function postdeletion($strTable, $intId, $strTitle, $intPageId="", $parent_id="") {
	global $user_data,$db_data;

	$strSql = "UPDATE $strTable SET active = '2' WHERE id = '".(int)$intId."'";
	$resUpdate = DB::executeQuery($strSql);
	$strSql = "INSERT INTO system_trash (trash_id, page_id, parent_id, tablename, title, user, time) VALUES ('".(int)$intId."','".(int)$intPageId."','".$parent_id."', '".$strTable."', '".\DB::escapeQueryString($strTitle)."', '".$user_data['id']."', UNIX_TIMESTAMP())";
	$resInsert = DB::executeQuery($strSql);
	if($resUpdate && $resInsert) {
		$text = "Aus \"$strTable\" wurde Element \"$strTitle\" in den Papierkorb verschoben";
		\Log::enterLog($intPageId, $text);
		return true;
	} else {
		return false;
	}

}

/**
 * Wrapper für Util::checkUrl
 * 
 * @see Util::checkUrl
 * @param type $sUrl
 * @param type $iTimeout
 * @return type 
 */
function urlExists($sUrl, $iTimeout=2) {
	
	$bExists = Util::checkUrl($sUrl, $iTimeout);
	
	return $bExists;
	
}

// linkchecker (prüft vorkommende links in einen string)
// by Übergabe von $type direkte Übergabe des links
function linkchecker($string,$path) {
	global $system_data, $session_data;
	$domain = $system_data['domain'];

	$failed = array();
	$j = 0;
	$buffer = $string;

	$tags = array("href","src");
	for($i=0;$i<=count($tags);$i++) {
		$buffer = $string;
		while($pos = strpos($buffer,$tags[$i])) {
			$search = "/".$tags[$i]."[\s]?=[\s]?[\"]?([a-zA-Z0-9 :\/.\-_]*)/i";
			$temp = substr($buffer,$pos,200);
			$regs = array();
			$cache = preg_match($search,$temp,$regs);
			if(!strstr($regs[1],"http")) {
				if(preg_match("/^\/.*$/",$regs[1]))
					$regs[1] = $domain.$regs[1];
				else
					$regs[1] = $domain.$path.$regs[1];
			}
			if(
				!strstr($regs[1],"javascript") && 
				!strstr($regs[1],"mailto:")
			) {
				// get parameter entfernen
				list($regs[1], $sParameter) = explode("?", $regs[1], 2);

				// check if url is in cache
				if(array_key_exists($regs[1], (array)$session_data['link_checker']['cache'])) {
					$bExists = $session_data['link_checker']['cache'][$regs[1]];
				} else {
					$bExists = urlExists($regs[1]);
					$session_data['link_checker']['cache'][$regs[1]] = $bExists;
				}

				if(!$bExists) {
					$failed[$j] = $regs[1];
					$j++;
				}
			}
			$buffer = substr($buffer,$pos+1);
		}
	}
	return $failed;
}

// end linkchecker


// make hyperlinks (popup or normal)

function makelink($id, $parameter=false, $bolPlainUrl=false, $bIncludeDomain=false, $bWithLanguage=true, $sExtension='html') {
	global $objWebDynamicsDAO, $system_data, $language, $page_data, $session_data;

	$my_link = $objWebDynamicsDAO->getPageData($id);

	$oSite = \Cms\Entity\Site::getInstance($my_link['site_id']);
	
	// Internetauftritt hat Sprachordner deaktiviert?
	if($oSite->no_language_folder == 1) {
		$bWithLanguage = false;
	}
	
	$template = "";
	if(!$bolPlainUrl) { 
		$template = $my_link['window'];
	}
	$width = $my_link['width'];
	$height = $my_link['height'];

	/*
	 * $parameters starts always with "?"
	 * $my_link['parameter'] starts always without "?" or "&"
	 */

	$temp = "";
	if(!empty($my_link['file'])) {
		$temp .= $sExtension;
	}
	
	if($my_link['element'] != "link") {
		
		if($parameter && $my_link['parameter'])      {
			$temp .= $parameter."&amp;".$my_link['parameter'];
		} elseif(!$parameter && $my_link['parameter']) {
			$temp .= "?".$my_link['parameter'];
		} elseif($parameter && !$my_link['parameter']) {
			$temp .= $parameter;	
		} else {
			$temp .= "";
		}

	} else {
		$temp .= $parameter;
	}

	if(!$my_link['language']) {
		$my_link['language'] = $page_data['language'];
	}

	$sUrl = "/";
	// Mit Sprache und nicht die Root Seite
	if(
		$bWithLanguage && 
		(
			!empty($my_link['path']) ||
			!empty($my_link['file'])
		)
	) {
		$sUrl .= $my_link['language']."/";		
	}

	if($my_link['path']) {
		$sUrl .= $my_link['path'];
	}
	if($my_link['file']) {
		$sUrl .= $my_link['file'];		
	}
	if($temp) {
		$sUrl .= ".".$temp;
	}

	if($bIncludeDomain) {
		
		if($my_link['https'] == 1) {
			$sSecureDomain = str_replace("http://", "https://", $system_data['domain']);
		} else {
			$sSecureDomain = str_replace("https://", "http://", $system_data['domain']);
		}
		$sUrl = $sSecureDomain.$sUrl;

	}
	
	if ($template == "popup") {
		$makelink = "#\" onClick=\"window.open('".$sUrl."','popup$id','status=no,resizable=no,menubar=no,scrollbars=yes,width=".$width.",height=".$height."');";
	} elseif ($template == "blank") {
		$makelink = $sUrl."\" target=\"_blank";
	} elseif ($template == "fullscreen") {
		$makelink = "#\" onClick=\"window.open('".$sUrl."','popup$id','fullscreen,status=no,resizable=no,menubar=no,scrollbars=yes,width=".$width.",height=".$height."');";
	} else {
		$makelink = $sUrl;
	}

	return $makelink;

}

function getPageTrack($id, $seperator=false, $iStartlevel=1, $bShowSite=false) {
	global $objWebDynamicsDAO, $db_data;

	if(!$seperator) {
		$seperator = " &raquo; ";
	}
	
	$objWebDynamicsDAO = new \Cms\Helper\Data;
	
	$my_page = $objWebDynamicsDAO->getPageData($id);

	$file_data = explode("/", $my_page["path"]);
	$output = "";
	$h=0;
	$link = "";
	$bEndSeparator = false;

	if($my_page['id'] < 1) {
		return false;
	}

	if($bShowSite) {
		$aSite = $objWebDynamicsDAO->getSiteData($my_page['site_id']);
		$output .= $aSite['name']." &raquo; ";
	}

	while(count($file_data) > $h) {

		if($h >= $iStartlevel) {

			$my_dir = getPageFromPath($link, $my_page['site_id'], $my_page['language']);
			$output .= $my_dir['title'];

			if(count($file_data)-1 > $h) {
				$output .= $seperator;
			} else {
				$bEndSeparator = true;
			}

		}
		
		$link .= $file_data[$h]."/";
		$h++;

	}

	// Wenn nicht Verzeichnis, Name der Datei ranhängen
	if($my_page['file'] != "index" || $output == "") {
		if($bEndSeparator) {
			$output .= $seperator;
		}
		$output .= $my_page['title'];
	}

	return stripslashes($output);
}

function getPageFromPath($sPath, $iSite, $sLanguage) {
	global $session_data;
	
	if(!isset($session_data['page_from_path'][$sPath][$iSite][$sLanguage])) {
		
		$sSql = "SELECT title FROM cms_pages WHERE path = '".$sPath."' AND file = 'index' AND site_id = ".(int)$iSite." AND (language = '".$sLanguage."' OR language = '') ORDER BY language DESC";
		$my_dir = DB::getQueryRow($sSql);

		$session_data['page_from_path'][$sPath][$iSite][$sLanguage] = $my_dir;

	}

	return $session_data['page_from_path'][$sPath][$iSite][$sLanguage];

}

function getPageUrl($intPageId, $sExtension='html') {
	global $objWebDynamicsDAO, $system_data, $page_data;

	$arrPage = $objWebDynamicsDAO->getPageData($intPageId);

	if(!$arrPage['language']) {
		$arrPage['language'] = $page_data['language'];
	}

	$arrSite = $objWebDynamicsDAO->getSiteData($arrPage['site_id']);
	
	$strUrl = "http://".$arrSite['domain']."/";
	if(
		$arrPage['language'] &&
		$arrSite['no_language_folder'] != 1
	) {
		$strUrl .= $arrPage['language']."/";
	}
	if($arrPage['path']) {
		$strUrl .= $arrPage['path'];
	}
	if($arrPage['file']) {
		$strUrl .= $arrPage['file'].".".$sExtension;
	}

	return $strUrl;

}

function getPageData($id,$field=0, $sExtension='html') {
	global $objWebDynamicsDAO, $system_data;

	$return = 0;

	$objWebDynamicsDAO = new \Cms\Helper\Data;
	
	$my_page = $objWebDynamicsDAO->getPageData($id);

	if($field) {
		return $my_page[$field];
	}

	$my_page['track'] 	= getPageTrack($my_page['id']);
	$my_page['url'] 	= $system_data['domain']."/".$my_page['language']."/".$my_page['path'].$my_page['file'].".".$sExtension;

	return $my_page;

}

function getTimestamp() {
	$objArgs = func_get_args();
	$nCount = count($objArgs);
	if ($nCount < 7) {
		$objDate = getdate();
		if ($nCount < 1)
			$objArgs[] = $objDate["hours"];
		if ($nCount < 2)
			$objArgs[] = $objDate["minutes"];
		if ($nCount < 3)
			$objArgs[] = $objDate["seconds"];
		if ($nCount < 4)
			$objArgs[] = $objDate["mon"];
		if ($nCount < 5)
			$objArgs[] = $objDate["mday"];
		if ($nCount < 6)
			$objArgs[] = $objDate["year"];
	}
	$nYear = $objArgs[5];
	$nOffset = 0;
	if ($nYear < 1970) {
		if ($nYear < 1902) {
			return 0;
		} else if ($nYear < 1952) {
			$nOffset = -2650838400;
			$objArgs[5] += 84;
			// Apparently dates before 1942 were never DST
			if ($nYear < 1942)
			$objArgs[6] = 0;
		} else {
			$nOffset = -883612800;
			$objArgs[5] += 28;
		}
	}

	return call_user_func_array("mktime", $objArgs) + $nOffset;
}

function strtotimestamp($strDate, $bolMySqlFormat=false) {

	// get current format
	$intTest = mktime(0, 0, 0, 12, 31, 1990);
	$strTest = strftime("%x", $intTest);

	$arrDate = array();
	
	// get seperator
	preg_match("/[^0-9]/", $strTest, $aReg);
	$strSeparator = $aReg[0];

	preg_match("/([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})/", $strTest, $aRegTest);
	preg_match("/([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})/", $strDate, $aReg);

	preg_match("/([0-9]{1,2}):([0-9]{2}):?([0-9]{0,2})/", $strDate, $aRegTime);

	unset($aRegTest[0]);

	foreach((array)$aRegTest as $intKey=>$mixTest) {
		if($mixTest == 31) {
			$arrDate['D'] = (int)$aReg[$intKey];
		}
		if($mixTest == 12) {
			$arrDate['M'] = (int)$aReg[$intKey];
		}
		if($mixTest == 1990) {
			$arrDate['Y'] = (int)$aReg[$intKey];
		}
		if($mixTest == 90) {
			$intValue = (int)$aReg[$intKey];
			if(
				$intValue <= (date("y")+2) &&
				$intValue >= 0
			) {
				$arrDate['Y'] = $intValue + 2000;
			} else {
				$arrDate['Y'] = $intValue + 1900;
			}
		}
	}
	
	$arrDate['h'] = (int)($aRegTime[1] ?? 0);
	$arrDate['m'] = (int)($aRegTime[2] ?? 0);
	$arrDate['s'] = (int)($aRegTime[3] ?? 0);

	if($bolMySqlFormat) {
		$sDate = sprintf("%04d%02d%02d%02d%02d%02d", $arrDate['Y'], $arrDate['M'], $arrDate['D'], $arrDate['h'], $arrDate['m'], $arrDate['s']);
		return $sDate;
	} else {
		return getTimestamp($arrDate['h'], $arrDate['m'], $arrDate['s'], $arrDate['M'], $arrDate['D'], $arrDate['Y']);
	}

}

function insertBookmark($idPage) {
	global $user_data,$db_data;
	if(count_rows(db_query($db_data['module'],"SELECT id FROM community_favourites WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' AND idPage = '".$idPage."'")) < 1) {
		db_query($db_data['module'],"INSERT INTO community_favourites SET tblUser = '".$user_data['idTable']."', idUser = '".$user_data['id']."', idPage = '".$idPage."'");
	}
}

function insertWhoisonline() {
	global $user_data, $page_data, $session_data, $system_data;

	db_query("DELETE FROM community_whoisonline WHERE UNIX_TIMESTAMP(changed) < (UNIX_TIMESTAMP(NOW()) - ".$system_data['whoisonline_valid'].")");
	if($user_data['id'] && $user_data['idTable']) {
		db_query("DELETE FROM community_whoisonline WHERE idUser = ".(int)$user_data['id']." AND tblUser = ".(int)$user_data['idTable']."");
	}
	if(session_id()) {
		db_query("DELETE FROM community_whoisonline WHERE idSession = '".session_id()."'");
	}
	if(
		$system_data['whoisonline_recordall'] || 
		(
			$user_data['id'] && 
			$user_data['idTable']
		)
	) {
		$sSql = "INSERT INTO community_whoisonline SET idUser = ".(int)$user_data['id'].", tblUser = ".(int)$user_data['idTable'].", idPage = ".(int)$page_data['id'].", idSession = '".session_id()."', ip = '".$_SERVER['REMOTE_ADDR']."', referrer = '".\DB::escapeQueryString($_SERVER['HTTP_REFERER'])."'";
		DB::executeQuery($sSql);
	}

}

function insertBuddy($idUser,$tblUser) {
	global $user_data,$db_data;
	if(count_rows(db_query($db_data['module'],"SELECT id FROM community_buddylist WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' AND idBuddy = '".$idUser."' AND tblBuddy = '".$tblUser."' LIMIT 1")) < 1) {
		db_query($db_data['module'],"INSERT INTO community_buddylist SET tblUser = '".$user_data['idTable']."', idUser = '".$user_data['id']."', idBuddy = '".$idUser."', tblBuddy = '".$tblUser."'");
	}
}

function insertBlacklist($idUser,$tblUser) {
	global $user_data,$db_data;
	if(count_rows(db_query($db_data['module'],"SELECT id FROM community_blacklist WHERE tblUser = '".$user_data['idTable']."' AND idUser = '".$user_data['id']."' AND idBlacklist = '".$idUser."' AND tblBlacklist = '".$tblUser."' LIMIT 1")) < 1) {
		db_query($db_data['module'],"INSERT INTO community_blacklist SET tblUser = '".$user_data['idTable']."', idUser = '".$user_data['id']."', idBlacklist = '".$idUser."', tblBlacklist = '".$tblUser."'");
	}
}

/**
 * @deprecated
 */
function generateRandomString($iDigits, $arrOptions=array()) {

	$sRandom = Util::generateRandomString($iDigits, $arrOptions);
	
	return $sRandom;
	
}

/**
 * @see Util::removeMagicQuotes
 * @deprecated
 */
function WD_gpc_extract(&$aArray, $mDummy=false) {

	$bReturn = Util::removeMagicQuotes($aArray);

	return $bReturn;
	
}

function getFilesize($size) {
	return Util::formatFilesize($size);
}

/**
 * Prüft eine E-Mail-Adresse
 * Ergebnis wird für 24h gecached
 * 
 * @param string $sEmail
 * @return boolean 
 */
function checkEmailMx($sEmail) { 
	return Util::checkEmailMx($sEmail);
}

/*
 * Funktionen für Module
 */


function getCustomerTables() {

	$arrCustomerTables = DB::getQueryPairs("SELECT `id`, `db_name` FROM customer_db_config WHERE active = 1 ORDER BY db_name ");

	return $arrCustomerTables;
}

function getCustomerGroups($sCustomerDb=0) {
	
	$arrCustomerGroups = \CustomerDb\Helper\Functions::getCustomerGroups($sCustomerDb);
	
	return $arrCustomerGroups;
}

function getCustomerFields($sCustomerDb, $sOrderBy="name", $bCompleteFieldNames=false) {
	global $db_data;

	$aStandard = array("id"=>"ID", "email"=>"E-Mail", "nickname"=>"Nickname", "password"=>"Passwort", "changed"=>"letzte &Auml;nderung", "last_login"=>"letzter Login", "changed_by"=>"ge&auml;ndert von", "created"=>"Erstellungszeitpunkt", "views"=>"Aufrufe", "groups"=>"Gruppen", "access_code"=>"Zugriffscode");

	$arrCustomerFields = $aStandard;

	$res = DB::getQueryRows("SELECT field_nr,name FROM customer_db_definition WHERE db_nr = '".$sCustomerDb."' AND field_nr != '0' AND active = 1 ORDER BY ".$sOrderBy."");
	foreach($res as $my) {
		if($bCompleteFieldNames) {
			$arrCustomerFields["ext_".$my['field_nr']] = $my['name'];
		} else {
			$arrCustomerFields[$my['field_nr']] = $my['name'];
		}
	}

	return $arrCustomerFields;
}

function copyPage($idPage, $strLanguage, $sTargetPath, $sTargetFile, $sToTitle=false, $iToSiteId=false) {

	$oPage = \Cms\Entity\Page::getInstance($idPage);
	$oNewPage = $oPage->copy($strLanguage, $sTargetPath, $sTargetFile, $sToTitle, $iToSiteId);

	return $oNewPage->id;
}

function getUrlContent($sUrl,$sParameter,$sServer="update.webdynamics.de") {

	$sContent = "";
	$errno = 0;
	$errstr = "";
	$fp = fsockopen($sServer, 80, $errno, $errstr, 10);
	if (!$fp) {
		return false;
	} else {
		fputs ($fp, "POST ".$sUrl."?".$sParameter." HTTP/1.0\r\nUser-Agent: Fidelo Update Service\r\nHost: ".$sServer."\r\n\r\n");
		while (!feof($fp)) {
			$sContent .= fgets($fp,128);
		}
		fclose($fp);
	}
	$sContent = substr($sContent,strpos($sContent,"START:")+6);
	return $sContent;

}

function getHTMLsubstr($sString, $iStart, $iMaxLength = 0) {
	// Parameter prÃ¯Â¿Â½fen.
	if (!is_numeric($iStart)) {
		$iStart = 0;
	}
	if (!is_numeric($iMaxLength)) {
		$iMaxLength = 0;
	}
	// Variablen zum Zwischenspeichern von Daten.
	$sResult = '';
	$iLength = 0;
	// Solange wir ein < im Eingabestring finden den entsprechenden
	// HTML-Tag nicht mit in die GesamtlÃ¯Â¿Â½nge des Strings einrechnen.
	while (($iPos = strpos($sString, '<')) !== false) {
		// Wenn ein Offset angegeben ist ($iStart) muss die angegebene Anzahl
		// an Zeichen Ã¯Â¿Â½bersprungen werden.
		if ($iStart > 0) {
			if ($iStart >= $iPos) {
				// Wenn mehr Zeichen Ã¯Â¿Â½bersprungen werden sollen als Ã¯Â¿Â½berhaupt
				// gefunden wurden wird einfach alles Ã¯Â¿Â½bersprungen.
				$sString = substr($sString, $iPos);
				$iStart -= $iPos;
				$iPos = 0;
			} else {
				// Wenn weniger Zeichen Ã¯Â¿Â½bersprungen werden sollen als gefunden
				// wurden die entsprechende Anzahl an Zeichen aus dem Eingabestring
				// entfernen.
				$sString = substr($sString, $iStart);
				$iPos -= $iStart;
				$iStart = 0;
			}
		}
		// Alles was vor der gefunden Klammer kam ist normaler Text, dieser wird
		// auf die GesamtlÃ¯Â¿Â½nge angerechnet und in die Ausgabe Ã¯Â¿Â½bernommen.
		// Wenn durch den Text die MaximallÃ¯Â¿Â½nge Ã¯Â¿Â½berschritten wird nehmen wir nur
		// die maximal mÃ¯Â¿Â½glichen Zeichen und brechen dann ab.
		if (($iLength + $iPos) > $iMaxLength) {
			$sResult .= substr($sString, 0, $iMaxLength - $iLength);
			// Alle nachfolgenden HTML-Tags werden in die Ausgabe ÃƒÂ¼bernommen.
			while (($iPos = strpos($sString, '<')) !== false) {
				// Alles vor dem gefundenen HTML-Tag verwerfen.
				$sString = substr($sString, $iPos);
				// Wenn der HTML-Tag nicht geschlossen wird ignorieren wir ihn einfach.
				if (($iPos = strpos($sString, '>')) === false) {
					break;
				}
				// Den Tag in die Ausgabe Ã¯Â¿Â½bernehmen.
				$sResult .= substr($sString, 0, $iPos + 1);
				$sString  = substr($sString, $iPos + 1);
			}
			// Den Eingabestring leeren damit nichts weiter eingelesen wird.
			$sString = '';
			break;
		}
		$iLength += $iPos;
		$sResult .= substr($sString, 0, $iPos);
		$sString  = substr($sString, $iPos);
		// Bei einem nicht geschlossenen HTML-Tag wird an dieser Stelle abgebrochen.
		if (($iPos = strpos($sString, '>')) === false) {
			$sString = '';
			break;
		}
		// Den HTML-Tag komplett in die Ausgabe Ã¯Â¿Â½bernehmen, aber nicht
		// auf die GesamtlÃ¯Â¿Â½nge anrechnen.
		$sResult .= substr($sString, 0, $iPos + 1);
		$sString  = substr($sString, $iPos + 1);
	}
	// Einen mÃ¯Â¿Â½glichen Rest aus der Eingabe mit in die Ausgabe Ã¯Â¿Â½bernehmen.
	if (($iLength + strlen($sString)) > $iMaxLength) {
		$sResult .= substr($sString, 0, $iMaxLength - $iLength);
	} else {
		$sResult .= $sString;
	}
	return $sResult;
}


/*
 * compress css code
 */
function compressCssOutput($sCss) {

	$sCss = Util::compressCssOutput($sCss);

	return $sCss;

}

/*
 * webDynamics exception handler
 */
function exceptionHandler($e) {
	global $system_data;

	// generate a webDynamics error on a failed database query if the
	// debug mode is disbaled and go on with the script execution
	if ($e instanceof DB_QueryFailedException && !\System::d('debugmode')) {
		error("Failed to execute database query, caught exception with the following message: ".$e->getMessage(), 1, 0, 1);
		return;
	}

	// if the debug mode is disabled, generate a webDynamics error and
	// stop the script execution here without any output
	if (!\System::d('debugmode')) {
		
		$sMessage = $e->getMessage();
		$sMessage .= "\n\n".$e->getTraceAsString();

		error("An uncaught exception occurs with the following message: ".$sMessage, 1, 0, 1);
		return;
	}

	// generate a php error on any uncaught exception
	// (if we are at this point, the debug mode is enabled)
	$sMessage  = "<b>Uncaught Exception of type '".get_class($e)."' with the following message:</b>\n";
	$sMessage .= $e->getMessage()."\n\n";
	$sMessage .= "<b>Thrown in:</b>\n";
	$sMessage .= \Util::convertHtmlEntities($e->getFile())." at line ".\Util::convertHtmlEntities($e->getLine())."\n\n";
	$sMessage .= "<b>Stack trace:</b>\n";
	$sMessage .= "<ul>";
	foreach (explode("\n", $e->getTraceAsString()) as $sTraceEntry) {
		$sMessage .= "<li>".$sTraceEntry."</li>";
	}
	$sMessage .= "</ul>";
	echo nl2br($sMessage);
	//trigger_error("Script execution aborted due to an uncaught exception, see messages above for details.", E_USER_ERROR);
	return;

}

/**
 * @deprecated
 *
 * @param string $strText
 * @param string $strCharset
 * @return string
 */
function convertHtmlEntities($strText, $strCharset = 'UTF-8') {
	$strText = htmlentities($strText, ENT_QUOTES, $strCharset);
	return $strText;
}

function getExtensionTranslations($strExtension, $strLanguage = false) {
	global $system_data, $LANGUAGE_DATA;
	
	if(!$strLanguage) {
		$strLanguage = $system_data['systemlanguage'];
	}

	$LANGUAGE_DATA = array();
	
	$strFile = \Util::getDocumentRoot()."system/extensions/".$strExtension."/lang/".$strLanguage.".inc.php";

	if(is_file($strFile)) {
		include_once($strFile);
		return true;
	} else {
		return false;
	}	
	
}

function encodeHtmlEmail($sCode) {
	return $sCode;
}

/**
 * create non existing funtions
 */

if (!function_exists("array_intersect_key")) {
  /**
   * Computes the intersection of arrays using keys for comparison
   *
   * implementation of PHP' CVS array_intersect_key()
   *
   * array_intersect_key() returns an array containing all the values
   * of array1 which have matching keys that are present in all the
   * arguments.
   *
   * might not be exactly equivalent with php.net/array_intersect_key
   * since we do not use === for comparison.
   *
   * will trigger an warning and return FALSE if one of the arguments
   * is not an array or if less than one argument is given.
   *
   * @see http://php.net/array_intersect_key
   *
   * @author: The Anarcat
   * @license: public domain
   */
  function array_intersect_key() {
   $arrays = func_get_args();
   // if only one array is given as argument, just return it
   if (count($arrays) == 1) return $arrays;
   elseif (count($arrays) < 1) {
     return FALSE;
   }
   $array1 = array_shift($arrays);
   foreach ($array1 as $key => $val) {
     for ($i = 0; $i < count($arrays); $i++) {
       $array =& $arrays[$i];
       if (!is_array($array)) {
         return FALSE;
       }
       if (!isset($array[$key])) {
         unset($array1[$key]);
       }
     }
   }
   return $array1;
  }
}

if(!function_exists('bcscale')) {
	$GLOBALS['bcscale'] = 4;
	function bcscale($iScale) {
		$GLOBALS['bcscale'] = $iScale;
	}
}

if(!function_exists('bcadd')) {
	function bcadd($mA, $mB, $iScale=false) {
		if(!$iScale) {
			$iScale = $GLOBALS['bcscale'];
		}
		$mC = $mA + $mB;
		$mC = round($mC, $iScale);
		return $mC;
	}
}

if(!function_exists('bcsub')) {
	function bcsub($mA, $mB, $iScale=false) {
		if(!$iScale) {
			$iScale = $GLOBALS['bcscale'];
		}
		$mC = $mA - $mB;
		$mC = round($mC, $iScale);
		return $mC;
	}
}

if(!function_exists('bcmul')) {
	function bcmul($mA, $mB, $iScale=false) {
		if(!$iScale) {
			$iScale = $GLOBALS['bcscale'];
		}
		$mC = $mA * $mB;
		$mC = round($mC, $iScale);
		return $mC;
	}
}

if(!function_exists('bcdiv')) {
	function bcdiv($mA, $mB, $iScale=false) {
		if(!$iScale) {
			$iScale = $GLOBALS['bcscale'];
		}
		$mC = $mA / $mB;
		$mC = round($mC, $iScale);
		return $mC;
	}
}

if(!function_exists('bccomp')) {
	function bccomp($floA, $floB, $intPrecision=0) {

		$floA = round($floA, $intPrecision);
		$floB = round($floB, $intPrecision);

		if($floA > $floB) {
			return 1;
		} elseif($floA < $floB) {
			return -1;
		} else {
			return 0;
		}

	}
}

if(!function_exists('bcmod')) {
	function bcmod($fA, $fB) {

		return $fA - ($fB * bcdiv($fA, $fB));

	}
}

if(!function_exists("strptime")){
    function strptime($sDate, $sFormat) {

    	$aResult = array
        (
            'tm_sec'   => 0,
            'tm_min'   => 0,
            'tm_hour'  => 0,
            'tm_mday'  => 1,
            'tm_mon'   => 0,
            'tm_year'  => 0,
            'tm_wday'  => 0,
            'tm_yday'  => 0,
            'unparsed' => $sDate,
        );
        
        while($sFormat != "")
        {
            // ===== Search a %x element, Check the static string before the %x =====
            $nIdxFound = strpos($sFormat, '%');
            if($nIdxFound === false)
            {
                
                // There is no more format. Check the last static string.
                $aResult['unparsed'] = ($sFormat == $sDate) ? "" : $sDate;
                break;
            }
            
            $sFormatBefore = substr($sFormat, 0, $nIdxFound);
            $sDateBefore   = substr($sDate,   0, $nIdxFound);
            
            if($sFormatBefore != $sDateBefore) break;
            
            // ===== Read the value of the %x found =====
            $sFormat = substr($sFormat, $nIdxFound);
            $sDate   = substr($sDate,   $nIdxFound);
            
            $aResult['unparsed'] = $sDate;
            
            $sFormatCurrent = substr($sFormat, 0, 2);
            $sFormatAfter   = substr($sFormat, 2);
            
            $nValue = -1;
            $sDateAfter = "";
            
            switch($sFormatCurrent)
            {
                case '%S': // Seconds after the minute (0-59)
                    
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);
                    
                    if(($nValue < 0) || ($nValue > 59)) return false;
                    
                    $aResult['tm_sec']  = $nValue;
                    break;
                
                // ----------
                case '%M': // Minutes after the hour (0-59)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);
                    
                    if(($nValue < 0) || ($nValue > 59)) return false;
                
                    $aResult['tm_min']  = $nValue;
                    break;
                
                // ----------
                case '%H': // Hour since midnight (0-23)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);
                    
                    if(($nValue < 0) || ($nValue > 23)) return false;
                
                    $aResult['tm_hour']  = $nValue;
                    break;
                
                // ----------
                case '%d': // Day of the month (1-31)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);
                    
                    if(($nValue < 1) || ($nValue > 31)) return false;
                
                    $aResult['tm_mday']  = $nValue;
                    break;
                
                // ----------
                case '%m': // Months since January (0-11)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);
                    
                    if(($nValue < 1) || ($nValue > 12)) return false;
                
                    $aResult['tm_mon']  = ($nValue - 1);
                    break;
                
                // ----------
                case '%Y': // Years since 1900
                    sscanf($sDate, "%4d%[^\\n]", $nValue, $sDateAfter);
                    
                    if($nValue < 1900) return false;
                
                    $aResult['tm_year']  = ($nValue - 1900);
                    break;
                
				// ----------
                case '%x': // Years since 1900
				    
                	$intTest = mktime(0, 0, 0, 12, 31, 1990);
					$strTest = strftime("%x", $intTest);
				
					$arrDate = array();
					
					// get seperator
					preg_match("/[^0-9]/", $strTest, $aReg);
					$strSeparator = $aReg[0];
				
					preg_match("/([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})/", $strTest, $aRegTest);
					preg_match("/([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})\\".$strSeparator."([0-9]{1,4})/", $sDate, $aReg);

					unset($aRegTest[0]);
				
					foreach((array)$aRegTest as $intKey=>$mixTest) {
						if($mixTest == 31) {
							$arrDate['D'] = (int)$aReg[$intKey];
						}
						if($mixTest == 12) {
							$arrDate['M'] = (int)$aReg[$intKey];
						}
						if($mixTest == 1990) {
							$arrDate['Y'] = (int)$aReg[$intKey];
						}
						if($mixTest == 90) {
							$intValue = (int)$aReg[$intKey];
							if(
								$intValue <= (date("y")+2) &&
								$intValue >= 0
							) {
								$arrDate['Y'] = $intValue + 2000;
							} else {
								$arrDate['Y'] = $intValue + 1900;
							}
						}
					}

					$aResult['tm_mday']  = $arrDate['D'];
					$aResult['tm_year']  = ($arrDate['Y'] - 1900);
					$aResult['tm_mon']  = ($arrDate['M'] - 1);

					break;
					
                // ----------
                default:
                    break 2; // Break Switch and while
                
            } // END of case format
            
            // ===== Next please =====
            $sFormat = $sFormatAfter;
            $sDate   = $sDateAfter;
            
            $aResult['unparsed'] = $sDate;
            
        } // END of while($sFormat != "")
        
        // ===== Create the other value of the result array =====
        $nParsedDateTimestamp = mktime($aResult['tm_hour'], $aResult['tm_min'], $aResult['tm_sec'],
                                $aResult['tm_mon'] + 1, $aResult['tm_mday'], $aResult['tm_year'] + 1900);
        
        // Before PHP 5.1 return -1 when error
        if(($nParsedDateTimestamp === false)
        ||($nParsedDateTimestamp === -1)) return false;
        
        $aResult['tm_wday'] = (int) strftime("%w", $nParsedDateTimestamp); // Days since Sunday (0-6)
        $aResult['tm_yday'] = (strftime("%j", $nParsedDateTimestamp) - 1); // Days since January 1 (0-365)

        return $aResult;
    } // END of function
    
} // END of if(function_exists("strptime") == false) 

 if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
      if( $temp=getenv('TMP') )        return $temp;
      if( $temp=getenv('TEMP') )        return $temp;
      if( $temp=getenv('TMPDIR') )    return $temp;
      $temp=tempnam(__FILE__,'');
      if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
      }
      return null;
  }
 }

 
if (!function_exists("posix_getpid")) {
	function posix_getpid() {
		return 0;
	}
}

if (!function_exists("dns_get_record")) {
	function dns_get_record() {
		return array(1);
	}
}

if (!function_exists("mb_strlen")) {
	function mb_strlen($sString, $sEncoding=null) {
		return strlen($sString);
	}
}

if (!function_exists("mb_substr")) {
	function mb_substr($sString, $iStart, $iLength=null, $sEncoding=null) {
		return substr($sString, $iStart, $iLength);
	}
}

if (!function_exists("mb_internal_encoding")) {
	function mb_internal_encoding($sEncoding=null) {
		return false;
	}
}

