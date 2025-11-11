<?php

namespace Poll\Helper;

class SmartyHelper {

	/**
	 * @var \Ext_Poll_Poll
	 */
	protected $_oPoll;
	protected $_sLanguage;
	protected $_aTemplateData = array();
	protected $_aResult = array();
	protected $_aErrorMessages = array();
	protected $_aErrorQuestions = array();
	protected $_sTan;
	protected $_sRestartHash;
	protected $_iPageId;
	protected $_iVisits;
	protected $_sJs;
	protected $_bPdf = false;

	public function __construct($oPoll) {
		$this->_oPoll = $oPoll;
	}

	public function setLanguage($sLanguage) {
		$this->_sLanguage = $sLanguage;
	}

	public function setTemplateData($aElementData) {
		$this->_aTemplateData = $aElementData;
	}

	public function setResult(array $aResult) {

		foreach($aResult as $mkey=>&$mValue) {
			$mValue = \Util::decodeSerializeOrJson($mValue);
		}

		$this->_aResult = $aResult;
	}

	public function setErrorMessages($aErrorMessages) {
		$this->_aErrorMessages = $aErrorMessages;
	}

	public function setErrorQuestions($aErrorQuestions) {
		$this->_aErrorQuestions = $aErrorQuestions;
	}

	public function setTan($sTan) {
		$this->_sTan = $sTan;
	}

	public function setRestartHash($sHash) {
		$this->_sRestartHash = $sHash;
	}

	public function setPageId($iPageId) {
		$this->_iPageId = $iPageId;
	}

	public function setVisits($iVisits) {
		$this->_iVisits = $iVisits;
	}

	public function setPdf($bPdf) {
		$this->_bPdf = $bPdf;
	}

