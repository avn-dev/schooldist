<?php

/**
 * Ticket #11985 – Alte Dokumentenversionen löschen
 *
 * Temporäre Dokumente löschen, die noch nie gelöscht wurden:
 * 1. Massendokumente
 * 2. Klassenplanung (Export)
 */
class Ext_Thebing_System_Checks_Documents_CleanTemporaryPdfs extends GlobalChecks {

	private $iFilesDeleted = 0;
	private $iFilesDeletedSize = 0;

	public function getTitle() {
		return 'Delete old temporary PDFs';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$oClient = Ext_Thebing_Client::getFirstClient();
		$sPath = $oClient->getFilePath();

		$oDirIterator = new DirectoryIterator($sPath);
		foreach($oDirIterator as $oFile) {
			if(
				$oFile->isDir() &&
				strpos($oFile->getFilename(), 'school_') === 0
			) {
				$this->cleanSchoolFileDirectory($oFile->getPathname());
			}
		}

		$this->logInfo('Deleted files: '.$this->iFilesDeleted);
		$this->logInfo('Deleted files (size): '.($this->iFilesDeletedSize / 1024 / 1024).' MB');

		return true;

	}

	private function cleanSchoolFileDirectory($sPath) {

		$oDirIterator = new DirectoryIterator($sPath);
		foreach($oDirIterator as $oFile) {

			if(
				$oFile->isDot() ||
				$oFile->isDir()
			) {
				continue;
			}

			$sExt = $oFile->getExtension();
			$sBaseFilename = $oFile->getBasename('.'.$sExt);

			if(
				(
					$sExt === 'pdf' && (
						(
							// Massendokumente ab 2011 (MD5)
							strlen($sBaseFilename) == 32 &&
							ctype_xdigit($sBaseFilename)
						) || (
							// Massendokumente bis 2011
							strpos($sBaseFilename, 'DOCUMENT_') === 0
						)
					)
				) || (
					// Export Klassenplanung
					$sExt === 'xls' &&
					strpos($sBaseFilename, 'week_') === 0
				)
			) {

				$iFileSize = $oFile->getSize();

				$bSuccess = unlink($oFile->getPathname());
				if($bSuccess) {
					$this->logInfo('DELETED file: '.$oFile->getPathname());
					$this->iFilesDeleted++;
					$this->iFilesDeletedSize += $iFileSize;
				} else {
					$this->logError('Could not delete file: '.$oFile->getPathname());
				}

			} else {
				$this->logInfo('SKIP file: '.$oFile->getPathname());
			}

		}

	}

}
