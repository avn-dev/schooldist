<?php

/**
 * Kommentar bei Anfragen sollen wohl Contact gebunden sein, ist bei Buchungen im Moment genau so (wurde so besprochen mit Anne F.)
 */
class Ext_Thebing_System_Checks_Enquiry_StudentComment extends GlobalChecks
{
	public function getDescription()
	{
		return 'Convert student comments to new structure.';
	}
	
	public function getTitle()
	{
		return 'Student Comments';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$aColumns = DB::describeTable('ts_enquiries', true);
		
		if(!isset($aColumns['comment']))
		{
			return true;
		}

		Ext_Thebing_Util::backupTable('ts_enquiries');
		
		$sSql = "
			SELECT
				*
			FROM
				`ts_enquiries` `ts_en` INNER JOIN
				`ts_enquiries_to_contacts` `ts_en_to_c` ON
					`ts_en_to_c`.`enquiry_id` = `ts_en`.`id` AND
					`ts_en_to_c`.`type` = 'booker'
			WHERE
				`active` = 1 AND
				`comment` != ''
		";
		
		$aResult = (array)DB::getQueryRows($sSql);
		
		foreach($aResult as $aRowData)
		{
			$sComment = $aRowData['comment'];
			
			DB::insertData('tc_contacts_details', array(
				'active'		=> 1,
				'contact_id'	=> $aRowData['contact_id'],
				'type'			=> 'comment',
				'value'			=> $sComment,
			));
		}
		
		$sSql = '
			ALTER TABLE
				`ts_enquiries`
			DROP 
				`comment`
		';
		
		DB::executeQuery($sSql);
		
		return true;
	}
}