	public function generate($aParagraphs, $iReportId) {
		global $_VARS, $bForward;

		$poll_id = $this->_oPoll->id;

		$oSmarty = new \Cms\Service\Smarty();

		$oConditionHelper = new \Poll\Helper\ConditionHelper;

		$iPages = $this->_oPoll->countPages();

		$aItems = $aParagraphs;
		$aParagraphs = array();

		$aPlaceholderQuestions = array();

		$iCount = 0;
		foreach ((array)$aItems as $iParagraphId => $aParagraph) {

			$aParagraphs[$iParagraphId] = $aParagraph;

			$aQuestions = array();
			$resContent = \DB::getQueryRows("SELECT * FROM poll_questions WHERE idPoll = ".(int)$this->_oPoll->id." AND idParagraph = ".(int)$iParagraphId." ORDER BY position");
			foreach($resContent as $aresContent) {
				$aresContent['data'] = \Util::decodeSerializeOrJson($aresContent['data']);
				$aresContent['parameter'] = \Util::decodeSerializeOrJson($aresContent['parameter']);
				$aresContent['route'] = \Util::decodeSerializeOrJson($aresContent['route']);
				$aresContent['css_value'] = \Util::decodeSerializeOrJson($aresContent['css_value']);
				$aQuestions[$aresContent['id']] = $aresContent;
				$aQuestionsCount[] = $aresContent['id'];
			}

			if($aParagraph['sorting'] === 'rotation') {

				$aQuestionGroups = [];

				foreach($aQuestions as $iQuestionId=>$aQuestion) {
					$aQuestionGroups[$aQuestion['rotation_parameter']][$iQuestionId] = $aQuestion;
				}

				unset($aQuestions);

				shuffle($aQuestionGroups);

				$aQuestions = [];

				foreach($aQuestionGroups as $aQuestionGroup) {
					foreach($aQuestionGroup as $iQuestionId=>$aQuestionGroupQuestion) {
						$aQuestions[$iQuestionId] = $aQuestionGroupQuestion;
					}
				}

				// Typ Blockanfang und Blockelement korrigieren
				$aFirstQuestion = reset($aQuestions);

				if($aFirstQuestion['template'] === 'block_item') {
					$iIndex = 0;
					foreach($aQuestions as &$aQuestion) {
						if($iIndex === 0) {
							$aQuestion['template'] = 'block_start';
						} else {
							$aQuestion['template'] = 'block_item';
						}
						$iIndex++;
					}
					unset($aQuestion);
				}

			}

			// Content
			foreach ((array)$aQuestions as $iQuestionId => $aQuestion) {

				$oConditionHelper->handleQuestionCondition($aQuestion);

				$aQuestion['title'] = $aQuestion[$this->_sLanguage.'_title'];
				$aQuestion['description'] = $aQuestion[$this->_sLanguage.'_description'];

				if(
					!isset($aQuestion['data']) ||
					$aQuestion['data'] == 'null'
				) {
					$aQuestion['data'] = array();
				}

				if(
					!isset($aQuestion['parameter']) ||
					$aQuestion['parameter'] == 'null'
				) {
					$aQuestion['parameter'] = array();
				}

				// Sortierung nach Werten
				if (is_array($aQuestion['data'])) {
					if($aQuestion['template'] === 'stars') {
						if ($aQuestion['data']['options'][1]['position'] != "") {
							uasort($aQuestion['data']['options'], array('Ext_Poll_Poll', "sortDataByPosition"));
						} else {
							uasort($aQuestion['data']['options'], array('Ext_Poll_Poll', "sortDataByValue"));
						}
					} else {
						if ($aQuestion['data'][1]['position'] != "") {
							uasort($aQuestion['data'], array('Ext_Poll_Poll', "sortDataByPosition"));
						} else {
							uasort($aQuestion['data'], array('Ext_Poll_Poll', "sortDataByValue"));
						}
					}
				}

				if (in_array($iQuestionId, $this->_aErrorQuestions))	{
					$aQuestion['error'] = true;
				}

				if (
					!isset($_VARS['result'][$iQuestionId]) &&
					!isset($_VARS['r'.$iQuestionId])
				) {
					$sResult = $this->_aResult['f_'.$iQuestionId];
				} else {
					if ($_VARS['r'.$iQuestionId] != "") {
						$sResult = $_VARS['r'.$iQuestionId];
					} else {
						$sResult = $_VARS['result'][$iQuestionId];
					}
				}

				$aQuestion['value'] = $sResult;

				if ($aQuestion['template'] == "reference") {
					preg_match_all("/'(.*?)'/ims", $aQuestion['data']['db_field_text'], $aFields);
					$sSql = "SELECT * FROM ".$aQuestion['data']['db_table']." ".$aQuestion['data']['db_query'];
					$aOptions = DB::getQueryRows($sSql);

					foreach((array)$aOptions as $aOption) {
						$aItem['value'] = $aOption[$aQuestion['data']['db_field_value']];
						$aItem[$this->_sLanguage] = $aOption[$aQuestion['data']['db_field_text']];
						$aQuestion['data'][] = $aItem;
					}
				}

				$aQuestion['name'] = "result[".$aQuestion['id']."]";
				$aQuestion['class'] = "result_".$aQuestion['id']."";

				$bOptionField = \Poll\Entity\Question::checkOptionField($aQuestion['template']);
				if($bOptionField === true) {
					foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
						if(!isset($aQuestion['data'][$iOptionKey])) {
							$aQuestion['data'][$iOptionKey] = array();
						}
						if(!empty($aQuestion['parameter'][$iOptionKey])) {
							$aQuestion['data'][$iOptionKey]['parameter'] = $aQuestion['parameter'][$iOptionKey];
						}
						if(!empty($aQuestion['css_value'][$iOptionKey])) {
							$aQuestion['data'][$iOptionKey]['css'] = $aQuestion['css_value'][$iOptionKey];
						}
					}
				}

				if ($aQuestion['hidden'] == 1) {

				} elseif ($aQuestion['hidden'] == 2) {

				} elseif ($aQuestion['hidden'] == 3) {

					if(
						empty($sResult) &&
						isset($_SESSION['aTracking']['f_'.$aQuestion['id']]) &&
						!isset($_VARS['result'][$aQuestion['id']])
					) {

						$sResult = \Util::decodeSerializeOrJson($_SESSION['aTracking']['f_'.$aQuestion['id']]);
						$aQuestion['value'] = $sResult;
					}

					$aPlaceholderQuestions[$aQuestion['id']] = $aQuestion;

					// Platzhalterfragen überspringen
					continue;
				} else {

					if(
						empty($sResult) &&
						isset($_SESSION['aTracking']['f_'.$iQuestionId]) &&
						!isset($_VARS['result'][$iQuestionId])
					) {

						$sResult = \Util::decodeSerializeOrJson($_SESSION['aTracking']['f_'.$iQuestionId]);
						$aQuestion['value'] = $sResult;
					}

					$extractImage = function($string) {
						if(strpos($string, '<img') !== false) {

							$pattern = '/<img\s*(?:class\s*\=\s*[\'\"](.*?)[\'\"].*?\s*|src\s*\=\s*[\'\"](.*?)[\'\"].*?\s*|alt\s*\=\s*[\'\"](.*?)[\'\"].*?\s*|width\s*\=\s*[\'\"](.*?)[\'\"].*?\s*|height\s*\=\s*[\'\"](.*?)[\'\"].*?\s*)+.*?>/si';
							preg_match_all($pattern, $string, $matches);

							if(isset($matches[0][0])) {
								$position = (strpos($string, $matches[0][0]) === 0) ? 'left' : 'right';
								return [$matches[0][0], $position];
							}
						}

						return [];
					};

					switch ($aQuestion['template']) {
						case "static":

							break;

						case "text":

							$_SESSION['poll'][$poll_id]['cms_edit_mode'];

							break;

						case "textarea":

							break;

						case "select":

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}
								if ($sResult == $aOption['value'])	{
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}
								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];
							}

							break;

						case "list":

							$aQuestion['name'] = "result[".$aQuestion['id']."][]";

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}

