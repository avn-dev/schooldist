<?php

namespace TcAccounting\Service\eInvoice\Service;

use TcAccounting\Service\eInvoice\Entity\File as EntityFile;

class File {
	
	private $aErrors = [];
	
	private $iIndex;
	private $iDocumentId;
	private $sType;
	private $sFile;
	private $sFileName;
	private $sBackupFile;
	
	public function __construct(int $iIndex, int $iDocumentId, string $sType) {
		$this->iIndex = $iIndex;
		$this->iDocumentId = $iDocumentId;
		$this->sType = $sType;
	}

	public function getIndex() {
		return $this->iIndex;
	}

	public function getDocumentId() {
		return $this->iDocumentId;
	}
	
	public function getType() {
		return $this->sType;
	}
	
	public function setFile(string $sFile) {
		$this->sFileName = basename($sFile);
		$this->sFile = $sFile;
	}
	
	
	public function hasFile() {
		return !is_null($this->sFile);
	}
	
	public function getFile() {
		return $this->sFile;
	}

	public function getBackupFile() {
		return $this->sBackupFile;
	}
	
	public function getFileName() {
		return $this->sFileName;
	}

	public function setBackupFile(string $sFile) {
		$this->sBackupFile = $sFile;
	}
	
	public function hasErrors() {
		return !empty($this->aErrors);
	}
	
	public function addError(string $sError) {
		$this->aErrors[] = $sError;
	}
	
	public function getErrors() {
		return $this->aErrors;
	}
	
	public function convertToEntity() {
		
		$oEntityFile = new EntityFile();
		$oEntityFile->document_id = $this->iDocumentId;
		$oEntityFile->type = $this->sType;
		$oEntityFile->file = basename($this->sBackupFile);
		
		return $oEntityFile;
	}

	public function store($sFileName, $sContent) {		
		Storage::storeFile($this, $sFileName, $sContent);
	}
	
}

