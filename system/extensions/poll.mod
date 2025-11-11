<?php
 
use Core\Handler\CookieHandler;

if(class_exists('\\Cms\\Helper\\ExtensionConfig')) {
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
} else {
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
}
$poll_id = $config->poll_id;

$oSession = \Core\Handler\SessionHandler::getInstance();

$strSql = "INSERT INTO `poll_log` SET 
	`poll_id` = :poll_id,
	`user_data` = :user_data,
	`request` = :request,
	`session` = :session
";

// Cookies, request

$logData = [
    'session' => $oSession->all(),
    'cookies' => $_COOKIE
];

$arrSql = array();
$arrSql['poll_id'] = $poll_id;
$arrSql['user_data'] = json_encode($user_data);
$arrSql['request'] = json_encode($_VARS);
$arrSql['session'] = json_encode($logData);
DB::executePreparedQuery($strSql, $arrSql);

$oPoll = new Ext_Poll_Poll($poll_id);

$sPages = $oPoll->countPages();

// Zurücksetzen

if(
	isset($_VARS['restart']) &&
	isset($_SESSION['poll_restart']) &&
	isset($_SESSION['poll'][$poll_id]) &&
	(string)$_VARS['restart'] === $_SESSION['poll_restart']
) {
	unset($_SESSION['poll'][$poll_id]);
}

$aErrorQuestions = array();
$aErrorMessages = array();

// CMS Editmodus
if(
	$user_data['cms'] &&
	isset($_VARS['report_id'])
) {
	$_SESSION['poll'][$poll_id]['cms_session'] = $_VARS['cms_session'];
	$_SESSION['poll'][$poll_id]['report_id'] = (int)$_VARS['report_id'];
	$_SESSION['poll'][$poll_id]['cms_edit_mode'] = true;
}

$aTrace = &$_SESSION['poll']['trace'][$poll_id];

if(isset($_SESSION['poll'][$poll_id]['cms_session'])) {
	$user_data['session'] = $_SESSION['poll'][$poll_id]['cms_session'];
}

$bCmsEditMode = false;
if(isset($_SESSION['poll'][$poll_id]['cms_edit_mode'])) {
	$bCmsEditMode = (bool)$_SESSION['poll'][$poll_id]['cms_edit_mode'];
}

$_VARS['loop'] = (int)$_VARS['loop'];

// TAN
if (
	$_VARS['tan'] != $_SESSION['tan'] && 
	$_VARS['idPage'] != ""
) {
	unset($_VARS['result']);
	$aErrorQuestions = array(
		0 => 0
	);
	$aErrorMessages = array(
		'Diese Seite wurde bereits abgeschickt! Bitte versuchen Sie es erneut.'
	);
}

$tan = \Util::generateRandomString(8);
$restart_hash = \Util::generateRandomString(20);

$_SESSION['tan'] = $tan;
// Hash mit dem die Umfrage zurückgesetzt werden kann
$_SESSION['poll_restart'] = $restart_hash;

$noNextPage = false;
$iRoutingTarget = null;

// Seite zurück
if($_VARS['idPage'] == -1) {

	$iLastPage = null;
	
	if(is_array($aTrace)) {
		// Vorletztes Element rauslesen
		$iLatestPage = end($aTrace);
		$aRevertedTrace = array_reverse($aTrace);


		foreach($aRevertedTrace as $iPage) {
			if($iPage < $iLatestPage) {
				$iLastPage = $iPage;
				break;
			}
		}
	}

	// Wenn die Seite nicht gefunden werden konnte
	if($iLastPage === null) {
		$iLastPage = $iLatestPage-1;
	}

	$_VARS['idPage'] = $iLastPage;
	
	$_VARS['action'] = "lastPage";
}

// Setzen der ersten Seite / aktuellen Seite
if ($_VARS['idPage'] == "") {
	$_VARS['idPage'] = 1;
}

$currentPage = $_VARS['idPage'];

$oPage = new Ext_Poll_Page($poll_id, $_VARS['idPage']);

$oSite = $this->getSite();

// Auslesen der Sprachen aus der Datenbank
$aLanguages = array();
$aItems = $oSite->getLanguages();
foreach((array)$aItems as $aresLanguages) {
	$aLanguages[$aresLanguages['id']] =  $aresLanguages['code'];
}

if ($_VARS['sys_language'] != "") {
	$_SESSION['language'] = $_VARS['sys_language'];
}
if (isset($_SESSION['language']))  {
	$language = $_SESSION['language'];
} else {
	$language = $page_data['language'];
}

/**
 * Bisherige Eingaben auslesen
 */
$sSql = "
	SELECT
		*
	FROM
		`poll_report_".(int)$poll_id."` 
	WHERE 
		`id` = ".(int)$_SESSION['poll'][$poll_id]['report_id'];
$aReport = DB::getQueryRow($sSql);

/**
 * Routing
 */
if($_VARS['action'] == "nextPage") {

	if(!empty($_VARS['jumpPage'])) {
		
		$iRoutingTarget = (int)$_VARS['jumpPage'];
		
	} else {

		$aRoutings = $oPage->getRoutings();

		$oConditionHelper = new \Poll\Helper\ConditionHelper;

		foreach($aRoutings as $aRouting) {

			$aFilter = (array)json_decode($aRouting['settings'], true);
			$bRouting = $oConditionHelper->checkConditions($aFilter, $_VARS['result'], $aReport);

			if($bRouting === true) {
				$iRoutingTarget = $aRouting['target'];
				break;
			}

		}
		
	}
	
}



// Schleife für alle Paragraphen der letzten Seite
$resCheckParagraphs = DB::getQueryRows("SELECT * FROM poll_paragraphs WHERE idPoll = '".(int)$poll_id."' AND idPage = ".(int)$_VARS['idPage']."");
foreach($resCheckParagraphs as $aresCheckParagraphs) {

	// Schleife für alle Fragen des aktuellen Paragraphen
	$resQuestion = DB::getQueryRows("SELECT * FROM poll_questions WHERE idPoll = ".(int)$poll_id." AND idParagraph = ".(int)$aresCheckParagraphs['id']."");
	foreach($resQuestion as $aresQuestion) {

		// Pflichtfrage
		if(
			$aresQuestion['important'] > 0 && 
			$_VARS['result'][$aresQuestion['id']] == "" &&
			$_VARS['action'] == "nextPage"
		) {
			$aErrorQuestions[$aresQuestion['id']] = $aresQuestion['id'];
		}

		$aRouting = Util::decodeSerializeOrJson($aresQuestion['route']);

		$mResult = $_VARS['result'][$aresQuestion['id']];

		// Wenn der Eintrag ein Array ist, dann das erste Element nehmen
		if(is_array($mResult)) {
			$mResult = reset($mResult);
		}

		// Routing
		if(
			$_VARS['action'] == "nextPage" &&
			$aRouting &&
			is_array($aRouting)
		) {

			// Prüfen, ob die gegebene Antwort ein Routing impliziert
			if ($aRouting[$mResult] > 0) {
				$iRoutingTarget = $aRouting[$mResult];
				$noNextPage = TRUE;
			}

		}

	}

}

