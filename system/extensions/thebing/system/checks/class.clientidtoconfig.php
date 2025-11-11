<?php

/**
 * Da es schon lange nur noch einen Client gibt, ID fest in die Config schreiben
 */
class Ext_Thebing_System_Checks_ClientIdToConfig extends GlobalChecks {

	public function getTitle() {
		return 'Performance improvements';
	}

	public function  getDescription() {
		return '';
	}

	public function executeCheck() {

		if(!System::d('ts_client_id')) {

			$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_clients`
				WHERE
					`active` = 1
			";

			$aResult = DB::getQueryCol($sSql);

			if(count($aResult) > 1) {
				Ext_TC_Util::reportError('Ext_Thebing_Client::getFirstClient() call: There is more than one client!');
			}

			System::s('ts_client_id', reset($aResult));

		}

		return true;

	}

}
