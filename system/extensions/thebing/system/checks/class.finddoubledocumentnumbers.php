<?php

/**
 * Sucht doppelte Rechnungsnummern und verschickt eine E-Mail an den Support 
 */
class Ext_Thebing_System_Checks_FindDoubleDocumentNumbers extends GlobalChecks {
	
	public function getTitle() {
		$sTitle = 'Checking the number ranges';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'The system is checked for false (double) invoice numbers';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		global $system_data;
		
		$system_data['debugmode'] = 2;
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		$sSql = "
		
			SELECT 
				`kid`.`id`,
				`kid`.`inquiry_id`,
				`kid`.`document_number`,
				`kid`.`numberrange_id`,
				(
					SELECT 
						COUNT(*) `count`
					FROM
						`kolumbus_inquiries_documents` `sub_kid` INNER JOIN
						`ts_inquiries` `sub_ts_i` ON
							`sub_ts_i`.`id` = `sub_kid`.`inquiry_id`
					WHERE
						`sub_kid`.`active`			= 1 AND
						`sub_kid`.`document_number`	= `kid`.`document_number` AND
						`sub_kid`.`numberrange_id`	= `kid`.`numberrange_id` AND
						`sub_ts_i`.`active` = 1 AND
						(
							`ts_i`.`group_id` = 0 OR
							`sub_ts_i`.`group_id` != `ts_i`.`group_id`
						)
				) `count`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kid`.`inquiry_id`
			WHERE
				`kid`.`active` = 1 AND
				`kid`.`document_number` != '' AND
				`ts_i`.`active` = 1
			HAVING
				`count` > 1
			ORDER BY
				`kid`.`numberrange_id`, `kid`.`document_number`
		";
		
		$aData = DB::getQueryData($sSql);
		
		$sMessage = 'Doppelte Rechnungsnummern bei %system';
		$aMessages = array();
		
		
		$aGrouped = array();
		
		foreach($aData as $aFound){
			
			$sKey = $aFound['numberrange_id'].'_'.$aFound['document_number'];
			
			$oDocument	= Ext_Thebing_Inquiry_Document::getInstance($aFound['id']);
			$oInquiry	= $oDocument->getInquiry();
			$oCustomer	= $oInquiry->getCustomer();
			$oNumberrange = Ext_TC_NumberRange::getInstance($aFound['numberrange_id']);
			
			$aGrouped[$sKey]['document_number']		= $aFound['document_number'];
			$aGrouped[$sKey]['numberrange_id']		= $aFound['numberrange_id'];
			$aGrouped[$sKey]['count']				= $aFound['count'];
			$aGrouped[$sKey]['numberrange']			= $oNumberrange->getName();
			
			$aInquiry							= array(
				'customer' => $oCustomer->getCustomerNumber().' - '.$oCustomer->getName(),
				'school' => $oInquiry->getSchool()->getName()
			);
			
			$aGrouped[$sKey]['inquiries'][]			= $aInquiry;		
			
		}
		
		foreach($aGrouped as $aFound){
			
			$sSubTitle		= ' <br/><br/>  ';
			$sSubTitle		.= '<u>Rechnungsnummer <b>%number</b> aus dem Nummernkreis %numberrange  wurde <b>%count</b> mal gefunden!</u>';
			$sSubMessage	= str_replace(array('%number', '%count', '%numberrange'), array($aFound['document_number'], $aFound['count'], $aFound['numberrange']), $sSubTitle);
			
			$sSubMessage .= '<pre>';
			
			foreach($aFound['inquiries'] as $aInquiries){
				$sSubMessage .= '<b>'.$aInquiries['customer'].'</b> wurde in der Schule <b>'.$aInquiries['school'].'</b> gefunden';
				$sSubMessage .= '<br/>';
			}
			
			$sSubMessage .= '</pre>';
			
			$aMessages[] = $sSubMessage;
		}

		if(!empty($aMessages)){
			
			$sMessageBody = implode('<br/>', $aMessages);
			$sMessage = str_replace('%system', $_SERVER['HTTP_HOST'], $sMessage);

			if(Ext_Thebing_Util::isDevSystem()){
				echo $sMessage;
				echo $sMessageBody;
			}

			$oMail			= new WDMail();
			$oMail->subject = $sMessage;
			$oMail->html	= $sMessageBody;
			$oMail->send('support@thebing.com');
			
		}
		
		return true;

	}
	
}
