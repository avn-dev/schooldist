<?php

class Ext_Elearning_Exam_Group {

	protected $_aGroup = array(
								'exam_id'=>'',
								'name'=>'',
								'minimum_score'=>'',
								'random_positions'=>'',
								'position'=>''
								);
	protected $_sLanguage = false;

	public function __construct($iGroupId=0) {
		
		if($iGroupId > 0) {
			$this->_aGroup['id'] = $iGroupId;
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
					`elearning_exams_groups` g
				WHERE
					id = :id
				";
		$aSql = array('id'=>$this->_aGroup['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aGroup = $aData[0];
		
		$sSql = "
				SELECT 
					*
				FROM
					`elearning_exams_groups_l10n` l
				WHERE
					group_id = :group_id
				";
		$aSql = array('group_id'=>$this->_aGroup['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$this->_aGroup['l10n'][$aItem['language_code']]['name'] = $aItem['name'];
			$this->_aGroup['l10n'][$aItem['language_code']]['description'] = $aItem['description'];
		}

	}

	public function __get($sField) {
		if(isset($this->_aGroup[$sField])) {
			return $this->_aGroup[$sField];
		}
	}

	public function __set($sField, $mValue) {
		if(isset($this->_aGroup[$sField])) {
			$this->_aGroup[$sField] = $mValue;
		}
	}

	public function getData() {
		
		if($this->_sLanguage) {
			foreach((array)$this->_aGroup['l10n'][$this->_sLanguage] as $sKey=>$sValue) {
				$this->_aGroup[$sKey] = $sValue;
			}
		}
		
		return $this->_aGroup;
	}

	public function save() {
		
		if($this->_aGroup['id'] < 1) {
			
			$sSql = "
					INSERT INTO 
						`elearning_exams_groups` 
					SET
						`created` = NOW(),
						`active` = 1
					";
			DB::executeQuery($sSql);
			$this->_aGroup['id'] = DB::fetchInsertId();
			
		}
		
		$sSql = "
				UPDATE
					`elearning_exams_groups` 
				SET
					`changed` = NOW(),
					`exam_id` = :exam_id,
					`name` = :name,
					`minimum_score` = :minimum_score,
					`random_positions` = :random_positions,
					`position` = :position
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['exam_id'] = (int)$this->_aGroup['exam_id'];
		$aSql['name'] = (string)$this->_aGroup['name'];
		$aSql['minimum_score'] = (int)$this->_aGroup['minimum_score'];
		$aSql['random_positions'] = (bool)$this->_aGroup['random_positions'];
		$aSql['position'] = (int)$this->_aGroup['position'];
		$aSql['id'] = (int)$this->_aGroup['id'];
		DB::executePreparedQuery($sSql, $aSql);

		return $this->_aGroup['id'];
		
	}
	
	public function getExam() {
		$oExam = new Ext_Elearning_Exam($this->_aGroup['exam_id']);
		return $oExam;
	}

	public function getQuestions($bUsePositions=0) {
		global $session_data;

		if(
			!$session_data['public'] ||
			!isset($_SESSION['elearning']['exam']['content'][$this->_aGroup['exam_id']]['group'][$this->_aGroup['id']]['questions'])
		) {

			$aSql = array();
			$sSql = "
						SELECT 
							* 
						FROM
							`elearning_exams_questions` q
						WHERE
							`q`.`group_id` = :group_id AND
							`q`.`active` = 1 
					";
			
			if(
				!$bUsePositions &&
				$this->_aExam['random_positions']
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
			
			$aSql['group_id'] = (int)$this->_aGroup['id'];
			$_SESSION['elearning']['exam']['content'][$this->_aGroup['exam_id']]['group'][$this->_aGroup['id']]['questions'] = DB::getPreparedQueryData($sSql, $aSql);

		}

		return $_SESSION['elearning']['exam']['content'][$this->_aGroup['exam_id']]['group'][$this->_aGroup['id']]['questions'];
	}

	public function setTranslations($aTranslations) {

		$sSql = "
					DELETE FROM
						`elearning_exams_groups_l10n` 
					WHERE
						`group_id` = :group_id
				";
		$aSql = array('group_id'=>(int)$this->_aGroup['id']);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach((array)$aTranslations as $sLanguage=>$aTranslation) {
			$sSql = "
						INSERT INTO
							`elearning_exams_groups_l10n`
						SET
							`group_id` = :group_id,
							`language_code` = :language_code,
							`name` = :name,
							`description` = :description
					";
			$aSql = array(
							'group_id'=>(int)$this->_aGroup['id'], 
							'language_code'=>$sLanguage, 
							'name'=>$aTranslation['name'], 
							'description'=>$aTranslation['description'],
						);
			DB::executePreparedQuery($sSql, $aSql);
		}

	}

	public function getL10N($sField) {
		return $this->_aGroup['l10n'][$this->_sLanguage][$sField];
	}

	public function setChildPositions($aPositions) {
		$iPosition = 0;
		foreach((array)$aPositions as $iQuestionId) {
			$oQuestion = new Ext_Elearning_Exam_Question($iQuestionId);
			$oQuestion->position = $iPosition;
			$oQuestion->save();

			$iPosition++;
		}
	}

	public function delete() {
		$sSql = "
				UPDATE
					`elearning_exams_groups` 
				SET
					`active` = :active
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['active'] = 0;
		$aSql['id'] = (int)$this->_aGroup['id'];
		DB::executePreparedQuery($sSql, $aSql);
	}

}
