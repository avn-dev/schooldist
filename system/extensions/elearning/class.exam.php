<?php

class Ext_Elearning_Exam {
	
	protected $_aExam = array(
								'name'=>'',
								'date_start'=>'',
								'date_end'=>'',
								'closed'=>'',
								'customer_db'=>'',
								'customer_db_id'=>'',
								'customer_db_firstname'=>'',
								'customer_db_lastname'=>'',
								'customer_db_email'=>'',
								'customer_db_group'=>'',
								'customer_db_level'=>'',
								'customer_db_location'=>'',
								'customer_db_language'=>'de',
								'display'=>'',
								'score_mode'=>'',
								'minimum_score'=>50,
								'maximum_time'=>'',
								'attempts'=>'',
								'show_result'=>'',
								'show_error_in_question'=>0,
								'show_success_in_question'=>0,
								'show_answers'=>'',
								'random_positions'=>'',
								'email_result'=>'',
								'email_comment'=>'',
								'pdf_logo'=>'',
								'pdf_color'=>'',
								'reminder_weeks'=>2,
								'send_second_after_failed'=>0,
								'send_email_after_success'=>0,
								'second_reminder_email_text'=>0,
								'comment_result',
								'show_result_pdf',
								'group_email_texts'
								);
	protected $_sLanguage = false;
	protected $_oCustomerDB = false;
	protected $_sLogoPath = "/media/elearning/";
	protected $_sEmailAttachmentPath = "media/elearning/email/";
	
	protected $aParticipantSummary = array();
	
	public function __construct($iExamId=0) {

		DB::setResultType(MYSQL_ASSOC);
		
		if($iExamId > 0) {
			$this->_aExam['id'] = $iExamId;
			$this->_getData();
			
			if($this->_aExam['customer_db']) {
				$this->_oCustomerDB = new Ext_CustomerDB_DB($this->_aExam['customer_db']);
			}
			
		}
		
	}	
	
	public function setLanguage($sLanguage) {
		
		$aLanguages = $this->getLanguages();
		$aLanguages = array_flip($aLanguages);

		if(array_key_exists($sLanguage, $aLanguages)) {
			$this->_sLanguage = $sLanguage;
			return true;
		}

		return false;

	}
	
