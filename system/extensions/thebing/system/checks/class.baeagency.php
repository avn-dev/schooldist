<?php

class Ext_Thebing_System_Checks_BaeAgency extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Add Provisiongroup and PaymentGroup infos to Agencies';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Add Provisiongroup and PaymentGroup infos to Agencies';
		return $sDescription;
	}


	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_agency_provison_groups');
		Ext_Thebing_Util::backupTable('kolumbus_agencies_payments_groups_assignments');
		
		// Alle Agenturen holen
		$sSql = "SELECT
						*
					FROm
						`kolumbus_agencies`
					WHERE
						`active` = 1
				";
		$aSql = array();
		
		$oDB = DB::getDefaultConnection();
		$aResult = $oDB->getCollection($sSql, $aSql);
		
		foreach($aResult as $aRowData){
			
			// Provisionsgruppen holen der Agentur
			$sSql = "SELECT
							*
						FROM
							`kolumbus_agency_provison_groups`
						WHERE
							`agency_id` = :agency_id AND
							`active` = 1
					";
			$aSql = array();
			$aSql['agency_id'] = $aRowData['id'];
			
			$aProvisiongroups = DB::getPreparedQueryData($sSql, $aSql);
			
			// Nur wenn noch keine existiert neu einfügen
			if(empty($aProvisiongroups)){
				
				$sSql = "INSERT INTO
								`kolumbus_agency_provison_groups`
							SET
								`active` = 1,
								`agency_id` = :agency_id,
								`description` = :description,
								`group_id` = :group_id,
								`valid_from = :valid_from,
								`valid_until` = :valid_until
						";

				$aSql = array();
				$aSql['agency_id']		= (int)$aRowData['id'];
				$aSql['description']	= '20% Commission';
				$aSql['group_id']		= (int)1;
				$aSql['valid_from']		= '2012-10-01';
				$aSql['valid_until']	= '0000-00-00';
				
				DB::executePreparedQuery($sSql, $aSql);
				
				
			}
			
			// Bezahlgruppen holen der Agenturen
			$sSql = "SELECT
							*
						FROM
							`kolumbus_agencies_payments_groups_assignments`
						WHERE
							`agency_id` = :agency_id AND
							`active` = 1
					";
			$aSql = array();
			$aSql['agency_id'] = $aRowData['id'];
			
			$aPaymentgroups = DB::getPreparedQueryData($sSql, $aSql);
			
			if(empty($aPaymentgroups)){
				$sSql = "INSERT INTO
								`kolumbus_agencies_payments_groups_assignments`
							SET
								`active` = 1,
								`agency_id` = :agency_id,
								`description` = :description,
								`group_id` = :group_id,
								`valid_from = :valid_from,
								`valid_until` = :valid_until
						";

				$aSql = array();
				$aSql['agency_id']		= (int)$aRowData['id'];
				$aSql['description']	= '2 weeks prior';
				$aSql['group_id']		= (int)2;
				$aSql['valid_from']		= '2012-10-01';
				$aSql['valid_until']	= '0000-00-00';
				
				DB::executePreparedQuery($sSql, $aSql);
			}
			
			
		}
		
		
		
		

		return true;

	}

}
