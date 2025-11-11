<?php

use TcExternalApps\Service\AppService;

class Ext_TS_System_Checks_UninstallHubspot extends GlobalChecks {

	public function getTitle() {
		return 'Uninstall Hubspot';
	}

	public function getDescription() {
		return $this->getTitle();
	}

	public function executeCheck() {

		if(AppService::hasApp(\TsHubspot\Handler\ExternalApp::APP_NAME)) {
			AppService::uninstallApp(AppService::getApp('hubspot'));
		}

		$tables = [
			'ts_inquiries_to_hubspot',
			'ts_enquiries_to_hubspot',
			'ts_agencies_contacts_to_hubspot',
		];

		foreach ($tables as $table) {
			if (Ext_Thebing_Util::checkTableExists($table)) {
				if (!Ext_Thebing_Util::backupTable($table)) {
					return false;
				} else {
					$sSql = "DROP TABLE #table";
					$aSql = [
						'table' => $table
					];
					DB::executePreparedQuery($sSql, $aSql);
				}
			}
		}

		return true;
	}

}
