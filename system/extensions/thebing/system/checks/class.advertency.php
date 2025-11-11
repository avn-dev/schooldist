<?php

class Ext_Thebing_System_Checks_Advertency extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Generate default class times';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '8:00 - 21:45, 15 minutes interval';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		
		global $system_data;
		
		Ext_Thebing_Util::backupTable('kolumbus_hearaboutus');
		
		Ext_Thebing_Util::updateLanguageFields();
		
		$sSql = "UPDATE `kolumbus_hearaboutus` SET ";
		
		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$aLanguages = $oSchool->getLanguageList();
		
		foreach((array)$system_data['allowed_languages'] as $sLang => $sName) {
			$sSql .= '`name_'.$sLang.'` = `text`, ';
		}
		
		$sSql = rtrim($sSql, ', ');
		
		DB::executeQuery($sSql);
		
		
		$sSql = "ALTER TABLE `kolumbus_hearaboutus` DROP `text`";
		
		DB::executeQuery($sSql);
		
		return true;

	}

}