/**
 * Plausichecks prüfen
 * Nicht bei zurück!
 */
if($_VARS['action'] == "nextPage") {
	
	$aPlausichecks = $oPage->getPlausichecks();
	foreach($aPlausichecks as $aPlausicheck) {

		$aPlausicheckErrorQuestions = array();

		if($aPlausicheck['hard'] == '0') {
			// Wenn es kein harter Check ist und er schonmal ausgelöst wurden, dann überspringen
			if(isset($_SESSION['poll']['plausicheck'][$aPlausicheck['id']])) {
				unset($_SESSION['poll']['plausicheck'][$aPlausicheck['id']]);
				continue;
			}
		}

		$sCondition = $aPlausicheck['condition'];

		if(empty($sCondition)) {
			continue;
		}
		
		// Formatfunktion ersetzen
		preg_match_all("/format\('(.*)',\s*(\{q_([0-9]+)(_[0-9]+)?(_[0-9]+)?\})\)/", $sCondition, $aMatches);

		if(is_array($aMatches)) {
			foreach($aMatches[0] as $iMatch=>$sComplete) {
				switch($aMatches[1][$iMatch]) {
					case 'int':
						$sPattern = '[0-9]+';
						break;
					case 'decimal':
						$sPattern = '([0-9]+)(.([0-9]+))?';
						break;
					default:
						$sPattern = $aMatches[1][$iMatch];
						break;
				}
				
				$sCondition = str_replace($sComplete, 'preg_match("/^'.$sPattern.'$/", '.$aMatches[2][$iMatch].') === 1', $sCondition);

			}
		}
					
		// kundendatenbank ersetzen	
		preg_match_all("/kundendatenbank\(([0-9]+),\s*'([a-z\-_]+)',\s*\{q_([0-9]+)(_[0-9]+)?(_[0-9]+)?\}\)/", $sCondition, $aMatches);

		if(is_array($aMatches)) {
			foreach($aMatches[0] as $iMatch=>$sComplete) {

				$mValue = $_VARS['result'][$aMatches[3][$iMatch]];
				
				if(!empty($aMatches[4][$iMatch])) {

					$iValueKey = str_replace('_', '', $aMatches[4][$iMatch]);
					if(isset($mValue[$iValueKey])) {
						$mValue = $mValue[$iValueKey];
					}

					if(!empty($aMatches[5][$iKey])) {
						$iValueKey = str_replace('_', '', $aMatches[5][$iMatch]);
						if(isset($mValue[$iValueKey])) {
							$mValue = $mValue[$iValueKey];
						}
					}

				}
				
				if(is_array($mValue)) {
					$mValue = reset($mValue);
				}

				$oCustomerDb = new Ext_CustomerDB_DB($aMatches[1][$iMatch]);
				$aCheck = $oCustomerDb->getCustomerByUniqueField($aMatches[2][$iMatch], $mValue);
				if(empty($aCheck)) {
					$sReplace = ' false ';
				} else {
					$sReplace = ' true ';
				}

				$aPlausicheckErrorQuestions[$aMatches[3][$iMatch]] = $aMatches[3][$iMatch];

				$sCondition = str_replace($sComplete, $sReplace, $sCondition);
			}
		}

		$aMessages = json_decode($aPlausicheck['messages'], true);

		preg_match_all("/\{q_([0-9]+)(_[0-9]+)?(_[0-9]+)?\}/", $sCondition, $aMatches);

		foreach($aMatches[1] as $iKey=>$iQuestionId) {

			$mValue = $_VARS['result'][$iQuestionId];
			
			if(!empty($aMatches[2][$iKey])) {

				$iValueKey = str_replace('_', '', $aMatches[2][$iKey]);
				if(isset($mValue[$iValueKey])) {
					$mValue = $mValue[$iValueKey];
				}
				
				if(!empty($aMatches[3][$iKey])) {
					$iValueKey = str_replace('_', '', $aMatches[3][$iKey]);
					if(isset($mValue[$iValueKey])) {
						$mValue = $mValue[$iValueKey];
					}
				}

			}
			
			if(is_array($mValue)) {
				$mValue = reset($mValue);
			}

            $iDecimals = 0;
			// Zahlenformat umwandeln
			$mMatch = preg_match('/^(([0-9]*)(\.[0-9]{3})*)(,([0-9]+))?$/', $mValue, $aMatch);
			// Wenn der Wert dem deutschen Zahlenformat entspricht
			if($mMatch === 1) {
				$mValue = str_replace('.', '', $aMatch[1]);
				if(!empty($aMatch[5])) {
					$mValue .= '.'.$aMatch[5];
					$iDecimals = (int)strlen($aMatch[5]);
				}
			}

			if(is_numeric($mValue)) {
				$mValue = (float)$mValue;
				if ($iDecimals > 0) {
					$mValue = '"'.number_format($mValue, $iDecimals).'"';
				}
			} else {
				// Achtung: Potentielle Sicherheitslücke!
				$mValue = preg_replace('/[^\p{L}\p{N}ÄÖÜäöüß\.:\- ]/i', '', $mValue);
				$mValue = '"'.$mValue.'"';
			}

			$aPlausicheckErrorQuestions[$iQuestionId] = $iQuestionId;

			$sCondition = str_replace($aMatches[0][$iKey], $mValue, $sCondition);
		}

		$sCondition = preg_replace("/\s+=\s+/", ' == ', $sCondition);

		$sCondition = '$bCheck = ('.$sCondition.');';
		$bCheck = false;
		try {
			eval($sCondition);
		} catch (\Throwable $e) {
			__pout($sCondition);
			__pout($e);
		}

		if($bCheck === false) {
			$_SESSION['poll']['plausicheck'][$aPlausicheck['id']] = false;
			$aErrorMessages[] = $aMessages[$language];
			$aErrorQuestions = array_merge($aErrorQuestions, (array)$aPlausicheckErrorQuestions);
		}

	}
}

