<?php

class Ext_Thebing_System_Checks_Documents_LatestVersion extends GlobalChecks
{
	public function getTitle()
	{
		$sTitle = 'Latest document version';

		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Creates an index with latest version of each document. This check can take up to one hour!';

		return $sDescription;
	}

	public function isNeeded()
	{
		return true;
	}

	public function executeCheck()
	{

		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		Util::backupTable('kolumbus_inquiries_documents');

		DB::addField('kolumbus_inquiries_documents', 'latest_version', 'INT(11) NOT NULL, ADD INDEX `latest_version` (`latest_version`)');

//		$sSql = "
//			UPDATE
//				`kolumbus_inquiries_documents`
//			SET
//				`changed` = `changed`,
//				`latest_version` = (
//					SELECT 
//						`id`
//					FROM 
//						`kolumbus_inquiries_documents_versions`
//					WHERE 
//						`document_id` = `kolumbus_inquiries_documents`.`id` AND 
//						`active` =1
//					ORDER BY 
//						`version` DESC
//					LIMIT 1
//				)
//		";
//		DB::executeQuery($sSql);
		
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_inquiries_documents`
			WHERE
				`active` = 1
		";
		
		$oDB = DB::getDefaultConnection();
		
		$oCollection = $oDB->getCollection($sSql, array());
		
		foreach($oCollection as $aRowData)
		{
			$iDocId = $aRowData['id'];
			
			$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_inquiries_documents_versions`
				WHERE
					`document_id` = :document_id AND
					`active` = 1
				ORDER BY
					`version` DESC
				LIMIT
					1
			";
			
			$aSql = array(
				'document_id' => (int)$iDocId,
			);
			
			$iMaxVersionId = (int)DB::getQueryOne($sSql, $aSql);
			
			$aUpdate = array(
				'latest_version' => $iMaxVersionId,
			);
						
			$rRes = DB::updateData('kolumbus_inquiries_documents', $aUpdate, array('id' => $iDocId), true);
		
			if(!$rRes)
			{
				__pout($aUpdate);
				__pout($iDocId); 
			}
		}
		
		return true;

	}
}