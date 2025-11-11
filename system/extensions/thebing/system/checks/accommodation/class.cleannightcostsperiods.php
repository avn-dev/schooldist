<?php


class Ext_Thebing_System_Checks_Accommodation_CleanNightCostsPeriods extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Accommodation Night Costs Periods';
	}
	
	public function getDescription()
	{
		return 'Check for periods outside of season.';
	}
	
	public function executeCheck()
	{
		Util::backupTable('kolumbus_accommodations_costs_nights_periods');
		
		$iClientId = Ext_Thebing_System::getClientId();
		
		$sSql = "
			UPDATE
				`kolumbus_accommodations_costs_nights_periods`
			SET
				`active` = 0
			WHERE
				`from` = '0000-00-00' OR
				`until` = '0000-00-00'
		";
		
		DB::executeQuery($sSql);
		
		$sSql = "
			SELECT
				`period`.*,
				`cost`.`school_id`
			FROM
				`kolumbus_accommodations_costs_nights_periods` `period` INNER JOIN
				`kolumbus_accommodations_costs_categories` `cost` ON
					`cost`.`id` = `period`.`costcategory_id` AND
					`cost`.`active` = 1
			WHERE
				`period`.`active` = 1
		";
		
		$oDB		= DB::getDefaultConnection();
		$aPeriods	= $oDB->getCollection($sSql, array());
		
		foreach($aPeriods as $aPeriod)
		{
			$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_periods`
				WHERE
					`idPartnerschool` = :school_id AND
					`idClient` = :client_id AND
					`valid_from` <= :from AND
					`valid_until` >= :until
			";
			
			$aSql = array(
				'from'			=> $aPeriod['from'],
				'until'			=> $aPeriod['until'],
				'client_id'		=> $iClientId,
				'school_id'		=> $aPeriod['school_id'],
			);
			
			$aFound = (array)DB::getQueryCol($sSql, $aSql);

			if(empty($aFound))
			{
				$aUpdate	= array('active' => '0');
				$sWhere		= ' `id` = ' . $aPeriod['id'];
				
				DB::updateData('kolumbus_accommodations_costs_nights_periods', $aUpdate, $sWhere);
			}
		}
		
		return true;
	}
}