// Visit speichern
if(
	$_VARS['idPage'] == 1 &&
	empty($_VARS['action']) &&
	!CookieHandler::is('poll_visit_saved')
) {
	
	try {
		
		$aData = array(
			'poll_id' => (int)$poll_id,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'sid' => $oSession->getId()
		);
		
		DB::insertData('poll_visits', $aData);

		CookieHandler::set('poll_visit_saved', 1);

	} catch(Exception $e) {
		
	}
	
}

// Visits auslesen
$sSql = "
	SELECT
		COUNT(*)
	FROM 
		`poll_visits`
	WHERE
		`poll_id` = :poll_id
	";
$aSql = array('poll_id' => $poll_id);
$iVisits = DB::getQueryOne($sSql, $aSql);

if (count($aErrorQuestions) == 0) {

	\System::wd()->executeHook('poll_save', $poll_id);

	// Wenn ein Routing geschieht, wird die Seitenanzahl nicht linear inkrementiert
	if (
		$_VARS['action'] == "nextPage" && 
		!$noNextPage
	) {
		$_VARS['idPage']++;
	}

	/**
	 * Erstellen des Reporteintrages wenn noch keiner da ist und das Formular 
	 * abgeschickt wurde
	 */
	if (
		$_SESSION['poll'][$poll_id]['report_id'] <= 0 && 
		in_array($_VARS['action'], ["nextPage", "init"])
	) {
		$strSql = "
			INSERT INTO 
				`poll_report_".(int)$poll_id."` 
			SET 
				`idPoll` = ".(int)$poll_id.", 
				`idUser` = ".(int)$user_data['id'].", 
				`idTable` = ".(int)$user_data['idTable'].",  
				`date` = NOW(), 
				`sid` = '".\DB::escapeQueryString($oSession->getId())."', 
				`spy` = '".\DB::escapeQueryString($session_data['spy'])."', 
				`ip` = '".\DB::escapeQueryString($_SERVER['REMOTE_ADDR'])."'
			";
		DB::executeQuery($strSql);
		$_SESSION['poll'][$poll_id]['report_id'] = DB::fetchInsertID();
	}

	$iLoops = $oPoll->getResultLoops($_SESSION['poll'][$poll_id]['report_id']);
	if($_VARS['loop'] >= $iLoops) {
		$iLoops = $_VARS['loop']+1;
	}
	
	$qReport = "UPDATE poll_report_".(int)$poll_id." SET ";

	// Speichern der Results
	foreach((array)$_VARS['result'] as $key => $val) {

		if(!is_numeric($key)) {
			continue;
		}

		// Darf nicht passieren!
		if(empty($_SESSION['poll'][$poll_id]['report_id'])) {
			Util::handleErrorMessage('Poll - No report id!', 1, 0, 1);
			throw new Exception('No report id found! Please contact the administrator!');
		}

		if (is_array($val)) {
			$sSetResult = json_encode($val);
		} else {
			$sSetResult = $val;
		}
		
		// Muss unique sein
		$sSql = "
			SELECT 
				* 
			FROM 
				`poll_results`
			WHERE
				`idPoll` = :idPoll AND
				`idQuestion` = :idQuestion AND
				`idReport` = :idReport AND
				`loop` = :loop
			";
		$aSql = array(
			'idPoll' => (int)$poll_id,
			'idQuestion' => (int)$key,
			'idReport' => (int)$_SESSION['poll'][$poll_id]['report_id'],
			'loop' => $_VARS['loop'],
		);
		$aCheckResult = DB::getQueryRow($sSql, $aSql);

		$aSql['data'] = $sSetResult;
		$aSql['idUser'] = (int)$user_data['id'];
		$aSql['idTable'] = (int)$user_data['idTable'];
		$aSql['sid'] = $oSession->getId();
		$aSql['ip'] = $_SERVER['REMOTE_ADDR'];
		
		if(empty($aCheckResult)) {
			DB::insertData('poll_results', $aSql);
		} else {
			DB::updateData('poll_results', $aSql, "`id` = ".(int)$aCheckResult['id']);
		}

		// Anhängen bzw. Update der bisherigen Einträge
		$aVal = array();
		$sSql = "
			SELECT 
				* 
			FROM 
				poll_results 
			WHERE 
				`idPoll` = ".(int)$poll_id." AND  
				`idQuestion` = ".(int)$key." AND  
				`idReport` = ".(int)$_SESSION['poll'][$poll_id]['report_id']."  
			ORDER BY  
				`loop`
			";
		$aResults = DB::getQueryData($sSql);

		foreach((array)$aResults as $aCount) {
			if (
				strpos($aCount['data'], "{") && 
				strpos($aCount['data'], "}")
			) {
				$aCount['data'] = Util::decodeSerializeOrJson($aCount['data']);
				if(count($aCount['data']) > 1) {
					foreach ($aCount['data'] as $data) {
						$aVal[$aCount['loop']][] = $data;
					}
				} else {
					$aVal[$aCount['loop']] = reset($aCount['data']);
				}
			} else {
				$aVal[$aCount['loop']] = $aCount['data'];
			}
		}

		$aTemp = $aVal;
		$aVal = array();
		for($i=0;$i<$iLoops;$i++) {
			if(is_array($aTemp[$i])) {
				$aVal[] = json_encode($aTemp[$i]);
			} else {
				$aVal[] = (string)$aTemp[$i];
			}
		}

		if(
			$aConfig['tracking'] == 'url' ||
			$aConfig['tracking'] == 'id' ||
			$aConfig['tracking'] == 'id_multi'
		) {
			$sVal = end($aVal);
		} else {
			$sVal = implode("|", $aVal);
		}

		$qReport .= "`f_".(int)$key."` = '".\DB::escapeQueryString($sVal)."', ";

		// @todo - das hier sollte man irgendwann entfernen
		if(!DB::getDefaultConnection()->checkField('poll_report_'.(int)$poll_id, 'f_'.(int)$key)) {
			if($oPoll instanceof Ext_Poll_Poll) {
				$oPoll->updateReportTable();
				// Hinweis das Column nicht da war
				Util::reportError('Poll report column missing - updated', 'Column "f_'.(int)$key.'" in "poll_report_'.(int)$poll_id.'" was not defined! Called updateReportTable()');
			} else {
				// Hinweis das Spalte nicht vorhanden ist!
				Util::reportError('Poll report column missing - Values get lost', 'Column "f_'.(int)$key.'" in "poll_report_'.(int)$poll_id.'" is not defined!');
			}			
		}
		
	}
	
	if($bCmsEditMode === false) {
		$qReport .= " `date` = NOW(), `sid` = '".\DB::escapeQueryString($oSession->getId())."'";
	} else {
		$qReport .= " `date` = `date` ";
	}
	$qReport .= " WHERE `id` = ".(int)$_SESSION['poll'][$poll_id]['report_id'];
	DB::executeQuery($qReport);

	if ($noNextPage === TRUE) {
		unset($_VARS['result']);
	}

	if($iRoutingTarget !== null) {
		$_VARS['idPage'] = $iRoutingTarget;
	}
	
} else {

	$_VARS['idPage'] = $currentPage;

}

