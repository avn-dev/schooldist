<?php
/**
 * Erster Core-Check!
 *
 * /media/secure/communication/ 		=> /media/secure/tc/communication/in
 * /media/secure/tc/communication/sent/ => /media/secure/tc/communication/out
 * /media/secure/templates/email/ 		=> /media/secure/tc/communication/templates/email
 */
class Ext_TC_System_Checks_Communication_MoveMediaDirs extends GlobalChecks {
	
	public function getTitle() {
		$sTitle = 'Move Communication Directories';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Moves the media directories of communication files.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {
		
		set_time_limit(120);
		ini_set("memory_limit", '512M');

		// --- Part 1 – verschieben ---

		$sSecureDir = Ext_TC_Util::getSecureDirectory(true);
		$sMediaRoot = str_replace('tc/', '', $sSecureDir);
		$sCommunicationDir = $sSecureDir.'communication/';

		$sOldIncomingDir = $sMediaRoot.'communication/';
		$sOldOutgoingDir = $sSecureDir.'communication/sent/';
		$sOldEmailTemplateUploads = $sMediaRoot.'templates/email/';

		$sCommunicationOutDir = 'out/';

		$bCheckCommunicationDir = Util::checkDir($sCommunicationDir);
		if(!$bCheckCommunicationDir) {
			throw new Exception($sCommunicationDir.' cannot be created!');
		}

		if(is_dir($sOldIncomingDir)) {
			#__uout($sOldIncomingDir." => ".$sCommunicationDir.'in/', 'gierling');
			Ext_TC_Util::recursiveMove($sOldIncomingDir, $sCommunicationDir.'in/');
		}

		if(is_dir($sOldOutgoingDir)) {
			#__uout($sOldOutgoingDir." => ".$sCommunicationDir.'out/', 'gierling');
			Ext_TC_Util::recursiveMove($sOldOutgoingDir, $sCommunicationOutDir);
		}

		if(is_dir($sOldEmailTemplateUploads)) {
			#__uout($sOldEmailTemplateUploads." => ".$sCommunicationDir.'templates/email/', 'gierling');
			$sTemplateDir = $sCommunicationDir.'templates/';
			$bCheckTemplateDir = Util::checkDir($sTemplateDir);
			if(!$bCheckTemplateDir) {
				throw new Exception($sTemplateDir.' cannot be created!');
			}

			Ext_TC_Util::recursiveMove($sOldEmailTemplateUploads, $sCommunicationDir.'templates/email/');

		}

		// --- Part 2 – leere Einträge entfernen --

		// Da der Wert zuvor immer geschrieben wurde, wurden lauter Leereinträge erzeugt.
		$sSql = "
			DELETE FROM
				`tc_communication_templates_contents_uploads`
			WHERE
				`filename` = ''
		";

		DB::executeQuery($sSql);


		// --- Part 3 – Leere Ordner bei gesendeten E-Mails entfernen ---

		if(is_dir($sCommunicationOutDir)) {

			$oDirIterator = new DirectoryIterator($sCommunicationOutDir);
			foreach($oDirIterator as $oDir) {
				/* @var DirectoryIterator $oDir */

				if($oDir->isDot()) {
					continue;
				}

				$iDataCount = 0;

				$oMsgDirIterator = new DirectoryIterator($oDir->getPathname());
				foreach($oMsgDirIterator as $oMsgDir) {
					/* @var DirectoryIterator $oMsgDir */

					if($oMsgDir->isDot()) {
						continue;
					}

					$iDataCount++;

				}

				if($iDataCount === 0) {
					rmdir($oDir->getPathname());
				}

			}
		}

		return true;

	}
	
}
