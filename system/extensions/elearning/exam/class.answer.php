<?php

class Ext_Elearning_Exam_Answer {

	protected $_aAnswer = array(
								'question_id'=>0,
								'name'=>'',
								'correct'=>0,
								'weighting'=>1,
								'position'=>0
								);
	protected $_sLanguage = false;

	public function __construct($iAnswerId=0) {
		
		if($iAnswerId > 0) {
			$this->_aAnswer['id'] = $iAnswerId;
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
					`elearning_exams_answers` a
				WHERE
					id = :id
				";
		$aSql = array('id'=>$this->_aAnswer['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aAnswer = $aData[0];
		
		$sSql = "
				SELECT 
					*
				FROM
					`elearning_exams_answers_l10n` l
				WHERE
					answer_id = :answer_id
				";
		$aSql = array('answer_id'=>$this->_aAnswer['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$this->_aAnswer['l10n'][$aItem['language_code']]['answer'] = $aItem['answer'];
			$this->_aAnswer['l10n'][$aItem['language_code']]['comment'] = $aItem['comment'];
		}

	}

	public function __get($sField) {
		if(isset($this->_aAnswer[$sField])) {
			return $this->_aAnswer[$sField];
		}
	}

	public function __set($sField, $mValue) {
		if(isset($this->_aAnswer[$sField])) {
			$this->_aAnswer[$sField] = $mValue;
		}
	}

	
	public function getData() {
		
		if($this->_sLanguage) {
			foreach((array)$this->_aAnswer['l10n'][$this->_sLanguage] as $sKey=>$sValue) {
				$this->_aAnswer[$sKey] = $sValue;
			}
		}

		return $this->_aAnswer;
	}

	public function save() {
		
		if($this->_aAnswer['id'] < 1) {
			
			$sSql = "
					INSERT INTO 
						`elearning_exams_answers` 
					SET
						`created` = NOW(),
						`active` = 1
					";
			DB::executeQuery($sSql);
			$this->_aAnswer['id'] = DB::fetchInsertId();
			
		}
		
		$sSql = "
				UPDATE
					`elearning_exams_answers` 
				SET
					`changed` = NOW(),
					`question_id` = :question_id,
					`name` = :name,
					`correct` = :correct,
					`weighting` = :weighting,
					`position` = :position
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['question_id'] = (int)$this->_aAnswer['question_id'];
		$aSql['name'] = (string)$this->_aAnswer['name'];
		$aSql['correct'] = (bool)$this->_aAnswer['correct'];
		$aSql['weighting'] = (int)$this->_aAnswer['weighting'];
		$aSql['position'] = (int)$this->_aAnswer['position'];
		$aSql['id'] = (int)$this->_aAnswer['id'];
		DB::executePreparedQuery($sSql, $aSql);

		return $this->_aAnswer['id'];
		
	}
	
	public function getQuestion() {
		$oQuestion = new Ext_Elearning_Exam_Question($this->_aAnswer['question_id']);
		return $oQuestion;
	}

	public function setTranslations($aTranslations) {

		$sSql = "
					DELETE FROM
						`elearning_exams_answers_l10n` 
					WHERE
						`answer_id` = :answer_id
				";
		$aSql = array('answer_id'=>(int)$this->_aAnswer['id']);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach((array)$aTranslations as $sLanguage=>$aTranslation) {
			$sSql = "
						INSERT INTO
							`elearning_exams_answers_l10n`
						SET
							`answer_id` = :answer_id,
							`language_code` = :language_code,
							`answer` = :answer,
							`comment` = :comment
					";
			$aSql = array(
							'answer_id'=>(int)$this->_aAnswer['id'], 
							'language_code'=>(string)$sLanguage, 
							'answer'=>(string)$aTranslation['answer'], 
							'comment'=>(string)$aTranslation['comment']
						);
			DB::executePreparedQuery($sSql, $aSql);
		}

	}

	public function getL10N($sField) {
		return $this->_aAnswer['l10n'][$this->_sLanguage][$sField];
	}

}
