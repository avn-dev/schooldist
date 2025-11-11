<?php

// Funktiom zum ersetzen von WD-Tags innerhalb des Mail-titels bzw. Mail-Contents.
function replaceWDTagsForSendPWMail($aMyData, $sText) {
	// Standardfelder ersetzen.
	$sText = str_replace('{$nickname}', $aMyData['nickname'], $sText);
	$sText = str_replace('{$email}', $aMyData['email'], $sText);
	// Neben den Standardfeldern ersetzen wir noch alle EXT-Felder.
	foreach ($aMyData as $sCurField => $sCurValue) {
		$sText = str_replace('{$'.$sCurField.'}', $sCurValue, $sText);
	}
	// Den veränderten Text zur�ckgeben.
	return $sText;
}

// Konfiguration laden.
$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$oCustomerDB = new Ext_CustomerDB_DB($oConfig->idTable);

$oPage = Cms\Entity\Page::getInstance($element_data['page_id']);

$buffer = $element_data['content'];

// überprüfen ob es Passwortanfragen gibt, die �lter als eine Woche sind
$sQuery = "SELECT *, UNIX_TIMESTAMP(`date`) `date` FROM `customer_db_activation`";
$rDate = DB::getQueryRows($sQuery);
foreach($rDate as $aDate) {
	if (($aDate['date'] + 30 * 24 * 3600) < mktime()) {
		$sQuery = "DELETE FROM `customer_db_activation` WHERE `id` = '".\DB::escapeQueryString($aDate['id'])."' LIMIT 1";
		DB::executeQuery($sQuery);
	}
}

if ($_VARS['task'] == "send") {

	if (!preg_match("/[a-z0-9]{2}/i", $_VARS['nickname']) && !preg_match("/[a-z0-9]{2}/i", $_VARS['email'])) {

		$_VARS['task'] = false;
		$sError        = $oConfig->error_nodata;

	} else {

		$aCheckEmail = array();
		$aCheckUser = array();		

		if(!empty($_VARS['email'])) {
			$aCheckEmail = $oCustomerDB->getCustomerByUniqueField('email', $_VARS['email']);
		}
		if(!empty($_VARS['nickname'])) {
			$aCheckUser = $oCustomerDB->getCustomerByUniqueField('user', $_VARS['nickname']);
		}

		if(
			!empty($aCheckEmail) ||
			!empty($aCheckUser)
		) {

			if(!empty($aCheckEmail)) {
				$aCustomer = $aCheckEmail;
			} else {
				$aCustomer = $aCheckUser;
			}

			if ($oCustomerDB->db_encode_pw == 1) {

				// Pr�fen, ob bereits ein Activation Key vom Benutzer angefordert wurde
				$sQuery = "SELECT *
				           FROM `customer_db_activation`
				           WHERE `id_user` = '".\DB::escapeQueryString($aCustomer['id'])."'
				             AND `id_table` = '".\DB::escapeQueryString($oConfig->idTable)."'
				           ORDER BY `date` DESC";
				$user = DB::getQueryRow($sQuery);
				if (!empty($user)) {
					$aCustomer['password'] = $user['activation_key'];
				} else {
					$aCustomer['password'] = \Util::generateRandomString(16);
					$sQuery = "INSERT INTO `customer_db_activation`
					                   SET `activation_key` = '".\DB::escapeQueryString($aCustomer['password'])."',
					                       `id_user` = '".\DB::escapeQueryString($aCustomer['id'])."',
					                       `id_table` = '".\DB::escapeQueryString($oConfig->idTable)."'";
					DB::executeQuery($sQuery);
				}
				// Aktivierungspasswort
				$oConfig->optinsubject = replaceWDTagsForSendPWMail($aCustomer, $oConfig->optinsubject);
				$oConfig->optincontent = replaceWDTagsForSendPWMail($aCustomer, $oConfig->optincontent);
				$sUrl = $oPage->getLink($element_data['language'], true)."?task=reset&key=".$aCustomer['password'];
				$oConfig->optinsubject = str_replace('{$get_new_password}', $sUrl, $oConfig->optinsubject);
				$oConfig->optincontent = str_replace('{$get_new_password}', $sUrl, $oConfig->optincontent);

				if($aCustomer['email'] && preg_match("/^([a-z0-9\._-]*)@([a-z0-9\.-]{2,66})\.([a-z]{2,6})$/i", $aCustomer['email'])) {
					$oMail = new WDMail;
					$oMail->subject = $oConfig->optinsubject;
					$oMail->text = $oConfig->optincontent;
					$oMail->send([$aCustomer['email']]);
				}

			} else {

				$oConfig->emailsubject = replaceWDTagsForSendPWMail($aCustomer, $oConfig->emailsubject);
				$oConfig->emailcontent = replaceWDTagsForSendPWMail($aCustomer, $oConfig->emailcontent);
				$oConfig->emailsubject = str_replace('{$password}', $aCustomer['password'], $oConfig->emailsubject);
				$oConfig->emailcontent = str_replace('{$password}', $aCustomer['password'], $oConfig->emailcontent);
				
				$oMail = new WDMail;
				$oMail->subject = $oConfig->emailsubject;
				$oMail->text = $oConfig->emailcontent;
				$oMail->send([$aCustomer['email']]);
				
			}

		} else {

			$_VARS['task'] = false;
			$sError        = $oConfig->error_noentry;

		}
	}
}

