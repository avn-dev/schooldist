<?php

class Ext_Elearning_Exam_Question {

	protected $_aQuestion = array(
								'group_id'=>0,
								'name'=>'',
								'type'=>'',
								'file'=>'',
								'score'=>1,
								'random_positions'=>0,
								'weighting'=>1,
								'position'=>0
								);
	protected $_sLanguage = false;

	public function __construct($iQuestionId=0) {
		
		if($iQuestionId > 0) {
			$this->_aQuestion['id'] = $iQuestionId;
			$this->_getData();
		}
		
	}
	
	public function setLanguage($sLanguage) {
		$this->_sLanguage = $sLanguage;
	}
	
	protected function _getData() {

		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`changed`) `changed`,
					UNIX_TIMESTAMP(`created`) `created` 
				FROM
					`elearning_exams_questions` q
				WHERE
					id = :id
				";
		$aSql = array('id'=>$this->_aQuestion['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aQuestion = $aData[0];
		
		$sSql = "
				SELECT 
					*
				FROM
					`elearning_exams_questions_l10n` l
				WHERE
					question_id = :question_id
				";
		$aSql = array('question_id'=>$this->_aQuestion['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$this->_aQuestion['l10n'][$aItem['language_code']]['question'] = $aItem['question'];
			$this->_aQuestion['l10n'][$aItem['language_code']]['description'] = $aItem['description'];
		}

	}

	public function __get($sField) {
		if(isset($this->_aQuestion[$sField])) {
			return $this->_aQuestion[$sField];
		}
	}

	public function __set($sField, $mValue) {
		if(isset($this->_aQuestion[$sField])) {
			$this->_aQuestion[$sField] = $mValue;
		}
	}

	public function getData() {
		
		if($this->_sLanguage) {
			foreach((array)$this->_aQuestion['l10n'][$this->_sLanguage] as $sKey=>$sValue) {
				$this->_aQuestion[$sKey] = $sValue;
			}
		}
	
		return $this->_aQuestion;
	}

	public function save() {
		
		if($this->_aQuestion['id'] < 1) {
			
			$sSql = "
					INSERT INTO 
						`elearning_exams_questions` 
					SET
						`created` = NOW(),
						`active` = 1
					";
			DB::executeQuery($sSql);
			$this->_aQuestion['id'] = DB::fetchInsertId();
			
		}
		
		$sSql = "
				UPDATE
					`elearning_exams_questions` 
				SET
					`changed` = NOW(),
					`group_id` = :group_id,
					`name` = :name,
					`type` = :type,
					`file` = :file,
					`score` = :score,
					`random_positions` = :random_positions,
					`weighting` = :weighting,
					`position` = :position
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['group_id'] = (int)$this->_aQuestion['group_id'];
		$aSql['name'] = (string)$this->_aQuestion['name'];
		$aSql['type'] = (string)$this->_aQuestion['type'];
		$aSql['file'] = (string)$this->_aQuestion['file'];
		$aSql['score'] = (int)$this->_aQuestion['score'];
		$aSql['random_positions'] = (bool)$this->_aQuestion['random_positions'];
		$aSql['weighting'] = (bool)$this->_aQuestion['weighting'];
		$aSql['position'] = (int)$this->_aQuestion['position'];
		$aSql['id'] = (int)$this->_aQuestion['id'];
		DB::executePreparedQuery($sSql, $aSql);

		return $this->_aQuestion['id'];
		
	}
	
	public function getGroup() {
		$oGroup = new Ext_Elearning_Exam_Group($this->_aQuestion['group_id']);
		return $oGroup;
	}

	public function getAnswers($bUsePositions=0) {
		global $session_data;

		if(
			!$session_data['public'] ||
			!isset($_SESSION['elearning']['exam']['content']['group'][$this->_aQuestion['group_id']]['question'][$this->_aQuestion['id']]['answers'])
		) {

			$aSql = array();
			$sSql = "
						SELECT 
							* 
						FROM
							`elearning_exams_answers` a LEFT OUTER JOIN
							`elearning_exams_answers_l10n` l ON
								`a`.`id` = `l`.`answer_id` AND
								`l`.`language_code` = :language_code
						WHERE
							`a`.`question_id` = :question_id AND
							`a`.`active` = 1 
					";

			if(
				!$bUsePositions &&
				$this->_aQuestion['random_positions']
			) {
				$sSql .= "
							ORDER BY 
								RAND()
						";
			} else {
				$sSql .= "
							ORDER BY 
								`position`
						";
			}

			$aSql['question_id'] = (int)$this->_aQuestion['id'];
			$aSql['language_code'] = (string)$this->_sLanguage;
			
			$_SESSION['elearning']['exam']['content']['group'][$this->_aQuestion['group_id']]['question'][$this->_aQuestion['id']]['answers'] = DB::getPreparedQueryData($sSql, $aSql);

		}

		return $_SESSION['elearning']['exam']['content']['group'][$this->_aQuestion['group_id']]['question'][$this->_aQuestion['id']]['answers'];
	}

	public function getCorrectAnswers() {
		
		$aCorrect = array();
		
		if($this->_aQuestion['type'] == 'true_false') {
			//$aCorrect[0] = 1;
			//$aCorrect[] = 1;
		} else {

			$aSql = array();
			$sSql = "
						SELECT 
							* 
						FROM
							`elearning_exams_answers` a
						WHERE
							`a`.`question_id` = :question_id AND
							`a`.`correct` = 1 AND
							`a`.`active` = 1 
					";
	
			$aSql['question_id'] = (int)$this->_aQuestion['id'];
			$aAnswers = DB::getPreparedQueryData($sSql, $aSql);
			foreach((array)$aAnswers as $aAnswer) {
				$aCorrect[$aAnswer['id']] = 1;
			}

		}

		return $aCorrect;
	}

	public function setTranslations($aTranslations) {

		$sSql = "
					DELETE FROM
						`elearning_exams_questions_l10n` 
					WHERE
						`question_id` = :question_id
				";
		$aSql = array('question_id'=>(int)$this->_aQuestion['id']);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach((array)$aTranslations as $sLanguage=>$aTranslation) {
			$sSql = "
						INSERT INTO
							`elearning_exams_questions_l10n`
						SET
							`question_id` = :question_id,
							`language_code` = :language_code,
							`question` = :question,
							`description` = :description
					";
			$aSql = array(
							'question_id'=>(int)$this->_aQuestion['id'], 
							'language_code'=>$sLanguage, 
							'question'=>$aTranslation['question'], 
							'description'=>$aTranslation['description'],
						);
			DB::executePreparedQuery($sSql, $aSql);
		}

	}

	public function getL10N($sField) {
		return $this->_aQuestion['l10n'][$this->_sLanguage][$sField];
	}

	public function setChildPositions($aPositions) {
		$iPosition = 0;
		foreach((array)$aPositions as $iAnswerId) {
			$oAnswer = new Ext_Elearning_Exam_Answer($iAnswerId);
			$oAnswer->position = $iPosition;
			$oAnswer->save();

			$iPosition++;
		}
	}

	public function delete() {
		$sSql = "
				UPDATE
					`elearning_exams_questions` 
				SET
					`active` = :active
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['active'] = 0;
		$aSql['id'] = (int)$this->_aQuestion['id'];
		DB::executePreparedQuery($sSql, $aSql);
	}

}
