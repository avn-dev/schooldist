<?php


class Ext_Thebing_System_Checks_GroupStructure extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Change Group Structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Convert Groups to new contacts structure';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aError = array();
		$aInfo = array();

		Ext_Thebing_Util::backupTable('kolumbus_groups');
		
		$sSql = "ALTER TABLE `kolumbus_groups` CHANGE `nationality_id` `nationality_id` VARCHAR( 2 ) NOT NULL";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `invoice_id` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `invoice_net_id` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `country_id` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `nationality` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `airport` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `airport2` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `airline` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `airline2` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `flightnumber` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `flightnumber2` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `group_address` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `arrival_time` ";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_groups` DROP `departure_time` ";
		DB::executeQuery($sSql);
		
		// Nationalitäten auslesen
		
		$sSql = "SELECT
						`k_g`.`id`,
						IFNULL(`dc`.`cn_iso_2`, '') `nationality`
					FROM
						`kolumbus_groups` `k_g` LEFT JOIN
						`data_countries` `dc` ON
						`dc`.`id` = `k_g`.`nationality_id`
					";
		$aResult = DB::getQueryData($sSql);
		
		
		foreach($aResult as $aData){
			$sSql = "UPDATE
							`kolumbus_groups`
						SET
							`nationality_id` = :nationality
						WHERE
							`id` = :id
					";
			$aSql = array();
			$aSql['nationality']	= $aData['nationality'];
			$aSql['id']				= $aData['id'];
			
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



