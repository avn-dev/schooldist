<?php

namespace Cms\Service;

class ReplaceVars {

	static public function execute($buffer) {
		global $page_data;
		
		$pos=0;
		$iLoop = 0;
		$asTagPre = array('#page|', '#wd:', '#page:');
		foreach($asTagPre as $sNeedle) {

			// get separator "|" or ":"
			$strSeparator = substr($sNeedle, -1);

			while(false !== ($pos = strpos($buffer, $sNeedle, $pos))) {

				$bIsVar = false;
				
				$end = strpos($buffer,'#',$pos+1);
				$var = substr($buffer, $pos+strlen($sNeedle), $end-$pos-strlen($sNeedle));
				$sVarComplete = substr($buffer, $pos, $end-$pos+1);

				$value = "";
				$bReplaceAll = true;
				if($var != '') {

					$strVar = str_replace('\\'.$strSeparator.'',"{~#~}",$var);

					$aInfo = explode($strSeparator, $strVar);
					foreach($aInfo as $intKey=>$strValue) {
						$aInfo[$intKey] = str_replace("{~#~}", $strSeparator, $strValue);
					}

					if($aInfo[0] == "if") {

						$iPos2 = $end + 1;
						// get closing if
						$bolStop = false;
						$iPos3 = $iPos2;
						do {
							$iEnd2 = strpos($buffer,$sNeedle.'/if#',$iPos3);
							$strTemp = substr($buffer, $iPos3, $iEnd2-$iPos3);
							$mixCheck = strpos($strTemp,$sNeedle.'if');
							if($mixCheck > 0) {
								$iPos3 = $iEnd2+1;
							} else {
								$bolStop = 1;
							}

						} while(!$bolStop);

						$bValue = null;

						$value = self::getVariableValue($aInfo[1]);
					
						if($aInfo[2] == 'empty') {
							$bValue = empty($value);
						} elseif($aInfo[2] == 'not_empty') {
							$bValue = !empty($value);
						} else {

							$sCompareOperator = self::getCompareOperator($aInfo[2]);

							if($sCompareOperator !== null) {
								$sCompareValue = self::getVariableValue($aInfo[3]);
								$bValue = self::compareValues($value, $sCompareValue, $sCompareOperator);
							}

						}

						if($bValue === null) {
							$value = 'ERROR';
						} elseif($bValue === true) {
							$value = substr($buffer, $iPos2, $iEnd2-$iPos2);
						} else {
							$value = "";
						}

						$end = $iEnd2 + 3 + strlen($sNeedle); //+7 before sNeedle

						$bReplaceAll = false;

					} elseif($aInfo[0] == "imgbuilder") {

						$value = \imgBuilder::doImgBuilder($aInfo);

					} elseif(strpos($aInfo[0], '_LANG') !== false) {

						$iEnd = strpos($strVar, "']");
						$iString = substr($strVar, 0, $iEnd + 2);
						$sText = substr($iString, 7);
						$sText = substr($sText, 0, -2);
						$value = \L10N::t($sText);

					} else {

						$value = self::getVariableValue($aInfo[0]);

					}

					if(\System::d('debugmode')) {
						echo "<!-- replacevars value: ".$value." -->";
					}

					if($aInfo[1] == "snippet") {

						$value = \Cms\Entity\Snippet::getContent($value, $page_data['language']);

					} elseif($aInfo[1] == "pagelink") {

						$oPage = \Cms\Entity\Page::getInstance($value);
						$value = $oPage->getLink($page_data['language']);

					} elseif($aInfo[1] == "pagelink_wl") {

						$oPage = \Cms\Entity\Page::getInstance($value);
						$value = $oPage->getLink($page_data['language']);

					} elseif(
						$aInfo[1] == "pagelink_https" ||
						$aInfo[1] == "spagelink"
					) {

						$oPage = \Cms\Entity\Page::getInstance($value);

						$sSecureDomain = str_replace("http://", "https://", $system_data['domain']);
						$value = $oPage->getLink($page_data['language']);

					} elseif($aInfo[1] == "number_format") {

						$value = floatval($value);
						$value = number_format($value, $aInfo[2], $aInfo[3], $aInfo[4]);

					} elseif($aInfo[1] == "strftime") {

						$sFormat = $aInfo[2];
						$sFormat = str_replace('"', "", $sFormat);
						$sFormat = str_replace("'", "", $sFormat);
						$value = strftime($sFormat, $value);

					} elseif($aInfo[1] == "count") {

						$value = count((array)$value);

					} elseif($aInfo[1] == "array_sum") {

						$value = array_sum((array)$value);

					} elseif($aInfo[1] == "escape") {

						if(empty($aInfo[2])) {
							$value = \Util::getEscapedString($value);
						} else {
							$value = \Util::getEscapedString($value, $aInfo[2]);
						}

					} elseif($aInfo[1] == "sprintf") {

						if(isset($value)) {
							
							$aParameter = [
								$value
							];

							$arrArgs = $aInfo;
							array_shift($arrArgs);
							array_shift($arrArgs);
							foreach($arrArgs as $intKey=>$strItem) {
								$aParameter[] = $strItem;
							}
							$value = call_user_func_array('sprintf', $aParameter);

						} else {
							$value = "";
						}

					}

				}

				$buffer = substr($buffer, 0, $pos)  .  $value  .  substr($buffer, $end+1);

				// replace all other occurencies of this string
				if($bReplaceAll) {
					$buffer = str_replace($sVarComplete, $value, $buffer);
				}

				$iLoop++;

			} // end while

		}

		if ($searchmatch) {
			$buffer = eregi_replace("(([>][^<>]*[^a-z])|([>]))(".$searchmatch.")(([<])|([^<>]*[<]))", "\\1<span class=\"searchmatch\">\\4</span>\\5", $buffer);
		}

		return $buffer;

	}

