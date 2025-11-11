<?php

include_once(dirname(__FILE__).'/../../system/legacy/admin/extensions/form.inc.html');
include_once(\Util::getDocumentRoot()."system/includes/functions.inc.php");

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$sQuery = "
	SELECT
		*
	FROM
		`form_init`
	WHERE
		`id` = '".\DB::escapeQueryString($config->form_id)."'
";
$aInit = DB::getQueryRow($sQuery);

if (
	$_VARS['fo_action'] == 'send' || 
	$_VARS['fo_action_'.$element_data['content_id']] == 'send'
) {

	$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'], "confirm");
	$buffer = str_replace("<#message#>", $aInit['message_success'], $buffer);
	if (strpos($buffer, "<#persist#>")) {
		$buffer = str_replace("<#persist#>", "", $buffer);
		$_SESSION['form_persistance_'.$config->form_id] = "persist";
	}

	$sQuery = "
		SELECT
			*
		FROM
			`form_options`
		WHERE
			`form_id` = '".\DB::escapeQueryString($aInit['id'])."' AND
			`active` = 1
		ORDER BY
			`position`
	";
	$aOptions = DB::getQueryRows($sQuery);

	$sLineBreak = "\n";
	if ($aInit['html']) {
		$sLineBreak = "<br>";
	}
	
	$text = "";
	
	// Standardtext
	if (!$aInit['text']) {
		if ($aInit['html']) {
			$text .= '<style>body, p, th, td {font-family: sans-serif; text-align: left; } th {background-color: #dedede;}</style>';	
		}
		$text .= "Folgende Daten wurden uebermittelt von ".$_SERVER['REMOTE_ADDR']." am ".strftime("%x %X", time()).":".$sLineBreak.$sLineBreak;
		if ($aInit['html']) {
			$text .= '<table>';	
		}
	} else {
		$text .= $aInit['text'];
	}

	$errors = array();

	// Array für die einzelnen Felder.
	$aFields       = array();
	$aFiles        = array();
	$aAllocations  = array();
	$sConfirmEmail = false;

	foreach($aOptions as $my_val) {

		$i = $my_val['id'];

		$oOption = \Form\Entity\Option::getInstance($my_val['id']);
		
		$aDisplayConditions = $oOption->display_conditions;
		
		if(!empty($aDisplayConditions)) {

			foreach($aDisplayConditions as $aDisplayCondition) {

				$strConditionValue = (array)$_VARS['option_'.$aDisplayCondition['field']];
				
				if (
					(string)$strConditionValue != (string)$aDisplayCondition['value']
				) {
					$my_val['check'] = false;
					$my_val['validation'] = false;	
				}

			}
		}

		// Wert muss geprüft werden.
		if ($my_val['check'] == 1) {
			if (is_array($_VARS["option_$i"])) {
				$sCheck = implode('', $_VARS["option_$i"]);
			} else {
				$sCheck = $_VARS["option_$i"];
			}
			
			if (empty($sCheck)) {
				$errors[$my_val['id']] = 1;
			}
		}
		if ($my_val['validation'] == "date") {
			if (!empty($_VARS["option_$i"]) && !strtotimestamp($_VARS["option_$i"])) {
				$errors[$my_val['id']] = "date";
            }
		}
		if ($my_val['validation'] == "plz") {
			if (!empty($_VARS["option_$i"]) && !preg_match("/^([0-9]{4,5})$/i", $_VARS["option_$i"])) {
				$errors[$my_val['id']] = "plz";
            }
		}
		if ($my_val['validation'] == "email") {
			if (!empty($_VARS["option_$i"]) && !checkEmailMx($_VARS["option_$i"])) {
				$errors[$my_val['id']] = "email";
            }
		}
		if ($my_val['validation'] == "numbers") {
			if (!empty($_VARS["option_$i"]) && !preg_match("/^\s*[0-9]+\s*[.,]*\s*[0-9]*\s*$/",$_VARS["option_$i"])) {
				$errors[$my_val['id']] = "numbers";
            }
		}
		if ($my_val['validation'] == "currency") {
			if (!empty($_VARS["option_$i"]) && !preg_match("/^\s*[0-9]+\s*[.,]*\s*[0-9]*\s*[a-z€$]{0,3}$/i",$_VARS["option_$i"])) {
				$errors[$my_val['id']] = "currency";
            }
		}

		if ($my_val['allocation']) {
			if (is_array($_VARS["option_".$my_val['id']])) {
				$aAllocations[$my_val['allocation']] = $_VARS["option_".$my_val['id']][0];
            } else {
				$aAllocations[$my_val['allocation']] = $_VARS["option_".$my_val['id']];
            }
		}
		if ($_VARS["option_$i"]) {
			// Wenn Dateiupload.
			if (is_array($_VARS["option_$i"]) && $_VARS["option_$i"]['tmp_name']) {
				$strName = strtolower($_VARS["option_$i"]['name']);
				$intPos = strrpos($strName, ".");
				$strExt = substr($strName, $intPos);
				$strName = \Util::getCleanFileName(substr($strName, 0, $intPos));
				$value = $strName.$strExt;
				$aFiles["option_$i"] = $value;
			} elseif (is_array($_VARS["option_$i"])) {
				$value = "";
				foreach ($_VARS["option_$i"] as $elem) {
					$value .= ', '.strip_tags($elem);
				}
				$value = substr($value,2 );
			} else {
				$value = strip_tags($_VARS["option_$i"]);
			}

			if (!$aInit['text']) {
				if ($aInit['html']) {
					$text .= '<tr><th>'.$my_val["name"]."</th><td>".$value."</td></tr>";	
				} else {
					$text .= $my_val["name"].": ".$value."\n\n";
				}
			}

			$aFields['field_'.$i] = $value;

		} else {

			if (
				$my_val['type'] != 'onlytext' &&
				$my_val['type'] != 'onlytitle'
			) {

				if (!$aInit['text']) {
					if ($aInit['html']) {
						$text .= '<tr><th>'.$my_val["name"]."</th><td>keine Angabe</td></tr>";	
					} else {
						$text .= $my_val["name"].": keine Angabe\n\n";
					}
				}				
				$aFields['field_'.$i] = "keine Angabe";

			} else {
				if (!$aInit['text']) {
					if ($aInit['html']) {
						$text .= '<tr><th colspan="2">'.$my_val["name"]."</th></tr>";	
					} else {
						$text .= $my_val["name"]."\n\n";
					}
				}	
			}
		}

	}
	
	if (!$aInit['text']) {
		if($aInit['html']) {
			$text .= '</table>';	
		}
	}

	$id = $aInit['id'];

	if (count($errors) == 0) {

		// Formular erfolgreich bearbeitet.
		if ($_SESSION['form_persistance_'.$config->form_id] == "persist") {
			$_SESSION['form_complete_'.$config->form_id] = "done";	
		}

		// Bestätigungsemail an Absender.
		if ($aAllocations['email'] && $aInit['confirm']) {
			$sConfirmEmail = $aAllocations['email'];
		}

		// Optionales Newsletter eintragen.
		$aNewsletter = array();
		if ($aAllocations['newsletter'] > 0) {
			$aNewsletter = $aAllocations;
			if ($aAllocations['sex'] == "Herr" || $aAllocations['sex'] == "Mr" || $aAllocations['sex'] == "Mr") {
				$aNewsletter['sex'] = 1;
			} elseif ($aAllocations['sex'] == "Frau" || $aAllocations['sex'] == "Mrs" || $aAllocations['sex'] == "Mrs") {
				$aNewsletter['sex'] = 2;
			} else {
				$aNewsletter['sex'] = 0;
			}
			if (preg_match("/^([a-z0-9\._-]*)@([a-z0-9\.-]{2,66})\.([a-z]{2,6})$/i", $aNewsletter['email'])) {
				$sQuery = "
					SELECT
						*
					FROM
						`newsletter2_recipients`
					WHERE
						`email` LIKE '".\DB::escapeQueryString($aNewsletter['email'])."' AND
						`idList` = '".\DB::escapeQueryString($aNewsletter['newsletter'])."'
				";
				$aRecipients = DB::getQueryRows($sQuery);
				if (empty($aRecipients)) {
					$sQuery = "
						INSERT INTO
							`newsletter2_recipients`
						SET
							`idList` = '".\DB::escapeQueryString($aNewsletter['newsletter'])."',
							`sex` = '".\DB::escapeQueryString($aNewsletter['sex'])."',
							`name` = '".\DB::escapeQueryString($aNewsletter['name'])."',
							`firstname` = '".\DB::escapeQueryString($aNewsletter['firstname'])."',
							`email` = '".\DB::escapeQueryString($aNewsletter['email'])."',
							`active` = 1
					";
					DB::executeQuery($sQuery);
				}
			}
		}

		// Bestätigungsemail an Absender nach Bedarf mit Platzhaltern.
		if (
			$sConfirmEmail &&
			preg_match("/^([a-z0-9\._-]*)@([a-z0-9\.-]{2,66})\.([a-z]{2,6})$/i", $sConfirmEmail)
		) {
			/*
			 * iterates every field
			 */
			$aEmailFields = array();
			foreach ($aOptions as $aFieldNames) {
				$aInit['cmail_title']	= str_replace("<#field_option_".$aFieldNames['id']."#>", $aFields['field_'.$aFieldNames['id']], $aInit['cmail_title']);
				$aInit['cmail_text']	= str_replace("<#field_option_".$aFieldNames['id']."#>", $aFields['field_'.$aFieldNames['id']], $aInit['cmail_text']);
				if (
					$aFieldNames['type'] != "onlytext" &&
					$aFieldNames['type'] != "onlytitle"
				) {
					$aEmailFields[$aFieldNames['name']] = $aFields['field_'.$aFieldNames['id']];
				}
			}
			$buffer_email = \Cms\Service\PageParser::checkForBlock($aInit['cmail_text'], "fields");
			if ($buffer_email) {
				foreach ($aEmailFields as $key => $val) {
					$buffer_email_loop = $buffer_email;
					$buffer_email_loop = str_replace("<#field_name#>", $key, $buffer_email_loop);
					$buffer_email_loop = str_replace("<#field_data#>", $val, $buffer_email_loop);
					$buffer_email_output .= $buffer_email_loop;
				}
				$aInit['cmail_text'] = \Cms\Service\PageParser::replaceBlock($aInit['cmail_text'], "fields", $buffer_email_output);
			}
			// Ersetzen der zugeordneten Felder.
			foreach ($aAllocations as $k => $v) {
				$aInit['cmail_title'] = str_replace("<#field_".$k."#>", $v, $aInit['cmail_title']);
				$aInit['cmail_text'] = str_replace("<#field_".$k."#>", $v, $aInit['cmail_text']);
			}
			wdmail(
				$sConfirmEmail, 
				$aInit['cmail_title'], 
				$aInit['cmail_text'], 
				$system_data['mail_from']
			);
		}

		// Eintrag der Daten in die Datenbank.
		$aInsert = [];
		$aInsert['date'] = date('Y-m-d H:i:s');
		$aInsert['ip'] = $_SERVER['REMOTE_ADDR'];
		$aInsert['data'] = $text;

		foreach ($aFields as $id => $value) {
			$aInsert[$id] = $value;
		}

		$intId = DB::insertData('form_data_'.(int)$aInit['id'], $aInsert);

		// Dateien speichern, wenn das Verzeichnis noch nicht besteht.
		$path = dirname(__FILE__).'/form';
		if (!is_dir($path)) {
			@mkdir($path, $system_data['chmod_mode_dir']);
		}
		$arrAttachments = array();
		foreach ($aFiles as $k => $strName) {
			$strTarget = $path.'/'.$aInit['id'].'_'.$intId.'_'.$strName;
			@copy($_FILES[$k]['tmp_name'], $strTarget);
			@chmod($strTarget, $system_data['chmod_mode_file']);
			$arrAttachments[$strTarget] = $strName;
		}
		
		$buffer_admin = \Cms\Service\PageParser::checkForBlock($aInit['text'], "fields");
		if ($aInit['text'] && $buffer_admin) {
			$text = "";
			$aEmailFields = array();
			foreach($aOptions as $aFieldNames) {
				if ($aFieldNames['type'] != "onlytext" && $aFieldNames['type'] != "onlytitle") {
					$aEmailFields[$aFieldNames['name']] = $aFields['field_'.$aFieldNames['id']];
				}
			}
			foreach ($aEmailFields as $key => $val) {
				$buffer_admin_loop = $buffer_admin;
				$buffer_admin_loop = str_replace("<#field_name#>", $key, $buffer_admin_loop);
				$buffer_admin_loop = str_replace("<#field_data#>", $val, $buffer_admin_loop);
				$buffer_admin_output .= $buffer_admin_loop;
			}
			$text .= \Cms\Service\PageParser::replaceBlock($aInit['text'], "fields", $buffer_admin_output);
		}

		// Mail an den Admin
		if (isset($_SESSION['referer']) && !$aInit['text']) {
			$text .= "--".$sLineBreak.$sLineBreak."Referer: ".$_SESSION['referer'];
		}

		if ($aInit['text']) {
			foreach ($aOptions as $aFieldNames) {
				$text = str_replace("<#field_option_".$aFieldNames['id']."#>", $aFields['field_'.$aFieldNames['id']], $text);
			}
		}

		$text = str_replace("<#field_ip#>", $_SERVER['REMOTE_ADDR'], $text);

		$text = preg_replace("/\r?\n/","\n", $text);

		// Wenn eine E-Mail gesetzt ist, soll diese als Absender angegeben werden.
		$arrEmail = array();
		if ($aAllocations['email']) {
			if ($aInit['allocate']) {	
				$arrEmail['from'] = $aAllocations['email'];
			} else {
				$arrEmail['replyto'] 	= $aAllocations['email'];
				$arrEmail['returnpath']	= $aAllocations['email'];
				$arrEmail['from'] 		= $system_data['project_name']." <".$system_data['admin_email'].">";
			}
		} else {
			$arrEmail['replyto'] 	= $system_data['admin_email'];
			$arrEmail['returnpath']	= $system_data['admin_email'];
			$arrEmail['from']		= $system_data['project_name']." <".$system_data['admin_email'].">";
		}

		// only send mail if address is set
		if ($aInit["email"]) {
			
			$oWDMail = new WDMail();
			
			if ($aInit['cc']) {
				$oWDMail->cc = $aInit['cc'];
			}
			if ($aInit['bcc']) {
				$oWDMail->bcc = $aInit['bcc'];
			}

			$oWDMail->subject = $aInit["subject"];
			
			if ($aInit['html']) {
				$oWDMail->html = $text;
			} else {
				$oWDMail->text = $text;
			}

			$oWDMail->attachments = $arrAttachments;
			$oWDMail->from = $arrEmail['from'];
			$oWDMail->replyto = $arrEmail['replyto'];
			$oWDMail->returnpath = $arrEmail['returnpath'];

			$bResult = $oWDMail->send($aInit["email"]);
			
		}

		/*
		* set hook if not in edit mode
		*/
		if ($session_data['public']) {
			$arrTransfer = array($intId, $aAllocations, $aEmailFields, $aInit, $config, $arrEmail);
			\System::wd()->executeHook('form_'.$element_data['content_id'], $arrTransfer);
			unset($arrTransfer);
		}

		// not longer used
		unset($arrEmail);

		// Anzeigen der eingegebenen Daten auf der Bestaetigungsseite
		$buffer_fields = \Cms\Service\PageParser::checkForBlock($element_data['content'], "fields");
		$buffer_onlytext = \Cms\Service\PageParser::checkForBlock($element_data['content'], "onlytext");
		$buffer_onlytitle = \Cms\Service\PageParser::checkForBlock($element_data['content'], "onlytitle");
		if ($buffer_fields) {
			$aShowFields = array();
			foreach($aOptions as $aFieldNames) {
				$aShowFields[$aFieldNames['name']] = $aFields['field_'.$aFieldNames['id']];
				$aFieldTypes[$aFieldNames['name']] = $aFieldNames['type'];
			}
			foreach ($aShowFields as $key => $val) {
				if ($aFieldTypes[$key] == "onlytext") {
					$buffer_fields_loop = $buffer_onlytext;
					$buffer_fields_loop = str_replace("<#title#>", $key, $buffer_fields_loop);
				} elseif ($aFieldTypes[$key] == "onlytitle") {
					$buffer_fields_loop = $buffer_onlytitle;
					$buffer_fields_loop = str_replace("<#title#>", $key, $buffer_fields_loop);
				} else {
					$buffer_fields_loop = $buffer_fields;
					$buffer_fields_loop = str_replace("<#field_name#>", $key, $buffer_fields_loop);
					$buffer_fields_loop = str_replace("<#field_data#>", $val, $buffer_fields_loop);
				}
				$buffer_fields_output .= $buffer_fields_loop;
			}
			$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "fields", $buffer_fields_output);
		}

		// Buffer aufheben, um ihn wiederholen zu können.
		if ($_SESSION['form_persistance_'.$config->form_id] == "persist") {
			$_SESSION['form_data_'.$config->form_id] = $buffer;
		}
	} else {
		$_VARS['fo_action'] = "";
		$_VARS['fo_action_'.$element_data['content_id']] = "";
	}
}

