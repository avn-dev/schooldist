<?php

class Ext_Elearning_Exam_Result {
	
	protected $_aResult = array();
	protected $_sLanguage = false;
	
	public function __construct($iExamId, $iResultId=0) {
		
		if(empty($iResultId)) {
			$this->addResult($iExamId);
		} else {
			$this->_aResult['id'] = $iResultId;
		}

		$this->_getData();

	}
	
	public function setLanguage($sLanguage) {
		$this->_sLanguage = $sLanguage;
	}
	
	public function __get($sField) {
		if(isset($this->_aResult[$sField])) {
			return $this->_aResult[$sField];
		}
	}

	public function __set($sField, $mValue) {
		if(isset($this->_aResult[$sField])) {
			$this->_aResult[$sField] = $mValue;
		}
	}
	
	protected function _getData() {
		
		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`changed`) `changed`,
					UNIX_TIMESTAMP(`created`) `created` 
				FROM
					`elearning_exams_results` r
				WHERE
					id = :id
				";
		$aSql = array('id'=>(int)$this->_aResult['id']);
		$this->_aResult = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aResult = $this->_aResult[0];
		
	}
	
	protected function addResult($iExamId) {
		global $user_data, $page_data;

		$sSql = "INSERT INTO
					elearning_exams_results
				SET
					`changed` = NOW(),
					`created` = NOW(),
					`exam_id` = :exam_id, 
					`ip` = :ip, 
					`user_table_id` = :user_table_id, 
					`user_id` = :user_id, 
					`session_id` = :session_id,
					`language` = :language
			";
		$aSql = array();
		$aSql['exam_id'] = (int)$iExamId;
		$aSql['ip'] = $_SERVER['REMOTE_ADDR'];
		$aSql['user_table_id'] = (int)$user_data['idTable'];
		$aSql['user_id'] = (int)$user_data['id'];
		$aSql['session_id'] = session_id();
		$aSql['language'] = (string)$page_data['language'];
		DB::executePreparedQuery($sSql, $aSql);
		$this->_aResult['id'] = DB::fetchInsertId();
		
	}
	
	static public function loadLastResult($iExamId, $iParticipantId, $sLanguage='de', $iDirectResultId=null) {
		
		$_SESSION['elearning']['exam'][$iExamId]['results'] = array();
		
		$oExam = new Ext_Elearning_Exam($iExamId);
		
		$sDate = '';
		
		$sSql = "
				SELECT 
					* 
				FROM
					elearning_exams_results eer
				WHERE
					`eer`.`exam_id` = :exam_id AND 
					`eer`.`user_id` = :user_id AND
					(
						`eer`.`state` = 'succeeded' OR
						`eer`.`state` = 'failed'
					)
				ORDER BY
					`eer`.`created` DESC
				";
		$aSql = array();
		$aSql['exam_id'] = (int)$iExamId;
		$aSql['user_id'] = (int)$iParticipantId;
		$aResultIds = DB::getQueryCol($sSql, $aSql);

		if(
			!empty($iDirectResultId) && 
			in_array($iDirectResultId, $aResultIds)
		) {
			$iResultId = $iDirectResultId;
		} else {
			$iResultId = reset($aResultIds);
		}
		
		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`created`) `created`
				FROM
					elearning_exams_results eer
				WHERE
					`eer`.`id` = :id
				";
		$aSql = array();
		$aSql['id'] = (int)$iResultId;
		$aResult = DB::getQueryRow($sSql, $aSql);

		$sDate = strftime('%x %X', $aResult['created']);
		$iState = 0;
		if($aResult['state'] == 'succeeded') {
			$iState = 1;
		}
		
		$sSql = "
				SELECT 
					*
				FROM
					elearning_exams_results_data eerd
				WHERE
					`eerd`.`result_id` = :result_id
				ORDER BY
					`eerd`.`created` ASC
				";
		$aSql = array();
		$aSql['result_id'] = (int)$iResultId;
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aResult as $aItem) {
			
			if(!isset($_SESSION['elearning']['exam'][$iExamId]['results']['answers'][$aItem['question_id']])) {
				$_SESSION['elearning']['exam'][$iExamId]['results']['answers'][$aItem['question_id']] = array();
			}
			$_SESSION['elearning']['exam'][$iExamId]['results']['answers'][$aItem['question_id']][$aItem['answer_id']] = 1;

		}
		
		foreach($_SESSION['elearning']['exam'][$iExamId]['results']['answers'] as $iQuestionId=>$aAnswers) {

			// check if answer is correct
			$oQuestion = new Ext_Elearning_Exam_Question($iQuestionId);
			$aCorrect = $oQuestion->getCorrectAnswers();
			
			$iCorrect = 0;
			foreach($aAnswers as $iAnswerId=>$bOne) {

				if(isset($aCorrect[$iAnswerId])) {
					$iCorrect++;
				}

			}

			if($iCorrect == count($aCorrect)) {
				$_SESSION['elearning']['exam'][$iExamId]['results']['questions'][$iQuestionId] = 1;
			} else {
				$_SESSION['elearning']['exam'][$iExamId]['results']['questions'][$iQuestionId] = 0;
			}

		}
		
		if($_REQUEST['debug']) {
			__uout($aResult, 'koopmann');
			__uout($_SESSION['elearning']['exam'][$iExamId]['results'], 'koopmann');
			__uout($aResultIds, 'koopmann');
			__uout($iResultId, 'koopmann');
		}

		$aReturn = array();
		$aGroups = $oExam->getGroups();
		foreach((array)$aGroups as $aGroup) {

			$oGroup = new Ext_Elearning_Exam_Group($aGroup['id']);
			$oGroup->setLanguage($sLanguage);
			$aData = $oGroup->getData();
			$aQuestions = $oGroup->getQuestions();
			foreach((array)$aQuestions as $aQuestion) {
					
				if($aQuestion['type'] != 'only_text') {

					$bCorrect = $_SESSION['elearning']['exam'][$iExamId]['results']['questions'][$aQuestion['id']];
					
					$oQuestion = new Ext_Elearning_Exam_Question($aQuestion['id']);
					$oQuestion->setLanguage($sLanguage);
					$aAnswers = $oQuestion->getAnswers();
	
					$aQuestion = $oQuestion->getData();
					$aWrongQuestion = $aQuestion;
					$aWrongQuestion['correct'] = $bCorrect;
	
					$aCorrectAnswers = $oQuestion->getCorrectAnswers();
					$aWrongAnswers = $_SESSION['elearning']['exam'][$iExamId]['results']['answers'][$aQuestion['id']];

					foreach((array)$aAnswers as $iAnswer=>$aAnswer) {
						if($aCorrectAnswers[$aAnswer['id']] == 1) {
							$aWrongQuestion['correct_answers'][$iAnswer] = $aAnswer;
						}
						if($aWrongAnswers[$aAnswer['id']] == 1) {
							$aWrongQuestion['wrong_answers'][$iAnswer] = $aAnswer;
						}
					}
					
					$aReturn[] = $aWrongQuestion;
					
				}
				
			}

		}

		return array('state'=>$iState, 'date'=>$sDate, 'results'=>$aReturn, 'result_ids'=>$aResultIds);
		
	}
	
	public function saveResultData($iQuestion, $aData) {
		
		// check if answer is correct
		$oQuestion = new Ext_Elearning_Exam_Question($iQuestion);
		$aCorrect = $oQuestion->getCorrectAnswers();

		$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['answers'][$iQuestion] = array();

		$iCorrect = 0;
		foreach((array)$aData as $iIndex=>$iAnswerId) {
			if(1) {
				
				$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['answers'][$iQuestion][$iAnswerId] = 1;

				if(isset($aCorrect[$iAnswerId])) {
					$iCorrect++;
				}
				
				$sSql = "INSERT INTO
							elearning_exams_results_data 
						SET
							`changed` = NOW(),
							`created` = NOW(),
							`result_id` = :result_id, 
							`question_id` = :question_id, 
							`answer_id` = :answer_id, 
							`loop` = :loop
					";
				$aSql = array();
				$aSql['question_id'] = (int)$iQuestion;
				$aSql['result_id'] = (int)$this->_aResult['id'];
				$aSql['answer_id'] = (int)$iAnswerId;
				$aSql['loop'] = 1;
				DB::executePreparedQuery($sSql, $aSql);
			}
		}
		
		if($iCorrect == count($aCorrect)) {
			$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$iQuestion] = 1;
		} else {
			$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$iQuestion] = 0;
		}
		
		return $_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$iQuestion];

	}
	
	public function checkResult() {
		
		$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
		
		$bSuccess = false;
		
		switch($oExam->score_mode) {
			case 'group_score':

				$aGroups = $this->getGroupScore();

				$bSuccess = true;
				foreach($aGroups as $aGroup) {
					if($aGroup['succeeded'] !== true) {
						$bSuccess = false;
					}
				}

				break;
			case 'exam_score':
			default:

				$iCorrectScore = $this->getResultScore();
				$iMinimumScore = $this->getMinimumScore();

				if($iCorrectScore >= $iMinimumScore) {
					$bSuccess = true;
				}

				break;
		}

		return $bSuccess;
			
	}
	
	public function getMaximumScore() {
		
		$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
		$aQuestions = $oExam->countQuestions();

		$iMaximumScore = $aQuestions['score'];

		return $iMaximumScore;
		
	}
	
	public function getMinimumScore() {
		
		$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
		$aQuestions = $oExam->countQuestions();

		$iMinumumScore = ($aQuestions['score'] * ($oExam->minimum_score/100));

		return $iMinumumScore;
		
	}
	
	public function getGroupScore() {
		
		$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
		
		$aGroups = $oExam->getGroups();

		$aReturn = array();

		foreach($aGroups as $aGroup) {
			
			$oGroup = new Ext_Elearning_Exam_Group($aGroup['id']);
			$aQuestions = $oGroup->getQuestions();
			
			$iTotalScore = 0;
			$iResultScore = 0;
			$iMinimumScore = 0;
			$bSucceeded = false;
			
			foreach($aQuestions as $aQuestion) {
				if(
					$aQuestion['score'] > 0 &&
					$aQuestion['type'] != 'only_text'
				) {
					$iTotalScore += $aQuestion['score'];
					$bCorrect = $_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$aQuestion['id']];
					if($bCorrect) {
						$iResultScore += $aQuestion['score'];
					}
				}
			}

			$iMinimumScore = ($iTotalScore * ($oGroup->minimum_score/100));
			
			if($iResultScore >= $iMinimumScore) {
				$bSucceeded = true;
			}
			
			$aReturn[] = array(
				'group' => $oGroup,
				'total_score' => $iTotalScore,
				'result_score' => $iResultScore,
				'minimum_score' => $iMinimumScore,
				'succeeded' => $bSucceeded
			);

		}

		return $aReturn;

	}
	
	public function getResultScore() {
		
		$iCorrect = 0;
		$iCorrectScore = 0;
		foreach((array)$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'] as $iQuestion=>$bCorrect) {
			$oQuestion = new Ext_Elearning_Exam_Question($iQuestion);
			if($bCorrect == 1) {
				$iCorrect++;
				$iCorrectScore += $oQuestion->score;
			}
		}

		return $iCorrectScore;
		
	}
	
	/**
	 * Gibt ein Array mit den beantworteten Fragen zurück
	 * 
	 * @param type $bOnlyWrong
	 * @return type 
	 */
	public function getQuestions($bOnlyWrong=true) {

		foreach((array)$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'] as $iQuestion=>$bCorrect) {

			if(
				$bOnlyWrong === false ||
				$bCorrect == 0
			) {

				$oQuestion = new Ext_Elearning_Exam_Question($iQuestion);
				$oQuestion->setLanguage($this->_sLanguage);
				$aAnswers = $oQuestion->getAnswers();

				$aQuestion = $oQuestion->getData();
				
				$oGroup = $oQuestion->getGroup();
				$oGroup->setLanguage($this->_sLanguage);
				$aGroupData = $oGroup->getData();
				$aQuestion['group'] = $aGroupData['name'];
				
				$aWrongQuestion = $aQuestion;

				$aCorrectAnswers = $oQuestion->getCorrectAnswers();
				$aWrongAnswers = $_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['answers'][$iQuestion];

				foreach((array)$aAnswers as $iAnswer=>$aAnswer) {
					if($aCorrectAnswers[$aAnswer['id']] == 1) {
						$aWrongQuestion['correct_answers'][$iAnswer] = $aAnswer;
						$aAnswers[$iAnswer]['correct'] = true;
					}
					if($aWrongAnswers[$aAnswer['id']] == 1) {
						$aWrongQuestion['wrong_answers'][$iAnswer] = $aAnswer;
						$aAnswers[$iAnswer]['checked'] = true;
					}
				}

				$aWrongQuestion['answers'] = $aAnswers;
				$aWrongQuestion['correct'] = $bCorrect;

				$aWrongQuestions[] = $aWrongQuestion;

			}

		}
		return $aWrongQuestions;
		
	}
	
	public function checkQuestion($iQuestionId) {
		if(isset($_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$iQuestionId])) {
			if($_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['questions'][$iQuestionId] === 1) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	public function markCheckedAnswers(&$aAnswers) {
		foreach((array)$aAnswers as $iKey=>$aAnswer) {
			if($_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['answers'][$aAnswer['question_id']][$aAnswer['id']] == 1) {
				$aAnswers[$iKey]['checked'] = 1;
			}	
		}
	}
	
	public function setFinished() {
		global $page_data;
		
		$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['finished'] = 1;
		
		$iScore = $this->getResultScore();
		$sState = 'failed';
		$iFailed = 0;

		// update participant state
		if($this->_aResult['user_id'] > 0) {
			$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
			$oParticipant = $oExam->getParticipant($this->_aResult['user_id']);
			$bResult = $this->checkResult();
			if($bResult) {
				$sState = 'succeeded';
			}
			$oParticipant->setState($sState);
			$oParticipant->addAttempt();

			$aFailed = $oParticipant->getLog('failed');
			$iFailed = count($aFailed);

			// Durchgefallen
		 	if($sState == 'failed') {
				// Zweiteinladung
		 		if($oExam->send_second_after_failed == 1) {
					if($iFailed == $oExam->attempts) {
						$oExam->sendInvitaitions('secondemail', false, $oParticipant->id);
					}
				}
			} else {
				if($oExam->send_email_after_success == 1) {
					$oExam->sendInvitaitions('successemail', false, $oParticipant->id);
				}
			}

		}

		$sSql = "
				UPDATE
					elearning_exams_results
				SET
					`score` = :score,
					`state` = :state,
					`language` = :language					
				WHERE
					`id` = :id
				LIMIT 1
			";
		$aSql = array();
		$aSql['id'] = (int)$this->_aResult['id'];
		$aSql['score'] = (int)$iScore;
		$aSql['state'] = $sState;
		$aSql['language'] = $page_data['language'];
		DB::executePreparedQuery($sSql, $aSql);

		return $iFailed;

	}

	public function checkFinished() {

		if($this->_aResult['user_id']) {
			$oExam = new Ext_Elearning_Exam($this->_aResult['exam_id']);
			$oParticipant = $oExam->getParticipant($this->_aResult['user_id']);

			if(
				$oParticipant->attempts >= $oExam->attempts &&
				$oExam->attempts != 0
			) {
				$bFinished = true;
			} else {
				$bFinished = false;
			}

		} else {
			$bFinished = (bool)$_SESSION['elearning']['exam'][$this->_aResult['exam_id']]['results']['finished'];
		}
		
		return $bFinished;

	}
	
}