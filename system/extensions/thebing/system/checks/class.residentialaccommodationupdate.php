<?php

/**
 * Check updatet die Checkboxen für Erwachsene und Kinder auf 1 für alle Unterkünfte die zum
 * Residential Matching gehören. Diese option kam später hinzu.
 */
class Ext_Thebing_System_Checks_ResidentialAccommodationUpdate extends GlobalChecks { 

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Residential Accommodation Cache';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Residential Accommodation Cache';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('customer_db_4');
		
		$sSql = "SELECT
						`cdb4`.`id`
					FROM
						`customer_db_4` `cdb4` INNER JOIN
						`customer_db_8` `cdb8` ON
							`cdb8`.`id` = `cdb4`.`ext_1` AND
							`cdb8`.`ext_6` = 0
				";
		$aSql = array();
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		foreach($aResult as $aData){
			
			$sSql = "UPDATE
							`customer_db_4`
						SET
							`ext_35` = 1,
							`ext_36` = 1
						WHERE
							`id` = :id
					";
			$aSql['id'] = (int)$aData['id'];
			
			DB::executePreparedQuery($sSql, $aSql);
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



