<?php

class Checks_Timezone extends GlobalChecks {
	
	public function executeCheck() {
		global $system_data;

		$sSql = "
			SELECT
				`c_value`
			FROM
				`system_config`
			WHERE
				`c_key` = 'locale'
		";

		// Wert über Query holen, da die System-Klasse $system_data['locale'] selber setzt!
		$mResult = DB::getQueryOne($sSql);

		// Wenn der Wert da ist wurde der Check schon ausgeführt.
		if($mResult !== null) {
			return true;
		}

		// Vorher Timezone, jetzt locale
		System::s('locale', $system_data['timezone']);
		System::s('timezone', '');

		return true;

	}
	
	public function getTitle() {
		return 'Checking the time zone settings';
	}
	
	public function getDescription() {
		return '...';
	}

	
}
