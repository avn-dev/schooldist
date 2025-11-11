<?php

class Ext_Thebing_System_Checks_CleanCommunicationFlags extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Clean communication flags';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		Ext_Thebing_Util::backupTable('kolumbus_email_templates_flags');

		$aFlags = Ext_Thebing_Communication::getFlags();
		$aFlags = array_keys($aFlags);

		$sSql = "
			DELETE FROM
				`kolumbus_email_templates_flags`
			WHERE
				`flag` NOT IN (:flags)
			";
		$aSql = array('flags'=>$aFlags);
		DB::executePreparedQuery($sSql, $aSql);

		return true;

	}

}
