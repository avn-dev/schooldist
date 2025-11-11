<?php
/**
 * Konvertiert kolumbus_costs multiple_onPdf und per_week nach charge (Checkbox => Select)
 * [T-2067]
 */
class Ext_Thebing_System_Checks_AdditionalCosts3 extends GlobalChecks {
	
	public function getTitle() {
		$sTitle = 'Marketing » Resources » Additional Fee Charge';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Converts charge per course and charge per course/week to a dropdown field.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		
		global $system_data;
		
		set_time_limit(3600);
		ini_set("memory_limit", '512M');
		
		Ext_Thebing_Util::backupTable('kolumbus_costs');
		
		$bAdded = DB::addField('kolumbus_costs', 'charge', 'TINYINT(1) NOT NULL DEFAULT \'0\' ');
		
		// Wenn Spalte schon da, Check abbrechen
		if($bAdded === false) {
			return true;
		} 
		
		$sSql = "
		SELECT
			`id`, 
			`multiple_onPdf`, 
			`per_week`
		FROM
			`kolumbus_costs`
		WHERE
			`multiple_onPdf` != 0 OR 
			`per_week` != 0
		";
		$aResult = DB::getQueryRows($sSql);
		
		foreach($aResult as $aResult2) {
			$iCharge = 0;
			
			if(
				$aResult2['per_week'] == 1
			) {
				
				$iCharge = 2;
				
			} elseif(
				$aResult2['multiple_onPdf'] == 1
			) {

				$iCharge = 1;

			}
			
			$aSql = array(
				'charge' => (int)$iCharge,
				'id' => (int)$aResult2['id']
			);
			$sSql = "UPDATE `kolumbus_costs` SET `charge` = :charge WHERE `id` = :id";
			DB::executePreparedQuery($sSql,$aSql);

		}
		
		$sSql = "ALTER TABLE `kolumbus_costs` DROP `multiple_onPdf`";
		DB::executeQuery($sSql);
		
		$sSql = "ALTER TABLE `kolumbus_costs` DROP `per_week`";
		DB::executeQuery($sSql);		
		
		return true;

	}
	
}
?>
