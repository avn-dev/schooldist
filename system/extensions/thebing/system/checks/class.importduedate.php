<?php

class Ext_Thebing_System_Checks_ImportDueDate extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Import Finalpay Due Date';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Import Finalpay Due Date';
		return $sDescription;
	}

	public function executeCheck(){
		global $user_data;
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$user_data['cms'] = 0;

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions');

		$sSql = " SELECT
						`kidv`.`id`
					FROM
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kidv`.`document_id` = `kid`.`id`
					WHERE
						(
							`kidv`.`amount_finalpay_due` <= 0 OR
							`kidv`.`amount_finalpay_due` IS NULL
						) AND
						`kidv`.`active` = 1 AND
						`kid`.`active` = 1
				";

		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $aData){
			
			$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($aData['id']);
			if(is_object($oVersion)){
				
				$oDocument = $oVersion->getDocument();
				if(is_object($oDocument)){

					$oInquiry = $oDocument->getInquiry();
					if(is_object($oInquiry)){

						$iFinalPay = $oInquiry->calcuateFinalpayDue();
						$iPrepay = $oInquiry->calcuatePrepayDue($oDocument->created);

						$sFinalPay = '0000-00-00';
						$sPrepay	= '0000-00-00';
						
						if($iFinalPay > 0){
							$sFinalPay = date('Y-m-d', $iFinalPay);
						}
						
						if($iPrepay > 0){
							$sPrepay = date('Y-m-d', $iPrepay);
						}

						$oVersion->amount_finalpay_due = $sFinalPay;

						if(
							(
								$oVersion->amount_prepay_due == '' ||
								$oVersion->amount_prepay_due == '0000-00-00'
							) &&
							$oVersion->amount_prepay > 0
						){
							$oVersion->amount_prepay_due = $sPrepay;
						}

						$oVersion->save();
					}
				}

			}

		}

		return true;

	}

}
