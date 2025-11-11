<?php


class Ext_Thebing_System_Checks_AdditionalRequestDocuments extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Additional Documents';
	}
	
	public function getDescription()
	{
		return 'Check for additional student-request documents.';
	}
	
	public function executeCheck()
	{	
		if(!Util::checkTableExists('ts_enquiries_to_documents'))
		{
			$sSql = "
				CREATE TABLE IF NOT EXISTS `ts_enquiries_to_documents` (
					`enquiry_id` INT(11) NOT NULL,
					`document_id` INT(11) NOT NULL
				)
				ENGINE=InnoDB;	
			";

			DB::executeQuery($sSql);
			
			$sSql = "
				ALTER TABLE 
					`ts_enquiries_to_documents` 
				ADD PRIMARY KEY ( `enquiry_id` , `document_id` );	
			";

			DB::executeQuery($sSql);	
		}
	
		//Alle Anfragen umstellen
		$sSql = "
			SELECT
				`ts_en`.`id` `enquiry_id`,
				`tc_co`.`id` `contact_id`,
				`old_inquiries`.`id` `old_inquiry_id`,
				`kid`.`id` `document_id`
			FROM
				`ts_enquiries` `ts_en` LEFT JOIN
				`ts_enquiries_to_inquiries` `ts_en_to_in` ON
					`ts_en_to_in`.`enquiry_id` = `ts_en`.`id` INNER JOIN
				`ts_enquiries_to_contacts` `ts_en_to_co` ON
					`ts_en_to_co`.`enquiry_id` = `ts_en`.`id` AND
					`ts_en_to_co`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_en_to_co`.`contact_id` AND
					`tc_c`.`active` = 1 INNER JOIN
				`__old_kolumbus_inquiries` `old_inquiries` ON
					`old_inquiries`.`idUser` = `tc_c`.`id` AND
					`old_inquiries`.`active` = 0 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`inquiry_id` = `old_inquiries`.`id` AND
					`kid`.`active` = 1
			WHERE
				`ts_en`.`active` = 1 AND
				`ts_en_to_in`.`inquiry_id` IS NULL
			GROUP BY
				`kid`.`id`
		";
		
		$aResult = (array)DB::getQueryRows($sSql);
		
		$aInquiryIds = array();
		
		$aInsert = array();
		
		foreach($aResult as $aRowData)
		{
			$aInquiryIds[] = $aRowData['old_inquiry_id'];
			
			$aInsert[] = array(
				'document_id'	=> $aRowData['document_id'],
				'enquiry_id'	=> $aRowData['enquiry_id'],
			);
		}
		
		$sSql = "
			UPDATE
				`kolumbus_inquiries_documents`
			SET
				`inquiry_id` = 0
			WHERE
				`inquiry_id` IN(:inquiry_ids)
		";
		
			
		$aSql = array(
			'inquiry_ids' => $aInquiryIds,
		);

		DB::executePreparedQuery($sSql, $aSql);
		
		DB::insertMany('ts_enquiries_to_documents', $aInsert, true);
		
		return true;
	}
}