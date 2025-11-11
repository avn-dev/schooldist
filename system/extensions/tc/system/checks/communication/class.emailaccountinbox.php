<?php

class Ext_TC_System_Checks_Communication_EmailAccountInbox extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'E-mail Account';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Update e-mail account settings for folder structure';
		return $sDescription;
	}

	public function executeCheck() {
		
		set_time_limit(120);
		ini_set("memory_limit", '1024M');
		
		$bBackupTable = \Util::backupTable('tc_communication_emailaccounts');
		
		if(!$bBackupTable) {
			__pout('Backup error');
			return false;
		}
		
		$sTransactionPoint = 'email_accounts_imap';
		
		\DB::begin($sTransactionPoint);
		
		try {
			
			$sSql = "SELECT * FROM `tc_communication_emailaccounts` WHERE `imap` = 1 AND `active` = 1 AND `imap_sent_mail_folder_root` = 0";
			// Alle Accounts mit Imap-Einstellungen laden
			$aAccounts = \DB::getQueryData($sSql);

			foreach($aAccounts as $aAccount) {

				if(empty($aAccount['imap_folder']) && empty($aAccount['imap_sent_mail_folder'])) {
					continue;
				}
				
				$oAccount = \Ext_TC_Communication_Imap::getInstance($aAccount['id']);
				// Vorhandene Ordnerstruktur laden
				$aExistingFolders = $oAccount->getFolders();

				if(is_array($aExistingFolders)) {					
					// Eingang
					$oAccount->imap_folder = $this->overwriteFolder($aExistingFolders, $aAccount['imap_folder']);					
					// Ausgang
					$oAccount->imap_sent_mail_folder = $this->overwriteFolder($aExistingFolders, $aAccount['imap_sent_mail_folder']);					
				}
				
				if(
					$oAccount->imap_folder !== $aAccount['imap_folder'] ||
					$oAccount->imap_sent_mail_folder !== $aAccount['imap_sent_mail_folder']	
				) {
					// Einstellungen wurden überschrieben
					$oAccount->save();
				}
				
			}

		} catch (\Exception $ex) {
			__pout($ex);
			\DB::rollback($sTransactionPoint);
			return false;
		}

		\DB::commit($sTransactionPoint);

		return true;		
	}
	
	protected function overwriteFolder($aExistingFolders, $sFolder) {

		if(empty($sFolder) || in_array($sFolder, $aExistingFolders)) {
			return $sFolder;
		}
						
		if(in_array('INBOX.'.$sFolder, $aExistingFolders)) {
			return 'INBOX.'.$sFolder;
		} 
//		else {
//			// Alten Ordner Namen in den existierenden Ordner suchen
//			foreach($aExistingFolders as $sExistingFolder) {				
//				if( 
//					strpos($sExistingFolder, $sFolder) !== false
//				) {
//					return $sExistingFolder;
//				}
//			}
//		}

		return $sFolder;
	}
	
}