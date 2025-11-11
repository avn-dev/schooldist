<?php

/*
 * FESTLEGUNG VON ADMINISTRATIONS PARAMETERN
 */
$admin_data['elements'] = array(
	"page"		=>"Standardseite",
	"link"		=>"externer Link",
	"intern"	=>"interne Verkn&uuml;pfung",
	"code"		=>"Quellcode",
	"frameset"	=>"Frameset",
	"template"	=>"Seitenvorlage",
	"xml"		=>"XML-Ausgabe"
	);

/*
 * ENDE FESTLEGUNG
 */

function loadAdminHeader($mixOptions="", $frameset=0, $strBody="", $charset="UTF-8", $bSubmitStatus=1, $bolXhtml=1) {
	
	Admin_Html::loadAdminHeader($mixOptions, $frameset, $strBody, $charset, $bSubmitStatus, $bolXhtml);

}

function loadAdminFooter($sAdditional="", $bFrameset=false) {
	
	Admin_Html::loadAdminFooter($sAdditional, $bFrameset);

}

/**
 * check page access
 *
 * @author	Mark Koopmann - mk@plan-i.de
 * @param	funktion und ob login eingeblendet werden soll oder nicht 1:0
 * @return	Typ des Rückgabewertes
 */
function accesschecker($function_name, $func_a="dummy", $func_b="dummy", $func_c="dummy") {

	if(!is_array($function_name))
	{
		$function_name = array($function_name, $func_a, $func_b, $func_c);
	}

	if(hasRight($function_name))
	{
		return true;
	}
	else
	{
		$objPage = new GUI_Page();
		$objPage->appendElement(L10N::t('Sie haben keine Berechtigung, diese Seite zu betreten!'));
		echo $objPage->generateHTML();
		exit();
	}
}

//  erzeugt einen button mit Mouseover-effekt in Win-XP-Sytle
function make_button($text, $href='#', $extra='', $style=''){
	echo '<button '.$extra.' class="btn btn-default">'.$text.'</button>';
}

/**
 * @global array $_VARS
 * @param array $topics
 * @param string $headtitle
 * @param string $name
 * @param string $parameter
 */
function printAdminMenue($topics, $headtitle="", $name="topic", $parameter="") {
	global $_VARS;

	echo '<ul class="nav nav-tabs">';
 
	$mCheckAccess = false;

	foreach((array)$topics as $elem) {
		$a = $elem[0];
		$b = $elem[1];
		// user has access to this topic
		if(!$elem[2] || hasRight($elem[2])) {
			$sLink = $_SERVER['PHP_SELF'].'?action=&'.$name.'='.$a.'&page_id='.$_VARS['page_id'].'&'.$parameter;
			if($_VARS[$name] == $a) {
				echo '<li class="active"><a href="'.$sLink.'">'.$b.'</a></li>';
			} else {
				echo '<li><a href="'.$sLink.'">'.$b.'</a></li>';
			}
		} else {
			if($_VARS[$name] == $a) {
				$mCheckAccess = $elem[2];	
			}
		}
	}

	echo '</ul>';

	if($mCheckAccess) {
		Access_Backend::checkAccess($mCheckAccess);
	}

}

function printAdminFooter() {
?>

	<div class="text-right">
		<input type="reset" class="btn btn-default" value="<?=L10N::t('Abbrechen', 'CMS')?>">
		<input type="submit" class="btn btn-primary" value="<?=L10N::t('Speichern', 'CMS')?>">
	</div>

<?
}

function printTableStart($sLeftWidth='40%', $sRightWidth='auto') {
?>
	<table width="100%" cellpadding="4" cellspacing="0" border="0" class="table">
		<colgroup>
			<col width="<?=$sLeftWidth?>">
			<col width="<?=$sRightWidth?>">
		</colgroup>
<?
}

function printTableEnd() {
?>
	</table>
<?
}

function printHint($idHelp) {
//$helpSubject gibt die ID aus der Datenbank zum Objekt wieder, zu der die Hilfe angezeigt werden soll
	global $db_data;
	if($idHelp!=0){
		$aHint = array();
		$rHint = array();

		$aHint = DB::getQueryRow("SELECT id, subject, helptext FROM system_hints WHERE id = ".(int)$idHelp." ");

		if(!empty($aHint['helptext'])) {
		?>
					<i class="fa fa-info-circle" onMouseMove="showHint('<?=$idHelp?>')" onMouseOut="hideHint()" style="cursor:hand;"></i>
					<div class="hint" id="<?=$idHelp?>">
						<?=$aHint['helptext']?>
					</div>
		<?
		}
	}
}