	protected function _getData() {
		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`changed`) `changed`,
					UNIX_TIMESTAMP(`created`) `created`,
					UNIX_TIMESTAMP(`date_start`) `date_start`,
					UNIX_TIMESTAMP(`date_end`) `date_end` 
				FROM
					`elearning_exams`
				WHERE
					`id` = :id
				";
		$aSql = array('id'=>$this->_aExam['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$this->_aExam = $aData[0];
	}
	
	public function __get($sField) {
		if(isset($this->_aExam[$sField])) {
			return $this->_aExam[$sField];
		}
	}
	
	public function __set($sField, $mValue) {
		if(isset($this->_aExam[$sField])) {

			if(
				$sField == 'date_start' ||
				$sField == 'date_end'
			) {
				$mValue = strtotimestamp($mValue);
			}

			$this->_aExam[$sField] = $mValue;
		}
	}
	
	public function getData() {
		return $this->_aExam;
	}
	
	public function save() {
		
		if($this->_aExam['id'] < 1) {
			
			$sSql = "
					INSERT INTO 
						`elearning_exams` 
					SET
						`created` = NOW(),
						`active` = 1
					";
			DB::executeQuery($sSql);
			$this->_aExam['id'] = DB::fetchInsertId();
			
		}
		
		$sSql = "
				UPDATE
					`elearning_exams` 
				SET
					`changed` = NOW(),
					`name` = :name,
					`date_start` = :date_start,
					`date_end` = :date_end,
					`closed` = :closed,
					`customer_db` = :customer_db,
					`customer_db_id` = :customer_db_id,
					`customer_db_firstname` = :customer_db_firstname,
					`customer_db_lastname` = :customer_db_lastname,
					`customer_db_email` = :customer_db_email,
					`customer_db_group` = :customer_db_group,
					`customer_db_level` = :customer_db_level,
					`customer_db_location` = :customer_db_location,
					`customer_db_language` = :customer_db_language,
					`display` = :display,
					`score_mode` = :score_mode,
					`minimum_score` = :minimum_score,
					`maximum_time` = :maximum_time,
					`attempts` = :attempts,
					`show_result` = :show_result,
					`show_answers` = :show_answers,
					`random_positions` = :random_positions,
					`email_result` = :email_result,
					`email_comment` = :email_comment,
					`pdf_logo` = :pdf_logo,
					`pdf_color` = :pdf_color,
					`reminder_weeks` = :reminder_weeks,
					`send_second_after_failed` = :send_second_after_failed,
					`send_email_after_success` = :send_email_after_success,
					`second_reminder_email_text` = :second_reminder_email_text,
					`show_error_in_question` = :show_error_in_question,
					`show_success_in_question` = :show_success_in_question,
					`comment_result` = :comment_result,
					`show_result_pdf` = :show_result_pdf,
					`group_email_texts` = :group_email_texts
				WHERE
					`id` = :id
				";
		$aSql = array();
		$aSql['name'] 					= (string)$this->_aExam['name'];
		$aSql['date_start'] 			= (string)date('Y-m-d H:i:s', $this->_aExam['date_start']);
		$aSql['date_end'] 				= (string)date('Y-m-d H:i:s', $this->_aExam['date_end']);
		$aSql['closed'] 				= (bool)$this->_aExam['closed'];
		$aSql['customer_db'] 			= (int)$this->_aExam['customer_db'];
		$aSql['customer_db_id'] 		= (string)$this->_aExam['customer_db_id'];
		$aSql['customer_db_firstname']	= (string)$this->_aExam['customer_db_firstname'];
		$aSql['customer_db_lastname'] 	= (string)$this->_aExam['customer_db_lastname'];
		$aSql['customer_db_email'] 		= (string)$this->_aExam['customer_db_email'];
		$aSql['customer_db_group'] 		= (string)$this->_aExam['customer_db_group'];
		$aSql['customer_db_level'] 		= (string)$this->_aExam['customer_db_level'];
		$aSql['customer_db_location'] 		= (string)$this->_aExam['customer_db_location'];
		$aSql['customer_db_language'] 	= (string)$this->_aExam['customer_db_language'];
		$aSql['display'] 				= (string)$this->_aExam['display'];
		$aSql['score_mode'] 			= (string)$this->_aExam['score_mode'];
		$aSql['minimum_score'] 			= (int)$this->_aExam['minimum_score'];
		$aSql['maximum_time'] 			= (int)$this->_aExam['maximum_time'];
		$aSql['attempts'] 				= (int)$this->_aExam['attempts'];
		$aSql['show_result'] 			= (bool)$this->_aExam['show_result'];
		$aSql['show_answers'] 			= (int)$this->_aExam['show_answers'];
		$aSql['random_positions'] 		= (bool)$this->_aExam['random_positions'];
		$aSql['email_result'] 			= (string)$this->_aExam['email_result'];
		$aSql['email_comment'] 			= (string)$this->_aExam['email_comment'];
		$aSql['pdf_logo'] 				= (string)$this->_aExam['pdf_logo'];
		$aSql['pdf_color'] 				= (string)$this->_aExam['pdf_color'];
		$aSql['reminder_weeks'] 		= (int)$this->_aExam['reminder_weeks'];
		$aSql['send_second_after_failed'] = (int)$this->_aExam['send_second_after_failed'];
		$aSql['send_email_after_success'] = (int)$this->_aExam['send_email_after_success'];
		$aSql['second_reminder_email_text'] = (int)$this->_aExam['second_reminder_email_text'];
		$aSql['show_error_in_question'] = (int)$this->_aExam['show_error_in_question'];
		$aSql['show_success_in_question'] = (int)$this->_aExam['show_success_in_question'];
		$aSql['comment_result']			= (int)$this->_aExam['comment_result'];
		$aSql['show_result_pdf']		= (int)$this->_aExam['show_result_pdf'];
		$aSql['group_email_texts']		= (int)$this->_aExam['group_email_texts'];
		$aSql['id'] 					= (int)$this->_aExam['id'];
		DB::executePreparedQuery($sSql, $aSql);
		
		return $this->_aExam['id'];
		
	}
	
	public function isActiveAndRunning() {
		
		if(
			$this->active == 1 &&
			$this->date_start < time() &&
			$this->date_end > time()
		) {
			return true;
		}
		
		return false;
	}
	
	static public function getList($aOptions=array()) {
		
		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`changed`) `changed`,
					UNIX_TIMESTAMP(`created`) `created`,
					UNIX_TIMESTAMP(`date_start`) `date_start`,
					UNIX_TIMESTAMP(`date_end`) `date_end` 
				FROM
					`elearning_exams`
				WHERE
					`active` = 1
				";
		
		$sSql .= " 
				ORDER BY 
					`name`";
		$aList = DB::getQueryData($sSql);
		
		return $aList;
		
	}
	
