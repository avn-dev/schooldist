<?php

/*******************************************************************************
* CMS
* Mark Koopmann
* PHP-Datei: newsletter.mod
* copyright by PLAN-I GmbH
********************************************************************************
* Erstellungsdatum: 13.04.2003
* Newslettermodul
* letzte Änderung:
* Ausgabeskript
* durch: Mark Koopmann
*******************************************************************************/

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$aList = DB::getQueryRow("SELECT * FROM `newsletter2_lists` WHERE `id` = ".(int)$config->newsletter_id);

if (isset($aList['optin_flag']) && $aList['optin_flag']) {
	$config->optin_flag = $aList['optin_flag'];
}

if (isset($aList['optin_subject']) && $aList['optin_subject']) {
	$config->optin_subject = $aList['optin_subject'];
}

if (isset($aList['optin_text']) && $aList['optin_text']) {
	$config->optin_text = $aList['optin_text'];
}

// Empfänger aktivieren (Double-Opt-In?)
if ($_VARS['newsletter_action'] == "activate") {

	if ($_VARS['k'] != "") {

		$sQuery = "SELECT * FROM `newsletter2_activation` WHERE `code` = '".DB::escapeQueryString($_VARS['k'])."' AND UNIX_TIMESTAMP(`committed`) = 0";
		$aResult = DB::getQueryRow($sQuery);

		$i = count($aResult);

		if ($i > 0) {

			$sQuery = "UPDATE `newsletter2_activation` SET `committed` = NOW() WHERE `code` = '".DB::escapeQueryString($_VARS['k'])."'";
			$aData = DB::getQueryRow($sQuery);

			$sQuery = "UPDATE `newsletter2_recipients` SET `active` = 1 WHERE `id` = ".(int)$aData['id_recipient'];
			DB::executeQuery($sQuery);

			$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"activation_success");
			\System::wd()->executeHook('newsletter_subscribe_'.$element_data['content_id'], $aData['id_recipient']);
			\System::wd()->executeHook('newsletter_subscribe', $aData['id_recipient']);

		} else {

			$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "activation_failure");

		}

	}

}

// Empfänger deaktivieren (Double-Opt-Out?)
elseif ($_VARS['newsletter_action'] == "deactivate") {

	if ($_VARS['k'] != "") {

		$sQuery = "SELECT * FROM `newsletter2_activation` WHERE `code` = '".DB::escapeQueryString($_VARS['k'])."' AND UNIX_TIMESTAMP(`committed`) = 0";
		$aResults = DB::getQueryRows($sQuery);

		$i = count($aResults);
		if ($i > 0) {

			$sQuery = "UPDATE `newsletter2_activation` SET `committed` = NOW() WHERE `code` = '".DB::escapeQueryString($_VARS['k'])."'";
			$aData = DB::getQueryRow($sQuery);

			$sQuery = "UPDATE `newsletter2_recipients` SET `active` = 0 WHERE `id` = ".(int)$aData['id_recipient'];
			DB::executeQuery($sQuery);

			$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "deactivation_success");
			\System::wd()->executeHook('newsletter_unsubscribe_'.$element_data['content_id'], $aData['id_recipient']);
			\System::wd()->executeHook('newsletter_unsubscribe', $aData['id_recipient']);

		} else {

			$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "deactivation_failure");

		}

	}

}

// Empfänger austragen
elseif ($_VARS['newsletter_action'] == "remuser" && $_VARS['newsletter_recipient']) {

    $sQuery = "UPDATE `newsletter2_recipients` SET `active` = 0 WHERE `email` = '".DB::escapeQueryString($_VARS['newsletter_recipient'])."'";
	DB::executeQuery($sQuery);

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "unsubscribe");

}

