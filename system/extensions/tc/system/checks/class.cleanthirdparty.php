<?php

class Ext_TC_System_Checks_CleanThirdParty extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Clean third party tools';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes files and directories.';
		return $sDescription;
	}

	public function executeCheck() {

		$aFiles = [
			'/system/extensions/tc/thirdparty/mimemessage',
			'/system/extensions/tc/thirdparty/swiftmailer',
			'/system/extensions/tc/thirdparty/class.mimemessage.php',
			'/system/extensions/tc/thirdparty/class.swiftmailer.php'
		];

		foreach($aFiles as $sFile) {
			$sFile = Util::getDocumentRoot(false).$sFile;
			Ext_TC_Util::recursiveDelete($sFile);
		}

		return true;

	}

}
