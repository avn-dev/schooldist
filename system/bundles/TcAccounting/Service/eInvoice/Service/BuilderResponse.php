<?php

namespace TcAccounting\Service\eInvoice\Service;

use TcAccounting\Service\eInvoice\Service\File;

class BuilderResponse {
	/**
	 * @var \TcAccounting\Service\eInvoice\Service\File[] 
	 */
	private $aFiles = [];
	/**
	 * @var array
	 */
	private $aErrors = [];
	
	/**
	 * Fügt einen Fehler hinzu
	 * 
	 * @param string $sError
	 * @return void
	 */
	public function addError(string $sError) : void {
		$this->aErrors[] = $sError;
	}
	
	/**
	 * Gibt an ob Fehler während der Generierung aufgetreten sind
	 * 
	 * @return bool
	 */
	public function hasErrors() : bool {
		return !empty($this->getErrors());
	}
	
	/**
	 * Liefert alle Fehler die während der Generierung aufgetreten sind
	 * 
	 * @return array
	 */
	public function getErrors() : array {
		return $this->aErrors;
	}
	
	/**
	 * Fügt eine neue Datei hinzu
	 * 
	 * @param \TcAccounting\Service\eInvoice\Entity\File $oFile
	 * @return void
	 */
	public function addFile(File $oFile) : void {
		
		if($oFile->hasErrors()) {
			$this->aErrors = array_merge($this->aErrors, $oFile->getErrors());
		}
		
		$this->aFiles[] = $oFile;
	}
	
	/**
	 * Liefert alle generierten Dateien
	 * 
	 * @return \TcAccounting\Service\eInvoice\Entity\File[]
	 */
	public function getFiles() : array {
		return $this->aFiles;
	}

	/**
	 * Löscht alle generierten Dateien
	 * 
	 * @return void
	 */
	public function cleanUp() : void {
		
		foreach ($this->aFiles as $oFile) {
			if($oFile->hasFile()) {
				unlink($oFile->getFile());
			}
		}
		
	}
	
	/**
	 * Generiert aus allen erstellen Dateien ein Zip
	 * 
	 * @return string|null
	 */
	public function buildZip() : ?string {
		
		if($this->hasErrors()) {
			return null;
		}
		
		$sFileZIP = Storage::getDirectory().'/export.zip';
		
		if(file_exists($sFileZIP)) {			
			unlink($sFileZIP);
		}
		
		$oZip = new \ZipArchive();
		$oZip->open($sFileZIP, \ZIPARCHIVE::CREATE);
		
		foreach ($this->aFiles as $oFile) {
			
			if(!file_exists($oFile->getFile())) {
				continue;
			}
			
			$oZip->addFile($oFile->getFile(), $oFile->getFileName());
		}
		
		$oZip->close();
		
		// Sobald das Zip erstellt ist alle Dateien Löschen
		$this->cleanUp();
		
		return $sFileZIP;		
	}
	
	/**
	 * Erstellt von allen generierten Dateien eine Kopie in /backup/ und speichert 
	 * den Eintrag in der Datenbank (Historie)
	 * 
	 * @return void
	 */
	public function backup() : void {
		
		foreach($this->aFiles as $oFile) {
			
			Storage::backupFile($oFile);
			
			$oFileEntity = $oFile->convertToEntity();
			$oFileEntity->save();
		}
		
	}
	
}