if (($_VARS['fo_action'] != "send") && ($_VARS['fo_action_'.$element_data['content_id']] != "send")) {

	if (
		($_SESSION['form_complete_'.$config->form_id] != "done") || 
		($_SESSION['form_persistance_'.$config->form_id] != "persist")
	) {

		// Felder mit display_condition rausfinden.
		$aConditions = array();
		$aFieldOptions = array();

		$sSql = "
				SELECT 
					id
				FROM 
					form_options 
				WHERE 
					form_id = ".(int)$config->form_id." AND 
					active = 1
				";
		$aFormOptions = (array)DB::getQueryRows($sSql);

		foreach($aFormOptions as $arrData) {

			$oOption = \Form\Entity\Option::getInstance($arrData['id']);

			$aDisplayConditions = $oOption->display_conditions;

			if (!empty($aDisplayConditions)) {
				
				foreach($aDisplayConditions as $aDisplayCondition) {
					if(empty($aDisplayCondition['value'])) {
						$aDisplayCondition['value'] = true;
					}
					$aConditions[$arrData['id']][$aDisplayCondition['field']][] = $aDisplayCondition['value'];
				}
				
			}
			
		}

		$buffer = \Cms\Service\PageParser::checkForBlock($element_data['content'],'form');

		if (count($errors) > 0) {
			$buffer = str_replace("<#message#>",$aInit['message_failed'],$buffer);
		}
		$buffer = str_replace("<#fo_action#>","<input type=\"hidden\" name=\"fo_action_".$element_data['content_id']."\" value=\"send\">",$buffer);

		$elem_code['error_numbers'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error_numbers");
		$elem_code['error_email'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error_email");
		$elem_code['error_plz'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error_plz");
		$elem_code['error_date'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error_date");
		$elem_code['error_currency'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error_currency");
		$elem_code['error'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "error");
		$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "text");
		$elem_code['onlytext'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "onlytext");
		$elem_code['file'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "file");
		$elem_code['onlytitle'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "onlytitle");
		$elem_code['smalltext'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "smalltext");
        $elem_code['infotext'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "infotext");
		$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "select");
		$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "textarea");
		$elem_code['checkbox'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "checkbox");
		$elem_code['radio'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "radio");
		$elem_code['hidden'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "hidden");
		$elem_code['reference'] = \Cms\Service\PageParser::checkForBlock($element_data['content'], "reference");

		$sQuery = "
			SELECT
				*,
				`form_options`.`id` `option_id`
			FROM
				`form_init`,
				`form_options`
			WHERE
				`form_init`.`id` = `form_options`.`form_id` AND
				`form_init`.`id` = '".\DB::escapeQueryString($config->form_id)."' AND
				`form_options`.`active` = 1
			ORDER BY
				`position`
		";
		$res_init = (array)DB::getQueryRows($sQuery);

		$temp_buffer = "";

		foreach($res_init as $my_init) {

			$aOptions = array();

			$oOption = \Form\Entity\Option::getInstance($my_init['option_id']);

			$aDisplayConditions = $oOption->display_conditions;

			$sEventType = 'click';
			
			$i = $my_init['id'];
			$s_buffer = $elem_code[$my_init["type"]];
			$bInitval = 0;
			if ($_GET["option_value_$i"]) {
				$val = strip_tags($_GET["option_value_$i"]);
			} elseif ($_VARS["option_$i"]) {
				$val = $_VARS["option_$i"];
				if (is_array($val)) {
					$val = $val[0];
				}
				$val = strip_tags($val);
			} else {
				$val = $my_init["value"];
				$bInitval = 1;
			}
 
            $sInputName = 'option_'.$i;

			$val_unconverted = $val;
			$val = \Util::convertHtmlEntities($val);

			if ($my_init['check'] == 1) {

				$my_init['checksign'] = ' <span class="red"><sup>*</sup></span>';
				$my_init['checksign_clean'] = " <span class=\"checksign\">*</span>";
				$jscode .= " && checkchanged(on,'option_$i')";

				// replace if is required field
				$s_buffer = str_replace("<#if::check::start#>", '', $s_buffer);
				$s_buffer = str_replace("<#if::check::end#>", '', $s_buffer);

			} else {

				$iCheck = 0;
				// search and cut off not needed required fields
				while (strpos($s_buffer, "<#if::check::start#>") !== false) {
					// cut off, if not required field
					$iStartOpen = strpos($s_buffer, "<#if::check::start#>");

					$iStartClose = strpos($s_buffer, "<#if::check::end#>");
					$iEndClose = $iStartClose + 18;

					$s_buffer = substr($s_buffer, 0, $iStartOpen) . substr($s_buffer, $iEndClose);

					// break if error
					$iCheck++;
					if ($iCheck >= 200) {
						break;
					}
				}

			}

			$s_buffer = str_replace("<#value_unconverted#>", $val_unconverted, $s_buffer);
			$s_buffer = str_replace("<#value#>", $val, $s_buffer);
			$s_buffer = str_replace("<#title#>", $my_init["name"].$my_init['checksign'], $s_buffer);
			$s_buffer = str_replace("<#title_clean#>", $my_init["name"].$my_init['checksign_clean'], $s_buffer);
			$s_buffer = str_replace("<#option#>", $my_init["options"], $s_buffer);
			$s_buffer = str_replace("<#id#>", $sInputName, $s_buffer);

			if ($errors[$my_init['id']] == 1) {
				$temp_buffer .= $elem_code['error'];
			}
			if ($errors[$my_init['id']] == "plz") {
				$temp_buffer .= $elem_code['error_plz'];
			}
			if ($errors[$my_init['id']] == "date") {
				$temp_buffer .= $elem_code['error_date'];
			}
			if ($errors[$my_init['id']] == "numbers") {
				$temp_buffer .= $elem_code['error_numbers'];
			}
			if ($errors[$my_init['id']] == "email") {
				$temp_buffer .= $elem_code['error_email'];
			}
			if ($errors[$my_init['id']] == "currency") {
				$temp_buffer .= $elem_code['error_currency'];
			}
			if ($my_init["type"] == "select") {
                $sInputName .= '[]';
				$sEventType = 'change';
				$s_buffer = str_replace("<#name#>", $sInputName, $s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer, "optionlist");
				$temp = "";
				$aOptions = explode(",", $my_init["value"]);
				foreach($aOptions as &$elem) {
					$elem = trim($elem);
					$sRow = $t_buffer;
					$temp .= str_replace("<#option_value#>", $elem, str_replace("<#select#>", (($elem==$val) ? "selected=\"selected\"" : ""), $sRow));
				}
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer, "optionlist", $temp);
			} elseif ($my_init["type"] == "reference") {
				$arrAdditional = \Util::decodeSerializeOrJson($my_init['additional']);
                $sInputName .= '[]';
				$s_buffer = str_replace("<#name#>", $sInputName, $s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer, "optionlist");
				$temp = "";
				$arrItems = array();
				$strQuery = "SELECT * FROM `".$arrAdditional['db_table']."` ".$arrAdditional['db_query']."";
		    	$resValues = DB::getQueryRows($strQuery);
		        foreach($resValues as $arrValues) {
					$aReferenceFields = explode(',', $arrAdditional['db_field']);
					$aReferenceValue = array();
					foreach ($aReferenceFields as $sReferenceField) {
						$aReferenceValue[] = $arrValues[trim($sReferenceField)];
					}
		            $arrItems[] = implode(' ', $aReferenceValue);
		        }
				foreach ($arrItems as $strValue) {
					$sRow = $t_buffer;
					$temp .= str_replace("<#option_value#>", $strValue, str_replace("<#select#>",(($strValue == $val)?"selected=\"selected\"":""),$sRow));
				}
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer, "optionlist", $temp);
			} elseif ($my_init["type"] == "checkbox") {
				$sEventType = 'click';
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer, "optionlist");
				// Wenn wir einen Optionlist-Block finden sollen mehrere Checkboxen
				// angezeigt und als Array abgesendet werden...
				if ($t_buffer != '') {
                    $sInputName .= '[]';
					// Variable in den wir den gesamten Blockcode schreiben.
					$sTemp = '';
					// Den String in "Vorbelegung" anhand der Kommas trennen und für jeden
					// der Werte einmal den Optionlist-Block einfügen.
					$aOptions = explode(",", $my_init['value']);
					foreach ($aOptions as &$sCurOption) {
						// Eingabewert passend formatieren und Buffer kopieren.
						$sCurOption = trim($sCurOption);
						$sCurBuffer = $t_buffer;
						// Ersetzten des Optionswertes und des Namens.
						$sCurBuffer = str_replace('<#option_value#>', $sCurOption, $sCurBuffer);
						$sCurBuffer = str_replace('<#name#>', $sInputName, $sCurBuffer);
						// Aktivieren der Checkbox wenn nötig.
						if (is_array($val)) {
							$bOptionSelected = false;
							foreach ($val as $sCurVal) {
								if ($sCurVal == $sCurOption) {
									$bOptionSelected = true;
									break;
								}
							}
							if ($bOptionSelected == true) {
								$sCurBuffer = str_replace('<#checked#>', 'checked="checked"', $sCurBuffer);
							} else {
								$sCurBuffer = str_replace('<#checked#>', '', $sCurBuffer);
							}
						}
						// Ausgabe des aktuellen Schleifendurchlaufs in die Ausgabe schreiben. 
						$sTemp .= $sCurBuffer;
					}
					// Den Block durch den generierten Inhalt ersetzen.
					$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer, 'optionlist', $sTemp);
				}
				// ...ansonsten zeigen wir einfach nur eine Checkbox an, dies ist
				// die alte Standard-Vorgehensweise und somit abwärtskompatibel.
				else {
					// Wenn Feld nicht leer, dann Checkbox aktivieren
					if ($val != '' && !$bInitval) {
						$s_buffer = str_replace('<#checked#>', 'checked="checked"', $s_buffer);
					} else {
						$s_buffer = str_replace('<#checked#>', '', $s_buffer);
					}
					$s_buffer = str_replace('<#name#>', $sInputName, $s_buffer);
				}
			} elseif ($my_init["type"] == "radio") {
				$sEventType = 'click';
				$s_buffer = str_replace("<#name#>", $sInputName, $s_buffer);
				$t_buffer = \Cms\Service\PageParser::checkForBlock($s_buffer, "optionlist");
				$sTemp = "";
				$aOptions = explode(",", $my_init['value']);
				$aMultiOptions = explode(",", $my_init['options']);
				for ($iCounter = 0; $iCounter < count($aOptions); $iCounter++) {
					$elem = trim($aOptions[$iCounter]);
					$elem = \Util::convertHtmlEntities($elem);
					$sMultiOption = $aMultiOptions[$iCounter];
					if ($elem == $val) {
						$sRow = str_replace("<#option_checked#>", "checked=\"checked\"", $t_buffer);
					} else {
						$sRow = str_replace("<#option_checked#>", "", $t_buffer);
					}
					$sRow = str_replace("<#option_value#>", $elem, $sRow);
					$sRow = str_replace("<#multi_option#>", $sMultiOption, $sRow);
					$sTemp .= $sRow;
				}
				$s_buffer = \Cms\Service\PageParser::replaceBlock($s_buffer, "optionlist", $sTemp);
			} else {
				$s_buffer = str_replace("<#name#>", $sInputName, $s_buffer);
			}

			if (!empty($aDisplayConditions)) {

				$bShow = true;

				if (!empty($aDisplayConditions)) {
					foreach($aDisplayConditions as $aDisplayCondition) {
						$strConditionValue = (array)$_VARS['option_'.$aDisplayCondition['field']];
						if (
							(string)$strConditionValue != (string)$aDisplayCondition['value']
						) {
							$bShow = false;
						}
					}
				}

				if ($bShow === false) {
					$s_buffer = str_replace("<#display_condition_check#>","id=\"field_".$config->form_id."_".$i."\" style=\"display:none;\"",$s_buffer);
				} else {
					$s_buffer = str_replace("<#display_condition_check#>","id=\"field_".$config->form_id."_".$i."\"",$s_buffer);
				}

			} else {
				$s_buffer = str_replace("<#display_condition_check#>", "id=\"field_".$config->form_id."_".$i."\"", $s_buffer);
			}

			$aFieldData[$i] = array(
				'type' => $my_init["type"],
                'name' => $sInputName
			);
			if (!empty($aOptions)) {
				$aFieldData[$i]['options'] = $aOptions;
			}

			$strAction = getFormFieldConditionAction($my_init["type"], $my_init["updateaction"]);
			$s_buffer = str_replace("<#display_condition_action#>", $strAction, $s_buffer);

            $sInfoText = '';
            if (strlen(trim($my_init["infotext"])) > 0) {
                $sInfoText = $elem_code['infotext'];
                $sInfoText = str_replace("<#text#>", $my_init["infotext"], $sInfoText);
            }
            $s_buffer = str_replace("<#info#>", $sInfoText, $s_buffer);

			$temp_buffer .= $s_buffer;

		}

		$temp_buffer .= getFormConditionJavaScript($aConditions, $aFieldData, $config->form_id);

		$buffer = str_replace("<#elements#>", $temp_buffer, $buffer);
		$jscode = substr($jscode,4);

		if (1 || $my_init["checklang"] == "js") {
	?>

	<script type="text/javascript">
	<!--
	function checkchanged(on,element) {
		var obj = eval("on."+element);
		if(obj.length && !obj.type) {
			for(i=0;i<obj.length;i++) {
				if(obj[i].checked) {
					return true;
				}
			}
		} else {
			if(obj.type == "text") {
				if(obj.value.length != 0) {
					return true;
				} else {
					return false;
				}
			} else {
				if(obj.selectedIndex != 0) {
					return true;
				} else {
					return false;
				}
			}
		}
	}

	function checkform(on) {
		if(<?=$jscode?>) {
			return true;
		} else {
			alert('<?=L10N::t("Sie haben nicht alle Pflichtfelder ausgefüllt!")?>');
			return false;
		}
	}

	//-->
	</script>

	<?php
		} else {
	?>

	<script type="text/javascript">
	<!--
	function checkform(on) {
		return true;
	}

	//-->
	</script>

	<?php
		}
	}
	else
	{
		$buffer = $_SESSION['form_data_'.$config->form_id];
	}

}


$buffer = str_replace("<#PHP_SELF#>", \Util::convertHtmlEntities($_SERVER['PHP_SELF']), $buffer);

$pos = 0;
while ($pos = strpos($buffer, '<#', $pos)) {
	$end = strpos($buffer, '#>', $pos);
	$var = substr($buffer, $pos+2, $end-$pos-2);
	$buffer = substr($buffer, 0, $pos).$$var.substr($buffer, $end+2);
}

echo $buffer;

