<?php

class glossary_frontend {
	
	function replaceGlossary($sCode) {
		global $system_data;

		$arrWords = DB::getQueryData("SELECT * FROM glossary WHERE `active` = '1'");
		$arrContent = array();

		preg_match_all("/<\!\-\- WD:CONTENT:START:0[0-9]{2} \-\->/", $sCode, $aRex);
		foreach($aRex[0] as $strItem) {

			$strTagStart = $strItem;
			$intStartPos = strpos($sCode, $strTagStart);
			$intStartPos = $intStartPos + strlen($strTagStart);
			$strTagEnd = str_replace("START", "END", $strTagStart);
			$intEndPos = strpos($sCode, $strTagEnd, $intStartPos);
			
			$strContent = substr($sCode, $intStartPos, $intEndPos - $intStartPos);
			
			foreach((array)$arrWords as $my_glossary) {

				$my_glossary['word'] = trim($my_glossary['word']);
				if(!$my_glossary['word']) {
					continue;
				}
	
				$sReplace = $system_data['glossary_replace'];
				$sReplace = str_replace("<#word#>", \Util::convertHtmlEntities($my_glossary['word']), $sReplace);
				$sReplace = str_replace("<#description#>", \Util::convertHtmlEntities($my_glossary['description']), $sReplace);
				$sReplace = str_replace("<#keywords#>", \Util::convertHtmlEntities($my_glossary['keywords']), $sReplace);

				//$strRegex = "/([>][^<>]*[\s]|[>])(".urlencode($my_glossary['word']).")([<]|[\s][^<>]*[<])/ims";
				//$strRegex = "/([>][^<>]*[\s]|[>\(])(".urlencode($my_glossary['word']).")([<,\)]|[\s][^<>]*[<])(?!\/option|\/textarea)/ims";
				//$strRegex = "/((?!<[^<>]*))(".urlencode($my_glossary['word']).")((?![^<>]*>|[^<>]*<\/option>|[^<>]*<\/textarea>|[^<>]*<\/select>|[^<>]*<\/a>))/ims";
				$strRegex = "/(>|\s|\(|,|\.|^)(".preg_quote($my_glossary['word'], '/').")((?(?=<)(?![^<>]*>|[^<>]*<\/option>|[^<>]*<\/textarea>|[^<>]*<\/select>|[^<>]*<\/a>)|(\s|\-|\)|,|\.|^)(?![^<>]*>|[^<>]*<\/option>|[^<>]*<\/textarea>|[^<>]*<\/select>|[^<>]*<\/a>)))/ims";
				$strContent = preg_replace($strRegex, "\\1".$sReplace."\\3", $strContent);

			}

			$sCode = substr($sCode, 0, $intStartPos).$strContent.substr($sCode, $intEndPos);

		}

		return $sCode;

	}

	function executeHook($strHook, &$mixInput) {
		global $_VARS, $system_data;

		switch($strHook) {
			case "page_processor_replace":
				if($system_data['glossary_function']) {
					$mixInput = $this->replaceGlossary($mixInput);
				}

				break;
			default:
				break;
		}
		
	}
	
}

\System::wd()->addHook('page_processor_replace', 'glossary');
