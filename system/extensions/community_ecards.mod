<?php

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

require_once(\Util::getDocumentRoot()."system/extensions/community_ecards/community_ecards.inc.php");

$objSmarty = new \Cms\Service\Smarty();

if($_VARS['ecard_key'] != "") {

	$arrOptions = array("active"=>1);
	$arrEcard = community_ecards::getEcardSending($_VARS['ecard_key']);
	$objSmarty->assign('arrEcard', $arrEcard);

} elseif($_VARS['ecard_task'] == "send") {
	
	$arrOptions = array("active"=>1);
	$arrEcard = community_ecards::getEcard($_VARS['ecard_id'], $arrOptions);
	
	if($arrEcard['id']) {
		
		// check input
		$arrErrors = array();
		if(!checkEmailMx($_VARS['ecard_sender_email'])) {
			$arrErrors['ecard_sender_email'] = 1;
		}
		if(!checkEmailMx($_VARS['ecard_recipient_email'])) {
			$arrErrors['ecard_recipient_email'] = 1;
		}
		if(!$_VARS['ecard_sender_name']) {
			$arrErrors['ecard_sender_name'] = 1;
		}
		if(!$_VARS['ecard_recipient_name']) {
			$arrErrors['ecard_recipient_name'] = 1;
		}

		// spam check
		$bolSpam = community_ecards::checkSpam($_VARS['ecard_recipient_email']);
		if($bolSpam) {
			$arrErrors['ecard_spam'] = 1;
		}

		if(count($arrErrors) > 0) {
			
			// switch to input view
			$_VARS['ecard_task'] = "detail";
			
		} else {
		
			$strKey = \Util::generateRandomString(16);
			$strLink = $system_data['domain'].$_SERVER['PHP_SELF']."?ecard_key=".$strKey;

			$config->subject = str_replace('{$key}', $strKey, $config->subject);
			$config->subject = str_replace('{$link}', $strLink, $config->subject);
			$config->subject = str_replace('{$sender_name}', $_VARS['ecard_sender_name'], $config->subject);
			$config->subject = str_replace('{$recipient_name}', $_VARS['ecard_recipient_name'], $config->subject);
		
			$config->message = str_replace('{$key}', $strKey, $config->message);
			$config->message = str_replace('{$link}', $strLink, $config->message);
			$config->message = str_replace('{$sender_name}', $_VARS['ecard_sender_name'], $config->message);
			$config->message = str_replace('{$recipient_name}', $_VARS['ecard_recipient_name'], $config->message);
		
			$bolSuccess = wdmail($_VARS['ecard_recipient_email'], $config->subject, $config->message, false, false, false, $_VARS['ecard_sender_email']);
			
			if($bolSuccess) {
				
				$strSql = "
							INSERT INTO
								community_ecards_sendings
							SET
								`created` = NOW(),
								`active` = 1,
								`sender_name` = :sender_name,
								`sender_email` = :sender_email,
								`recipient_name` = :recipient_name,
								`recipient_email` = :recipient_email,
								`key` = :key,
								`message` = :message,
								`ecard_id` = :ecard_id
							";
				$arrSql = array();
				$arrSql['sender_name'] = $_VARS['ecard_sender_name'];
				$arrSql['sender_email'] = $_VARS['ecard_sender_email'];
				$arrSql['recipient_name'] = $_VARS['ecard_recipient_name'];
				$arrSql['recipient_email'] = $_VARS['ecard_recipient_email'];
				$arrSql['key'] = $strKey;
				$arrSql['message'] = $_VARS['ecard_message'];
				$arrSql['ecard_id'] = $_VARS['ecard_id'];
				DB::executePreparedQuery($strSql, $arrSql);

			}

		}
		
	}
	
	$objSmarty->assign('_VARS', $_VARS);
	$objSmarty->assign('strKey', $strKey);
	$objSmarty->assign('arrEcard', $arrEcard);
	$objSmarty->assign('arrErrors', $arrErrors);

} elseif($_VARS['ecard_task'] == "detail") {

	$arrOptions = array("active"=>1);
	$arrEcard = community_ecards::getEcard($_VARS['ecard_id'], $arrOptions);
	$objSmarty->assign('arrEcard', $arrEcard);

} else {

	$arrOptions = array("active"=>1);

	$arrEcards = community_ecards::getEcards($arrOptions);
	$objSmarty->assign('arrEcards', $arrEcards);

}


$objSmarty->displayExtension($element_data);

?>