// Anmeldung
elseif ($_VARS['newsletter_action'] == "save" && ($_VARS['newsletter_task'] == "subscribe" || !$_VARS['newsletter_task'])) {

    $buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "save");

    if (!Util::checkEmailMx($_VARS["ne_email"])) {

        $message = \Cms\Service\PageParser::checkForBlock($buffer, "wrongemail");
        $form = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");
        $buffer = str_replace("<#form#>", $form, $buffer);

    } elseif ($config->optin_terms_flag && $_VARS["newsletter_acceptterms"] != "1") {

        $message = \Cms\Service\PageParser::checkForBlock($buffer, "mustacceptterms");
        $form = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");
        $buffer = str_replace("<#form#>", $form, $buffer);

    } else {

        $aCheck = DB::getQueryRow("SELECT * FROM `newsletter2_recipients` WHERE `email` LIKE '".DB::escapeQueryString($_VARS['ne_email'])."' AND `idList` = ".(int)$config->newsletter_id);

        if (empty($aCheck) || $aCheck['active'] == 0) {

            if ($config->optin_flag) {
                $iActive = 0;	
            } else {
                $iActive = 1;
            }

            if (!empty($aCheck) && $aCheck['active'] == 0) {

                $aData = array(
                    'active' => (int)$iActive,
                    'name' => (string)$_VARS['ne_name'],
                    'firstname' => (string)$_VARS['ne_firstname'], 
                    'email' => (string)$_VARS['ne_email'], 
                    'sex' => (int)$_VARS['ne_sex'],
                    'title' => (int)$_VARS['ne_title'],
                    'terms_accepted' => null,
                    'terms_ip' => ''
                );
                DB::updateData('newsletter2_recipients', $aData, '`id` = '.(int)$aCheck['id']);
                $id = $aCheck['id'];

            } else {

                $aData = array(
                    'idList' => (int)$config->newsletter_id, 
                    'active' => (int)$iActive,
                    'name' => (string)$_VARS['ne_name'],
                    'firstname' => (string)$_VARS['ne_firstname'], 
                    'email' => (string)$_VARS['ne_email'], 
                    'sex' => (int)$_VARS['ne_sex'],
                    'title' => (int)$_VARS['ne_title'],
                    'terms_accepted' => null,
                    'terms_ip' => ''
                );
                DB::insertData('newsletter2_recipients', $aData);
                $id = DB::fetchInsertId();

            }

            if ($config->optin_terms_flag) {
                $sQuery = "
                    UPDATE
                        `newsletter2_recipients`
                    SET
                        `terms_accepted` = NOW(),
                        `terms_ip` = '".DB::escapeQueryString($_SERVER['REMOTE_ADDR'])."'
                    WHERE
                        `id` = ".(int)$id."
                ";
                DB::executeQuery($sQuery);
            }

            if (!$config->optin_flag) {
                \System::wd()->executeHook('newsletter_unsubscribe_'.$element_data['content_id'], $id);
                \System::wd()->executeHook('newsletter_unsubscribe', $id);
            }

            $message = \Cms\Service\PageParser::checkForBlock($buffer, "success");

            if ($config->optin_flag) {

                $sCode = \Util::generateRandomString(16);

                // Optin
                $sQuery = "INSERT INTO `newsletter2_activation` SET `id_recipient` = ".(int)$id.", `changed` = NOW(), `created` = NOW(), `committed` = 0, `code` = '".DB::escapeQueryString($sCode)."', `active` = 1";
                DB::executeQuery($sQuery);

                $strOptInSubject = $config->optin_subject;
                $iOptInTargetPageId = $config->optin_target;

                if ($iOptInTargetPageId > 0) {
                    $sTargetUrl = \Cms\Service\Smarty::getPageUrl($iOptInTargetPageId);
                } else {
                    $sTargetUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                }

                // Str Replace
                $strOptInText = $config->optin_text;
                $strOptInText = str_replace("<#firstname#>", $_VARS['ne_firstname'], $strOptInText);
                $strOptInText = str_replace("<#name#>", $_VARS['ne_name'], $strOptInText);
                $strOptInText = str_replace("<#lastname#>", $_VARS['ne_name'], $strOptInText);
                $strOptInText = str_replace("<#email#>", $_VARS['ne_email'], $strOptInText);
                $strOptInText = str_replace("<#nickname#>", $_VARS['ne_email'], $strOptInText);
                $strOptInText = str_replace("<#password#>", $_VARS['ne_email'], $strOptInText);
                $strOptInText = str_replace("<#link#>", $sTargetUrl."?newsletter_action=activate&k=".$sCode, $strOptInText);
                $strOptInText = str_replace("<#deactivation_link#>", $sTargetUrl."?newsletter_action=save&newsletter_task=unsubscribe&ne_email=".urlencode($_VARS['ne_email']), $strOptInText);

				$mail = new WDMail();
				$mail->subject = $strOptInSubject;
				$mail->text = $strOptInText;
                $mail->send([$_VARS['ne_email']]);

            }

        } else {

            $message = \Cms\Service\PageParser::checkForBlock($buffer, "exists");
            $form = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");
            $buffer = str_replace("<#form#>", $form, $buffer);

        }

    }

    $t = "ne_sex_".$_VARS["ne_sex"];
    $_VARS[$t] = ' selected="selected"';
    $t1 = "ne_title_".$_VARS["ne_title"];
    $_VARS[$t1] = ' selected="selected"';
    $buffer = \Cms\Service\PageParser::replaceBlock($buffer, "messages", $message);

}

