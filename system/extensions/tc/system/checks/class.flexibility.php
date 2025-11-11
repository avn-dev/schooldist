<?php


class Ext_TC_System_Checks_Flexibility extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Add/Change the Flexibility of the System';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Add/Change the Flexibility of the System';
		return $sDescription;
	}

	public function isNeeded() {

		return true;
	
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');
		
		$aTables = array(
			'kolumbus_flex_sections'						=> 'tc_flex_sections',
			'kolumbus_flex_sections_fields'					=> 'tc_flex_sections_fields',
			'kolumbus_flex_sections_fields_options'			=> 'tc_flex_sections_fields_options',
			'kolumbus_flex_sections_fields_options_values'	=> 'tc_flex_sections_fields_options_values',
			'kolumbus_flex_sections_fields_values'			=> 'tc_flex_sections_fields_values',
			'system_gui2_flex_data'							=> 'tc_flex_data',
		);
		
		foreach($aTables as $sTable => $sTableNew){
			$mSuccess = Util::backupTable($sTable);
			if($mSuccess !== false){
				$sSql = "RENAME TABLE #table TO #table2";
				$aSql = array('table' => $sTable, 'table2' => $sTableNew);
				DB::executePreparedQuery($sSql, $aSql);
			}
		}
		
		try {
			$sSql = "ALTER TABLE `tc_flex_sections_fields` DROP `client_id` ";
			DB::executeQuery($sSql);
		} catch (Exception $exc) {
			
		}

		return true;

	}

}