function printFormText($title, $name, $value="", $parameter="", $onerow=1, $idHelp=0) {
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td><input type="text" name="<?=$name?>" class="txt form-control" value="<?=\Util::convertHtmlEntities($value)?>" <?=$parameter?>></td>
		</tr>
<?
}

function printFormHidden($sName, $mValue="", $sParameter="") {
?>
		<input type="hidden" name="<?=$sName?>" value="<?=\Util::convertHtmlEntities($mValue)?>" <?=$sParameter?> />
<?
}

function printFormMedia($strTitle, $strName, $strValue="", $strParameter="", $onerow=1, $idHelp=0) {
?>
		<tr>
			<th><?=$strTitle?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td><input type="text" name="<?=$strName?>" class="txt w300" value="<?=\Util::convertHtmlEntities($strValue)?>" site="60" class="form_elem" readonly="readonly" style="background-color:#dededf;" <?=$strParameter?>>&nbsp;
											<button class="btn" onclick="window.open('/admin/frame.html?file=media&mode=selecticon&form_name='+this.form.name+'&input_name=&submit_button_value=<?=urlencode('übernehmen')?>&form_submit=0&input_name_title=&input_name_name=<?=$strName?>', 'media','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');return false;">auswählen</button>&nbsp;<input class="btn" type="button" onclick="this.form.elements['<?=$strName?>'].value='';" value="entfernen"></td>
		</tr>
<?
}

//ACHTUNG!!!!! Forumular muss name="formular" haben, sonst geht's nicht!
function printFormImage($title, $name, $value="", $idHelp="0", $iWidth="30", $parameter="") {
?>

		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
			<td>
				<input type="text" name="<?=$name;?>" value="<?=\Util::convertHtmlEntities($value)?>" size="60" class="txt w300" readonly="readonly" style="background-color:#dededf;">
				<button class="btn" type="button" onclick="window.open('/admin/frame.html?file=media&mode=selecticon&type=images&form_name='+this.form.name+'&input_name=&submit_button_value=Bild auswählen&form_submit=0&search=0&input_name_title=&input_name_name=<?=$name;?>', 'media','status=yes,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550'); return false;" class="btn">ausw&auml;hlen</button>
				<button class="btn" type="button" onclick="$(this).previous().previous().value = ''; return false;" class="btn">entfernen</button>
			</td>
		</tr>

<?
}

function printFormFile($title, $name, $value="", $parameter="", $onerow=1, $idHelp=0) {
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td>
				<input type="file" name="<?=$name?>" class="txt w300" value="<?=\Util::convertHtmlEntities($value)?>" <?=$parameter?>>
<?
	if(!empty($value)) {
?>
		<?=L10N::t('Aktuell')?>: <?=$value?>
<?
	}
?>
			</td>
		</tr>
<?
}

function printFormDate($title, $name, $value="", $parameter="", $onerow=1, $idHelp=0, $sFormat="%x %X") {
	if($value>0)
		$value = strftime($sFormat,$value);
	else
		$value = "";
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td><input type="text" name="<?=$name?>" class="txt form-control w300" value="<?=$value?>" <?=$parameter?>></td>
		</tr>
<?
}

function printFormTextarea($title, $name, $value="", $rows = 5, $parameter="", $onerow=1, $idHelp=0) {
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td><textarea name="<?=$name?>" class="txt form-control" rows="<?=$rows?>" <?=$parameter?>><?=\Util::convertHtmlEntities($value)?></textarea></td>
		</tr>
<?
}

function printFormHTMLarea($title, $name, $value="", $sToolbar="Basic", $onerow=1, $idHelp=0, $iWidth='100%', $iHeight='200') {
	global $_SERVER;

?>
		<tr>
			<th <?=(($onerow==0)?"colspan=2":"")?>><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td <?=(($onerow==0)?"colspan=2":"")?>>
				<textarea name="<?=$name?>" class="txt w300 tinymce" rows="<?=$rows?>" <?=$parameter?> style="height: <?=$iHeight?>;width: <?=$iWidth?>"><?=\Util::convertHtmlEntities($value)?></textarea>
			</td>
		</tr>
<?
}

