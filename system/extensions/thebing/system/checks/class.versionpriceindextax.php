<?php


class Ext_Thebing_System_Checks_VersionPriceIndexTax extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		try
		{
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions_priceindex');

			$sSql = "
				SELECT
					`kidvpi`.`id` `price_index_id`
				FROM
					`kolumbus_inquiries_documents_versions_priceindex` `kidvpi` INNER JOIN
					`kolumbus_inquiries_documents_versions` `kidv` ON
						`kidv`.`id` = `kidvpi`.`version_id` INNER JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi` ON
						`kidvi`.`version_id` = `kidv`.`id` AND
						`kidvi`.`calculate` = 1 AND
						`kidvi`.`onPdf` = 1 AND
						`kidvi`.`tax` > 0 INNER JOIN
					`kolumbus_inquiries_documents` `kid` ON
						`kid`.`id` = `kidv`.`document_id` AND
						`kid`.`active` = 1 INNER JOIN
					`kolumbus_inquiries` `ki` ON
						`ki`.`id` = `kid`.`inquiry_id` AND
						`ki`.`active` = 1 INNER JOIN
					`customer_db_1` `cdb1` ON
						`cdb1`.`id` = `ki`.`idUser` AND
						`cdb1`.`active` = 1
				WHERE
					`kidvpi`.`active` = 1 AND
					`kidv`.`tax` = 2
				GROUP BY
					`kidvpi`.`id`
			";

			$aResult = (array)DB::getQueryCol($sSql);

			if(
				!empty($aResult)
			)
			{
				$sSql = "
					DELETE FROM
						`kolumbus_inquiries_documents_versions_priceindex`
					WHERE
						`id` IN(:index_ids)
				";

				$aSql = array(
					'index_ids' => $aResult,
				);	

				$rRes = DB::executePreparedQuery($sSql, $aSql);

				if(
					$rRes
				)
				{
					$oPriceIndex = new Ext_Thebing_System_Checks_AmountCache();

					$oPriceIndex->executeCheck();
				}
				else
				{
					__pout('Index delete has failed');
				}
			}	
		}
		catch(DB_QueryFailedException $e)
		{
			__pout($e->getMessage()); 
		}
		catch(Exception $e)
		{
			__pout($e->getMessage()); 
		}

		return true;
	}
	
	public function getTitle()
	{
		return 'Refresh Version Price Index Tax.';
	}

	public function getDescription()
	{
		return 'Refresh all tax for invoice items with exclusive tax.';
	}
}