// Abmeldung
elseif ($_VARS['newsletter_action'] == "save" && $_VARS['newsletter_task'] == "unsubscribe") {

    $buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],"save");
    $bCheckEmail = Util::checkEmailMx($_VARS["ne_email"]);

    if ($bCheckEmail) {

		$sQuery = "SELECT * FROM `newsletter2_recipients` WHERE `email` LIKE '".DB::escapeQueryString($_VARS['ne_email'])."' AND `idList` = ".(int)$config->newsletter_id." AND `active` = 1";
		if($config->optout_all) {
			$sQuery = "SELECT * FROM `newsletter2_recipients` WHERE `email` LIKE '".DB::escapeQueryString($_VARS['ne_email'])."' AND `active` = 1";
		}
		$aResults = DB::getQueryRows($sQuery);

		if(
			(
				!$config->optout_all &&
				count($aResults) == 1
			) || (
				$config->optout_all &&
				count($aResults) >= 1
			)
		) {

			foreach($aResults as $arrRecipient) {

				if(!$config->optout_flag) {

					$sQuery = "UPDATE `newsletter2_recipients` SET `active` = 0 WHERE `id` = ".(int)$arrRecipient['id']." LIMIT 1";
					DB::executeQuery($sQuery);
					\System::wd()->executeHook('newsletter_unsubscribe_'.$element_data['content_id'], $arrRecipient['id']);
					\System::wd()->executeHook('newsletter_unsubscribe', $arrRecipient['id']);

				} else {

					$sCode = \Util::generateRandomString(16);

					// Optin
					$sQuery = "INSERT INTO `newsletter2_activation` SET `id_recipient` = ".(int)$arrRecipient['id'].", `changed` = NOW(), `created` = NOW(), `committed` = 0, `code` = '".DB::escapeQueryString($sCode)."', `active` = 1";
					DB::executeQuery($sQuery);

					$strOptOutSubject = $config->optout_subject;

					// Str Replace
					$strOptOutText = $config->optout_text;
					$strOptOutText = str_replace("<#firstname#>", $arrRecipient['firstname'], $strOptOutText);
					$strOptOutText = str_replace("<#name#>", $arrRecipient['name'], $strOptOutText);
					$strOptOutText = str_replace("<#lastname#>", $arrRecipient['name'], $strOptOutText);
					$strOptOutText = str_replace("<#link#>", "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?newsletter_action=deactivate&k=".$sCode, $strOptOutText);

					$mail = new WDMail();
					$mail->subject = $strOptOutSubject;
					$mail->text = $strOptOutText;
					$mail->send([$arrRecipient['email']]);
					
				}

			}

			if(!$config->optout_flag) {
				$message = \Cms\Service\PageParser::checkForBlock($buffer, "deactivation_success");
			} else {
				$message = \Cms\Service\PageParser::checkForBlock($buffer, "success_deactivation");
			}

        } else {

            $message = \Cms\Service\PageParser::checkForBlock($buffer,"existsnot");
            $form = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");
            $buffer = str_replace("<#form#>", $form, $buffer);

        }

    } else {

        $message = \Cms\Service\PageParser::checkForBlock($buffer, "wrongemail");
        $form = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");
        $buffer = str_replace("<#form#>", $form, $buffer);

    }

    $t = "ne_sex_".$_VARS["ne_sex"];
    $_VARS[$t] = ' selected="selected"';
    $t1 = "ne_title_".$_VARS["ne_title"];
    $_VARS[$t1] = ' selected="selected"';
    $buffer = \Cms\Service\PageParser::replaceBlock($buffer, "messages", $message);

}

// Standardmäßig die Anmeldemaske anzeigen
else {

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "subscribe");

}

// Checkbox zum akzeptieren der Bedingungen einfügen wenn Option aktiviert
// (ansonsten wird der Tag mit einem leeren String ersetzt)
$sAcceptTerms = '';
if ($config->optin_terms_flag) {
    $sAcceptTerms = \Cms\Service\PageParser::checkForBlock($element_data['content'], "acceptterms");
}
$buffer = str_replace("<#insert_acceptterms#>", $sAcceptTerms, $buffer);

// Variablen ersetzen
$pos = 0;
while ($pos = strpos($buffer, '<#', $pos)) {
	$end = strpos($buffer, '#>', $pos);
	$var = substr($buffer, $pos+2, $end-$pos-2);
	$buffer = substr($buffer, 0, $pos).$_VARS[$var].substr($buffer, $end+2);
}

echo $buffer;