// Wenn Routing, loop hochzählen
if($noNextPage === true) {
	$_VARS['loop']++;
}

$_VARS['idPage'] = (int)$_VARS['idPage'];

// Sicherheitsabfrage: Seite kleiner 1 geht nicht
if($_VARS['idPage'] < 1) {
	$_VARS['idPage'] = 1;
}

// Jede Seite im Verlauf speichern
$aTrace[] = (int)$_VARS['idPage'];

$resParagraphs = DB::getQueryRows("SELECT * FROM `poll_paragraphs` WHERE `idPoll` = '".(int)$poll_id."' AND `idPage` = '".(int)$_VARS['idPage']."' ORDER BY `position`");
foreach($resParagraphs as $aresParagraphs) {
	if ($aresParagraphs['isPage'] == 0) {
		$aParagraphs[$aresParagraphs['id']] = $aresParagraphs;
	}
}

$aReport = array();
if(isset($_SESSION['poll'][$poll_id]['report_id'])) {
	$aReport = $oPoll->getReport($_SESSION['poll'][$poll_id]['report_id'], $_VARS['loop']);
}

if($config->use_smarty == 1) {

	$oSmartyHelper = new \Poll\Helper\SmartyHelper($oPoll);

	$oSmartyHelper->setResult($aReport);
	$oSmartyHelper->setLanguage($language);
	$oSmartyHelper->setTemplateData($element_data);
	$oSmartyHelper->setErrorMessages($aErrorMessages);
	$oSmartyHelper->setErrorQuestions($aErrorQuestions);
	$oSmartyHelper->setPageId($page_data['id']);
	$oSmartyHelper->setTan($tan);
	$oSmartyHelper->setRestartHash($restart_hash);
	$oSmartyHelper->setVisits($iVisits);

	$sCode = $oSmartyHelper->generate($aParagraphs, $_SESSION['poll'][$poll_id]['report_id']);

	echo $sCode;

	$sJs = $oSmartyHelper->getJs();

	if(!empty($sJs)) {
		echo $sJs;
	}

} else {

	$oConditionHelper = new Poll\Helper\ConditionHelper;
	
	$element_data['content'] = str_replace('<#visits#>', $iVisits, $element_data['content']);
	
	$buffer					= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'poll');
	$buffer_block			= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'block');
	$buffer_error_message	= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'error_message');
	$buffer_error_code		= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'error_code');

	$aPlaceholderQuestions = array();

	unset($iCount);
	foreach ((array)$aParagraphs as $pkey => $pval) {

		$aContent = array();
		$resContent = DB::getQueryRows("SELECT * FROM poll_questions WHERE idPoll = '".(int)$poll_id."' AND idParagraph = '".(int)$pkey."' ORDER BY position");
		foreach($resContent as $aresContent) {
			$aresContent['data'] = Util::decodeSerializeOrJson($aresContent['data']);
			$aresContent['parameter'] = Util::decodeSerializeOrJson($aresContent['parameter']);
			$aresContent['route'] = Util::decodeSerializeOrJson($aresContent['route']);
			$aContent[$aresContent['id']] = $aresContent;
			$aContentCount[] = $aresContent['id'];
		}

		// Content
		unset($buffer_content);
		foreach ((array)$aContent as $ckey => $cval) {

			$oConditionHelper->handleQuestionCondition($cval);

			$sQuestionBuffer = "";
			
			// Sortierung nach Werten
			if (is_array($cval['data'])) {
				if ($cval['data'][1]['position'] != "") {
					uasort($cval['data'], array('Ext_Poll_Poll', "sortDataByPosition"));
				} else {
					uasort($cval['data'], array('Ext_Poll_Poll', "sortDataByValue"));
				}
			}
			
			$title = $cval[$language.'_title'];

			$description = $cval[$language.'_description'];
			$id = $cval['id'];

			if ($cval['important'] == 1 and $title != "") {
				//$title .= " *";
			}

			if (in_array($ckey, $aErrorQuestions))	{
				$cval['error'] = true;
			}

			if (empty($_VARS['result'][$ckey])) {
				$sResult = $aReport['f_'.$ckey];
			} else {
				if ($_VARS['r'.$ckey] != "") {
					$sResult = $_VARS['r'.$ckey];
				} else {
					$sResult = $_VARS['result'][$ckey];
				}
			}

			$cval['value'] = $sResult;

			if ($cval['hidden'] == 1) {

				if(
					!empty($cval['input_addon']) &&
					empty($sResult) &&
					!empty($user_data['data'][$cval['input_addon']])
				) {
					$sResult = $user_data['data'][$cval['input_addon']];
				}
				
				$sQuestionBuffer .= "<input type=\"hidden\" name=\"result[".$cval['id']."]\" value=\"".$sResult."\">";

			} elseif ($cval['hidden'] == 2) {

				$buffer_template_static									= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_static');
				$buffer_template_static									= str_replace("<#title#>", $title, $buffer_template_static);
				$buffer_template_static									= str_replace("<#description#>", $description, $buffer_template_static);
				$buffer_template_static								   .= "<input type=\"hidden\" name=\"result[".$cval['id']."]\" value=\"".$sResult."\">";

				if ($cval['template'] == "reference") {
					preg_match_all("/'(.*?)'/ims", $cval['data']['db_field_text'], $aFields);
					$sQuery = "SELECT * FROM ".$cval['data']['db_table']." WHERE ".$cval['data']['db_field_value']." = ".$sResult;
					$aQuery = get_data(db_query($sQuery));
					foreach ((array)$aFields[1] as $col) {
						$cval['data']['db_field_text'] = preg_replace("/'".$col."'/", $aQuery[$col], $cval['data']['db_field_text']);
					}
					$buffer_template_static								= str_replace("<#static_value#>", $cval['data']['db_field_text'], $buffer_template_static);
				} else {
					$buffer_template_static								= str_replace("<#static_value#>", "<input type=\"hidden\" name=\"result[".$cval['id']."]\" value=\"".$sResult."\">".$cval['data'][$sResult][$language], $buffer_template_static);
				}
				$sQuestionBuffer										   .= $buffer_template_static;

			// Platzhalterfrage
			} elseif ($cval['hidden'] == 3) {

				$aPlaceholderQuestions[$cval['id']] = $cval;

				continue;
				
			} else {

				if(
					trim($sResult) == '' && 
					isset($_SESSION['aTracking']['f_'.$ckey]) && 
					!isset($_VARS['result'][$ckey])
				) {
					$sResult = $_SESSION['aTracking']['f_'.$ckey];
				}

				switch ($cval['template']) {
					case "static":
						$buffer_template_static							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_static');
						$buffer_template_static							= str_replace("<#id#>", $id, $buffer_template_static);
						$buffer_template_static							= str_replace("<#title#>", $title, $buffer_template_static);
						$buffer_template_static							= str_replace("<#description#>", $description, $buffer_template_static);
						$buffer_template_static							= str_replace("<#static_value#>", $sResult, $buffer_template_static);
						$sQuestionBuffer								   .= $buffer_template_static;
					break;

					case "text":
						$buffer_template_text							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_text');
						$buffer_template_text							= str_replace("<#id#>", $id, $buffer_template_text);
						$buffer_template_text							= str_replace("<#title#>", $title, $buffer_template_text);
						$buffer_template_text							= str_replace("<#description#>", $description, $buffer_template_text);
						$buffer_template_text							= str_replace("<#text_name#>", "result[".$cval['id']."]", $buffer_template_text);
						$buffer_template_text							= str_replace("<#text_class#>", "result_".$cval['id']."", $buffer_template_text);
						$buffer_template_text							= str_replace("<#text_value#>", $sResult, $buffer_template_text);
						$buffer_template_text							= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_text);
						$buffer_template_text							= str_replace("<#css#>", $cval['input_css'], $buffer_template_text);
						$sQuestionBuffer								   .= $buffer_template_text;
					break;

					case "textarea":
						$buffer_template_textarea						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_textarea');
						$buffer_template_textarea						= str_replace("<#id#>", $id, $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#title#>", $title, $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#description#>", $description, $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#textarea_name#>", "result[".$cval['id']."]", $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#textarea_class#>", "result_".$cval['id']."", $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#textarea_value#>", $sResult, $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_textarea);
						$buffer_template_textarea						= str_replace("<#css#>", $cval['input_css'], $buffer_template_textarea);
						$sQuestionBuffer								   .= $buffer_template_textarea;
					break;

					case "select":
						unset($buffer_template_select_list_output);
						$buffer_template_select							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_select');
						$buffer_template_select							= str_replace("<#id#>", $id, $buffer_template_select);
						$buffer_template_select							= str_replace("<#title#>", $title, $buffer_template_select);
						$buffer_template_select							= str_replace("<#description#>", $description, $buffer_template_select);
						$buffer_template_select							= str_replace("<#select_name#>", "result[".$cval['id']."]", $buffer_template_select);
						$buffer_template_select							= str_replace("<#select_class#>", "result_".$cval['id']."", $buffer_template_select);
						$buffer_template_select							= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_select);
						$buffer_template_select							= str_replace("<#css#>", $cval['input_css'], $buffer_template_select);
						$buffer_template_select_list					= \Cms\Service\PageParser::checkForBlock($element_data['content'],'select_list');

						foreach ((array)$cval['data'] as $dkey => $dval) {
							// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
							if ($dval[$language] == "DEFAULT") {
								$dval[$language] = "";
							}
							if ($sResult == $dval['value'])	{ $select = 'selected="selected"'; }
							else							{ $select = ""; }
							$buffer_template_select_list_loop			= $buffer_template_select_list;
							$buffer_template_select_list_loop			= str_replace("<#select_item_value#>", $dval['value'], $buffer_template_select_list_loop);
							$buffer_template_select_list_loop			= str_replace("<#select_item_title#>", $dval[$language], $buffer_template_select_list_loop);
							$buffer_template_select_list_loop			= str_replace("<#selected#>", $select, $buffer_template_select_list_loop);
							$buffer_template_select_list_output 	   .= $buffer_template_select_list_loop;
						}
						$buffer_template_select							= \Cms\Service\PageParser::replaceBlock($buffer_template_select, "select_list", $buffer_template_select_list_output);
						$sQuestionBuffer								   .= $buffer_template_select;
					break;

					case "list":
						unset($buffer_template_list_list_output);
						$buffer_template_list							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_list');
						$buffer_template_list							= str_replace("<#id#>", $id, $buffer_template_list);
						$buffer_template_list							= str_replace("<#title#>", $title, $buffer_template_list);
						$buffer_template_list							= str_replace("<#description#>", $description, $buffer_template_list);
						$buffer_template_list							= str_replace("<#list_name#>", "result[".$cval['id']."][]", $buffer_template_list);
						$buffer_template_list							= str_replace("<#list_class#>", "result_".$cval['id']."", $buffer_template_list);
						$buffer_template_list							= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_list);
						$buffer_template_list							= str_replace("<#css#>", $cval['input_css'], $buffer_template_list);
						$buffer_template_list_list						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'list_list');

						foreach ((array)$cval['data'] as $dkey => $dval) {
							// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
							if ($dval[$language] == "DEFAULT") {
								$dval[$language] = "";
							}
							if ($sResult == $dval['value'])	{ $select = 'selected="selected"'; }
							else							{ $select = ""; }
							$buffer_template_list_list_loop				= $buffer_template_list_list;
							$buffer_template_list_list_loop				= str_replace("<#list_name#>", "result[".$cval['id']."][]", $buffer_template_list_list_loop);
							$buffer_template_list_list_loop				= str_replace("<#list_class#>", "result_".$cval['id']."", $buffer_template_list_list_loop);
							$buffer_template_list_list_loop				= str_replace("<#list_item_value#>", $dval['value'], $buffer_template_list_list_loop);
							$buffer_template_list_list_loop				= str_replace("<#list_item_title#>", $dval[$language], $buffer_template_list_list_loop);
							$buffer_template_list_list_loop				= str_replace("<#selected#>", $select, $buffer_template_list_list_loop);
							$buffer_template_list_list_output 		   .= $buffer_template_list_list_loop;
						}
						$buffer_template_list							= \Cms\Service\PageParser::replaceBlock($buffer_template_list, "list_list", $buffer_template_list_list_output);
						$sQuestionBuffer								   .= $buffer_template_list;
					break;

					case "radio":
						unset($buffer_template_radio_list_output);
						$buffer_template_radio							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_radio');
						$buffer_template_radio							= str_replace("<#id#>", $id, $buffer_template_radio);
						$buffer_template_radio							= str_replace("<#title#>", $title, $buffer_template_radio);
						$buffer_template_radio							= str_replace("<#description#>", $description, $buffer_template_radio);
						$buffer_template_radio							= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_radio);
						$buffer_template_radio							= str_replace("<#css#>", $cval['input_css'], $buffer_template_radio);
						$buffer_template_radio_list						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'radio_list');

						foreach ((array)$cval['data'] as $dkey => $dval) {
							if ($sResult == $dval['value'])	{ $select = 'checked="checked"'; }
							else							{ $select = ""; }
							$buffer_template_radio_list_loop			= $buffer_template_radio_list;
							$buffer_template_radio_list_loop			= str_replace("<#radio_name#>", "result[".$cval['id']."]", $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#radio_class#>", "result_".$cval['id']."", $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#radio_item_value#>", $dval['value'], $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#radio_item_title#>", $dval[$language], $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#selected#>", $select, $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_radio_list_loop);
							$buffer_template_radio_list_loop			= str_replace("<#css#>", $cval['input_css'], $buffer_template_radio_list_loop);
							$buffer_template_radio_list_output 		   .= $buffer_template_radio_list_loop;
						}
						$buffer_template_radio							= \Cms\Service\PageParser::replaceBlock($buffer_template_radio, "radio_list", $buffer_template_radio_list_output);
						$sQuestionBuffer								   .= $buffer_template_radio;
					break;

					case "check":
						unset($buffer_template_check_list_output);
						$buffer_template_check							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_check');
						$buffer_template_check							= str_replace("<#id#>", $id, $buffer_template_check);
						$buffer_template_check							= str_replace("<#title#>", $title, $buffer_template_check);
						$buffer_template_check							= str_replace("<#description#>", $description, $buffer_template_check);
						$buffer_template_check							= str_replace("<#css#>", $cval['input_css'], $buffer_template_check);
						$buffer_template_check_list						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'check_list');

						$aCheckResult = (array)Util::decodeSerializeOrJson($sResult);

						foreach ((array)$cval['data'] as $dkey => $dval) {

							if (in_array($dval['value'], $aCheckResult))	{ $select = 'checked="checked"'; }
							else							{ $select = ""; }

							$buffer_template_check_list_loop			= $buffer_template_check_list;
							$buffer_template_check_list_loop			= str_replace("<#check_name#>", "result[".$cval['id']."][]", $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#check_class#>", "result_".$cval['id']."", $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#check_item_title#>", $dval[$language], $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#check_item_value#>", $dval['value'], $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#selected#>", $select, $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_check_list_loop);
							$buffer_template_check_list_loop			= str_replace("<#css#>", $cval['input_css'], $buffer_template_check_list_loop);
							$buffer_template_check_list_output 		   .= $buffer_template_check_list_loop;
						}
						$buffer_template_check							= \Cms\Service\PageParser::replaceBlock($buffer_template_check, "check_list", $buffer_template_check_list_output);
						$sQuestionBuffer								   .= $buffer_template_check;
					break;

					case "block_start":
						unset($buffer_template_block_start_value_output); unset($buffer_template_block_start_title_output);
						$buffer_template_block_start					= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_block_start');
						$buffer_template_block_start_title				= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'block_start_title_loop');
						$buffer_template_block_start_value				= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'block_start_value_loop');
						$buffer_template_block_start					= str_replace("<#id#>", $id, $buffer_template_block_start);

						if(strpos($title, '|') !== false) {
							list($sTitleLeft, $sTitleRight) = explode('|', $title, 2);
							$buffer_template_block_start = str_replace("<#title#>", $sTitleLeft, $buffer_template_block_start);
							$buffer_template_block_start = str_replace("<#title_right#>", $sTitleRight, $buffer_template_block_start);
						} else {
							$buffer_template_block_start = str_replace("<#title#>", $title, $buffer_template_block_start);
							$buffer_template_block_start = str_replace("<#title_right#>", '', $buffer_template_block_start);
						}

						$buffer_template_block_start					= str_replace("<#description#>", $description, $buffer_template_block_start);
						$buffer_template_block_start					= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_block_start);
						$buffer_template_block_start					= str_replace("<#css#>", $cval['input_css'], $buffer_template_block_start);

						foreach ((array)$cval['data'] as $dkey => $dval) {
							if ($sResult == $dval['value'])	{ $select = 'checked="checked"'; }
							else							{ $select = ""; }
							$strParameter = $cval['parameter'][$dkey];
							$buffer_template_block_start_title_loop		= $buffer_template_block_start_title;
							$buffer_template_block_start_title_loop		= str_replace("<#block_start_title#>", $dval[$language], $buffer_template_block_start_title_loop);
							$buffer_template_block_start_title_output  .= $buffer_template_block_start_title_loop;
							$buffer_template_block_start_value_loop		= $buffer_template_block_start_value;
							$buffer_template_block_start_value_loop		= str_replace("<#block_start_value#>", "<input type=\"radio\" class=\"result_".$cval['id']."\" name=\""."result[".$cval['id']."]"."\" value=\"".$dval['value']."\" ".$select.">", $buffer_template_block_start_value_loop);
							$buffer_template_block_start_value_loop		= str_replace("<#block_start_title#>", $dval[$language], $buffer_template_block_start_value_loop);
							$buffer_template_block_start_value_loop		= str_replace("<#block_start_parameter#>", $strParameter, $buffer_template_block_start_value_loop);
							$buffer_template_block_start_value_output  .= $buffer_template_block_start_value_loop;
						}
						$buffer_template_block_start					= \Cms\Service\PageParser::replaceBlock($buffer_template_block_start, "block_start_title_loop", $buffer_template_block_start_title_output);
						$buffer_template_block_start					= \Cms\Service\PageParser::replaceBlock($buffer_template_block_start, "block_start_value_loop", $buffer_template_block_start_value_output);
						
						if (
							in_array($ckey, $aErrorQuestions)
						) {
							$buffer_template_block_start = str_replace("<#question_error#>", '1', $buffer_template_block_start);
							$buffer_template_block_start = str_replace("<#question_error_class#>", 'question_error', $buffer_template_block_start);
						}
												
						$buffer_template_block_output 					.= $buffer_template_block_start;

						// Wenn das nächste Element kein Blockelement mehr ist
						if ($aContent[$aContentCount[$iCount+1]]['template'] != "block_item" or $aContent[$aContentCount[$iCount+1]]['template'] == "" or !isset($aContentCount[$iCount+1])) {
							$buffer_template_block						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_block');
							$buffer_template_block						= str_replace("<#content_elements#>", $buffer_template_block_output, $buffer_template_block);
							$sQuestionBuffer							   .= $buffer_template_block;
							unset($buffer_template_block_output);
						}
					break;

					case "block_item":
						unset($buffer_template_block_item_value_output);
						$buffer_template_block_item						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_block_item');
						$buffer_template_block_item_value				= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'block_item_value_loop');
						$buffer_template_block_item						= str_replace("<#id#>", $id, $buffer_template_block_item);

						if(strpos($title, '|') !== false) {
							list($sTitleLeft, $sTitleRight) = explode('|', $title, 2);
							$buffer_template_block_item = str_replace("<#title#>", $sTitleLeft, $buffer_template_block_item);
							$buffer_template_block_item = str_replace("<#title_right#>", $sTitleRight, $buffer_template_block_item);
						} else {
							$buffer_template_block_item = str_replace("<#title#>", $title, $buffer_template_block_item);
							$buffer_template_block_item = str_replace("<#title_right#>", '', $buffer_template_block_item);
						}

						$buffer_template_block_item						= str_replace("<#description#>", $description, $buffer_template_block_item);
						$buffer_template_block_item						= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_block_item);
						$buffer_template_block_item						= str_replace("<#css#>", $cval['input_css'], $buffer_template_block_item);

						foreach ((array)$cval['data'] as $dkey => $dval) {
							if ($sResult == $dval['value'])	{ $select = 'checked="checked"'; }
							else							{ $select = ""; }
							$strParameter = $cval['parameter'][$dkey];
							$buffer_template_block_item_value_loop		= $buffer_template_block_item_value;
							$buffer_template_block_item_value_loop 		= str_replace("<#block_item_value#>", "<input type=\"radio\" class=\"result_".$cval['id']."\" name=\""."result[".$cval['id']."]"."\" value=\"".$dval['value']."\" title=\"".$dval[$language]."\" ".$select.">", $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_loop		= str_replace("<#block_item_title#>", $dval[$language], $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_loop 		= str_replace("<#block_item_parameter#>", $strParameter, $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_output   .= $buffer_template_block_item_value_loop;
						}
						if (count($cval['data']) == 0) {
							$buffer_template_block_item_value_loop		= $buffer_template_block_item_value;
							$buffer_template_block_item_value_loop 		= str_replace("<#block_item_value#>", "&nbsp", $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_loop		= str_replace("<#block_item_title#>", "&nbsp;", $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_loop 		= str_replace("<#block_item_parameter#>", "colspan=\"100\"", $buffer_template_block_item_value_loop);
							$buffer_template_block_item_value_output   .= $buffer_template_block_item_value_loop;
						}
						$buffer_template_block_item						= \Cms\Service\PageParser::replaceBlock($buffer_template_block_item, "block_item_value_loop", $buffer_template_block_item_value_output);
						
						if (
							in_array($ckey, $aErrorQuestions)
						) {
							$buffer_template_block_item = str_replace("<#question_error#>", '1', $buffer_template_block_item);
							$buffer_template_block_item = str_replace("<#question_error_class#>", 'question_error', $buffer_template_block_item);
						}
						
						$buffer_template_block_output 				    .= $buffer_template_block_item;

						// Wenn das nächste Element kein Blockelement mehr ist
						if ($aContent[$aContentCount[$iCount+1]]['template'] != "block_item" or $aContent[$aContentCount[$iCount+1]]['template'] == "" or !isset($aContentCount[$iCount+1])) {
							$buffer_template_block						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_block');
							$buffer_template_block						= str_replace("<#content_elements#>", $buffer_template_block_output, $buffer_template_block);
							$sQuestionBuffer							   .= $buffer_template_block;
							unset($buffer_template_block_output);
						}
					break;

					case "reference":
						unset($buffer_template_reference_list_output);
						$buffer_template_reference						= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_reference');
						$buffer_template_reference						= str_replace("<#id#>", $id, $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#title#>", $title, $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#description#>", $description, $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#reference_name#>", "result[".$cval['id']."]", $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#reference_class#>", "result_".$cval['id']."", $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#reference_value#>", $sResult, $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#parameter#>", $cval['input_addon'], $buffer_template_reference);
						$buffer_template_reference						= str_replace("<#css#>", $cval['input_css'], $buffer_template_reference);
						$buffer_template_reference_list					= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'reference_list');

						// Holt sich alle Spaltennamen in Anführungszeichen
						preg_match_all("/'(.*?)'/ims", $cval['data']['db_field_text'], $aFields);

						$sQuery = "SELECT * FROM ".$cval['data']['db_table']." ".$cval['data']['db_query'];
						$rQuery = db_query($sQuery);
						while ($aQuery = get_data($rQuery)) {
							if ($sResult == $aQuery['id'])	{ $select = 'selected="selected"'; }
							else							{ $select = ""; }
							foreach ((array)$aFields[1] as $col) {
								$cval['data']['db_field_text'] = preg_replace("/'".$col."'/", $aQuery[$col], $cval['data']['db_field_text']);
							}
							$buffer_template_reference_list_loop		= $buffer_template_reference_list;
							$buffer_template_reference_list_loop		= str_replace("<#reference_item_value#>", $aQuery[$cval['data']['db_field_value']], $buffer_template_reference_list_loop);
							$buffer_template_reference_list_loop		= str_replace("<#reference_item_title#>", $cval['data']['db_field_text'], $buffer_template_reference_list_loop);
							$buffer_template_reference_list_loop		= str_replace("<#selected#>", $select, $buffer_template_reference_list_loop);
							$buffer_template_reference_list_output 	   .= $buffer_template_reference_list_loop;
						}
						$buffer_template_reference						= \Cms\Service\PageParser::replaceBlock($buffer_template_reference, "reference_list", $buffer_template_reference_list_output);
						$sQuestionBuffer								   .= $buffer_template_reference;
					break;

					case "slider":
						$buffer_template_slider							= \Cms\Service\PageParser::checkForBlock($element_data['content'], 'template_slider');
						$buffer_template_slider							= str_replace("<#id#>", $id, $buffer_template_slider);
						$buffer_template_slider							= str_replace("<#title#>", $title, $buffer_template_slider);
						$buffer_template_slider							= str_replace("<#description#>", $description, $buffer_template_slider);
						$buffer_template_slider							= str_replace("<#static_value#>", $sResult, $buffer_template_slider);
						$sQuestionBuffer								   .= $buffer_template_slider;
					break;

				}
			}

			if (
				(
					$cval['template'] != 'block_start' &&
					$cval['template'] != 'block_item'
				) &&
				in_array($ckey, $aErrorQuestions)
			)	{
				$sQuestionBuffer = str_replace("<#content_element#>", $sQuestionBuffer, $buffer_error_code);
				$sQuestionBuffer = str_replace("<#question_error#>", '1', $sQuestionBuffer);
				$sQuestionBuffer = str_replace("<#question_error_class#>", 'question_error', $sQuestionBuffer);
			} else {
				$sQuestionBuffer = str_replace("<#question_error#>", '', $sQuestionBuffer);
				$sQuestionBuffer = str_replace("<#question_error_class#>", '', $sQuestionBuffer);
			}
	
			$buffer_content .= $sQuestionBuffer;
			
			$iCount++;
		}
		// Block
		$buffer_block_loop = $buffer_block;
		$buffer_block_loop = str_replace("<#block_title#>", $pval[$language.'_title'], $buffer_block_loop);
		$buffer_block_loop = str_replace("<#block_description#>", $pval[$language.'_description'], $buffer_block_loop);
		$buffer_block_loop = str_replace("<#block_content#>", $buffer_content, $buffer_block_loop);
		$buffer_block_loop = str_replace("<#block_label_width#>", $pval['label_width'], $buffer_block_loop);
		$buffer_block_output .= $buffer_block_loop;
	}

	// Statusbalken
	$intLeft = 0;
	if($sPages != 0)
	{
		$intLeft = intval($_VARS['idPage'] * 100 / $sPages);
	}
	$buffer = str_replace("<#progress#>", $intLeft, $buffer);

	// Bezeichnungen für Fehler, Seite weiter und Seitenzähler
	$aPollDetails = get_data(db_query("SELECT * FROM poll_init WHERE id = ".(int)$poll_id.""));
	$aPollDetails[$language.'_data'] = Util::decodeSerializeOrJson($aPollDetails[$language.'_data']);
	$aPollDetails[$language.'_data']['page'] = str_replace("&lt;#", "<#", $aPollDetails[$language.'_data']['page']);
	$aPollDetails[$language.'_data']['page'] = str_replace("#&gt;", "#>", $aPollDetails[$language.'_data']['page']);
	$aPollDetails[$language.'_data']['page'] = str_replace("<#current#>", $_VARS['idPage'], $aPollDetails[$language.'_data']['page']);
	$aPollDetails[$language.'_data']['page'] = str_replace("<#total#>", $sPages, $aPollDetails[$language.'_data']['page']);

	if(!empty($aErrorMessages)) {
		$buffer_error_message = str_replace("<#error_message_title#>", implode("<br>", $aErrorMessages), $buffer_error_message);
	} else {
		$buffer_error_message = str_replace("<#error_message_title#>", $aPollDetails[$language.'_data']['error'], $buffer_error_message);
	}
	
	$buffer = str_replace("<#count_page_title#>", $aPollDetails[$language.'_data']['page'], $buffer);

	$buffer = str_replace("<#block_elements#>", $buffer_block_output, $buffer);

	// Formtags
	$buffer_error = $buffer_error_message;
	$buffer_header = "<form name=\"poll\" method=\"post\" action=\"".idtopath($page_data['id'], $page_data['language'])."\">
					  <input type=\"hidden\" name=\"poll_id\" value=\"".(int)$poll_id."\" />
					  <input type=\"hidden\" name=\"idPage\" value=\"".(int)$_VARS['idPage']."\" />
					  <input type=\"hidden\" name=\"loop\" value=\"".(int)$_VARS['loop']."\" />
					  <input type=\"hidden\" name=\"tan\" value=\"".$tan."\" />
					  <input type=\"hidden\" name=\"task\" value=\"saveStats\" />
					  <input type=\"hidden\" name=\"action\" value=\"nextPage\" />";

	$buffer_footer = "</form>";

	// Fehleranzeige
	if (count($aErrorQuestions) != 0) {
		$buffer_header = $buffer_error . $buffer_header;
	}

	// Nächste Seite
	$buffer_next_page = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'next_page');
	$buffer_next_page = str_replace("<#next_page_title#>", $aPollDetails[$language.'_data']['next'], $buffer_next_page);

	$buffer_last_page = \Cms\Service\PageParser::checkForBlock($element_data['content'], 'last_page');
	$buffer_last_page = str_replace("<#last_page_title#>", $aPollDetails[$language.'_data']['last'], $buffer_last_page);

	if ($sPages > $_VARS['idPage']) {

		$buffer_next_page_link = $buffer_next_page;

	} else {

		// poll is ready, generate new session id and set complete flag
		$buffer_next_page_link = "";

		// Report-ID zu dem TAN merken um das PDF abrufen zu können. Dadurch das hiernach überall die Report-ID aus
        // der Session gelöscht wird, muss die ID irgendwo noch vorhanden sein um die Werte zu laden.
		$_SESSION['pdf'][$tan] = $_SESSION['poll'][$poll_id]['report_id'];

		$oPoll->setCompleted($_SESSION['poll'][$poll_id]['report_id'], $_VARS['result'], $aReport);

	}

	if($_VARS['idPage'] > 1) {
		$buffer_last_page_link = $buffer_last_page;
	} else {
		$buffer_last_page_link = "";
	}

	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "last_page", $buffer_last_page_link);
	$buffer = \Cms\Service\PageParser::replaceBlock($buffer, "next_page", $buffer_next_page_link);

	$buffer = $buffer_header . $buffer . $buffer_footer;
	
	unset($noNextPage);
	
	$oPoll->replacePlaceholderQuestions($buffer, $aPlaceholderQuestions, $language);
	
	echo $buffer;

	$sJs = $oConditionHelper->getQuestionJs($aReport);
	
	if(!empty($sJs)) {
		echo $sJs;
	}
	
}
