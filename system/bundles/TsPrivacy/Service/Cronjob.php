<?php

namespace TsPrivacy\Service;

class Cronjob extends \Ext_Thebing_System_Server_Update {

	public function executeUpdate() {

		set_time_limit(3600); // Execute: 1000 Einträge = ~10 Minuten
		ini_set('memory_limit', '1G'); // Execute: 1000 Einträge = ~350 MB

		$oLogger = \Log::getLogger('privacy');
		$oLogger->addInfo(__METHOD__.' started');

		$oPrivacyDepuration = new Execute();

		// Einträge aus der Tabelle (von letzter Woche) ausführen
		$oPrivacyDepuration->execute();

		// Tabelle füllen
		$oPrivacyDepuration->prepare();

		$oLogger->addInfo(__METHOD__.' finished');

	}

}
