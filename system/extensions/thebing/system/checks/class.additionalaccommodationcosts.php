<?php


class Ext_Thebing_System_Checks_AdditionalAccommodationCosts extends GlobalChecks {

	public function getTitle() 
	{
		$sTitle = 'Accommodation Costs';
		return $sTitle;
	}

	public function getDescription() 
	{
		$sDescription = 'Add roomtype and meal to additonal costs.';
		return $sDescription;
	}

	public function executeCheck()
	{
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$sCurrentName	= 'kolumbus_costs_accommodations';
		$sBackupName	= '__kolumbus_costs_accommodations_backup';
		
		$aColumns = DB::describeTable($sCurrentName);
		
		if(
			isset($aColumns['roomtype_id']) &&
			isset($aColumns['meal_id'])
		)
		{
			$bExistsOldTable = Ext_Thebing_Util::checkTableExists($sBackupName);
			
			if(
				!$bExistsOldTable
			){
				return true;
			}
				
			$sSql = "
				TRUNCATE
					#table_current
			";
			
			$aSql = array(
				'table_current' => $sCurrentName
			);
		
			DB::executeQuery($sSql);
		}
		elseif(
			isset($aColumns['roomtype_id']) ||	
			isset($aColumns['meal_id'])
		){
			return true;
		}
		else 
		{
			$sSql = "
				RENAME TABLE
					#table_current
				TO
					#table_backup
			";
			
			$aSql = array(
				'table_current'	=> $sCurrentName,
				'table_backup'	=> $sBackupName
			);
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		$sSql = "
			CREATE TABLE IF NOT EXISTS `kolumbus_costs_accommodations` (
			  `kolumbus_costs_id` int(11) NOT NULL,
			  `customer_db_8_id` int(11) NOT NULL,
			  `roomtype_id` int(11) NOT NULL,
			  `meal_id` int(11) NOT NULL,
			  KEY `kolumbus_costs_id` (`kolumbus_costs_id`,`customer_db_8_id`,`roomtype_id`,`meal_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;	
		";
		
		DB::executeQuery($sSql);
		
		$sSql = "
			SELECT
				`kcosa`.`kolumbus_costs_id`,
				`kcosa`.`customer_db_8_id`,
				`cdb4`.`id` `accommodation_id`,
				`cdb4`.`ext_6` `meals`,
				`cdb10`.`id` `roomtype_id`
			FROM
				#table_backup `kcosa` INNER JOIN
				`kolumbus_accommodations_categories` `kac` ON
					`kac`.`id` = `kcosa`.`customer_db_8_id` AND
					`kac`.`active` = 1 INNER JOIN
				`customer_db_4` `cdb4` ON
					`cdb4`.`ext_1` = `kac`.`id` AND
					`cdb4`.`active` = 1 INNER JOIN
				`kolumbus_rooms` `kr` ON
					`kr`.`accommodation_id` = `cdb4`.`id` AND
					`kr`.`active` = 1 INNER JOIN
				`customer_db_10` `cdb10` ON
					`cdb10`.`id` = `kr`.`type_id` AND
					`cdb10`.`active` = 1
		";
		
		$aSql = array(
			'table_backup' => $sBackupName
		);
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aCombinations = array();
		
		foreach($aResult as $aRowData)
		{
			$sMeals = $aRowData['meals'];
			
			if(
				!empty($sMeals)
			)
			{
				$aMeals = (array)json_decode($sMeals);
				
				foreach($aMeals as $iMealId)
				{
					$sKey = $aRowData['kolumbus_costs_id']	. '_'
							.$aRowData['customer_db_8_id']	. '_'
							.$aRowData['roomtype_id']		. '_'
							.$iMealId;
					
					$aCombinations[$sKey] = 1;
				}
			}
		}
		
		foreach($aCombinations as $sCombination => $mValue)
		{
			$aCombination = explode('_', $sCombination);
			
			$aInsertData = array(
				'kolumbus_costs_id' => $aCombination[0],
				'customer_db_8_id'	=> $aCombination[1],
				'roomtype_id'		=> $aCombination[2],
				'meal_id'			=> $aCombination[3],
			);
			
			DB::insertData($sCurrentName, $aInsertData);
		}


		return true;
	}
	
}
