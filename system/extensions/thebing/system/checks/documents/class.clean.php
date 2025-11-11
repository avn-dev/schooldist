<?php

/**
 * Dokumente ohne Verbindung löschen & Index erneuern
 */
class Ext_Thebing_System_Checks_Documents_Clean extends GlobalChecks
{
	public function executeCheck()
	{
		$sBackup = Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');
		
		if(!$sBackup)
		{
			__pout('Couldnt create backup table!'); 


			return true;
		}
		
		$sSql = "
			SELECT
				`kid`.`id`
			FROM
				`kolumbus_inquiries_documents` `kid` LEFT JOIN
				`ts_enquiries_offers_to_documents` `offer_relation` ON
					`offer_relation`.`document_id` = `kid`.`id` LEFT JOIN
				`ts_enquiries_to_documents` `enquiry_relation` ON
					`enquiry_relation`.`document_id` = `kid`.`id` LEFT JOIN
				`ts_manual_creditnotes_to_documents` `mc_relation` ON
					`mc_relation`.`document_id` = `kid`.`id` LEFT JOIN
				`ts_inquiries` `inquiry_relation` ON
					`inquiry_relation`.`id` = `kid`.`inquiry_id` AND
					`inquiry_relation`.`id` > 0 AND
					`inquiry_relation`.`active` = 1
			WHERE
				`offer_relation`.`enquiry_offer_id` IS NULL AND
				`enquiry_relation`.`enquiry_id` IS NULL AND
				`mc_relation`.`manual_creditnote_id` IS NULL AND
				`inquiry_relation`.`id` IS NULL AND
				`kid`.`active` = 1
		";
		
		$aDocIdsToClean = (array)DB::getQueryCol($sSql);
		
		$sSql = "
			UPDATE
				`kolumbus_inquiries_documents`
			SET
				`active` = 0
			WHERE
				`id` IN(:clean_ids)
		";
		
		$aSql = array(
			'clean_ids' => $aDocIdsToClean
		);
		
		$rRes = DB::executePreparedQuery($sSql, $aSql);
		
		if(!$rRes)
		{
			__pout('Couldnt clean documents!'); 
		}
		else
		{
			$oCheck = new Ext_Thebing_System_Checks_Index_Reset_Document();
			
			$oCheck->executeCheck(); 
		}

		return true;
	}
	
	public function getTitle()
	{
		return 'Check Documents';
	}
	
	public function getDescription()
	{
		return 'Check Documents without connection to inquiry/offer';
	}
}