	static public function getCompareOperator($sInput) {

		$aWhitelist = [
			'==',
			'===',
			'!=',
			'<>',
			'!==',
			'<',
			'>',
			'<=',
			'>=',
			'<=>'
		];
		
		if(in_array($sInput, $aWhitelist)) {
			return $sInput;
		}
		
	}
		
	static public function compareValues($mValue1, $mValue2, $sOperator) {

		switch ($sOperator) {
			case "==":  
				return $mValue1 == $mValue2;
			case "===":  
				return $mValue1 === $mValue2;
			case "!=": 
				return $mValue1 != $mValue2;
			case "<>": 
				return $mValue1 <> $mValue2;
			case "!==": 
				return $mValue1 !== $mValue2;
			case "<": 
				return $mValue1 < $mValue2;
			case ">":  
				return $mValue1 >  $mValue2;
			case "<=":  
				return $mValue1 <= $mValue2;
			case ">=":  
				return $mValue1 >= $mValue2;
			case "<=>":  
				return $mValue1 >= $mValue2;
			default:
				throw new \RuntimeException('Invalid operator "'.$sOperator.'".');
		}

	}
	
	static public function getVariableValue($sInput) {
		global $_LANG,$_VARS,$file_data,$user_data,$page_data,$system_data,$searchmatch,$session_data;

		if($sInput === 'spy') {
			$sValue = \Core\Handler\SessionHandler::getInstance()->get('frontend_spy');
			return $sValue;
		}
		
		/*
		 * Mögliche Werte vorbereiten
		 */
		$TIMESTAMP = time();
		$JAHR = strftime("%Y",time());
		$idUser = $user_data['id'];
		$NAME = $user_data['name'];
		
		$PHP_SELF = $_SERVER['PHP_SELF'];
		$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
		
		$project_name = \System::d('project_name');
		
		$TITEL = $page_data['title'];
		$BESCHREIBUNG = $page_data['description'];
		$page_title = $page_data['title'];
		$page_id = $page_data['id'];
		$idPage = $page_data['id'];
		$parameter = $page_data['parameter'];

		$maincat = ($file_data['dir'][2])?$file_data['dir'][2]:"root";

		$PARAMETER = $session_data['query_string'];

		$addbookmark = "?".$PARAMETER."addbookmark=1";

		$encParameter = urlencode($PARAMETER);

		/*
		 * Wert ermitteln
		 */
		$bIsVar = false;
		$bMaybeVar = false;
		if(substr($sInput, 0, 1) == '$') {
			$bIsVar = true;
			$sInput = substr($sInput, 1);
		} elseif(
			substr($sInput, 0, 1) == '"' || 
			substr($sInput, 0, 1) == "'"
		) {
			if(substr($sInput, 0, 1) == '"') {
				$sInput = trim($sInput, '"');
			} else {
				$sInput = trim($sInput, "'");
			}
		} elseif(preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $sInput) === 1) {
			$bMaybeVar = true;
		}
		
		$sVariable = (string)$sInput;
		
		$sValue = '';

		if(
			$bIsVar || 
			$bMaybeVar
		) {

			$aVariableParts = null;

			// Zugriff auf Array-Elemente
			if(strpos($sVariable, '[') !== false) {
				$aVariableParts = explode('[', $sVariable);
				$sVariable = array_shift($aVariableParts);
			}

			if(isset(${$sVariable})) {
				$sValue = ${$sVariable};

				if($aVariableParts !== null) {

					foreach($aVariableParts as $sVariablePart) {
						// Rechte Klammer und eventuell vorhandene Anführungszeichen entfernen
						$sVariablePart = trim($sVariablePart, '"\']');										

						if(isset($sValue[$sVariablePart])) {
							$sValue = $sValue[$sVariablePart];
						} else {
							$sValue = false;
							break;
						}
					}

				}

			} else {
				$sValue = false;
			}

			if(
				!$bIsVar && 
				$sValue === false
			) {
				$sValue = $sInput;
			} else {
				$bIsVar = true;
			}
		} else {
			$sValue = $sInput;
		}
	
		return $sValue;
	}

}
