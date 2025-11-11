<?php

class Ext_TS_System_Checks_Document_CleanUnusedDocuments extends GlobalChecks {

	public function getTitle() {
		return 'Clean unused files';
	}

	public function getDescription() {
		return 'Delete files that are not link in the document table.';
	}

	public function executeCheck() {
		
		set_time_limit(14400);
		ini_set("memory_limit", '8G');

		$deletedFiles = $checkedFiles = $totalSize = 0;
		
		// Alle gültigen Dateien in ein Array
		$validFiles = \DB::getQueryPairs("SELECT `path`, 1 FROM `kolumbus_inquiries_documents_versions` WHERE `path` != ''");

		// Alle Schulen durchlaufen
		$schools = Ext_Thebing_School::query()->get();
		foreach($schools as $school) {
			
			$schoolDir = $school->getSchoolFileDir().'/inquirypdf/';

			if(!is_dir($schoolDir)) {
				continue;
			}

			$deleteFiles = [];
			
			$zipFile = $school->getSchoolFileDir().'/archive_clean_unused_documents_'.date('YmdHis').'.zip';

			$zip = new ZipArchive();
			if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
				$this->logInfo('Could not create ZIP file');
				return false;
			}

			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($schoolDir, FilesystemIterator::SKIP_DOTS));

			foreach ($iterator as $file) {
				if ($file->isFile()) {

					$relativePath = str_replace(\Util::getDocumentRoot().'storage', '', $file->getPathname());

					if (!isset($validFiles[$relativePath])) {

						$zip->addFile($file->getPathname(), ltrim($relativePath, DIRECTORY_SEPARATOR));

						$totalSize += $file->getSize();
						
						$deleteFiles[] = $file->getPathname();

						$deletedFiles++;
					}
					
					$checkedFiles++;
					
				}
				
			}

			$zip->close();

			// Muss wegen dem ZIP (Sperre) nachträglich passieren
			foreach($deleteFiles as $deleteFile) {
				unlink($deleteFile);
			}

		}

		$this->logInfo('Clean unused files', ['checked'=> $checkedFiles, 'deleted'=>$deletedFiles, 'size'=>$totalSize]);
		
		return true;
	}

}
