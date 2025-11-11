<?php

class Ext_TS_System_Checks_Agency_Manual_Creditnote_Storno_InquirySchoolRelation extends GlobalChecks
{
	public function getTitle()
	{
		return 'Canceled manual creditnotes';
	}
	
	public function getDescription()
	{
		return 'Set relation between canceled manual creditnote and inboxes/schools.';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackup1 = Ext_Thebing_Util::backupTable('kolumbus_agencies_manual_creditnotes');
		$bBackup2 = Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions');
		$bBackup3 = Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');
		if(
			!$bBackup1 ||
			!$bBackup2 ||
			!$bBackup3
		) {
			__pout('backup error!');
			return false;
		}

		$sSql = "
			SELECT
				`id`
			FROM 
				`kolumbus_agencies_manual_creditnotes`
			WHERE
				`storno_id` > 0
		";
		
		$aData = (array) DB::getQueryCol($sSql);
		
		$bStack = false;
		
		foreach($aData as $iId) {
			
			$oCreditNote	= Ext_Thebing_Agency_Manual_Creditnote::getInstance($iId);
			$iStornoId		= (int) $oCreditNote->storno_id;			
			
			if($iStornoId > 0) {
				
				$oStorno = Ext_Thebing_Agency_Manual_Creditnote::getInstance($iStornoId);
				
				$bSave = false;
				
				if($oStorno->inbox_id == 0) {
					$oStorno->inbox_id		= (int) $oCreditNote->inbox_id;
					$bSave = true;
				}
				
				if($oStorno->school_id == 0) {
					$oStorno->school_id		= (int) $oCreditNote->school_id;
					$bSave = true;
				}
				
				
				
				if($bSave) {
					$oStorno->save();
				}
				
				$oDocument = $oStorno->getDocument();
				if(
					$oDocument->id > 0 &&
					$oDocument->latest_version <= 0
				) {
					$oVersion = $oDocument->newVersion();
					$oVersion->save();
					
					Ext_Gui2_Index_Stack::add('ts_document', $oDocument->id, 0);
					$bStack = true;
				}			
				
			}			
		}
		
		if($bStack) {
			Ext_Gui2_Index_Stack::executeCache();
		}
		
		return true;
	}

}