<?php
/**
 * Created on 17.01.2007
 * @author Bastian Haustein
 * @copyright Copyright &copy; 2007, Bastian Haustein, medienartig gmbh
 * @license http://www.gesetze-im-internet.de/urhg/BJNR012730965.html UrhG / full rights granted to plan-i GmbH
 * @package guestbook
 * @version 1.01
**/

# ERLEDIGT: A-Prio: Wiederanzeige von Variablen funktioniert nicht (#PAGE-Tags aus???)
# ERLEDIGT: A-Prio: Löschen-Link einbinden
# ERLEDIGT: A-Prio: Löschen-Recht einbauen
# ERLEDIGT: B-Prio: Templates hinterlegen
# ERLEDIGT: B-Prio: Install über Modul-Manager bereitstellen
# TODO: C-Prio: d einbauen
# TODO: C-Prio: Mail sollte Nachricht enthalten können
# TODO: D-Prio: Freischaltung per Mail-Link
# TODO: D-Prio: Ausbau zu Blog-Modul (sollte easy sein!!!)

/*

Recht in DB eintragen:
  INSERT INTO `system_rights` ( `id` , `right` , `element` , `description` ) VALUES (NULL , 'guestbook_del', 'guestbook', 'Module &raquo; G&auml;stebucheintrag l&ouml;schen')

Element in DB eintragen
  INSERT INTO `system_elements` ( `id` , `title` , `description` , `element` , `category` , `file` , `version` , `parent` , `image` , `documentation` , `template` , `sql` , `administrable` , `include_backend` , `include_frontend` , `active` ) VALUES ('', 'G&auml;stebuch', '', 'modul', 'Standardmodule', 'guestbook', '1.04', '', '', '', '<#block_save#> <strong>Ihre Nachricht wurde gespeichert und muss noch von einem Administrator freigeschaltet werden.</strong> <#block_index#> <#block_active#><a href=''<#url#>''><strong><#page#></strong></a><#/block_active#> <#block_inactive#><a href=''<#url#>''><#page#></a><#/block_inactive#> <#block_separator#> | <#/block_separator#> <#/block_index#> <br> <#block_entries#> <#date#>, <#name#>, <#email#><br> <#text#><hr> <#/block_entries#> <br> <#index_repeated#> <#/block_save#> <#block_view#> <#error_messages#> <form> Name: <input type=''text'' name=''msg_name'' value=''#page:_VARS[''msg_name'']#''><br> eMail: <input type=''text'' name=''msg_email'' value=''#page:_VARS[''msg_email'']#''><br> Text: <textarea name=''msg_text''>#page:_VARS[''msg_text'']#</textarea><br> <input type=''hidden'' name=''task'' value=''msg_save''> <input type=''submit''> </form> <#block_index#> <#block_active#><a href=''<#url#>''><strong><#page#></strong></a><#/block_active#> <#block_inactive#><a href=''<#url#>''><#page#></a><#/block_inactive#> <#block_separator#> | <#/block_separator#> <#/block_index#> <br> <#block_entries#> <#date#>, <#name#><br> <#email#><#block_admin#> <a href=''<#url#>''>l&ouml;schen</a><#/block_admin#><br> <#text#><hr> <#/block_entries#> <br> <#index_repeated#> <#/block_view#> <#block_activate#> Der Eintrag wurde freigeschaltet! <#/block_activate#> <#block_delete#> Der Eintrag wurde gel&ouml;scht! <#/block_delete#> <#error_name#><p style=''color:red''>Bitte geben Sie einen Namen an!</p><#/error_name#> <#error_email#><p style=''color:red''>Bitte geben Sie eine <b>g&uuml;ltige</b> Emailadresse an!</p><#/error_email#> <#error_text#><p style=''color:red''>Bitte geben Sie eine Nachricht ein!</p><#/error_text#>', '', '1', '0', '0', '1')

Tabelle anlegen:
  CREATE TABLE `guestbook` (`id` int(10) unsigned NOT NULL auto_increment, `modified` timestamp(14) NOT NULL, `created` timestamp(14) NOT NULL, `active` tinyint(3) unsigned NOT NULL default '0', `ip` tinytext NOT NULL, `session` varchar(32) NOT NULL default '', `name` text NOT NULL, `email` text NOT NULL, `text` text NOT NULL, PRIMARY KEY  (`id`), KEY `active` (`active`))

So koennte ein Template aussehen:
*
$element_data["content"] =
"
<#block_save#>
	<strong>Ihre Nachricht wurde gespeichert und muss noch von einem Administrator freigeschaltet werden.</strong>
	<#block_index#>
		<#block_active#><a href='<#url#>'><strong><#page#></strong></a><#/block_active#>
		<#block_inactive#><a href='<#url#>'><#page#></a><#/block_inactive#>
		<#block_separator#> | <#/block_separator#>
	<#/block_index#>
	<br>
	<#block_entries#>
		<#text#><hr>
	<#/block_entries#>
	<br>
	<#index_repeated#>
<#/block_save#>

<#block_view#>
	<#error_messages#>
	<form>
		Name:	<input type='text' name='msg_name' value='#page:_VARS['msg_name']#'><br>
		eMail:  <input type='text' name='msg_email' value='#page:_VARS['msg_email']#'><br>
		Text:   <textarea name='msg_text'>#page:_VARS['msg_text']#</textarea><br>
		<#captcha#><br>
		Captcha: <input type='text' name='msg_captcha' value='#page:_VARS['msg_captcha']#'><br>
		<input type='hidden' name='task' value='msg_save'>
		<input type='submit'>
	</form>
	<#block_index#>
		<#block_active#><a href='<#url#>'><strong><#page#></strong></a><#/block_active#>
		<#block_inactive#><a href='<#url#>'><#page#></a><#/block_inactive#>
		<#block_separator#> | <#/block_separator#>
	<#/block_index#>
	<br>
	<#block_entries#>
		<#date#>, <#name#><br>
		<#email#><#block_admin#> <a href='<#url#>'>l&ouml;schen</a><#/block_admin#><br>
		<#text#><hr>
	<#/block_entries#>
	<br>
	<#index_repeated#>
<#/block_view#>

<#block_activate#>
	Der Eintrag wurde freigeschaltet!
<#/block_activate#>

<#block_delete#>
	Der Eintrag wurde gel&ouml;scht!
<#/block_delete#>

<#error_captcha#><p style='color:red'>Bitte geben Sie den richtigen Code an!</p><#/error_captcha#>
<#error_name#><p style='color:red'>Bitte geben Sie einen Namen an!</p><#/error_name#>
<#error_email#><p style='color:red'>Bitte geben Sie eine <b>g&uuml;ltige</b> Emailadresse an!</p><#/error_email#>
<#error_text#><p style='color:red'>Bitte geben Sie eine Nachricht ein!</p><#/error_text#>
";



/**
 * oConfig contains the configuration data
**/
$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

