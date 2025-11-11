<?php


class Ext_Thebing_System_Checks_TuitionAllocation extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		//Backup erstellen
		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks_inquiries_courses');

		//inaktive Zuweisungen entfernen
		$sSql = "
			DELETE FROM
				`kolumbus_tuition_blocks_inquiries_courses`
			WHERE
				`active` = 0
		";

		DB::executeQuery($sSql);

		//doppelte Datensätze raussuchen
		$sSql = "
			SELECT
				`ktbic`.`id` `self_id`,
				GROUP_CONCAT(DISTINCT `ktbic_c`.`id`) `other_ids`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic_c` ON
					`ktbic`.`block_id` = `ktbic_c`.`block_id` AND
					`ktbic`.`inquiry_course_id` = `ktbic_c`.`inquiry_course_id` AND
					`ktbic`.`course_id` = `ktbic_c`.`course_id` AND
					`ktbic`.`id` != `ktbic_c`.`id`
			GROUP BY
				`ktbic`.`id`
		";

		$aResult			= (array)DB::getQueryRows($sSql);
		
		$aUniqueAllocations = array();

		foreach($aResult as $aRowData)
		{
			$aOtherIds	= explode(',',$aRowData['other_ids']);
			$aAllocations = array(
				$aRowData['self_id']
			);
			$aAllocations = array_merge($aAllocations,$aOtherIds);
			sort($aAllocations);
			$sKey = implode('_',$aAllocations);
			$aUniqueAllocations[$sKey] = $aAllocations;
		}

		foreach($aUniqueAllocations as $aUniqueAllocation)
		{
			//eine Allocation behalten
			$iFirstAllocationKey = key($aUniqueAllocation);
			
			unset($aUniqueAllocation[$iFirstAllocationKey]);

			//alle anderen entfernen
			$sSql = "
				DELETE FROM
					`kolumbus_tuition_blocks_inquiries_courses`
				WHERE
					ID IN(:allocation_ids)
			";

			$aSql = array(
				'allocation_ids' => $aUniqueAllocation
			);

			DB::executePreparedQuery($sSql, $aSql);
		}

		//Unique setzen
		$sSql = "
			ALTER TABLE
				`kolumbus_tuition_blocks_inquiries_courses`
			ADD UNIQUE
				`uniquie_ktbic` ( `block_id` , `inquiry_course_id` , `course_id` )
		";

		try
		{
			DB::executeQuery($sSql);
		}
		catch(DB_QueryFailedException $e)
		{
			//Falls Unqiue immer noch nicht klappt, hat irgendwas nicht funktioniert oben :)
			$oMail = new WDMail();
			$oMail->subject = "Unqiue Ktbic Import Error";

			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('YmdHis')."\n\n";
			$sText .= $e->getMessage();
			$sText .= "\n\n";

			$oMail->text = $sText;
			$oMail->send(array('m.durmaz@thebing.com'));
		}

		return true;
	}

	public function getTitle()
	{
		return 'Clear Tuition Allocations';
	}

	public function  getDescription()
	{
		return 'Check tuition allocations and set to unique.';
	}
}