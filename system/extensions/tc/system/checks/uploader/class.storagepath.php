<?php

class Ext_TC_System_Checks_Uploader_StoragePath extends \GlobalChecks {
	
	public function getTitle() {
		return 'Uploader';
	}
	
	public function getDescription() {
		return 'Adapt old upload files to new fidelo structure';
	}

	public function executeCheck() {
		
		if(!Util::checkTableExists('tc_uploader')) {
			return true;
		}
		
		$bBackup = Util::backupTable('tc_uploader');
        if(!$bBackup){
			throw new Exception('Backup Error!');
		}
        
        DB::begin('Ext_TC_System_Checks_Uploader_StoragePath');
        
        try {
			
			$aUploadFiles = (array) DB::getQueryData("SELECT * FROM `tc_uploader`");
			
			foreach($aUploadFiles as $aUploadFile) {
				
				$sPath = $aUploadFile['path'];
				
				if(strpos($sPath, 'media/secure/') === 0) {					
					$sNewPath = str_replace('media/secure/', 'storage/', $sPath);					
					DB::updateData('tc_uploader', ['path' => $sNewPath ], ['id' => $aUploadFile['id']]);					
				}
				
			}
			
		} catch (Exception $ex) {
            __pout($ex);
            DB::rollback('Ext_TC_System_Checks_Uploader_StoragePath');
            return false;
        }
        
        DB::commit('Ext_TC_System_Checks_Uploader_StoragePath');
		
		return true;		
	}
	
}