/**
 * sTemplate contains the template (after some checks, which template to use)
**/
$sTemplate='';

/**
 * asMessages is an array of string (parsed from the template) whith problem-messages
 */
$asMessages = array();

/**
 * oCapture is an object for dealing whith captchas
 */
$oCaptcha = new \Cms\Service\Captcha;

#########################################################################################################
# actions: save, delete, error-reporting / choosing the right template-block
#########################################################################################################

// save
if($_VARS['task']=='msg_save') {
	// Validitaetscheck
	$abValid = array();

	if($oConfig->name_needed == 0 || strlen($_VARS['msg_name']) > 3 )	{	$abValid['name']=true;		}
	else																{	$abValid['name']=false;		}

	if($oConfig->mail_needed == 0 ||checkEmailMx($_VARS['msg_email']))	{	$abValid['email']=true;		}
	else																{	$abValid['email']=false;	}

	if($oConfig->captcha_needed == 0 || $oCaptcha->checkCaptcha($_VARS['msg_captcha']))
																		{	$abValid['captcha']=true;	}
	else																{	$abValid['captcha']=false;	}

	if(strlen($_VARS['msg_text']) > 3)									{	$abValid['text']=true;		}
	else																{	$abValid['text']=false;		}

	$bOk = true;
	foreach($abValid as $sKey=>$sVal) {
		if($sVal == false) {
			$asMessages[$sKey] = \Cms\Service\PageParser::checkForBlock($element_data["content"], 'error_' . $sKey);
			$bOk = false;
		}
		// captcha ???*/
	}

	if($bOk) {
		global $user_data;
		$sSQL = "INSERT INTO `guestbook` SET " .
				"`id` = ''," .
				"`modified` = NOW()," .
				"`created` = NOW()," .
				"`active` = '1'," .
				"`ip` = '" .	\DB::escapeQueryString($_SERVER['REMOTE_ADDR']) . "'," .
				"`session`='" .	\DB::escapeQueryString($user_data['session']) . "'," .
				"`name`='" . 	\DB::escapeQueryString($_VARS['msg_name']) . "'," .
				"`email`='" .	\DB::escapeQueryString($_VARS['msg_email']) . "'," .
				"`text`='" .	\DB::escapeQueryString($_VARS['msg_text']) . "'";
		db_query($sSQL);


		// unlock mail to admin?

		if(strlen($oConfig->email_add)>5 && strlen($oConfig->email_subject)>5) {
			wdmail(
				$oConfig->email_add,
				$oConfig->email_subject,
				str_replace(	'<#time#>',		date('H:i:s'),
					str_replace('<#date#>',		date('m.d.Y'),
					str_replace('<#text#>',		$_VARS['msg_text'],
					str_replace('<#name#>',		$_VARS['msg_name'],
					str_replace('<#email#>',	$_VARS['msg_email'],
					str_replace('<#ip#>',		$_SERVER['REMOTE_ADDR'],
					$oConfig->email_body
				)))))),
				"FROM: ".$oConfig->email_from.""
				);
		}

		// reset the form vars
		foreach($_VARS as $sKey => $sVal) {
			if(substr($sKey, 0, 4) == "msg_") {
				unset($_VARS[$sKey]);
			}
		}

		// set the template
		$sTemplate = \Cms\Service\PageParser::checkForBlock($element_data["content"], "block_save");
	} else {
		// escape the form vars for output
		foreach($_VARS as $sKey => $sVal) {
			if(substr($sKey, 0, 4) == "msg_") {
				$_VARS[$sKey] = htmlspecialchars($_VARS[$sKey]);
			}
		}

	}
}
// delete
else if($_VARS['task']=='msg_delete' && intval($_VARS['msg_del_id']) > 0) {
	if(hasRight('guestbook_del'))
	{
		$sSQL = "UPDATE `guestbook` SET " .
				"`modified` = NOW()," .
				"`created` = `created`," .
				"`active` = '2' " .
				"WHERE id='".intval($_VARS['msg_del_id'])."'";
		db_query($sSQL);
	}
		$sTemplate = \Cms\Service\PageParser::checkForBlock($element_data["content"], "block_delete");
}

