<?php

/**
 * Durch nen Bug wurden Blöcke gelöscht, aber nicht die Zuweisungen, das wurde jetzt zwar verändert, aber alte Datensätze
 * sollte man auch bereinigen...
 * 
 * @author Mehmet Durmaz
 */
class Ext_Thebing_System_Checks_Tuition_ClearDeletedBlockAllocations extends GlobalChecks
{
	public function getTitle()
	{
		return 'Check Tuition Allocations';
	}
	
	public function getDescription()
	{
		return 'Check for deleted block allocations';
	}
	
	public function executeCheck()
	{
		// Alle Zuweisungen finden die aktiv sind, aber wo die Blöcke inaktiv sind
		$sSql = '
			SELECT
				`ktbic`.`id`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 0
			WHERE
				`ktbic`.`active` = 1
		';
		
		$aResult = (array)DB::getQueryCol($sSql);
		
		foreach($aResult as $iAllocationId)
		{
			$aUpdate = array(
				'active' => 0,
			);
			
			$sWhere = ' `id` = ' . $iAllocationId;
			
			$rRes	= DB::updateData('kolumbus_tuition_blocks_inquiries_courses', $aUpdate, $sWhere);
			
			if(!$rRes)
			{
				__pout('couldnt delete allocation "' . $iAllocationId . '"!'); 
			}
		}
		
		return true;
	}
}