function printFormSelect($title, $name, array $values, $value=-1, $parameter="", $onerow=1, $idHelp=0, $iWidth="30") {
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td>
				<select name="<?=$name?>" class="txt form-control" <?=$parameter?>>
					<?
					foreach($values as $key=>$val) {
						echo "<option value=\"".\Util::convertHtmlEntities($key)."\" ".(($key==$value)?"selected":"").">".$val."</option>";
					}
					?>
				</select>
			</td>
		</tr>
<?
}

function printFormSiteSelect($strTitle, $strName, $strValue, $strParameter="", $bolOnerow=1, $intHelpId=0, $intWidth="30") {
	global $system_data;

	$objWebDynamicsDAO = new \Cms\Helper\Data;

	$system_data['sites'] = $objWebDynamicsDAO->getSites();
	
	$arrSites = $system_data['sites'];
?>
		<tr>
			<th><?=$strTitle?> <?=printHint($intHelpId)?></th>
		<? if($bolOnerow == 0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td>
				<select name="<?=$strName?>" class="txt form-control" <?=$strParameter?>>
					<?
					foreach($arrSites as $arrSite) {
						echo "<option value=\"".\Util::convertHtmlEntities($arrSite['id'])."\" ".(($arrSite['id'] == $strValue)?"selected":"").">".$arrSite['name']."</option>";
					}
					?>
				</select>
			</td>
		</tr>
<?	
} 

function printFormMultiSelect($title, $name, $values, $aValue=array(), $parameter="", $onerow=1, $idHelp=0, $iWidth="30") {
	
	if(!is_array($aValue)) {
		$aValue = (array)$aValue;
	}
	
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td>
				<select name="<?=$name?>" class="txt form-control" multiple="multiple" <?=$parameter?>>
					<?
					foreach($values as $key=>$val) {
						echo "<option value=\"".$key."\" ".((in_array($key,$aValue))?"selected":"").">".$val."</option>";
					}
					?>
				</select>
			</td>
		</tr>
<?
}

function printFormPageSelect($title, $name, $value="", $mixOptions=0, $extra="", $bIdValue=0, $sWhere="", $iWidth="30", $strLanguageCode=false, $intSiteId=false, $sRight=false) {

	if(!is_array($mixOptions)) {
		$mixTmpOptions = $mixOptions;
		$mixOptions = array();
		$mixOptions['empty'] 		= $mixTmpOptions;
		$mixOptions['extra'] 		= $extra;
		$mixOptions['value_type'] 	= $bIdValue;
		$mixOptions['where'] 		= $sWhere;
		$mixOptions['language'] 	= $strLanguageCode;
		$mixOptions['site_id'] 		= $intSiteId;
		$mixOptions['right'] 		= $sRight;
	}

?>
		<tr>
			<th><?=$title?> <?=printHint($mixOptions['hint_id'])?></th>
			<td>
				<?=printPageSelect($name, $value, $mixOptions)?>
			</td>
		</tr> 
<?
}

function printFormCheckbox($title, $name, $value="1", $checked=false, $onerow=1, $idHelp=0, $iWidth="30", $parameter="") {
?>
		<tr>
			<th><?=$title?> <?=printHint($idHelp)?></th>
		<? if($onerow==0) { ?>
		</tr>
		<tr>
		<? } ?>
			<td><input type="hidden" name="<?=$name?>" value="0"><input type="checkbox" name="<?=$name?>" id="<?=$name?>" value="<?=\Util::convertHtmlEntities($value)?>" <?=(($checked)?"checked":"")?> <?=$parameter?> /></td>
		</tr>
<?
}

function getCalendarData($sTimestamp = "") {
	if ($sTemp = strtotimestamp($sTimestamp)) {
		$sTimestamp = $sTemp;
	}
	// Namen den Wochentage
	$aWeekDayNamesTime = mktime(0, 0, 0, 2, 6, 2006);
	for ($k = 1; $k <= 7; $k++) {
		$aW[$k] = strftime("%a", $aWeekDayNamesTime);
		$aWeekDayNamesTime += 86400;
	}
	$aCalendar['weekdays'] = array(0 => $aW[1], 1 => $aW[2], 2 => $aW[3], 3 => $aW[4], 4 => $aW[5], 5 => $aW[6], 6 => $aW[7]);

	if ($sTimestamp == "") {
		$sTimestamp = mktime();
	}
	$sMonth			= date("m", $sTimestamp);
	$sYear			= date("Y", $sTimestamp);

	$sMonthFwd		= ($sMonth == 12)	?	1			:	$sMonth + 1;
	$sMonthBack		= ($sMonth == 1)	?	12			:	$sMonth - 1;
	$sYearFwd		= ($sMonth == 12)	?	$sYear+1	:	$sYear;
	$sYearBack		= ($sMonth == 1)	?	$sYear-1	:	$sYear;

	$sMonthStart	= mktime(0, 0, 0, $sMonth, 1, $sYear);

	$aCalendar['months']['prev']['title']	= \Util::convertHtmlEntities(strftime("%b %Y", mktime(0, 0, 0, $sMonthBack, 1, $sYearBack)));
	$aCalendar['months']['curr']['title']	= \Util::convertHtmlEntities(strftime("%b %Y", $sMonthStart));
	$aCalendar['months']['next']['title']	= \Util::convertHtmlEntities(strftime("%b %Y", mktime(0, 0, 0, $sMonthFwd, 1, $sYearFwd)));
	$aCalendar['months']['prev']['timestamp']	= mktime(0, 0, 0, $sMonthBack, 1, $sYearBack);
	$aCalendar['months']['curr']['timestamp']	= $sMonthStart;
	$aCalendar['months']['next']['timestamp']	= mktime(0, 0, 0, $sMonthFwd, 1, $sYearFwd);

	$aCalendar['now'] = sprintf("%02d", $sMonth).".".$sYear;

	$sMonthDayAmount = date("t", $sMonthStart);
	for ($iDay = 1; $iDay <= $sMonthDayAmount; $iDay++) {
		$iCurrentWeekDay = date("w", mktime(0, 0, 0, $sMonth, $iDay, $sYear));
		$iCurrentWeek = date("W", mktime(0, 0, 0, $sMonth, $iDay, $sYear));
		if ($sMonth == 12 and $iCurrentWeek == 1) {
			$iCurrentWeek = 53;
		} elseif ($sMonth == 1 and $iCurrentWeek == 52) {
			$iCurrentWeek = 1;
		}
		if ($iDay == 1) {
			if ($iCurrentWeekDay == 0) {
				$iCheckWeekDay = 7;
			} else {
				$iCheckWeekDay = $iCurrentWeekDay;
			}
			for ($i = 1; $i < $iCheckWeekDay; $i++) {
				$aCalendar['weeks'][$iCurrentWeek][$i] = "";
			}
		}
		$aCalendar['weeks'][$iCurrentWeek][$iCurrentWeekDay] = sprintf("%02d", $iDay);
	}
	return $aCalendar;
}

/**
 * @param $strTitle = Bezeichnung des Feldes
 * @param $strName = Name des Feldes
 * @param $strValue = Übergebener Wert des Feldes
 * @param $strParameter = zusätzliche Parameter
 * @param $strAdditionalPost = zusätzlich übergebene Parameter, die für den Aufruf der Seite notwendig sind
 * ACHTUNG: Am Anfang und Ende der Datei muss ob_start(); und ob_end_flush(); eingebunden werden, damit SAJAX funktioniert
 */
function printFormCalendar($strTitle, $strName, $strValue = "", $strParameter = "", $strAdditionalPost="") {

?>
		<tr>
			<th><?=$strTitle?></th>
			<td>
				<div class="calendar_container">
					<input class="txt" style="width:125px;" id="<?=$strName?>" name="<?=$strName?>" value="<?=$strValue?>" <?=$strParameter?> />
					<img src="/admin/media/calendar.png" alt="" id="<?=$strName?>_button" />
				</div>
			</td>
		</tr>
		
<script type="text/javascript">
	Event.observe( window, 'load', function() {
		Calendar.prepare({
				dateField      : '<?=$strName?>', // ID des Inputfeldes
				triggerElement : '<?=$strName?>_button' // ID des Elements, das angeklickt wird
			});
		}
	); 
</script>
		
<?
}

function printSubmit($text, $parameter="") {
?>
		<p align="right">
		<input type="submit" value="<?=$text?>" class="btn btn-primary" <?=$parameter?> />
		</p>
<?
}

function printButton($text,$onclick) {
?>
		<p align="right">
		<button onClick="<?=$onclick?>" class="btn"><?=$text?></button>
		</p>
<?
}

function printPageSelect($name, $value="", $mixOptions="", $class="txt form-control", $style="", $extra="", $empty=false, $iValueType=false, $sWhere="", $strLanguageCode=false, $intSiteId=false, $sRight=false) {

	$objWebDynamicsDAO = new \Cms\Helper\Data;
	
	if(!is_array($mixOptions)) {
		$strId 						= $mixOptions;
		$mixOptions 				= array();
		$mixOptions['id'] 			= (string)$strId;
		$mixOptions['class'] 		= (string)$class;
		$mixOptions['style'] 		= (string)$style;
		$mixOptions['extra'] 		= (string)$extra;
		if(!$empty)
		{
			$mixOptions['empty'] 		= $empty;
		}
		else if(is_array($empty))
		{
			$mixOptions['empty'] 		= (array)$empty;
		}
		$mixOptions['value_type'] 	= (string)$iValueType;
		$mixOptions['where'] 		= (string)$sWhere;
		$mixOptions['language'] 	= (string)$strLanguageCode;
		$mixOptions['site_id'] 		= (int)$intSiteId;
		$mixOptions['right'] 		= $sRight;
	}

	if(!$mixOptions['class']) {
		$mixOptions['class'] = "txt form-control";
	}

	/*
	 * $iValueType
	 * 0 - url
	 * 1 - id
	 * 2 - wd tag 
	 */
	if(!is_array($value)) {
		$value = array($value);
	}

	$output = "";
	$output .= "<select id=\"".$mixOptions['id']."\" name=\"".$name."\" class=\"".$mixOptions['class']."\" style=\"".$mixOptions['style']."\" ".$mixOptions['extra'].">";
	if($mixOptions['empty']) {
		$output .= "<option value=\"".key($mixOptions['empty'])."\">".current($mixOptions['empty'])."</option>";
	}
	
	if($mixOptions['language']) {
		$mixOptions['where'] .= " AND (language = '".\DB::escapeQueryString($mixOptions['language'])."' OR language = '') ";
	}
	
	if($mixOptions['site_id']) {
		$mixOptions['where'] .= " AND (site_id = '".intval($mixOptions['site_id'])."' OR site_id = '0') ";
	}

	$aArray = array();

	$strSql = "
				SELECT 
					id, 
					site_id, 
					language, 
					path, 
					file  
				FROM  
					cms_pages  
				WHERE  
					active != 2 AND  
					element != 'template'  
					".$mixOptions['where']."  
				ORDER BY  
					site_id,
					path,  
					(file != 'index'),  
					position,  
					id,  
					level";

	$aPages = DB::getQueryRows($strSql);
	foreach($aPages as $my_pages) {
		
		$bCheck = true;
		if(isset($mixOptions['right']) && !empty($mixOptions['right'])) {
			$bCheck = checkRightInPath($mixOptions['right'], $my_pages['id']);
			if(!$bCheck) {
				continue;
			}
		}
		
		if($mixOptions['value_type'] == 1) {
			$sValue = $my_pages['id'];
		} elseif($mixOptions['value_type'] == 2) {
			$sValue = '#page:'.$my_pages['id'].':pagelink#';
		} else {
			$sValue = "/";
			if($my_pages['language'] != "") {
				$sValue .= $my_pages['language']."/";
			} elseif($mixOptions['language'] && !$my_pages['language']) {
				$sValue .= $mixOptions['language']."/";
			}
			if($my_pages['path'] != "") {
				$sValue .= $my_pages['path'];
			}
			if($my_pages['file'] != "") {
				$sValue .= $my_pages['file'].".html";
			}
		}

		$sName = ""; 
		if(!$mixOptions['site_id']) {
			$aSite = $objWebDynamicsDAO->getSiteData($my_pages['site_id']);
			$sName .= $aSite['name']." &raquo; ";
		}

		$sName .= getPageTrack($my_pages['id']);
		$output .= "<option value=\"".$sValue."\" ".((in_array($sValue,$value))?"selected":"").">".$sName." (". idtopath($my_pages['id']).")\n";

		$aArray[$sValue] = $sName;
	}

	$output .= "</select>\n";

	if(isset($mixOptions['as_array'])) {
		return $aArray;
	}

	return $output;
}

function searchContent($strSearch) {

	$intResults = 0;

	$intResults += count((array)DB::getQueryRows("SELECT * FROM cms_blockdata WHERE public LIKE '%".\DB::escapeQueryString($strSearch)."%'"));

	$intResults += count((array)DB::getQueryRows("SELECT * FROM cms_content WHERE public LIKE '%".\DB::escapeQueryString($strSearch)."%'"));

	$intResults += count((array)DB::getQueryRows("SELECT * FROM cms_pages WHERE template LIKE '%".\DB::escapeQueryString($strSearch)."%'"));

	$intResults += count((array)DB::getQueryRows("SELECT * FROM cms_extensions_config WHERE param LIKE '%".\DB::escapeQueryString($strSearch)."%'"));

	$intResults += count((array)DB::getQueryRows("SELECT * FROM system_elements WHERE template LIKE '%".\DB::escapeQueryString($strSearch)."%'"));

	return $intResults;
}

function replaceContent($from, $to) {

	DB::executeQuery("UPDATE cms_blockdata 	SET `content`	= REPLACE(`content`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");
	DB::executeQuery("UPDATE cms_blockdata 	SET `public`	= REPLACE(`public`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");

	DB::executeQuery("UPDATE cms_content 	SET `content`	= REPLACE(`content`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");
	DB::executeQuery("UPDATE cms_content 	SET `public`	= REPLACE(`public`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");

	DB::executeQuery("UPDATE cms_pages		SET `template`	= REPLACE(`template`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");
	DB::executeQuery("UPDATE cms_pages		SET `parameter` = REPLACE(`parameter`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");
	DB::executeQuery("UPDATE cms_pages 		SET `login` 	= REPLACE(`login`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");

	DB::executeQuery("UPDATE system_elements 	SET `template` = REPLACE(`template`,'".\DB::escapeQueryString($from)."','".\DB::escapeQueryString($to)."')");

	return true;
}

/**
 * move existing page or directory
 *
 * TODO: checken, ob gleichnamige seite schon existiert
 */
function movePage($intPageId, $strTargetPath) {

	$strSql = "SELECT * FROM cms_pages WHERE id = '".(int)$intPageId."' LIMIT 1";
	$arrSql = DB::getQueryData($strSql);
	$arrPage = reset($arrSql);

	$arrElements = explode("/", $strTargetPath);
	$intLevel = count($arrElements);

	if($arrPage['file'] == "index") {

		$arrElements = explode("/", $arrPage['path']);

		$arrElementPath = array_splice($arrElements, -2, 1);

		$strElementPath = reset($arrElementPath);

		$strNewElementPath = $strTargetPath.$strElementPath."/";

		// get old level
		$intLevelOld = substr_count($arrPage['path'], "/") + 1;

		// get new level
		$intLevel = substr_count($strNewElementPath, "/") + 1;
		
		// get level difference
		$intLevelDiff = $intLevel - $intLevelOld;
		
		$strPathFrom = "/".$arrPage['language']."/".$arrPage['path'];
		$strPathTo = "/".$arrPage['language']."/".$strNewElementPath;

		DB::executeQuery("UPDATE cms_pages SET path = '".\DB::escapeQueryString($strNewElementPath)."', level = '".(int)$intLevel."' WHERE id = '".(int)$intPageId."'");

		DB::executeQuery("UPDATE cms_pages SET path = REPLACE(path, '".$arrPage['path']."', '".$strNewElementPath."'), level = (level + ".($intLevelDiff).") WHERE path LIKE '".$arrPage['path']."%'");

		\Log::enterLog($intPageId, "Verzeichnis wurde verschoben.");

	} else {

		$strPathFrom = "/".$arrPage['language']."/".$arrPage['path'].$arrPage['file'].".html";
		$strPathTo = "/".$arrPage['language']."/".$strTargetPath.$arrPage['file'].".html";
	
		DB::executeQuery("UPDATE cms_pages SET path = '".\DB::escapeQueryString($strTargetPath)."', level = '".(int)$intLevel."' WHERE id = '".(int)$intPageId."'");

		\Log::enterLog($intPageId, "Seite wurde verschoben.");

	}

	replaceContent($strPathFrom, $strPathTo);

}

function getNewMessages($idUser) {
	global $db_data;
	$arrOutput = array();
	$aComments = DB::getQueryRows("SELECT *,c.id FROM system_comments c, system_user u WHERE c.status = 'new' AND c.receiver = ".(int)$idUser." AND c.user = u.id ORDER BY c.send DESC");
	foreach($aComments as $aComment) {
		$arrOutput[] = $aComment;
	}
	return $arrOutput;
}

function checkValidLicense($aUser) {
	return System::checkValidLicense($aUser);
}

/* XML Parser */


function xml2array($xml) {

    $_data = NULL;

    $xp = xml_parser_create();
    xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, false);
    xml_parser_set_option($xp, XML_OPTION_SKIP_WHITE, true);
	$vals =		NULL;
	$index =	NULL;
    xml_parse_into_struct($xp,$xml,$vals,$index);
    xml_parser_free($xp);

    $temp = $depth = array();
    $dc = array();

    foreach((array)$vals as $value) {

        $p = join('::', $depth);

        $key = $value['tag'];

        switch ($value['type']) {

          case 'open':
            array_push($depth, $key);
            array_push($depth, (int)$dc[$p]++ );
            break;

          case 'complete':
            array_push($depth, $key);
            $p = join('::',$depth);
            $temp[$p] = $value['value'];
            array_pop($depth);
            break;

          case 'close':
			array_pop($depth);
            array_pop($depth);
            break;

        }

    }

    foreach ((array)$temp as $key=>$value) {

        $levels = explode('::',$key);
        $num_levels = count($levels);

        if ($num_levels==1) {
            $_data[$levels[0]] = $value;
        } else {
            $pointer = &$_data;
            for ($i=0; $i<$num_levels; $i++) {
                if ( !isset( $pointer[$levels[$i]] ) ) {
                    $pointer[$levels[$i]] = array();
                }
                $pointer = &$pointer[$levels[$i]];
            }
            $pointer = $value;
        }

    }

    return ($_data);

}

