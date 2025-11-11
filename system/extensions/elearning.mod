<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(isset($oConfig->exam_id)) {

	System::wd()->executeHook('elearning', $oConfig);

	$oSmarty = new \Cms\Service\Smarty();

	$oExam = new Ext_Elearning_Exam($oConfig->exam_id);
	$bLanguage = $oExam->setLanguage($page_data['language']);

	// Wenn es den Test nicht in der Sprache gibt, wird weitergeleitet
	if($bLanguage === false) {
		$aLanguages = $oExam->getLanguages();
		$sLanguage = reset($aLanguages);
		$sTarget = idtopath($page_data['id'], $sLanguage);
		header("Location: ".$sTarget);
	}

	$bErrorNoAnswer = 0;
	$bErrorWrongAnswer = null;
	$aErrorQuestions = array();
	$iStep = $_SESSION['elearning']['exam']['step'];
	$sShow = 'exam';

	$bSetSession = false;
	
	$oResult = new Ext_Elearning_Exam_Result($oConfig->exam_id, $_SESSION['elearning']['exam']['result_id']);
	$oResult->setLanguage($page_data['language']);

	// Inhaltscache prüfen
	if($_SESSION['elearning']['exam']['language'] != $page_data['language']) {
		$_SESSION['elearning']['exam']['content'] = array();
		$_SESSION['elearning']['exam']['language'] = $page_data['language'];
	}

	if(
		$oExam->comment_result &&
		$_VARS['task'] == 'comment'
	) {

		$oExam->saveComment($_VARS['comment']);

		$sShow = 'comment';

	} elseif($_VARS['task'] == 'next') {

		foreach((array)$_SESSION['elearning']['exam']['lastquestions'] as $aGroup) {

			foreach((array)$aGroup['questions'] as $aQuestion) {

				if($aQuestion['type'] != 'only_text') {

					$bCheckQuestion = $oResult->checkQuestion($aQuestion['id']);

					/**
					 * Beantwortung einer Frage nur einmal erlauben wenn der 
					 * Fehler mit korrekter Antwort angezeigt wird 
					 * WENN NOCH KEINE ANTWORT ODER FEHLER NICHT ANZEIGEN
					 */
					if(
						is_null($bCheckQuestion) ||
						!$oExam->show_error_in_question
					) {

						// check for answer
						if(empty($_VARS['result'][$aQuestion['id']])) {
							$aErrorQuestions[] = $aQuestion['id'];
							$bErrorNoAnswer = 1;
						// save result
						} else {
							// save result
							$bSuccess = $oResult->saveResultData($aQuestion['id'], $_VARS['result'][$aQuestion['id']]);
							if(!$bSuccess) {
								$bErrorWrongAnswer = 1;
							} else {
								$bErrorWrongAnswer = 0;
							}
						}

					}

				}

			}

		}

		// WENN ANTWORT GEGEBEN WURDE
		if($bErrorNoAnswer === 0) {
			
			// WENN 
			//		ANTWORT RICHTIG UND RICHTIG MELDUNG NICHT ANZEIGEN 
			//			ODER 
			//		ANTWORT FALSCH UND FEHLER NICHT ANZEIGEN 
			//			ODER
			//		ANTWORT NICHT BEARBEITET
			if(
				(
					$bErrorWrongAnswer === 0 &&
					!$oExam->show_success_in_question
				) ||
				(
					$bErrorWrongAnswer === 1 &&
					!$oExam->show_error_in_question
				) ||
				$bErrorWrongAnswer === null
			) {
				$iStep++;
			}

		}

	} elseif($_VARS['task'] == 'back') {

		$iStep--;

	} elseif($_VARS['task'] == 'reload') {

		$iStep = $iStep;

	} elseif($_VARS['task'] == 'start') {

		$iStep = 0;

	} else {

		if(
			strip_tags($oExam->intro) != ''
		) {
			$sShow = 'intro';
		} else {
			$iStep = 0;
		}

	}

	// Sicherheitsabfrage
	if($iStep < 0) {
		$iStep = 0;
	}
	
	//check if exam is active
	if(
		$oExam->isActiveAndRunning() === true &&
		(
			!$oExam->closed ||
			$user_data['id'] > 0
		)
	) {
	
		$bActive = 1;

	} else {

		$bActive = 0;

	}
	
	if(
		$oResult->checkFinished()
	) {
		$bFinished = 1;
	} else {
		$bFinished = 0;
	}

	if(
		$bActive &&
		!$bFinished &&
		$sShow != 'comment'
	) {

		$aGroups = $oExam->getGroups();

		$aReturnGroups = array();

		if($oExam->display == 'all') {
			
		} elseif($oExam->display == 'one') {

			$iTotal = 0;
			$iCount = 0;
			$iEmpty = 1;
			$bBreak = 0;
			foreach((array)$aGroups as $aGroup) {

				$oGroup = new Ext_Elearning_Exam_Group($aGroup['id']);
				$oGroup->setLanguage($page_data['language']);
				$aData = $oGroup->getData();
				$aQuestions = $oGroup->getQuestions();
				
				$iTotal += count($aQuestions);
				
				foreach((array)$aQuestions as $aQuestion) {
					
					if(
						$iStep == $iCount
					) {

						$oQuestion = new Ext_Elearning_Exam_Question($aQuestion['id']);
						$oQuestion->setLanguage($page_data['language']);
						$aQuestionData = $oQuestion->getData();
						$bCheckQuestion = $oResult->checkQuestion($aQuestion['id']);

						if($bCheckQuestion === false) {
							$aQuestionData['wrong'] = true;
						} elseif($bCheckQuestion === true) {
							$aQuestionData['correct'] = true;
						}

						if(
							!is_null($bCheckQuestion) &&
							$oExam->show_error_in_question
						) {
							$aQuestionData['disabled'] = true;
						}

						$aQuestionData['answers'] = $oQuestion->getAnswers();
						$oResult->markCheckedAnswers($aQuestionData['answers']);

						// Interne Bezüge ersetzen
						$aAnswerIndex = array();
						$aQuestionData['count_correct_answers'] = 0;
						foreach((array)$aQuestionData['answers'] as $iAnswer=>$aAnswer) {
							if($aAnswer['correct'] == 1) {
								$aQuestionData['count_correct_answers']++;
							}
							$aAnswerIndex[$aAnswer['name']] = $iAnswer;
						}
						foreach((array)$aAnswerIndex as $sAnswer=>$sIndex) {
							$aQuestionData['description'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aQuestionData['description']);
							foreach((array)$aQuestionData['answers'] as $iAnswer=>$aAnswer) {
								$aQuestionData['answers'][$iAnswer]['answer'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aQuestionData['answers'][$iAnswer]['answer']);
							}
						}

						$aData['questions'][] = $aQuestionData;
						$bBreak = 1;
						$iEmpty = 0;
						break;
					}
					$iCount++;
				}

				$aReturnGroups[] = $aData;
				
				if($bBreak) {
					break;
				}
			}

			// set state at first view
			if($iStep == 0) {
				if($user_data['id'] > 0) {
					$oParticipant = $oExam->getParticipant($user_data['id']);
					$oParticipant->setState('started');
				}
			}

			// show result page
			if($iEmpty) {

				$iFailed = $oResult->setFinished();
				$iMaximumScore = $oResult->getMaximumScore();
				$iMinimumScore = $oResult->getMinimumScore();
				$iResultScore = $oResult->getResultScore();
				$bSucceeded = $oResult->checkResult();
				
				if($oExam->show_answers > 0) {

					if($oExam->show_answers == 2) {
						$aWrongQuestions = $oResult->getQuestions(false);
					} else {
						$aWrongQuestions = $oResult->getQuestions(true);
					}

					// Interne Bezüge ersetzen
					foreach((array)$aWrongQuestions as $iKey=>$aQuestion) {
						$oQuestion = new Ext_Elearning_Exam_Question($aQuestion['id']);
						$oQuestion->setLanguage($page_data['language']);

						$aAnswers = $aQuestion['answers'];

						$aAnswerIndex = array();
						foreach((array)$aAnswers as $iAnswer=>$aAnswer) {
							$aAnswerIndex[$aAnswer['name']] = $iAnswer;
						}
						foreach((array)$aAnswerIndex as $sAnswer=>$sIndex) {

							$aWrongQuestions[$iKey]['description'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aWrongQuestions[$iKey]['description']);

							foreach((array)$aWrongQuestions[$iKey]['answers'] as $iAnswer=>$aAnswer) {
								$aWrongQuestions[$iKey]['answers'][$iAnswer]['answer'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aAnswer['answer']);	
							}

							foreach((array)$aWrongQuestions[$iKey]['correct_answers'] as $iAnswer=>$aAnswer) {
								$aWrongQuestions[$iKey]['correct_answers'][$iAnswer]['answer'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aAnswer['answer']);
							}

							foreach((array)$aWrongQuestions[$iKey]['wrong_answers'] as $iAnswer=>$aAnswer) {
								$aWrongQuestions[$iKey]['wrong_answers'][$iAnswer]['answer'] = str_replace('{INDEX|'.$sAnswer.'}', ($sIndex+1), $aAnswer['answer']);
							}

						}
					}

				}

				// Kommentarfeld anzeigen
				if($oExam->comment_result) {
					$oSmarty->assign('bShowCommentField', true);
				}

				// PDF mit den falschen Antworten generieren
				if($oExam->show_result_pdf) {
					$oPdf = $oExam->getPdf();
					$sResultPdfPath = $oPdf->generateResultPdf($oResult, $aWrongQuestions);
					$sResultPdfPath = str_replace(\Util::getDocumentRoot(), '', $sResultPdfPath);
					$aPathInfo = pathinfo($sResultPdfPath);
					$sSecureAccess = str_replace('/media/', '', $aPathInfo['dirname']);
					$_SESSION['access']['media']['secure'][$sSecureAccess.'/'][$aPathInfo['basename']] = 1;
					$oSmarty->assign('sResultPdfPath', $sResultPdfPath);
				}

				$oSmarty->assign('aWrongQuestions', $aWrongQuestions);
				$oSmarty->assign('iMaximumScore', $iMaximumScore);
				$oSmarty->assign('iMinimumScore', $iMinimumScore);
				$oSmarty->assign('iResultScore', $iResultScore);
				$oSmarty->assign('iFailed', $iFailed);
				$oSmarty->assign('bSucceeded', $bSucceeded);

				$sShow = 'result';

				// Session leeren um neu anzufangen
				$_SESSION['elearning']['exam'] = array();

			// Wenn nicht letzte Seite, Session setzen
			} else {

				// save last page in session for checking
				$_SESSION['elearning']['exam']['result_id'] = $oResult->id;
				$_SESSION['elearning']['exam']['step'] = $iStep;
				$_SESSION['elearning']['exam']['lastquestions'] = $aReturnGroups;

			}

		} elseif($oExam->display == 'group') {

		}

	}

	$oSmarty->assign('iTotal', $iTotal);
	$oSmarty->assign('iStep', $iStep);
	$oSmarty->assign('sShow', $sShow);
	$oSmarty->assign('aErrorQuestions', $aErrorQuestions);
	$oSmarty->assign('bErrorNoAnswer', $bErrorNoAnswer);
	$oSmarty->assign('aGroups', $aReturnGroups);
	$oSmarty->assign('bActive', $bActive);
	$oSmarty->assign('bFinished', $bFinished);
	$oSmarty->assign('aExam', $oExam->getData());
	
	$oSmarty->displayExtension($element_data);

}
