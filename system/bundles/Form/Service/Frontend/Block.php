<?php

namespace Form\Service\Frontend;

class Block extends \Form\Service\Frontend {
	
	public function parse() {

		
		$buffer = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "confirm");
		$buffer = str_replace("<#message#>", $this->oForm->message_success, $buffer);
		if (strpos($buffer, "<#persist#>")) {
			$buffer = str_replace("<#persist#>", "", $buffer);
			$_SESSION['form_persistance_'. $this->oForm->id] = "persist";
		}

		// Anzeigen der eingegebenen Daten auf der Bestaetigungsseite
		$buffer_fields = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "fields");
		$buffer_onlytext = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "onlytext");
		$buffer_onlytitle = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "onlytitle");
		if ($buffer_fields) {

			$aOptions = $this->oForm->getJoinedObjectChilds('options');

			$aShowFields = array();
			foreach($aOptions as $oOption) {
				if(!isset($this->aFieldValues[$oOption->id])) {
					continue;
				}
				
				$aShowFields[$oOption->name] = $this->aFieldValues[$oOption->id];
				$aFieldTypes[$oOption->name] = $oOption->type;
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
				
		if(!$this->bSuccess) {

			// Felder mit display_condition rausfinden.
			$aConditions = array();
			$aFieldOptions = array();
			$aFields = $this->getFields();
			$aFieldProxies = $this->getFieldProxies();

			$buffer = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'],'form');

			if (count($this->aErrors) > 0) {
				$buffer = str_replace("<#message#>",$this->oForm->message_failed, $buffer);
			}
			$buffer = str_replace("<#fo_action#>","<input type=\"hidden\" name=\"fo_action_".$this->aElementData['content_id']."\" value=\"send\">",$buffer);

			$elem_code['error_numbers'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error_numbers");
			$elem_code['error_email'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error_email");
			$elem_code['error_plz'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error_plz");
			$elem_code['error_date'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error_date");
			$elem_code['error_currency'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error_currency");
			$elem_code['error'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "error");
			$elem_code['text'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "text");
			$elem_code['onlytext'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "onlytext");
			$elem_code['file'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "file");
			$elem_code['onlytitle'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "onlytitle");
			$elem_code['smalltext'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "smalltext");
			$elem_code['infotext'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "infotext");
			$elem_code['select'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "select");
			$elem_code['textarea'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "textarea");
			$elem_code['checkbox'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "checkbox");
			$elem_code['radio'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "radio");
			$elem_code['hidden'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "hidden");
			$elem_code['reference'] = \Cms\Service\PageParser::checkForBlock($this->aElementData['content'], "reference");

			$temp_buffer = "";
			
			$errors = $this->aErrors;
			
			foreach($aFields as $oField) {

				$aOptions = array();

				$my_init = $oField->getData();

				$val = null;
				if(isset($aFieldProxies[$oField->id])) {
					$oFieldProxy = $aFieldProxies[$oField->id];
					$val = $oFieldProxy->getValue();
				}

				$aDisplayConditions = $oField->display_conditions;

				$sEventType = 'click';

				$i = $my_init['id'];
				$s_buffer = $elem_code[$my_init["type"]];
				$bInitval = 0;
				
				if($val === null) {
					$val = $my_init["value"];
					$bInitval = 1;
				}

				$sInputName = 'option_'.$i;

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

				if(is_scalar($val)) {
					$s_buffer = str_replace("<#value_unconverted#>", $val, $s_buffer);
					$s_buffer = str_replace("<#value#>", \Util::convertHtmlEntities($val), $s_buffer);
				}
				$s_buffer = str_replace("<#default_value#>", $my_init["value"], $s_buffer);
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
					$resValues = \DB::getQueryRows($strQuery);
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
						$s_buffer = str_replace("<#display_condition_check#>","id=\"field_".$this->oForm->id."_".$i."\" style=\"display:none;\"",$s_buffer);
						#$aFields[$my_init['option_id']]->setConditionCheck("id=\"field_".$oConfig->form_id."_".$i."\" style=\"display:none;\"");
					} else {
						$s_buffer = str_replace("<#display_condition_check#>","id=\"field_".$this->oForm->id."_".$i."\"",$s_buffer);
						#$aFields[$my_init['option_id']]->setConditionCheck("id=\"field_".$oConfig->form_id."_".$i."\"");
					}

				} else {
					$s_buffer = str_replace("<#display_condition_check#>", "id=\"field_".$this->oForm->id."_".$i."\"", $s_buffer);
					#$aFields[$my_init['option_id']]->setConditionCheck("id=\"field_".$oConfig->form_id."_".$i."\"");
				}

				$aFieldData[$i] = array(
					'type' => $my_init["type"],
					'name' => $sInputName
				);
				if (!empty($aOptions)) {
					$aFieldData[$i]['options'] = $aOptions;
				}

				$strAction = \Form\Service\Frontend\Conditions::getFormFieldConditionAction($my_init["type"], $my_init["updateaction"]);
				$s_buffer = str_replace("<#display_condition_action#>", $strAction, $s_buffer);

				$sInfoText = '';
				if (strlen(trim($my_init["infotext"])) > 0) {
					$sInfoText = $elem_code['infotext'];
					$sInfoText = str_replace("<#text#>", $my_init["infotext"], $sInfoText);
				}
				$s_buffer = str_replace("<#info#>", $sInfoText, $s_buffer);

				$temp_buffer .= $s_buffer;

			}

		}
		
		$oConditionService = $this->getConditionService();
		$sConditionJavaScript = $oConditionService->getFormConditionJavaScript();
		$temp_buffer .= $sConditionJavaScript;

		$buffer = str_replace("<#elements#>", $temp_buffer, $buffer);
		$jscode = substr($jscode,4);
		

		$buffer = str_replace("<#PHP_SELF#>", \Util::convertHtmlEntities($_SERVER['PHP_SELF']), $buffer);

		$pos = 0;
		while ($pos = strpos($buffer, '<#', $pos)) {
			$end = strpos($buffer, '#>', $pos);
			$var = substr($buffer, $pos+2, $end-$pos-2);
			$buffer = substr($buffer, 0, $pos).$$var.substr($buffer, $end+2);
		}

		echo $buffer;
		
	}
	
}