// standard -> view
if(!$sTemplate) $sTemplate = \Cms\Service\PageParser::checkForBlock($element_data["content"], "block_view");


// template choosen, actions fullfilled


#########################################################################################################
# pre-parsing: entry-block, error-messages, page-index
#########################################################################################################


// get guestbook entries:
// fetch data
$iFirst =	intval($_VARS['start']);
$sMessageBlock = \Cms\Service\PageParser::checkForBlock($sTemplate, 'block_entries');
$sReplaceMessages = "";
$sSQL =		"SELECT `id`, UNIX_TIMESTAMP(`created`) as date, `text`, `name`, `email` " .
			"FROM `guestbook` " .
			"WHERE `active`='1' " .
			"ORDER BY `date` DESC " .
			"LIMIT $iFirst, " . intval($oConfig->per_page);
$rRes =		db_query($sSQL);
$sAdminDeleteBlock = \Cms\Service\PageParser::checkForBlock($sTemplate, 'block_admin');
// put the things together for the guestbook entrys
while($asMy = get_data($rRes)) {
	$sTempBlock	=	$sMessageBlock;
	if(hasRight('guestbook_del')) {
		$sAdminReplace = str_replace('<#url#>', '?task=msg_delete&msg_del_id=' . $asMy['id'], $sAdminDeleteBlock);
	} else {
		$sAdminReplace = '';
	}
	$sTempBlock = \Cms\Service\PageParser::replaceBlock($sTempBlock, 'block_admin', $sAdminReplace); 			// page index
	foreach($asMy as $sKey=>$sVal) {
		if($sKey=='date') continue;
		$sTempBlock = str_replace("<#" . $sKey . "#>", htmlspecialchars($sVal), $sTempBlock);
	}
	$sTempBlock = str_replace("<#date#>", date("d.m.Y", $asMy['date']), $sTempBlock);
	$sTempBlock = str_replace("<#time#>", date("H:i:s", $asMy['date']), $sTempBlock);
	$sReplaceMessages .= $sTempBlock;
}




