<?php


class Ext_TC_System_Checks_Flex2v5 extends GlobalChecks {

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
			'tc_flex_data' => 'system_gui2_flex_data',
		);
		
		foreach($aTables as $sTable => $sTableNew){
			$mSuccess = Util::backupTable($sTable);
			if($mSuccess !== false){
				$sSql = "RENAME TABLE #table TO #table2";
				$aSql = array('table' => $sTable, 'table2' => $sTableNew);
				DB::executePreparedQuery($sSql, $aSql);
			}
		}

		return true;

	}

}