function getPageStatus($intIdPage) {
	global $db_data;

	$bolUpToDate = 1;

	$arrPage = DB::getQueryRow("SELECT `element` FROM cms_pages WHERE id = ".(int)$intIdPage." LIMIT 1");

	if($arrPage['element'] == 'page') {

		$resUpToDate1 = DB::executeQuery("SELECT b.id FROM cms_content c, cms_blockdata b WHERE c.page_id = ".(int)$intIdPage." AND c.id = b.content_id AND b.uptodate = 0 LIMIT 1");
		$intUpToDate1 = DB::numRows($resUpToDate1);
		$resUpToDate2 = DB::executeQuery("SELECT c.id FROM cms_content c WHERE page_id = ".(int)$intIdPage." AND uptodate = 0 LIMIT 1");
		$intUpToDate2 = DB::numRows($resUpToDate2);

		if($intUpToDate1 > 0 || $intUpToDate2 > 0) {
			$bolUpToDate = 0;
		}
	}

	return $bolUpToDate;

}

/**
 * Check if right exists 
 * 
 * @param string $sRight
 * @param string $sTitle
 * @param string $sExtension
 * @return bool 
 */
function addSystemRight($sRight, $sTitle, $sExtension='') {
	$sSql = "SELECT * FROM system_rights WHERE `right` = :right";
	$aSql = array('right'=>$sRight);
	$aResult = DB::getPreparedQueryData($sSql, $aSql);
	if(empty($aResult)) {
		$sSql = "INSERT INTO system_rights SET `right` = :right, `element` = :element, `description` = :description ";
		$aSql = array(
						'right'=>$sRight,
						'description'=>$sTitle,
						'element'=>$sExtension
					);
		DB::executePreparedQuery($sSql, $aSql);
		return true;
	}
	return false;
}

function SQL_getRoles()  // nur f�r Steps, die nur einsprachig sind
{
	$aRoles = DB::getQueryRows("SELECT * FROM `system_roles` ORDER BY name");
	foreach((array)$aRoles as $my) {
		$ret[$my['id']] = $my['name'];
	}
	return $ret;
}
