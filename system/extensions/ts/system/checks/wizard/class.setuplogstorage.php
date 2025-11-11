<?php

class Ext_TS_System_Checks_Wizard_SetupLogStorage extends Ext_Thebing_System_Checks_Enquiry_Filterset {

	public function getTitle() {;
		return 'Setup Wizard';
	}

	public function getDescription() {
		return 'Changes process storage of setup wizard.';
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {

		$logs = \System::d('ts_setup_wizard_completed_steps', []);

		if (empty($logs)) {
			return true;
		}

		$dir = \Util::getDocumentRoot(false).'/storage/ts/wizard';

		\Util::checkDir($dir);

		$file = $dir.'/setup.txt';

		file_put_contents($file, $logs);

		return file_exists($file);
	}
}