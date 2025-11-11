<?php

class Checks_RemoveSmarty3 extends GlobalChecks {

	public function getTitle() {
		return 'Remove Smarty3 from core structure';
	}
	
	public function getDescription() {
		return 'Removes disused files';
	}
	
	/**
	 * Löscht das komplette Smarty3-Verzeichnis aus dem Framework
	 * 
	 * @return boolean
	 */
	public function executeCheck() {

		\Util::$iDeletedFiles = 0;
		
		$aSmartyFiles = $this->getFilesFromSmartyDir($this->getOldSmartyDir());
		$aSmarty3Files = $this->getFilesFromSmartyDir($this->getOldSmarty3Dir());

		$aAllSmartyFiles = array_merge($aSmartyFiles, $aSmarty3Files);
		
		// Verzeichnisse die über das ParallelProcessing gelöscht werden sollen
		$aStackDirs = array('templates', 'templates_c', 'configs', 'config', 'cache');
		
		foreach($aAllSmartyFiles as $sFileName) {						
			if(				
				is_file($sFileName) ||
				is_dir($sFileName)			
			) {
				if(in_array(basename($sFileName), $aStackDirs)) {
					// Da sich in den Cache-Verzeichnissen von Smarty3 ein Menge Dateien befinden 
					// können lassen wir diese Verzeichnisse durch dass ParallelProcessing
					// löschen, damit die Ausführung des Checks nicht zu lange dauert
					$this->addProcess(array('filename' => $sFileName));
				} else {
					// Andere Verzeichnisse/Dateien können direkt gelöscht werden
					\Util::recursiveDelete($sFileName);
				}				
			}
		}
		
		$sSecureDir = \Util::getDocumentRoot() . 'media/secure/smarty3/';

		// Die Cache-Datein von Smarty3 werden ab sofort unter /media/secure gespeichert. Daher
		// legen wir hier schon mal die Verzeichnisse an
		\Util::checkDir($sSecureDir . 'templates/');
        \Util::checkDir($sSecureDir . 'templates_c/');
        \Util::checkDir($sSecureDir . 'configs/');
        \Util::checkDir($sSecureDir . 'cache/');
		
		$this->logInfo('deleteDisusedFiles', array('deleted_files' => Util::$iDeletedFiles));

		return true;
	}

	/**
	 * Arbeitet einen Stackeintrag aus dem ParallelProcessing ab
	 * 
	 * Hier werden die Verzeichnisse gelöscht die nicht über den generellen Durchlauf des Checks 
	 * gelöscht wurden, da sie ggf. zu groß sind und der Check sonst zu lange dauern würde
	 * 
	 * @param array $aData
	 * @return boolean
	 */
	public function executeProcess(array $aData) {
		
		$sFilename = $aData['filename'];
		
		if(
			is_dir($sFilename) ||
			is_file($sFilename)
		) {
			\Util::recursiveDelete($sFilename);
			$this->logInfo('deleteDisusedFile - ParallelProcessing', array('deleted_file' => $sFilename));
		} else {
			$this->logInfo('deleteDisusedFile - Error', array('file' => $sFilename));
		}
		
		$aSmarty3Files = $this->getFilesFromSmartyDir($this->getOldSmarty3Dir());				
		// Wenn das alte Smarty3-Verzeichnis komplett leer ist kann dieses auch komplett gelöscht werden
		if(empty($aSmarty3Files)) {
			$this->deleteCompleteDirectory($this->getOldSmarty3Dir());
		}
		
		$aSmartyFiles = $this->getFilesFromSmartyDir($this->getOldSmartyDir());
		// Wenn das alte Smarty-Verzeichnis komplett leer ist kann dieses auch komplett gelöscht werden
		if(empty($aSmartyFiles)) {
			$this->deleteCompleteDirectory($this->getOldSmartyDir());
		}
		
		return true;
	}
	
	private function deleteCompleteDirectory($sDirectory) {
		
		if(!is_dir($sDirectory)) {
			return false;
		}
		
		\Util::recursiveDelete($sDirectory);			
		$this->logInfo('deleteCompleteDir', array('directory' => $sDirectory));
	}
	
	/**
	 * Liefert alle Dateien aus dem Smarty3-Verzeichnis
	 * 
	 * @param string $sSmartyDir
	 * @return array
	 */
	private function getFilesFromSmartyDir($sSmartyDir) {
		if(!is_dir($sSmartyDir)) {
			return array();
		}
		
		return glob($sSmartyDir . '*');
	}
	
	/**
	 * Liefert den Pfad zu dem Smarty3-Verzeichnis
	 * 
	 * @return string
	 */
	private function getOldSmarty3Dir() {
		return \Util::getDocumentRoot() . 'system/extensions/smarty3/';
	}
	
	/**
	 * Liefert den Pfad zu dem Smarty-Verzeichnis
	 * 
	 * @return string
	 */
	private function getOldSmartyDir() {
		return \Util::getDocumentRoot() . 'system/smarty/';
	}
}
