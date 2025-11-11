<?php
/**
 * Mit irgendeiner Version wurden Uploads im Buchungsdialog direkt in den Root-Ordner des Hosts geschrieben.
 * Das kam dadurch, weil eine Variable mit dem Schulpfad versehentlich beim Inquiry-Dialog gelöscht wurde.
 * Dieser Check verschiebt die Dateien der Ordner in die richtigen Ordner.
 *
 * @since 18.01.2013
 * @author DG <dg@thebing.com>
 */
class Ext_Thebing_System_Checks_Inquiry_MoveUploads extends GlobalChecks {

	protected $_aLog = array();
	protected $_sMediaPath;

	public function getTitle() {
		$sTitle = 'Move uploaded inquiry documents';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Move uploaded inquiry documents';
		return $sDescription;
	}

	public function executeCheck() {
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$this->_setMediaPath();
		$this->_executePart('passport');
		$this->_executePart('studentcards');

		$this->_dumpLog();

		return true;
	}

	/**
	 * Führt Aktionen für einen Ordner aus
	 *
	 * @param string $sSubject
	 */
	protected function _executePart($sSubject) {
		$aFileNames = $this->_getContactIdsFromFiles($sSubject);
		$aContactIdsToSchoolIds = $this->_getSchoolsOfContactIds(array_keys($aFileNames));

		foreach($aContactIdsToSchoolIds as $iContactId => $iSchoolId) {
			$this->_moveFile($sSubject, $iSchoolId, $iContactId, $aFileNames[$iContactId]);
		}

		$sDir = Ext_TC_Util::getPathWithRoot($sSubject);
		if(is_dir($sDir)) {
			$bSuccess = @rmdir($sDir);
			$this->_aLog[$sSubject][] = 'Deleted '.$sDir.', success: '.(int)$bSuccess;
		}
	}

	/**
	 * Client-ID holen und entsprechenden Media-Pfad setzen
	 * @throws UnexpectedValueException
	 */
	protected function _setMediaPath() {

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_clients`
			WHERE
				`active` = 1
		";

		$aResult = DB::getQueryRow($sSql);
		if(count($aResult) > 1) {
			throw new UnexpectedValueException('There is more than one active client!');
		}

		$iClientId = (int)reset($aResult);
		$this->_sMediaPath = Ext_TC_Util::getPathWithRoot('media/secure/clients/client_'.$iClientId.'/school_');

	}

	/**
	 * Läuft alle Dateien des Verzeichnisses durch und extrahiert die Kontakt-ID der jeweiligen Datei
	 * @param string $sSubject
	 * @return array
	 */
	protected function _getContactIdsFromFiles($sSubject) {
		$aIds = array();

		try {
			$oDir = new DirectoryIterator(Ext_TC_Util::getPathWithRoot($sSubject));
			foreach($oDir as $oFile) {
				if($oFile->isFile()) {
					// Kontakt-ID aus Dateinamen extrahieren
					$iStart = strrpos($oFile->getFilename(), '_') + 1;
					$iEnd = strlen($oFile->getFilename()) - strrpos($oFile->getFilename(), '.') + 1;
					$iContactId = (int)substr($oFile->getFilename(), $iStart, $iEnd);
					$aIds[$iContactId] = $oFile->getBasename();
				}
			}

		} catch(UnexpectedValueException $e) {
			
		}

		return $aIds;
	}

	/**
	 * Holt sich zu jeder Kontakt-ID die passende Schule anhand der aktuellesten Buchung.
	 * Da ein Kunde n Buchungen in n Schulen haben kann, ist ein SubSelect nötig.
	 *
	 * @param array $aContactIds
	 * @return array
	 */
	protected function _getSchoolsOfContactIds(array $aContactIds) {

		$aSql = array(
			'contact_ids' => $aContactIds
		);

		$sSql = "
			SELECT
				*
			FROM (
				SELECT
					`tc_c`.`id` `contact_id`,
					`ts_ij`.`school_id`
				FROM
					`tc_contacts` `tc_c` LEFT JOIN
					`ts_inquiries_to_contacts` `ts_itc` ON
						`ts_itc`.`contact_id` = `tc_c`.`id` LEFT JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_itc`.`inquiry_id`
				WHERE
					`tc_c`.`id` IN ( :contact_ids )
				ORDER BY
					`ts_ij`.`inquiry_id` DESC
			) `sub`
			GROUP BY
				`sub`.`contact_id`
		";

		$aResult = (array)DB::getQueryPairs($sSql, $aSql);
		return $aResult;

	}

	/**
	 * Verschiebt die Datei in das richtige Verzeichnis
	 *
	 * @param string $sSubject
	 * @param int $iSchoolId
	 * @param int $iContactId
	 * @param string $sFileName
	 * @throws UnexpectedValueException
	 */
	protected function _moveFile($sSubject, $iSchoolId, $iContactId, $sFileName) {
		if(empty($iSchoolId)) {
			throw new UnexpectedValueException('School-ID is empty for contact "'.$iContactId.'"! ('.$sSubject.')');
		}

		$sOldFile = Ext_TC_Util::getPathWithRoot($sSubject).'/'.$sFileName;
		$sTargetDir = $this->_sMediaPath.$iSchoolId.'/'.$sSubject.'/';
		$sTargetFile = $sTargetDir.$sFileName;

		Util::checkDir($sTargetDir);
		$bSuccess = rename($sOldFile, $sTargetFile);
		$this->_aLog[$sSubject][] = 'Renamed '.$sOldFile.' to '.$sTargetFile.', success: '.(int)$bSuccess;
	}

	/**
	 * Schreibt den Log in eine Datei
	 */
	protected function _dumpLog() {
		$sDir = Ext_TC_Util::getPathWithRoot('media/secure/logs/');
		Util::checkDir($sDir);
		$sFilePath = $sDir.'Check-'.get_class($this).'_'.date('Ymd-His').'.log';
		$rLog = fopen($sFilePath, 'w');
		foreach($this->_aLog as $sSubject => $aLog) {
			foreach($aLog as $sLog) {
				fwrite($rLog, '['.$sSubject.'] '.$sLog."\n");
			}
		}
		fclose($rLog);
	}

}
