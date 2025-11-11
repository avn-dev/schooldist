<?php

class Ext_Thebing_System_Checks_RegFormUsePrices extends GlobalChecks
{
	public function getTitle()
	{
		$sTitle = 'Prices settings on registration forms';

		return $sTitle;
	}


	public function getDescription()
	{
		$sDescription = 'Prices settings on registration forms';

		return $sDescription;
	}


	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_forms');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			SELECT
				`kf`.`id`
			FROM
				`kolumbus_forms` AS `kf`				INNER JOIN
				`kolumbus_forms_pages` AS `kfp`				ON
					`kf`.`id` = `kfp`.`form_id`			INNER JOIN
				`kolumbus_forms_pages_blocks` AS `kfpb`		ON
					`kfp`.`id` = `kfpb`.`page_id`
			WHERE
				`kf`.`active` = 1		AND
				`kfp`.`active` = 1		AND
				`kfpb`.`active` = 1		AND
				`kfpb`.`block_id` = 5
		";
		$aFormIDs = DB::getQueryCol($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach((array)$aFormIDs as $iFormID)
		{
			$sSQL = "
				UPDATE
					`kolumbus_forms`
				SET
					`changed` = `changed`,
					`use_prices` = 1
				WHERE
					`id` = :iFormID
			";
			$aSQL = array(
				'iFormID' => $iFormID
			);
			DB::executePreparedQuery($sSQL, $aSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return true;
	}
}