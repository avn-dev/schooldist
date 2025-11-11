<?php

class Ext_Elearning_Exam_Participants {

	protected $_oExam = false;
	protected $_oCustomerDB = false;
	protected $_aParticipant = array('id'=>0, 'state'=>'', 'attempts'=>0, 'last_email'=>'');

	public function __construct($oExam, $iParticipantId=0) {
		
		$this->_oExam = $oExam;
		$this->_aParticipant['id'] = $iParticipantId;

		if($this->_aParticipant['id']) {
			$this->_getData();
		}

		if($this->_oExam->customer_db) {
			$this->_oCustomerDB = new Ext_CustomerDB_DB($this->_oExam->customer_db);
		}
		
	}	
	
	public function __get($sField) {
		if(isset($this->_aParticipant[$sField])) {
			return $this->_aParticipant[$sField];
		}
	}

	public function __set($sField, $mValue) {
		if(isset($this->_aParticipant[$sField])) {
			$this->_aParticipant[$sField] = $mValue;
		}
	}

	protected function _getData() {
		$sSql = "
				SELECT 
					*,
					UNIX_TIMESTAMP(`changed`) `changed`,
					UNIX_TIMESTAMP(`created`) `created` 
				FROM
					`elearning_exams_participants`
				WHERE
					`id` = :id AND
					`exam_id` = :exam_id
				";
		$aSql = array('id'=>$this->_aParticipant['id'], 'exam_id'=>$this->_oExam->id);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		if(!empty($aData[0])) {
			$this->_aParticipant = $aData[0];
		}
	}

	public function setState($sState, $sInfo='') {

		$this->state = $sState;

		$this->save();

		$aInsert = array();
		$aInsert['exam_id'] = $this->_oExam->id;
		$aInsert['user_id'] = $this->_aParticipant['id'];
		$aInsert['state'] = $sState;
		$aInsert['info'] = $sInfo;
		DB::insertData('elearning_exams_logs', $aInsert);

	}
	
	public function addAttempt() {
		
		$this->attempts = $this->attempts + 1;
		
		$this->save();

	}
	
	public function getReport($iLastResultId=null) {
		
		$aReport = Ext_Elearning_Exam_Result::loadLastResult($this->_oExam->id, $this->_aParticipant['id'], 'de', $iLastResultId);
		return $aReport;
		
	}
	
	public function saveInviteState($sStep='') {
		
		$this->_aParticipant['invited'] = date('Y-m-d H:i:s');
		
		$this->save();

		$this->setState('invited', $sStep);

	}
	
	public function save() {

		$sSql = "
				REPLACE
					`elearning_exams_participants` 
				SET
					`id` = :id,
					`exam_id` = :exam_id,
					`changed` = NOW(),
					`state` = :state,
					`attempts` = :attempts,
					`last_email` = :last_email,
					`invited` = :invited
				";
		$aSql = array();
		$aSql['state'] = (string)$this->_aParticipant['state'];
		$aSql['attempts'] = (int)$this->_aParticipant['attempts'];
		$aSql['last_email'] = $this->_aParticipant['last_email'];
		$aSql['invited'] = (string)$this->_aParticipant['invited'];
		$aSql['id'] = (int)$this->_aParticipant['id'];
		$aSql['exam_id'] = (int)$this->_oExam->id;
		DB::executePreparedQuery($sSql, $aSql);

	}
	
	public function getData() {

		$aParticipant = $this->_oCustomerDB->getCustomerByUniqueField('id', $this->_aParticipant['id']);
		$aParticipant['id'] = $aParticipant[$this->_oExam->customer_db_id];
		$aParticipant['firstname'] = $aParticipant[$this->_oExam->customer_db_firstname];
		$aParticipant['lastname'] = $aParticipant[$this->_oExam->customer_db_lastname];
		$aParticipant['email'] = $aParticipant[$this->_oExam->customer_db_email];
		$aParticipant['group'] = $aParticipant[$this->_oExam->customer_db_group];
		$aParticipant['level'] = $aParticipant[$this->_oExam->customer_db_level];
		$aParticipant['location'] = $aParticipant[$this->_oExam->customer_db_location];

		return $aParticipant;
		
	}
	
	public function getSummary() {

		$aParticipantData = $this->_oCustomerDB->getCustomerByUniqueField('id', $this->_aParticipant['id']);
		$aParticipant = array();
		$aParticipant['id'] = $aParticipantData[$this->_oExam->customer_db_id];
		$aParticipant['firstname'] = $aParticipantData[$this->_oExam->customer_db_firstname];
		$aParticipant['lastname'] = $aParticipantData[$this->_oExam->customer_db_lastname];
		$aParticipant['email'] = $aParticipantData[$this->_oExam->customer_db_email];
		$aParticipant['group'] = $aParticipantData[$this->_oExam->customer_db_group];
		$aParticipant['level'] = $aParticipantData[$this->_oExam->customer_db_level];
		$aParticipant['location'] = $aParticipantData[$this->_oExam->customer_db_location];

		return $aParticipant;
		
	}
	
	public function delete() {
		
//		$sSql = "
//				DELETE FROM
//					`elearning_exams_participants` 
//				WHERE
//					`id` = :id AND
//					`exam_id` = :exam_id
//				";
//		$aSql = array();
//		$aSql['id'] = (int)$this->_aParticipant['id'];
//		$aSql['exam_id'] = (int)$this->_oExam->id;
//		DB::executePreparedQuery($sSql, $aSql);

		$this->setState('deleted');

		$this->_oCustomerDB->deleteCustomer($this->_aParticipant['id']);

	}

	public function getLog($sState) {
		
		$sSql = "
			SELECT 
				* 
			FROM 
				elearning_exams_logs 
			WHERE 
				user_id = :user_id AND 
				`state` = :state  AND 
				`exam_id` = :exam_id 
			ORDER BY 
				`changed` ";
		$aSql = array(
			'user_id'=>$this->id, 
			'state'=>$sState,
			'exam_id' => (int)$this->_oExam->id
		);
		$aLogs = DB::getPreparedQueryData($sSql, $aSql);

		return $aLogs;
		
	}
	
}
