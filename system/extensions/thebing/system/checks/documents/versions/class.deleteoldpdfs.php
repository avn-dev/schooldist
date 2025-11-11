<?php

/**
 * Ticket #11985 – Alte Dokumentenversionen löschen
 *
 * PDFs löschen von Versionen, die aus dem vorletzten Jahr stammen und keine Hauptversion sind
 * Zusätzlich auch PDFs von Hauptversionen löschen, deren Dokument gelöscht wurde (active = 0)
 */
class Ext_Thebing_System_Checks_Documents_Versions_DeleteOldPdfs extends GlobalChecks {

	private $iFilesDeleted = 0;
	private $iFilesDeletedSize = 0;

	public function getTitle() {
		return 'Delete old PDFs of not actual document versions';
	}

	public function getDescription() {
		return 'Delete PDF files which are older than two years and not the actual document version to free space.';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2G');
		
		$iYear = date('Y') - 2;

		$sSql = "
			SELECT
				`kid`.`id` `document_id`,
				`kid`.`active` `document_active`, 
				`kidv`.`id` `main_version_id`,
				GROUP_CONCAT(DISTINCT `kidv2`.`id`) `version_ids`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` AND
					YEAR(`kidv`.`created`) <= {$iYear} INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv2` ON
					`kidv2`.`document_id` = `kid`.`id` AND
					`kidv2`.`id` != `kidv`.`id`
			GROUP BY
				`kid`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			$aVersions = [];
			if(!empty($aRow['version_ids'])) {
				$aVersions = explode(',', $aRow['version_ids']);
			}

			// Wenn Dokumnent gelöscht wurde, soll PDF von der Hauptversion auch weg
			if(!$aRow['document_active']) {
				$aVersions[] = $aRow['main_version_id'];
			}

			foreach($aVersions as $iVersionId) {

				$sSql = "SELECT `id`, `path` FROM `kolumbus_inquiries_documents_versions` WHERE `id` = {$iVersionId}";
				$aRow2 = DB::getQueryRow($sSql);

				if($aRow2 === null) {
					throw new RuntimeException('Version '.$iVersionId.' does not exist!');
				}

				if(empty($aRow2['path'])) {
					continue;
				}

				$sFullPath = Util::getDocumentRoot().'storage/'.$aRow2['path'];
				if(is_file($sFullPath)) {

					if(!is_writeable(dirname($sFullPath))) {
						throw new RuntimeException('Directory is not writeable! '.$sFullPath);
					}

					$iFileSize = filesize($sFullPath);

					if(!unlink($sFullPath)) {
						throw new RuntimeException('Could not delete PDF of version '.$iVersionId.'! ('.$sFullPath.')');
					}

					$this->iFilesDeleted++;
					$this->iFilesDeletedSize += $iFileSize;

					$this->logInfo('Deleted PDF of version '.$iVersionId. ' ('.$sFullPath.')');
				} else {
					$this->logError('Version '.$iVersionId.' has path ('.$sFullPath.') but PDF does not exist!');
				}

				$sSql = "UPDATE `kolumbus_inquiries_documents_versions` SET `path` = NULL WHERE `id` = {$iVersionId}";
				DB::executeQuery($sSql);

			}

		}

		$this->logInfo('Deleted PDF files: '.$this->iFilesDeleted);
		$this->logInfo('Deleted PDF files (size): '.($this->iFilesDeletedSize / 1024 / 1024).' MB');

		return true;

	}

}
