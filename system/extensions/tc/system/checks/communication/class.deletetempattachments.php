<?php

use Illuminate\Support\Facades\Storage;

class Ext_TC_System_Checks_Communication_DeleteTempAttachments extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Delete temporary attachments';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '4G');

		$dir = str_replace(storage_path(), '', Ext_TC_Communication::getUploadPath('in', true));
		
		$serverDirectories = Storage::directories($dir);
		
		$deletedFiles = 0;
		
		foreach($serverDirectories as $serverDirectory) {
			
			$accountDirectories = Storage::directories($serverDirectory);
			
			foreach($accountDirectories as $accountDirectory) {
				
				$accountFiles = Storage::files($accountDirectory);
			
				// Dateien direkt im Account-Verzeichnis darf es nicht geben. Die sind temporär!
				foreach($accountFiles as $accountFile) {
					Storage::delete($accountFile);
					$deletedFiles++;
				}
				
			}
			
		}
		
		$this->logInfo('Temporary attachments deleted', ['count'=>$deletedFiles]);
		
		return true;
	}

}