	public function getLanguages() {
		$sSql = "
					SELECT * FROM
						elearning_exams_languages 
					WHERE
						`exam_id` = :exam_id
				";
		$aSql = array('exam_id'=>(int)$this->_aExam['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$aLanguages = array();
		foreach((array)$aData as $aItem) {
			$aLanguages[] = $aItem['language_code'];
		} 
		return $aLanguages;
	}

	public function getGroups($bUsePositions=0) {
		global $session_data;

		if(
			!$session_data['public'] ||
			!isset($_SESSION['elearning']['exam'][$this->_aExam['id']]['groups'])
		) {

			$aSql = array();
			$sSql = "
						SELECT 
							* 
						FROM
							`elearning_exams_groups` g
						WHERE
							`g`.`exam_id` = :exam_id AND
							`g`.`active` = 1 
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

			$aSql['exam_id'] = (int)$this->_aExam['id'];
			$aGroups = DB::getPreparedQueryData($sSql, $aSql);

			$_SESSION['elearning']['exam'][$this->_aExam['id']]['groups'] = $aGroups;

		}

		return $_SESSION['elearning']['exam'][$this->_aExam['id']]['groups'];
	}

	public function setLanguages($aLanguages) {
		
		$sSql = "
					DELETE FROM
						elearning_exams_languages 
					WHERE
						`exam_id` = :exam_id
				";
		$aSql = array('exam_id'=>(int)$this->_aExam['id']);
		DB::executePreparedQuery($sSql, $aSql);
		
		foreach((array)$aLanguages as $sLanguage) {
			$sSql = "
						INSERT INTO
							elearning_exams_languages 
						SET
							`exam_id` = :exam_id,
							`language_code` = :language_code
					";
			$aSql = array('exam_id'=>(int)$this->_aExam['id'], 'language_code'=>$sLanguage);
			DB::executePreparedQuery($sSql, $aSql);
		}
	}

	public function setChildPositions($aPositions) {
		$iPosition = 0;
		foreach((array)$aPositions as $iGroupId) {
			$oGroup = new Ext_Elearning_Exam_Group($iGroupId);
			$oGroup->position = $iPosition;
			$oGroup->save();

			$iPosition++;
		}
	}

	public function countQuestions() {
		
		$sSql = "
				SELECT 
					COUNT(*) `count`,
					SUM(`q`.`score`) `score`
				FROM
					elearning_exams_groups g JOIN
					elearning_exams_questions q ON
						g.id = q.group_id AND
						q.type != 'only_text' AND
						q.active = 1
				WHERE 
					g.exam_id = :exam_id AND
					g.active = 1
			";
		$aSql = array('exam_id'=>(int)$this->_aExam['id']);
		$aCount = DB::getPreparedQueryData($sSql, $aSql);
		$aCount = $aCount[0];

		return $aCount;

	}


	public function getParticipants($sGroup="", $sSearch="", $sLanguage="", $iParticipant=false) {
		
		$sSql = "
				SELECT 
					*
				FROM 
					`elearning_exams_participants`
				WHERE
					`active` = 1 AND
					`exam_id` = :exam_id
					";
		$aSql = array('exam_id'=>(int)$this->_aExam['id']);
		if($iParticipant) {
			$sSql .= " AND `id` = :id";
			$aSql['id'] = (int)$iParticipant;
		}
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		$aParticipantData = array();
		foreach((array)$aData as $aItem) {
			$aParticipantData[$aItem['id']] = $aItem;
		}
		unset($aData);

		$aSearch = array();
		
		if($iParticipant) {
			$aSearch[$this->_aExam['customer_db_id']] = (int)$iParticipant;
		} else {
			$aSearch[$this->_aExam['customer_db_id']] = $sSearch;
			$aSearch[$this->_aExam['customer_db_firstname']] = $sSearch;
			$aSearch[$this->_aExam['customer_db_lastname']] = $sSearch;
			$aSearch[$this->_aExam['customer_db_email']] = $sSearch;
			$aSearch[$this->_aExam['customer_db_group']] = $sGroup;
			$aSearch[$this->_aExam['customer_db_language']] = $sLanguage;
		}
		$aParticipants = $this->_oCustomerDB->searchCustomers($aSearch, $this->_aExam['customer_db_lastname']);
		foreach((array)$aParticipants as $iKey=>$aParticipant) {
			
			if($iParticipant) {
				if($aParticipant['id'] != $iParticipant) {
					unset($aParticipants[$iKey]);
					continue;
				}
			}
			
			$aParticipant['invited'] = $aParticipantData[$aParticipant['id']]['invited'];
			$aParticipant['state'] = $aParticipantData[$aParticipant['id']]['state'];
			$aParticipant['attempts'] = $aParticipantData[$aParticipant['id']]['attempts'];
			$aParticipant['last_email'] = $aParticipantData[$aParticipant['id']]['last_email'];
			$aParticipant['id'] = $aParticipant[$this->_aExam['customer_db_id']];
			$aParticipant['firstname'] = $aParticipant[$this->_aExam['customer_db_firstname']];
			$aParticipant['lastname'] = $aParticipant[$this->_aExam['customer_db_lastname']];
			$aParticipant['email'] = $aParticipant[$this->_aExam['customer_db_email']];
			$aParticipant['group'] = $aParticipant[$this->_aExam['customer_db_group']];
			$aParticipant['level'] = $aParticipant[$this->_aExam['customer_db_level']];
			$aParticipant['location'] = $aParticipant[$this->_aExam['customer_db_location']];
			$aParticipant['language'] = $aParticipant[$this->_aExam['customer_db_language']];
			$aParticipants[$iKey] = $aParticipant;
		}

		return $aParticipants;

	}
	
	public function getParticipantGroups() {
		 
		 $aGroups = $this->_oCustomerDB->getFieldValues($this->_aExam['customer_db_group']);
		 $aReturn = array();
		 foreach((array)$aGroups as $aGroup) {
		 	$aReturn[$aGroup['field']] = $aGroup['field'];		 	
		 }
		 return $aReturn;
		 
	}
	
	public function getParticipantLevels() {
		 
		 $aLevels = $this->_oCustomerDB->getFieldValues($this->_aExam['customer_db_level']);
		 $aReturn = array();
		 foreach((array)$aLevels as $aGroup) {
		 	$aReturn[$aGroup['field']] = $aGroup['field'];		 	
		 }
		 return $aReturn;
		 
	}
	
	public function getParticipantLocations() {
		 
		 $aLocations = $this->_oCustomerDB->getFieldValues($this->_aExam['customer_db_location']);
		 $aReturn = array();
		 foreach((array)$aLocations as $aGroup) {
		 	$aReturn[$aGroup['field']] = $aGroup['field'];		 	
		 }
		 return $aReturn;
		 
	}
	
	public function getParticipant($iParticipantId) {
		
		$oParticipant = new Ext_Elearning_Exam_Participants($this, $iParticipantId);
		return $oParticipant;
		
	}
	
	public function getParticipantSummary() {

		return $this->aParticipantSummary;

	}
	
	public function getResultSummary($sGroupField='group') {

		$this->aParticipantSummary = array();
		
		$aReturn = array();
		$aFailed = array();
		$aSucceded = array();
		$aNotSucceded = array();
		$aNotParticipated = array();
		$aSecondNotParticipated = array();
		
		$aData = $this->getParticipants();
		foreach((array)$aData as $aParticipant) {
			
			$oParticipant = new Ext_Elearning_Exam_Participants($this, $aParticipant['id']);
			$aParticipantSummary = $oParticipant->getSummary();

			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE exam_id = :exam_id AND user_id = :user_id AND state = 'invited' AND info = 'secondemail'";
			$aSql = array('exam_id'=>(int)$this->_aExam['id'], 'user_id'=>(int)$aParticipant['id']);
			$aLog = DB::getPreparedQueryData($sSql, $aSql);
			
			$iSecond = time();
			$bSecond = false;
			if(!empty($aLog)) {
				$iSecond = $aLog[0]['changed'];
				$bSecond = true;
			}

			$sGroup = $aParticipant[$sGroupField];
			$sState = $aParticipant['state'];
			if(empty($sGroup)) {
				$sGroup = 'no_group';
			}
			if(empty($sState)) {
				$sState = 'new';
			}
			// get info from first attempt
			$bFirstFailed = false;
			$bFirstSucceeded = false;
			$bSecondFailed = false;
			$bSecondSucceeded = false;
				
			// state = 'invited'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed < :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'invited'";
			$aSql = array('exam_id'=>(int)$this->_aExam['id'], 'user_id'=>(int)$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
			$aLog = DB::getPreparedQueryData($sSql, $aSql);
			if(!empty($aLog)) {
				$aReturn['first'][$sGroup]['invited']++;
				$aReturn['first']['total']['invited']++;
			}
			
			// state = 'started'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed < :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'started'";
			$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
			$aLog = DB::getPreparedQueryData($sSql, $aSql);
			if(!empty($aLog)) {
				$aReturn['first'][$sGroup]['started']++;
				$aReturn['first']['total']['started']++;
			}
			
			// state = 'failed'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed <= :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'failed'";
			$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
			$aLogFailed = DB::getPreparedQueryData($sSql, $aSql);

			// state = 'succeeded'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed <= :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'succeeded'";
			$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
			$aLogSucceeded = DB::getQueryRow($sSql, $aSql);

			// Durchgefallen und Bestanden schliessen sich aus (Fall: Zuerst durchfallen und dann noch bestehen
			if(!empty($aLogSucceeded)) {
				$aReturn['first'][$sGroup]['succeeded']++;
				$aReturn['first']['total']['succeeded']++;
				$bFirstSucceeded = true;
				$aParticipantSummary['succeeded'] = date('d.m.Y H:i:s', $aLogSucceeded['changed']);
			} elseif(!empty($aLogFailed)) {
				$aReturn['first'][$sGroup]['failed']++;
				$aReturn['first']['total']['failed']++;
				$bFirstFailed = true;
			}

			$aReturn['first'][$sGroup]['total']++;
			$aReturn['first']['total']['total']++;

			if($bSecond) {

				// state = 'invited'
				$aReturn['second'][$sGroup]['invited']++;
				$aReturn['second']['total']['invited']++;
				
				// state = 'started'
				$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed > :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'started'";
				$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
				$aLog = DB::getPreparedQueryData($sSql, $aSql);
				if(!empty($aLog)) {
					$aReturn['second'][$sGroup]['started']++;
					$aReturn['second']['total']['started']++;
				}
				
				// state = 'failed'
				$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed > :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'failed'";
				$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
				$aLogFailed = DB::getPreparedQueryData($sSql, $aSql);

				// state = 'succeeded'
				$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE changed > :time AND exam_id = :exam_id AND user_id = :user_id AND state = 'succeeded'";
				$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id'], 'time'=>date('YmdHis', $iSecond));
				$aLogSucceeded = DB::getQueryRow($sSql, $aSql);
				
				// Durchgefallen und Bestanden schliessen sich aus (Fall: Zuerst durchfallen und dann noch bestehen
				if(!empty($aLogSucceeded)) {
					$aReturn['second'][$sGroup]['succeeded']++;
					$aReturn['second']['total']['succeeded']++;
					$bSecondSucceeded = true;
					$aParticipantSummary['succeeded'] = date('d.m.Y H:i:s', $aLogSucceeded['changed']);
				} elseif(!empty($aLogFailed)) {
					$aReturn['second'][$sGroup]['failed']++;
					$aReturn['second']['total']['failed']++;
					$bSecondFailed = true;
				}

				if(
					$bSecondFailed === true &&
					$bSecondSucceeded !== true
				) {
					$aFailed[] = &$aParticipantSummary;
				}

				// Wenn nicht durchgefallen und nicht erfolgreich im zweiten Durchlauf
				if(
					$bSecondFailed !== true &&
					$bSecondSucceeded !== true
				) {
					$aSecondNotParticipated[] = &$aParticipantSummary;
				}
				
				$aReturn['second'][$sGroup]['total']++;
				$aReturn['second']['total']['total']++;

			}

			/**
			 * get total
			 */

			$bFailed = false;
			$bSucceded = false;
			$aInvitations = array();

			// state = 'invited'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE exam_id = :exam_id AND user_id = :user_id AND state = 'invited' ORDER BY `changed` DESC";
			$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id']);
			$aLog = DB::getPreparedQueryData($sSql, $aSql);
			if(!empty($aLog)) {
				
				foreach($aLog as $aLogEntry) {
					if(
						!empty($aLogEntry['info']) &&	
						!isset($aInvitations[$aLogEntry['info']])
					) {
						$aInvitations[$aLogEntry['info']] = $aLogEntry['changed'];
					}
				}
				
				$aReturn['total'][$sGroup]['invited']++;
				$aReturn['total']['total']['invited']++;
			}

			// state = 'started'
			$sSql = "SELECT *, UNIX_TIMESTAMP(`changed`) `changed` FROM elearning_exams_logs WHERE exam_id = :exam_id AND user_id = :user_id AND state = 'started'";
			$aSql = array('exam_id'=>$this->_aExam['id'], 'user_id'=>$aParticipant['id']);
			$aLog = DB::getPreparedQueryData($sSql, $aSql);
			if(!empty($aLog)) {
				$aReturn['total'][$sGroup]['started']++;
				$aReturn['total']['total']['started']++;
			}

			// Durchgefallen und Bestanden schliessen sich aus (Fall: Zuerst durchfallen und dann noch bestehen
			if(
				$bFirstSucceeded === true ||
				$bSecondSucceeded === true
			) {
				$aReturn['total'][$sGroup]['succeeded']++;
				$aReturn['total']['total']['succeeded']++;
				$bSucceded = true;
				$aSucceded[] = &$aParticipantSummary;
			} else {
				if(
					$bSecondFailed === true
				) {
					$aReturn['total'][$sGroup]['failed']++;
					$aReturn['total']['total']['failed']++;
					$bFailed = true;
				}
				$aNotSucceded[] = &$aParticipantSummary;
			}

			if(
				$bFailed == false &&
				$bSucceded == false
			) {
				$aNotParticipated[] = &$aParticipantSummary;
			}

			$aParticipantSummary['invitations_firstemail'] = $aInvitations['firstemail'] ? date('d.m.Y H:i:s', $aInvitations['firstemail']) : '';
			$aParticipantSummary['invitations_secondemail'] = $aInvitations['secondemail'] ? date('d.m.Y H:i:s', $aInvitations['secondemail']) : '';
			$aParticipantSummary['invitations_reminderemail'] = $aInvitations['reminderemail'] ? date('d.m.Y H:i:s', $aInvitations['reminderemail']) : '';

			unset($aInvitations);
			unset($aParticipantSummary);

			$aReturn['total'][$sGroup]['total']++;
			$aReturn['total']['total']['total']++;

		}

		$this->aParticipantSummary['failed'] = $aFailed;
		$this->aParticipantSummary['succeded'] = $aSucceded;
		$this->aParticipantSummary['not_succeded'] = $aNotSucceded;
		$this->aParticipantSummary['not_participated'] = $aNotParticipated;
		$this->aParticipantSummary['second_not_participated'] = $aSecondNotParticipated;

		return $aReturn;

	}

	public function getLogoPath() {
		$sLogo = \Util::getDocumentRoot().$this->_sLogoPath.$this->_aExam['pdf_logo'];
		if(is_file($sLogo)) {
			return $sLogo;
		} else {
			return false;
		}
	}
	
	public function updateLogo(&$aLogo) {

		global $system_data;

		$sName = \Util::getCleanFileName($aLogo['name']);
		$sTarget = \Util::getDocumentRoot().$this->_sLogoPath.$sName;
		
		if(!is_dir(\Util::getDocumentRoot().$this->_sLogoPath)) {
			mkdir(\Util::getDocumentRoot().$this->_sLogoPath, $system_data['chmod_mode_dir']);
			chmod(\Util::getDocumentRoot().$this->_sLogoPath, $system_data['chmod_mode_dir']);
		}
		
		$bSuccess = move_uploaded_file($aLogo['tmp_name'], $sTarget);
		if($bSuccess) {
			$this->pdf_logo = $sName;
		}
		return $bSuccess;
	}
	
	public function getEmails() {
		$sSql = "
				SELECT 
					* 
				FROM
					elearning_exams_emails
				WHERE
					`active` = 1 AND
					`exam_id` = :exam_id
				ORDER BY
					`item` ASC
				";
		$aSql = array('exam_id'=>$this->_aExam['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		
		$aEmails = array();
		foreach((array)$aData as $aItem) {
			if($this->group_email_texts) {
				$aEmails[$aItem['user_group']][$aItem['language']][$aItem['item']] = $aItem;
			} else {
				$aEmails[$aItem['language']][$aItem['item']] = $aItem;
			}
		}

		return $aEmails;
	}

	public function importParticipants($sPath, $bUtf8=false) {

		$aLines = file($sPath);
		
		$iSuccess = 0;
		
		foreach((array)$aLines as $sLine) {
			$aLine = explode(";", $sLine);
			
			foreach((array)$aLine as $iKey=>$sField) {
				$sField = trim($sField);
				if($bUtf8 === false) {
					$sField = iconv('cp1252', 'utf-8', $sField);
				}
				$aLine[$iKey] = $sField;
			}

			// check valid email
			if(checkEmailMx($aLine[2])) {
				
				$sSql = "
						INSERT INTO
							`customer_db_".$this->_aExam['customer_db']."`
						SET
							`active` = 1,
							`email` = :email,
							`nickname` = :email,
							`password` = MD5(:password),
							`created` = NOW(),
							`access_code` = :access_code,
							#field_firstname = :value_firstname,
							#field_lastname = :value_lastname,
							#field_group = :value_group,
							#field_level = :value_level,
							#field_language = :value_language,
							#field_location = :value_location
						";
				$aSql = array();
				$aSql['email'] = strtolower($aLine[2]);
				$aSql['password'] = \Util::generateRandomString(6);
				$aSql['access_code'] = strtolower(\Util::generateRandomString(16));
				$aSql['field_firstname'] = $this->_aExam['customer_db_firstname'];
				$aSql['field_lastname'] = $this->_aExam['customer_db_lastname'];
				$aSql['field_group'] = $this->_aExam['customer_db_group'];
				$aSql['field_level'] = $this->_aExam['customer_db_level'];
				$aSql['field_location'] = $this->_aExam['customer_db_location'];
				$aSql['field_language'] = $this->_aExam['customer_db_language'];
				$aSql['value_firstname'] = $aLine[1];
				$aSql['value_lastname'] = $aLine[0];
				$aSql['value_group'] = $aLine[4];
				$aSql['value_level'] = $aLine[5];
				$aSql['value_location'] = $aLine[6];
				$aSql['value_language'] = strtolower(substr($aLine[3], 0, 2));

				try {
					$bSuccess = DB::executePreparedQuery($sSql, $aSql);
					if($bSuccess) {
						$iSuccess++;
					}
				} catch(Exception $e) {
					if(System::d('debugmode')) {
						__out($e->getMessage());
					}
				}

			} else {
				__out($aLine);
			}
			
		}
		
		return $iSuccess;
		
	}

	public function sendInvitaitions($sStep, $bTrialRun=true, $iParticipant=false) {
		global $user_data;		

		$iSuccess = 0;
		$bSkipInviteMode = false;

		// get all recipients for this step
		$aParticipants = $this->getParticipants('', '', '', $iParticipant);

		if($sStep == 'successemail') {
			foreach((array)$aParticipants as $iKey=>$aParticipant) {
				if($aParticipant['state'] != 'succeeded') {
					unset($aParticipants[$iKey]);
				}
			}
			$bSkipInviteMode = true;
		} elseif($sStep == 'secondemail') {
			foreach((array)$aParticipants as $iKey=>$aParticipant) {
				if($aParticipant['state'] != 'failed') {
					unset($aParticipants[$iKey]);
				}
			}
		} elseif($sStep == 'reminderemail') {

			$iReminder = strtotime("-".(int)$this->reminder_weeks." weeks");

			foreach((array)$aParticipants as $iKey=>$aParticipant) {

				$iInvited = strtotime($aParticipant['invited']);

				if(!$iInvited) {
					$iInvited = strtotime($aParticipant['created']);
				}

				if(
					$aParticipant['state'] == 'succeeded' ||
					$aParticipant['state'] == 'failed' ||
					$aParticipant['last_email'] == 'reminderemail' ||
					$iInvited > $iReminder
				) {
					unset($aParticipants[$iKey]);
				}

			}

		} elseif($sStep == 'reminderemail_notparticipated') {
			
			foreach((array)$aParticipants as $iKey=>$aParticipant) {

				if(
					$aParticipant['state'] == 'succeeded' ||
					$aParticipant['state'] == 'failed'
				) {
					unset($aParticipants[$iKey]);
				}

			}

			$sStep = 'reminderemail';

		} elseif($sStep == 'reminderemail_force') {

			foreach((array)$aParticipants as $iKey=>$aParticipant) {

				if(
					$aParticipant['state'] == 'succeeded'
				) {
					unset($aParticipants[$iKey]);
				}

			}

			$sStep = 'reminderemail';

		} elseif($sStep == 'single_invitation') {

			$aParticipant = reset($aParticipants);
			$aParticipants = array($aParticipant);

			$sStep = 'firstemail';
			
		} else {
			foreach((array)$aParticipants as $iKey=>$aParticipant) {
				if($aParticipant['state'] != '') {
					unset($aParticipants[$iKey]);
				}
			}
		}

		// get templates for languages
		$aEmails = $this->getEmails();

		$iTotal = count($aParticipants);

		foreach((array)$aParticipants as $aParticipant) {

			$sCurrentStep = $sStep;
			
			// Wenn Reminder und andere E-Mail beim zweiten Durchlauf
			if(
				$sStep == 'reminderemail' &&
				$this->second_reminder_email_text &&
				$aParticipant['last_email'] == 'secondemail'
			) {
				$sCurrentStep = 'secondreminderemail';
			}			

			$sPath = Util::getDocumentRoot().$this->_sEmailAttachmentPath.$this->id.'_'.$sCurrentStep.'_'.$aParticipant['language'].'_';

			// Wenn Text pro Gruppe
			if($this->group_email_texts) {
				$aCurrentEmailTexts = $aEmails[$aParticipant['group']][$aParticipant['language']][$sCurrentStep];
			} else {
				$aCurrentEmailTexts = $aEmails[$aParticipant['language']][$sCurrentStep];
			}

			$sSubject = $aCurrentEmailTexts['subject'];
			$sBody = $aCurrentEmailTexts['body'];
			$aAttachment = array(
				'file' => $sPath.$aCurrentEmailTexts['attachment'],
				'name' => $aCurrentEmailTexts['attachment']
			);	

			if(
				empty($sBody) || 
				empty($sSubject)
			) {
				continue;
			}

			if(
				$this->_oCustomerDB &&
				$bSkipInviteMode === false
			) {

				// create and save new password
				$aParticipant['password'] = strtolower(Util::generateRandomString(6));

				$this->_oCustomerDB->updateCustomerField($aParticipant['id'], 'password', $aParticipant['password']);
	
			}

			// replace placeholders in templates
			foreach((array)$aParticipant as $sKey=>$sValue) {
				$sSubject = str_replace("{".$sKey."}", $sValue, $sSubject);
				$sBody = str_replace("{".$sKey."}", $sValue, $sBody);
			}

			// send email
			$oWDMail = new WDMail();
			
			$oWDMail->subject = $sSubject;
			$oWDMail->text = $sBody;

			if(is_file($aAttachment['file'])) {
				$oWDMail->attachments = array(
					$aAttachment['file'] => $aAttachment['name']
				);
			}
			
			$sEmail = $this->email;
			
			if(empty($sEmail)) {
				$oWDMail->replyto = System::d('admin_email');
				$oWDMail->returnpath = System::d('admin_email');
			} else {
				$oWDMail->replyto = $sEmail;
				$oWDMail->returnpath = $sEmail;
				$oWDMail->from = $this->name.' <'.$sEmail.'>';
			}

			if($bTrialRun) {
				$bSuccess = $oWDMail->send($user_data['email']);
			} else {
				$bSuccess = $oWDMail->send($aParticipant['email']);
			}

			if($bSuccess) {

				// change state of recipient
				if(
					!$bTrialRun &&
					$bSkipInviteMode === false
				) {

					$oParticipant = new Ext_Elearning_Exam_Participants($this, $aParticipant['id']);
					$oParticipant->last_email = $sStep;
					$oParticipant->saveInviteState($sStep);

					// Bei einer Einladung, Anzahl Versuche zurücksetzen
					if($oParticipant->attempts >= $this->attempts) {
						$oParticipant->attempts = 0;
						$oParticipant->save();
					}

				}
				$iSuccess++;

				if($iSuccess % 20 == 0) {
					if($bTrialRun) {
						break;
					}
					sleep(1);
				}

				unset($oParticipant);

			}

		}

		return array('success'=>$iSuccess, 'total'=>$iTotal);

	}

	/**
	 *
	 * @return Ext_Elearning_Exam_Pdf 
	 */
	public function getPdf() {
		
		$oPdf = new Ext_Elearning_Exam_Pdf($this);

		return $oPdf;

	}

	public function saveComment($sComment) {
		$oComment = new Ext_Elearning_Exam_Comment();
		$oComment->exam_id = (int)$this->id;
		$oComment->comment = $sComment;
		$oComment->save();
		$oComment->send($this->email_comment);
	}

	public function createCopy() {

		$iOriginalId = $this->id;

		$this->_aExam['id'] = 0;

		$this->name = 'Kopie von '.$this->name;
		$this->save();
		
		$aItems = self::getById('elearning_exams_emails', 'exam_id', $iOriginalId);
		foreach((array)$aItems as $aData) {
			$aData['id'] = 0;
			$aData['exam_id'] = $this->id;
			DB::insertData('elearning_exams_emails', $aData);
		}
		
		$aItems = self::getById('elearning_exams_languages', 'exam_id', $iOriginalId);
		foreach((array)$aItems as $aData) {
			$aData['exam_id'] = $this->id;
			DB::insertData('elearning_exams_languages', $aData);
		}

		$aGroups = self::getById('elearning_exams_groups', 'exam_id', $iOriginalId);
		foreach((array)$aGroups as $aGroup) {

			$aData = $aGroup;
			$aData['id'] = 0;
			$aData['exam_id'] = $this->id;
			$iGroupId = DB::insertData('elearning_exams_groups', $aData);

			$aL10Ns = self::getById('elearning_exams_groups_l10n', 'group_id', $aGroup['id']);
			foreach((array)$aL10Ns as $aL10N) {
				$aData = $aL10N;
				$aData['group_id'] = $iGroupId;
				DB::insertData('elearning_exams_groups_l10n', $aData);
			}

			$aQuestions = self::getById('elearning_exams_questions', 'group_id', $aGroup['id']);
			foreach((array)$aQuestions as $aQuestion) {

				$aData = $aQuestion;
				$aData['id'] = 0;
				$aData['group_id'] = $iGroupId;
				$iQuestionId = DB::insertData('elearning_exams_questions', $aData);

				$aL10Ns = self::getById('elearning_exams_questions_l10n', 'question_id', $aQuestion['id']);
				foreach((array)$aL10Ns as $aL10N) {
					$aData = $aL10N;
					$aData['question_id'] = $iQuestionId;
					DB::insertData('elearning_exams_questions_l10n', $aData);
				}

				$aAnswers = self::getById('elearning_exams_answers', 'question_id', $aQuestion['id']);
				foreach((array)$aAnswers as $aAnswer) {

					$aData = $aAnswer;
					$aData['id'] = 0;
					$aData['question_id'] = $iQuestionId;
					$iAnswerId = DB::insertData('elearning_exams_answers', $aData);

					$aL10Ns = self::getById('elearning_exams_answers_l10n', 'answer_id', $aAnswer['id']);
					foreach((array)$aL10Ns as $aL10N) {
						$aData = $aL10N;
						$aData['answer_id'] = $iAnswerId;
						DB::insertData('elearning_exams_answers_l10n', $aData);
					}

				}

			}

		}

	}

	protected static function getById($sTable, $sField, $iId) {

		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				#field = :id
			";

		$aSql = array(
			'table'=>$sTable,
			'field'=>$sField,
			'id'=>$iId
		);

		$aResults = DB::getQueryRows($sSql, $aSql);

		return $aResults;

	}

	public function saveEmailAttachment($sFile, $sFilename, $sType, $sLanguage) {
		
		$sPath = Util::getDocumentRoot().$this->_sEmailAttachmentPath.$this->id.'_'.$sType.'_'.$sLanguage.'_'.$sFilename;

		$aPath = pathinfo($sPath);
		
		Util::checkDir($aPath['dirname']);

		move_uploaded_file($sFile, $sPath);

	}
	
}
