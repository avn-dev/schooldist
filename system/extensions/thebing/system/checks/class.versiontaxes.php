<?php

class Ext_Thebing_System_Checks_Versiontaxes extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions');

		
		// Alle Clients
		$sSql = "SELECT `id` FROM `kolumbus_clients` WHERE `active` = 1";
		$aClients = DB::getQueryData($sSql);
		
		foreach((array)$aClients as $aClient){
			$aSql = array();

			// Alle Schulen für diesen Client
			$sSql = "SELECT `id`, `ext_341` FROM `customer_db_2` WHERE `active` = 1 AND `idClient` = " . (int)$aClient['id'];
			$aSchools = DB::getQueryData($sSql);
			
			foreach((array)$aSchools as $aSchool){
				
				$sSql = "SELECT
								`kidv`.`id`
							FROM
								`kolumbus_inquiries_documents_versions` `kidv` INNER JOIN
								`kolumbus_inquiries_documents` `kid` ON
									`kid`.`id` = `kidv`.`document_id` INNER JOIN
								`kolumbus_inquiries` `ki` ON
									`ki`.`id` = `kid`.`inquiry_id`
							WHERE
								`ki`.`crs_partnerschool` = :school_id AND
								`kidv`.`tax` = 0
						";
					$aSql['school_id'] = (int)$aSchool['id'];
					
					$aVersions = DB::getPreparedQueryData($sSql, $aSql);
					
					foreach((array)$aVersions as $aVersion){
						$sSql = "UPDATE
										`kolumbus_inquiries_documents_versions`
									SET
										`tax` = :tax
									WHERE
										`id` = :id
								";
						$aSql = array();
						$aSql['tax'] = (int)$aSchool['ext_341'];
						$aSql['id'] = (int)$aVersion['id'];
						
						DB::executePreparedQuery($sSql, $aSql);
					}
			}
			
		}
		
		
		
		
		

		return true;
	}

	public function getTitle()
	{
		return 'VAT rate per line item.';
	}

	public function getDescription()
	{
		return 'Check assigned taxes on invoices.';
	}
	
}