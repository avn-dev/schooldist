<?php 

class Ext_Thebing_System_Checks_ResetTableIndex extends GlobalChecks {
	
	public function executeCheck(){
		global $user_data;
		
		$aLists = array();
		$aLists[] = '/admin/extensions/thebing/marketing/agencies.html';
		$aLists[] = '/admin/extensions/thebing/pickup/confirmation.html';
		$aLists[] = '/admin/extensions/ac/Accommodation/ac.accommodations.html';

		$sSql = "DELETE FROM
					`system_gui_lists`
				WHERE
					`path` IN ('" . implode("', '", $aLists) . "')";
		$aSql = array();
		DB::executePreparedQuery($sSql,$aSql);
		
		return true;
		
	}
	
	
	
	
}


?>