<?php


class Ext_Thebing_System_Checks_AmountCache extends GlobalChecks
{
	/**
	 * Get the check title
	 * 
	 * @return string
	 */
	public function getTitle() {
		$sTitle = 'Caching Amount Values';
		return $sTitle;
	}


	/**
	 * Get the check description
	 * 
	 * @return string
	 */
	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}


	/**
	 * Execute the check
	 * 
	 * @return bool
	 */
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

//		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_inquiries_documents_versions_priceindex` (
//				  `id` int(11) NOT NULL auto_increment,
//				  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
//				  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
//				  `active` tinyint(1) NOT NULL default '1',
//				  `user_id` int(11) NOT NULL,
//				  `creator_id` int(11) NOT NULL,
//				  `version_id` int(11) NOT NULL,
//				  `type` tinyint(1) NOT NULL,
//				  `amount_gross` decimal(15,5) NOT NULL,
//				  `amount_net` decimal(15,5) NOT NULL,
//				  `amount_provision` decimal(15,5) NOT NULL,
//				  `amount_discount_gross` decimal(15,5) NOT NULL,
//				  `amount_discount_net` decimal(15,5) NOT NULL,
//				  `amount_discount_provision` decimal(15,5) NOT NULL,
//				  `amount_vat_gross` decimal(15,5) NOT NULL,
//				  `amount_vat_net` decimal(15,5) NOT NULL,
//				  `amount_vat_provision` decimal(15,5) NOT NULL,
//				  PRIMARY KEY  (`id`),
//				  KEY `creator_id` (`creator_id`),
//				  KEY `version_id` (`version_id`),
//				  KEY `user_id` (`user_id`)
//				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
//
//		$rRes = DB::executeQuery($sSql);
//
//		if(!$rRes){
//			__pout("Create Table Error 'kolumbus_inquiries_documents_versions_priceindex'");
//			return true;
//		}

		Util::backupTable('kolumbus_inquiries_documents_versions_priceindex');

		DB::executeQuery(" TRUNCATE TABLE kolumbus_inquiries_documents_versions_priceindex; ");

		$sSql = "
			SELECT
				`kidv`.`id` `version_id`,
				SUM(
					`kidvi`.`amount`
				) `amount_gross`,
				`kidvi`.`initalcost` `type`,
				SUM(
					`kidvi`.`amount` - `kidvi`.`amount_provision`
				) `amount_net`,
				SUM(
					`kidvi`.`amount_provision`
				) `amount_provision`,
				SUM(
					`kidvi`.`amount`
					*
					`kidvi`.`amount_discount`
					/
					100
				) `amount_discount_gross`,
				SUM(
					(
						`kidvi`.`amount` - `kidvi`.`amount_provision`
					)
					*
					`kidvi`.`amount_discount` 
					/
					100
				) `amount_discount_net`,
				SUM(
					`kidvi`.`amount_provision`
					*
					`kidvi`.`amount_discount`
					/
					100
				) `amount_discount_provision`,
				SUM(
					IF(
						`kidv`.`tax` = 2,
						(
							(
								`kidvi`.`amount` * `kidvi`.`tax` / 100
							)
							-
							(
								`kidvi`.`amount` * (`kidvi`.`amount_discount` / 100) * (`kidvi`.`tax` / 100)
							)
						),
						(
							`kidvi`.`amount`
							-
							(
								`kidvi`.`amount`
								/
								(
									1
									+
									`kidvi`.`tax`
									/
									100
								)
							)
							-
							(
								`kidvi`.`amount` * `kidvi`.`amount_discount` / 100
								-
								(
									`kidvi`.`amount` * `kidvi`.`amount_discount` / 100
									/
									(
										1
										+
										`kidvi`.`tax`
										/
										100
									)
								)
							)
						)
					)
				) `amount_vat_gross`,
				SUM(
					IF(
						`kidv`.`tax` = 2,
						(
							(
								(`kidvi`.`amount` - `kidvi`.`amount_provision`) * `kidvi`.`tax` / 100
							)
							-
							(
								(`kidvi`.`amount` - `kidvi`.`amount_provision`) * (`kidvi`.`amount_discount` / 100) * (`kidvi`.`tax` / 100)
							)
						),
						(
							(`kidvi`.`amount` - `kidvi`.`amount_provision`)
							-
							(
								(`kidvi`.`amount` - `kidvi`.`amount_provision`)
								/
								(
									1
									+
									`kidvi`.`tax`
									/
									100
								)
							)
							-
							(
								(`kidvi`.`amount` - `kidvi`.`amount_provision`) * `kidvi`.`amount_discount` / 100
								-
								(
									(`kidvi`.`amount` - `kidvi`.`amount_provision`) * `kidvi`.`amount_discount` / 100
									/
									(
										1
										+
										`kidvi`.`tax`
										/
										100
									)
								)
							)
						)
					)
				) `amount_vat_net`,
				SUM(
					IF(
						`kidv`.`tax` = 2,
						(
							(
								`kidvi`.`amount_provision` * `kidvi`.`tax` / 100
							)
							-
							(
								`kidvi`.`amount_provision` * (`kidvi`.`amount_discount` / 100) * (`kidvi`.`tax` / 100)
							)
						),
						(
							`kidvi`.`amount_provision`
							-
							(
								`kidvi`.`amount_provision`
								/
								(
									1
									+
									`kidvi`.`tax`
									/
									100
								)
							)
							-
							(
								`kidvi`.`amount_provision` * `kidvi`.`amount_discount` / 100
								-
								(
									`kidvi`.`amount_provision` * `kidvi`.`amount_discount` / 100
									/
									(
										1
										+
										`kidvi`.`tax`
										/
										100
									)
								)
							)
						)
					)
				) `amount_vat_provision`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`document_id` = `kid`.`id` AND
					`kidv`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 AND
					(
						`kidvi`.`initalcost` = 0 OR
						`kidvi`.`initalcost` = 1
					) AND
					`kidvi`.`calculate` = 1 AND
					`kidvi`.`onPdf` = 1 LEFT JOIN
				`kolumbus_inquiries_documents_versions_priceindex` `kidvp` ON
					`kidvp`.`version_id` = `kidv`.`id`
			WHERE 
				`kid`.`active` = 1 AND
				`kidvp`.`id` IS NULL
			GROUP BY
				`kidv`.`id`,
				`kidvi`.`initalcost`
		";

		$aResult = (array)DB::getQueryData($sSql);

		$sSql = "INSERT INTO
						`kolumbus_inquiries_documents_versions_priceindex`
					SET
						`version_id` = :version_id,
						`type` = :type,
						`amount_gross` = :amount_gross,
						`amount_net` = :amount_net,
						`amount_provision` = :amount_provision,
						`amount_discount_gross` = :amount_discount_gross,
						`amount_discount_net` = :amount_discount_net,
						`amount_discount_provision` = :amount_discount_provision,
						`amount_vat_gross` = :amount_vat_gross,
						`amount_vat_net` = :amount_vat_net,
						`amount_vat_provision` = :amount_vat_provision
				";
		
		$aErrors = array();
		
		foreach($aResult as $iCount => $aData){
			
			$aSql = array();
			$aSql['version_id']					= (int)$aData['version_id'];
			$aSql['type']						= (int)$aData['type'];
			$aSql['amount_gross']				= (float)$aData['amount_gross'];
			$aSql['amount_net']				= (float)$aData['amount_net'];
			$aSql['amount_provision']			= (float)$aData['amount_provision'];
			$aSql['amount_discount_gross']		= (float)$aData['amount_discount_gross'];
			$aSql['amount_discount_net']		= (float)$aData['amount_discount_net'];
			$aSql['amount_discount_provision']	= (float)$aData['amount_discount_provision'];
			$aSql['amount_vat_gross']			= (float)$aData['amount_vat_gross'];
			$aSql['amount_vat_net']			= (float)$aData['amount_vat_net'];
			$aSql['amount_vat_provision']		= (float)$aData['amount_vat_provision'];
			
			$rRes = DB::executePreparedQuery($sSql, $aSql);

			if(!$rRes)
			{
				__pout("calculate price errro");
				__pout($sSql);
				__pout($aSql);
			}
		}
		
		return true;
	}
}