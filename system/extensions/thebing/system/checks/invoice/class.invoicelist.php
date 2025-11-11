<?php

/**
 * Alle Rechnungen einer Buchung in eine Indextabelle packen, 
 * damit man sie in der Inbox suchen kann (aus Performance gründen kein direkter join mehr)
 */
class Ext_Thebing_System_Checks_Invoice_InvoiceList extends GlobalChecks
{
	public function getTitle()
	{
		return 'Create Invoice List Index';
	}
	
	public function getDescription()
	{
		return 'Generate an index to search for invoice numbers.';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$sBackup = Util::backupTable('ts_inquiries_documents_index');
	
		if(!$sBackup)
		{
			__pout('Backup failed!');
			
			return true;
		}
		
		$aColumns = DB::describeTable('ts_inquiries_documents_index', true);

		if(!isset($aColumns['invoice_list']))
		{
			//Neue Spalte einfügen, wenn noch nicht vorhanden
			$sSql = '
				ALTER TABLE 
					`ts_inquiries_documents_index` 
				ADD 
					`invoice_list` VARCHAR( 500 ) NOT NULL 
			';
			
			$rRes = DB::executeQuery($sSql);
			
			if(!$rRes)
			{
				__pout('Couldnt add new column!'); 
				
				return true;
			}
			
			$sSql = '
				ALTER TABLE 
					`ts_inquiries_documents_index` 
				ADD INDEX 
					`invoice_list` ( `invoice_list` ) 
			';
			
				$rRes = DB::executeQuery($sSql);
			
			if(!$rRes)
			{
				__pout('Couldnt add index to new column!'); 
				
				return true;
			}
			
		}
		
		$sSql = "
			SELECT
				GROUP_CONCAT(
					`kid`.`document_number`
				) `invoice_list`,
				`ts_i_d_i`.`id` `index_id`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kid`.`inquiry_id` AND
					`ts_i`.`active` = 1 INNER JOIN
				`ts_inquiries_documents_index` `ts_i_d_i` ON
					`ts_i_d_i`.`inquiry_id` = `ts_i`.`id`
			WHERE
				`kid`.`active` = 1 AND
				`kid`.`type` IN (:types)
			GROUP BY
				`ts_i`.`id`
		";
		
		//Rechnungsrelevante Dokumente
		$aTypes			= Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');
		
		$aSql			= array(
			'types'		=> $aTypes
		);
		
		$oDB			= DB::getDefaultConnection();
		$oCollection	= $oDB->getCollection($sSql, $aSql);

		foreach($oCollection as $aRow)
		{
			$aUpdate = array(
				'invoice_list' => $aRow['invoice_list']
			);
			
			$sWhere = ' id = ' . $aRow['index_id'];
			
			$rRes = DB::updateData('ts_inquiries_documents_index', $aUpdate, $sWhere);
			
			if(!$rRes)
			{
				__pout('Couldnt add index!');
				__pout($aUpdate); 
				__pout($sWhere); 
			}
		}
		
		return true;
	}
}