// get error messages
$sErrorMessages = implode(' ',$asMessages);





// get page index
// fetch data
$sIndexBlock = \Cms\Service\PageParser::checkForBlock($sTemplate, 'block_index');
$sSQL =		"SELECT COUNT(*) as cnt FROM `guestbook` WHERE `active`='1'";
$rRes =		db_query($sSQL);
$asMy = 	get_data($rRes);
$iCnt =		intval($asMy['cnt']);
$sActiveBlock =		\Cms\Service\PageParser::checkForBlock($sIndexBlock, 'block_active');
$sInactiveBlock =	\Cms\Service\PageParser::checkForBlock($sIndexBlock, 'block_inactive');
$sSeparatorBlock =	\Cms\Service\PageParser::checkForBlock($sIndexBlock, 'block_separator');
$sReplaceIndex = '';

// put the things together for the index
$iMyFirst =		0;
$iLoops =		1;
$bFirstRepeat =	true;
while($iMyFirst < $iCnt && $iLoops < 100)
{
	if(!$bFirstRepeat) {
		$sReplaceIndex .= $sSeparatorBlock;
	} else {
		$bFirstRepeat = false;
	}

	if($iMyFirst == $iFirst) {
		$sTempBlock = $sActiveBlock;
	} else {
		$sTempBlock = $sInactiveBlock;
	}

	$sTempBlock = str_replace("<#page#>",$iLoops,$sTempBlock);
	$sTempBlock = str_replace("<#url#>","?start=".$iMyFirst,$sTempBlock);

	$sReplaceIndex .=$sTempBlock;

	$iMyFirst += $oConfig->per_page;
	$iLoops ++;
}



#########################################################################################################
# now do the template parsing stuff and throw it out...
#########################################################################################################

$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate, 'block_entries', $sReplaceMessages); 		// block_entries
$sTemplate = str_replace('<#error_messages#>', $sErrorMessages, $sTemplate);	// error messages
$sTemplate = \Cms\Service\PageParser::replaceBlock($sTemplate, 'block_index', $sReplaceIndex); 			// page index
$sTemplate = str_replace('<#index_repeated#>', $sReplaceIndex, $sTemplate);		// another place fpr a page index
$sTemplate = str_replace('<#captcha#>', $oCaptcha->getCaptchaImageTag(
	$oConfig->sCapWidth,
	$oConfig->sCapHeight,
	$oConfig->sCapLen,
	array(
		hexdec(substr($oConfig->sCapBgColor,0,2)),
		hexdec(substr($oConfig->sCapBgColor,2,2)),
		hexdec(substr($oConfig->sCapBgColor,4,2))),
	array(
		hexdec(substr($oConfig->sCapBorderColor,0,2)),
		hexdec(substr($oConfig->sCapBorderColor,2,2)),
		hexdec(substr($oConfig->sCapBorderColor,4,2)))
	), $sTemplate); // captcha-image

echo $sTemplate;



/*
Simple example of a template

*/
?>
