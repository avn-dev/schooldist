<?php


class Ext_Thebing_System_Checks_Convertinquiry extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries');

		$sSql = "
			SELECT
				`cdb1`.*
			FROM
				`customer_db_1` `cdb1` LEFT JOIN
				`kolumbus_inquiries` `ki` ON
					`cdb1`.`id` = `ki`.`idUser`
			WHERE
				`cdb1`.`active` = 1 AND `ki`.`id` IS NULL
		";

		$aCustomers = DB::getQueryRows($sSql);

		foreach((array)$aCustomers as $aCustomerData){

			$iCustomerId = (int)$aCustomerData['id'];
			$iAgencyId	 = (int)$aCustomerData['ext_45'];
			$iRefererId  = (int)$aCustomerData['ext_44'];
			$iSchoolId	 = (int)$aCustomerData['ext_31'];
			$iOffice	 = (int)$aCustomerData['office'];

			$aSchoolData = DB::getRowData('customer_db_2', $iSchoolId);

			if( empty($aSchoolData) || 0 >= $iOffice ){
				__pout("clientID or schoolID not found for customer: ".$aCustomerData['ext_1'].', '.$aCustomerData['ext_2'].' ('.$aCustomerData['id'].')');
				continue;
			}

			$aInsertData			= array();
			$aInsertData['created']				= $aCustomerData['created'];
			$aInsertData['idUser']				= $iCustomerId;
			$aInsertData['crs_partnerschool']	= $iSchoolId;
			$aInsertData['office']				= $iOffice;
			$aInsertData['active']				= 0;

			// Wie sind Sie auf uns aufmerksam geworden?
			$aInsertData['referer_select']		= $iRefererId;

			// Übernahme der Agentur ID/ Bezahlinfos
			$aResultAgency = DB::getRowData('kolumbus_agencies', $iAgencyId);

			if(!empty($aResultAgency)){
				$aInsertData['idAgency']				= $iAgencyId;
				$aInsertData['payment_method']			= $aResultAgency['ext_26'];
				$aInsertData['payment_method_comment']	= $aResultAgency['ext_38'];
			}

			$aInsertData['currency_id']	= $aSchoolData['currency'];

			// Save Inquiry
			try{
				$rRes	= DB::insertData('kolumbus_inquiries', $aInsertData);
				if(!$rRes){
					__pout("couldn't save customer: ".$aCustomerData['ext_1'].', '.$aCustomerData['ext_2'].' ('.$aCustomerData['id'].')');
				}

			}catch(Exception $e){
				__pout($e->getMessage());
			}catch(DB_QueryFailedException $e){
				__pout($e->getMessage());
			}
		}

		return true;
	}

	public function getTitle()
	{
		return 'Convert customers';
	}

	public function  getDescription()
	{
		return 'Convert customers to inquiries.';
	}
}
