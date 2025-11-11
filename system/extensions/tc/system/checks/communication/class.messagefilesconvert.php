<?php
/**
 * Zweiter Core-Check
 *
 * Konvertiert alle Pfade in der tc_communication_messages_files für die neue Struktur der Pfade
 */
class Ext_TC_System_Checks_Communication_MessageFilesConvert extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update data of communication uploads';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Update data of communication uploads.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {
		
		set_time_limit(120);
		ini_set("memory_limit", '1024M');

		DB::begin('check_communication_messages_files');
		Util::backupTable('tc_communication_messages_files');

		$sSql = "
			SELECT
				`tc_cmf`.*
			FROM
				`tc_communication_messages_files` `tc_cmf` INNER JOIN
				`tc_communication_messages` `tc_cm` ON  `tc_cmf`.`message_id` = `tc_cm`.`id`
		";
		
		$aResult = DB::getQueryRows($sSql);

		try {

			foreach((array)$aResult as $aFile) {

				$bIsOutFile = strpos($aFile['file'], '/media/secure/communication/') !== false;
				$bIsInFile = strpos($aFile['file'], '/media/secure/tc/communication/sent/') !== false;

				if($bIsOutFile || $bIsInFile) {

					if($bIsOutFile) {
						$sNewPath = str_replace('/media/secure/communication/', '/media/secure/tc/communication/in/', $aFile['file']);
					} elseif($bIsInFile) {
						$sNewPath = str_replace('/media/secure/tc/communication/sent/', '/media/secure/tc/communication/out/', $aFile['file']);
					}

					$aSql = array(
						'newpath' => $sNewPath,
						'oldpath' => $aFile['file'],
						'message_id' => (int)$aFile['message_id']
					);

					$sSql = "
						UPDATE
							`tc_communication_messages_files`
						SET
							`file` = :newpath
						WHERE
							`message_id` = :message_id AND
							`file` = :oldpath
					";

					DB::executePreparedQuery($sSql, $aSql);

				}

			}

		} catch(Exception $e) {

			throw $e;
			return false;

		}

		DB::commit('check_communication_messages_files');

		return true;
		
	}

	
}