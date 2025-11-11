<?php

class Ext_Thebing_System_Checks_SpecialPositions extends GlobalChecks
{
	public function getDescription()
	{
		return 'Change special structure.';
	}
	
	public function getTitle()
	{
		return 'Special Positions';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		//Zwischentabelle von SpecialPositionen zu Buchungen
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_to_special_positions` (
			  `inquiry_id` int(10) unsigned NOT NULL,
			  `special_position_id` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`inquiry_id`,`special_position_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
		
		//Zwischentabelle von SpecialPositionen zu Angeboten
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_enquiries_offers_to_special_positions` (
			  `enquiry_offer_id` int(10) unsigned NOT NULL,
			  `special_position_id` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`enquiry_offer_id`,`special_position_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
		
		//Zwischentabelle von SpecialPositionen zu Buchungen leeren
		$sSql = "
			TRUNCATE
				`ts_inquiries_to_special_positions`
		";
		
		DB::executeQuery($sSql);
		
		//Backup erstellen
		Util::backupTable('kolumbus_inquiries_positions_specials');
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_positions_specials`
		";
		
		$aRows = (array)DB::getQueryRows($sSql);

		foreach($aRows as $aRow)
		{
			$aInsert = array(
				'inquiry_id'			=> $aRow['inquiry_id'],
				'special_position_id'	=> $aRow['id'],
			);


			$rRes = DB::insertData('ts_inquiries_to_special_positions', $aInsert);
			
			if($rRes === false)
			{
				__pout($aInsert); 
			}
		}
		
		//Spalte inquiry_id entfernen, da die Spalte jetzt in der Zwischenspalte ist
		$sSql = "
			ALTER TABLE
				`kolumbus_inquiries_positions_specials`
			DROP
				`inquiry_id`
		";
		
		DB::executeQuery($sSql);
		
		return true;
	}
}