elseif ($_VARS['task'] == "reset") {

	$sQuery = "SELECT * FROM `customer_db_activation` WHERE `activation_key` = '".\DB::escapeQueryString($_VARS['key'])."'";
	$activation = DB::getQueryRow($sQuery);
	if (!empty($activation)) {
		
		$aCustomer = $oCustomerDB->getCustomer($activation['id_user']);
		
		$aCustomer['password'] = \Util::generateRandomString(8);
		// Setzen des neuen Passworts
		$oCustomerDB->updateCustomerField($activation['id_user'], 'password', $aCustomer['password']);

		$sQuery = "DELETE FROM `customer_db_activation` WHERE `id` = '".\DB::escapeQueryString($activation['id'])."'";
		DB::executeQuery($sQuery);
		
		// Mail senden.
		$oConfig->emailsubject = replaceWDTagsForSendPWMail($aCustomer, $oConfig->emailsubject);
		$oConfig->emailcontent = replaceWDTagsForSendPWMail($aCustomer, $oConfig->emailcontent);
		$oConfig->emailsubject = str_replace('{$password}', $aCustomer['password'], $oConfig->emailsubject);
		$oConfig->emailcontent = str_replace('{$password}', $aCustomer['password'], $oConfig->emailcontent);
		
		$oMail = new WDMail;
		$oMail->subject = $oConfig->emailsubject;
		$oMail->text = $oConfig->emailcontent;
		$oMail->send([$aCustomer['email']]);

	}

}

if ($_VARS['task'] == "send") {
	$buffer = \Cms\Service\PageParser::checkForBlock($buffer, 'confirm');
	$buffer = str_replace("<#nickname#>", strip_tags($_VARS['nickname']), $buffer);
	$buffer = str_replace("<#email#>", strip_tags($_VARS['email']), $buffer);
} elseif ($_VARS['task'] == "reset") {
	$buffer = \Cms\Service\PageParser::checkForBlock($buffer, 'reset');
} else {
	$buffer = \Cms\Service\PageParser::checkForBlock($buffer, 'form');
	$buffer = str_replace("<#error#>", $sError, $buffer);
}

$pos = 0;
while ($pos = strpos($buffer,'<#',$pos)) {
	$end = strpos($buffer,'#>',$pos);
	$var = substr($buffer, $pos+2, $end-$pos-2);
	if ($_VARS[$var]) {
		$$var = strip_tags($_VARS[$var]);
	}
	$buffer = substr($buffer, 0, $pos).$$var.substr($buffer, $end+2);
}

echo $buffer;