								if(
									$sResult == $aOption['value'] ||
									in_array($aOption['value'], (array)$sResult)
								) {
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}

								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];

							}

							break;

						case "radio":

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}

								if(
									$sResult == $aOption['value'] ||
									in_array($aOption['value'], (array)$sResult)
								) {
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}

								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];
								$aQuestion['data'][$iOptionKey]['name'] = "result[".$aQuestion['id']."]";
								$aQuestion['data'][$iOptionKey]['class'] = "result_".$aQuestion['id'];

							}

							break;

						case "check":

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}

								if(
									$sResult == $aOption['value'] ||
									in_array($aOption['value'], (array)$sResult)
								) {
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}

								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];
								$aQuestion['data'][$iOptionKey]['name'] = "result[".$aQuestion['id']."][]";
								$aQuestion['data'][$iOptionKey]['class'] = "result_".$aQuestion['id'];

							}

							break;

						case "matrix":

							$aQuestionAnswerGroups = &$aQuestion['data']['answer_groups'];
							$aQuestionQuestions = &$aQuestion['data']['questions'];

							if(is_array($aQuestionAnswerGroups)) {
								foreach($aQuestionAnswerGroups as &$aQuestionAnswerGroup) {
									$aQuestionAnswerGroup['title'] = $aQuestionAnswerGroup['name_'.$this->_sLanguage];
									$aQuestionAnswerGroup['cols'] = 1;
									if(is_array($aQuestionAnswerGroup['options'])) {

										$aQuestionAnswerGroup['cols'] = count($aQuestionAnswerGroup['options']);
										foreach($aQuestionAnswerGroup['options'] as &$aQuestionAnswerGroupOption) {
											$aQuestionAnswerGroupOption['title'] = $aQuestionAnswerGroupOption['name_'.$this->_sLanguage];

											foreach($aQuestionQuestions as $aQuestionQuestion) {
												if(
													is_array($sResult) &&
													(
														$sResult[$aQuestionQuestion['key']][$aQuestionAnswerGroup['key']] == $aQuestionAnswerGroupOption['value'] ||
														in_array($aQuestionAnswerGroupOption['value'], (array)$sResult[$aQuestionQuestion['key']][$aQuestionAnswerGroup['key']])
													)
												) {
													$aQuestionAnswerGroupOption['selected'][$aQuestionQuestion['key']] = true;
												}
											}

										}
									}
								}
							}

							// Referenzen löschen
							unset($aQuestionAnswerGroup);
							unset($aQuestionAnswerGroupOption);

							if(is_array($aQuestionQuestions)) {
								foreach($aQuestionQuestions as &$aQuestionQuestion) {
									$aQuestionQuestion['title'] = $aQuestionQuestion['name_'.$this->_sLanguage];
								}
							}

							// Referenzen löschen
							unset($aQuestionQuestion);

							// Bei diesem Typ muss der Value immer ein Array sein
							$aQuestion['value'] = (array)$aQuestion['value'];

							break;

						case "stars":

							$aCheckedPosition = array_filter($aQuestion['data']['options'], function($aOption) use ($aQuestion){
								return ($aOption['value'] == $aQuestion['value']);
							});

							if(!empty($aCheckedPosition)) {

								$aCheckedPosition = reset($aCheckedPosition);

								foreach($aQuestion['data']['options'] as $iIndex => $aOption) {
									if((int)$aOption['position'] <= (int)$aCheckedPosition['position']) {
										$aQuestion['data']['options'][$iIndex]['checked'] = 1;
									}
								}
							}

							break;

						case "block_start":

							if(strpos($aQuestion['title'], '|') !== false) {
								list($aQuestion['title'], $aQuestion['title_right']) = explode('|', $aQuestion['title'], 2);
								$aQuestion['block_head_colspan']++;
							} else {
								$aQuestion['title_right'] = '';
							}

							$sBlockHeadTest = strip_tags($aQuestion[$this->_sLanguage.'_block_head']);

							if(!empty($sBlockHeadTest)) {
								$aQuestion['block_head_colspan'] = count($aQuestion['data']);
								$aQuestion['block_head'] = $aQuestion[$this->_sLanguage.'_block_head'];
							} else {
								$aQuestion['block_head'] = false;
							}

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}

								if(
									$sResult == $aOption['value'] ||
									in_array($aOption['value'], (array)$sResult)
								) {
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}

								[$sImage, $sImagePosition] = $extractImage($aOption[$this->_sLanguage]);

								if(!empty($sImage)) {
									$aQuestion['hasImages'] = true;
									$aOption[$this->_sLanguage] = str_replace($sImage,'', $aOption[$this->_sLanguage]);
								}

								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];
								$aQuestion['data'][$iOptionKey]['image'] = $sImage;
								$aQuestion['data'][$iOptionKey]['image_pos'] = $sImagePosition;
								$aQuestion['data'][$iOptionKey]['name'] = "result[".$aQuestion['id']."]";
								$aQuestion['data'][$iOptionKey]['class'] = "result_".$aQuestion['id'];

							}

							// Wenn das nächste Element kein Blockelement mehr ist
							if (
								$aQuestions[$aQuestionsCount[$iCount+1]]['template'] != "block_item" or
								$aQuestions[$aQuestionsCount[$iCount+1]]['template'] == "" or
								!isset($aQuestionsCount[$iCount+1])
							) {
								$aQuestion['last_block'] = true;
							}

							break;

						case "block_item":

							if(strpos($aQuestion['title'], '|') !== false) {
								list($aQuestion['title'], $aQuestion['title_right']) = explode('|', $aQuestion['title'], 2);
								$aQuestion['block_head_colspan']++;
							} else {
								$aQuestion['title_right'] = '';
							}

							foreach ((array)$aQuestion['data'] as $iOptionKey => $aOption) {
								// Gibt ein leeres Element zur�ck (als vorselektiertes Element bei Dropdowns)
								if ($aOption[$this->_sLanguage] == "DEFAULT") {
									$aOption[$this->_sLanguage] = "";
								}

								if(
									$sResult == $aOption['value'] ||
									in_array($aOption['value'], (array)$sResult)
								) {
									$aQuestion['data'][$iOptionKey]['selected'] = true;
								}

								[$sImage, $sImagePosition] = $extractImage($aOption[$this->_sLanguage]);

								if(!empty($sImage)) {
									$aQuestion['hasImages'] = true;
									$aOption[$this->_sLanguage] = str_replace($sImage,'', $aOption[$this->_sLanguage]);
								}

								$aQuestion['data'][$iOptionKey]['text'] = $aOption[$this->_sLanguage];
								$aQuestion['data'][$iOptionKey]['image'] = $sImage;
								$aQuestion['data'][$iOptionKey]['image_pos'] = $sImagePosition;
								$aQuestion['data'][$iOptionKey]['name'] = "result[".$aQuestion['id']."]";
								$aQuestion['data'][$iOptionKey]['class'] = "result_".$aQuestion['id'];

							}

							// Wenn das nächste Element kein Blockelement mehr ist
							if (
								$aQuestions[$aQuestionsCount[$iCount+1]]['template'] != "block_item" ||
								$aQuestions[$aQuestionsCount[$iCount+1]]['template'] == "" ||
								!isset($aQuestionsCount[$iCount+1])
							) {
								$aQuestion['last_block'] = true;
							}
							break;

						case "reference":

							break;

						case "slider":

							break;

					}
				}

				$iCount++;

				$aParagraphs[$iParagraphId]['questions'][$iQuestionId] = $aQuestion;

			}

			$aParagraphs[$iParagraphId]['title'] = $aParagraphs[$iParagraphId][$this->_sLanguage.'_title'];
			$aParagraphs[$iParagraphId]['description'] = $aParagraphs[$iParagraphId][$this->_sLanguage.'_description'];

		}

		// Statusbalken
		$intLeft = 0;
		if($iPages != 0) {
			$intLeft = intval($_VARS['idPage'] * 100 / $iPages);
		}
		$iProgress = (int)$intLeft;

		// Bezeichnungen für Fehler, Seite weiter und Seitenzähler
		$aPollDetails = \DB::getQueryRow("SELECT * FROM poll_init WHERE id = '".(int)$poll_id."'");
		$aPollConfig = \Util::decodeSerializeOrJson($aPollDetails[$this->_sLanguage.'_data']);
		$aPollConfig['page'] = str_replace("&lt;#", "<#", $aPollConfig['page']);
		$aPollConfig['page'] = str_replace("#&gt;", "#>", $aPollConfig['page']);
		$aPollConfig['page'] = str_replace("<#current#>", $_VARS['idPage'], $aPollConfig['page']);
		$aPollConfig['page'] = str_replace("<#total#>", $iPages, $aPollConfig['page']);

		$iCurrentPage = (int)$_VARS['idPage'];
		$iTotalPages = (int)$iPages;

		// Fehleranzeige
		$bError = false;
		if (count($this->_aErrorQuestions) != 0) {
			$bError = true;
		}

		if ($iPages > $_VARS['idPage']) {

			$bForward = true;

		} else {

			$bForward = false;

			// Report-ID zu dem TAN merken um das PDF abrufen zu können. Dadurch das hiernach überall die Report-ID aus
			// der Session gelöscht wird, muss die ID irgendwo noch vorhanden sein um die Werte zu laden.
			$_SESSION['pdf'][$this->_sTan] = $iReportId;

			// poll is ready, generate new session id and set complete flag
			$this->_oPoll->setCompleted($iReportId, $_VARS['result'], $this->_aResult);

		}

		if($_VARS['idPage'] > 1) {
			$bBack = true;
		} else {
			$bBack = false;
		}

		$this->_sJs = $oConditionHelper->getQuestionJs($this->_aResult);

		$oSmarty->assign('aParagraphs', $aParagraphs);

		$oSmarty->assign('aPollConfig', $aPollConfig);

		$oSmarty->assign('iCurrentPage', $iCurrentPage);
		$oSmarty->assign('iTotalPages', $iTotalPages);

		$oSmarty->assign('bError', $bError);
		$oSmarty->assign('aErrorMessages', $this->_aErrorMessages);

		$oSmarty->assign('bBack', $bBack);
		$oSmarty->assign('bForward', $bForward);

		$oPage = \Cms\Entity\Page::getInstance($this->_iPageId);

		$oSmarty->assign('sFormAction', $oPage->getLink($this->_sLanguage));
		$oSmarty->assign('iPollId', (int)$poll_id);
		$oSmarty->assign('iPageId', (int)$_VARS['idPage']);
		$oSmarty->assign('iLoop', (int)$_VARS['loop']);
		$oSmarty->assign('tan', $this->_sTan);
		$oSmarty->assign('sRestartHash', $this->_sRestartHash);

		$oSmarty->assign('iProgress', $iProgress);
		$oSmarty->assign('sLanguage', $this->_sLanguage);

		$oSmarty->assign('iVisits', $this->_iVisits);

		$oSmarty->assign('bPdf', $this->_bPdf);

		$sCode = $oSmarty->displayExtension($this->_aTemplateData, false);

		// Platzhalterfragen ersetzen
		$this->_oPoll->replacePlaceholderQuestions($sCode, $aPlaceholderQuestions, $this->_sLanguage);

		return $sCode;
	}

	public function getJs() {
		return $this->_sJs;
	}

}
