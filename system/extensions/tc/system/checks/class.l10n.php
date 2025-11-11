<?php 

class Ext_TC_System_Checks_L10N extends GlobalChecks {
		
	protected static $bIgnoreErrors = false;
	protected static $bDropOldFields = true;
	protected static $bRenameFields = true;
	protected static $bBackupTables = true;
	protected static $bUserOldTableNamesForBackup = true;
	
	public function executeCheck(){
		global $user_data;
		
		Ext_TC_Util::backupTable('language_data');
		
		// GROUP CONCAT Länge hochsetzen
		$sSql = "SET SESSION group_concat_max_len = 1048576;";
		DB::executeQuery($sSql);
		
		// Doppelte Backendübersetzungen löschen
		$sSql = "
			SELECT 
				GROUP_CONCAT(
					DISTINCT l2.id 
					ORDER BY l2.id 
					SEPARATOR ','
				) ids ,
				l2.code
			FROM 
				language_data l1 JOIN 
				language_data l2 ON 
					l1.code = l2.code AND 
					l1.file_id = l2.file_id AND 
					l1.use = l2.use AND 
					l1.code != ''
			WHERE 
				1 
			GROUP BY 
				l1.code,
				l1.file_id,
				l1.use
				";
		$aResult = DB::getQueryData($sSql);
		foreach($aResult as $aData){
			$aIds = explode(',',$aData['ids']);
			unset($aIds[0]);
			$sIds = implode(',',$aIds);
			if(!empty($sIds)){
				try 
		        {
			   		$sSql = " DELETE FROM `language_data` WHERE `id` IN (".$sIds.") ";
					$aSql = array('ids'=>$sIds);
					DB::executePreparedQuery($sSql,$aSql);	
		        }
		        catch (Exception $e)
		        {
		           __pout($e);
		        }
			}
		}
	
		return true;
		
	}
	
}
