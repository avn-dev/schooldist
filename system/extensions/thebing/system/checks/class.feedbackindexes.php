<?php


class Ext_Thebing_System_Checks_FeedbackIndexes extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Feedback Indexes';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Feedback Indexes';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try{	
			$sSql = "ALTER TABLE `kolumbus_feedback_customer` ADD INDEX `active` ( `active` )";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_customer_answer` ADD INDEX `customer_feedback_id`( `customer_feedback_id` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_customer_answer` ADD INDEX `question_id` ( `question_id` )";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_customer_answer` ADD INDEX `parent_id` ( `parent_id` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_customer_answer` ADD INDEX `active` ( `active` )";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_question` ADD INDEX `active` ( `active` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_question` ADD INDEX `type` ( `type` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_question` ADD INDEX `answer_type` ( `answer_type` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_answer` ADD INDEX `active` ( `active` )";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_answer` ADD INDEX `note_id` ( `note_id` )";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_note` ADD INDEX `active` ( `active` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_feedback_customer_answer` ADD INDEX `answer` ( `answer` ) ";
			DB::executeQuery($sSql);
			
			$sSql = "ALTER TABLE `kolumbus_currency_factor` ADD INDEX `c_a_d` ( `currency_id` , `active` , `date` ) ";
			DB::executeQuery($sSql);
			
			
		}catch(Exception $exc){ 
			return true;
		}

	
		
		
		
		return true;
	}

	
	public static function report($aError, $aInfo){
		
		$oMail = new WDMail();
		$oMail->subject = 'Inquiry Structure';
		
		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($aInfo, 1)."\n\n";
		
		if(!empty($aError)){
			$sText .= '------------ERROR------------';
			$sText .= "\n\n";
			$sText .= print_r($aError, 1);
		}
		
		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
				
	}
	

	
	
}



