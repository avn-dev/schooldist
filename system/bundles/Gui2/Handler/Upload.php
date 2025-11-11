<?php

namespace Gui2\Handler;

use \Illuminate\Support\Arr;

class Upload {

	/**
	 * @var array
	 */
	protected $aFiles;

	/**
	 * @var \WDBasic
	 */
	protected $oEntity;
	
	/**
	 *
	 * @var bool
	 */
	protected $bEntityAttachFiles = true;

	/**
	 * @var string
	 */
	protected $sColumn;

	/**
	 * @var array
	 */
	protected $aDelete;

	/**
	 * @var mixed
	 */
	protected $mCurrent;

	/**
	 * @var array
	 */
	protected $aOptions;

	/**
	 * @var bool
	 */
	protected $bMultiple;

	public function __construct(array $aFiles, array $aOptions, bool $bMultiple) {

		$this->aFiles = $aFiles;
		$this->aOptions = $aOptions;
		$this->bMultiple = $bMultiple;

	}

	/**
	 * @param \WDBasic $oEntity
	 */
	public function setEntity(\WDBasic $oEntity, $bAutoAttachFiles = true) {
		$this->oEntity = $oEntity;
		$this->bEntityAttachFiles = $bAutoAttachFiles;
		return $this;
	}

	/**
	 * @param string $sColumn
	 */
	public function setColumn($sColumn) {
		$this->sColumn = $sColumn;
		return $this;
	}

	/**
	 * @param array $aDelete
	 */
	public function setDelete(array $aDelete) {
		$this->aDelete = $aDelete;
		return $this;
	}

	/**
	 * @param mixed $mCurrent
	 */
	public function setCurrent($mCurrent) {
		$this->mCurrent = $mCurrent;
		return $this;
	}

	/**
	 * @return array|mixed $mResult
	 */
	public function handle() {

		if(empty($this->aOptions['upload_path'])) {
			throw new \Exception("Upload path has no value!");
		}
		
		$this->aOptions['upload_path'] = rtrim($this->aOptions['upload_path'], '/');
		
		$sDocumentRoot = \Util::getDocumentRoot(false);

		$mResult = [];
		foreach ($this->aFiles as $oFile) {

			$sFileName = $this->getFileName($oFile, $this->aOptions);

			/* Zursicherheit aus dem Filename
				 * nochmal alle verzeichnisse ausschließen */
			if (!Arr::get($this->aOptions, 'no_path_check')) {
				$aTemp = explode('/', $sFileName);
				$sFileName = end($aTemp);
			}

			// Verzeichnis prüfen
			$bCheckDir = \Util::checkDir($sDocumentRoot . $this->aOptions['upload_path']);

			$mSuccess = false;
			// Wenn das Verzeichnis in Ordnung ist
			if ($bCheckDir) {

				$sFileDir = str_replace('/storage/', '', $this->aOptions['upload_path']);

				// Datei verschieben und umbennennen
				$mSuccess = $oFile->storeAs($sFileDir, $sFileName);

			}

			if ($mSuccess !== false) {

				if (
					!empty($this->aDelete) &&
					$iKey = array_search($sFileName, $this->aDelete) !== false
				) {
					unset($this->aDelete[$iKey]);
				}

				$mResult[] = $sFileName;

			}
		}

		if (
			isset($this->aOptions['post_process']) &&
			$this->aOptions['post_process'] instanceof \Gui2\Interfaces\PostProcess &&
			$this->oEntity !== null
		) {

			$oPostProcess = $this->aOptions['post_process'];
			$mResult = $oPostProcess->execute($mResult, $this->aOptions, $this->oEntity);

		}

		if
		(
			$this->mCurrent !== null &&
			!is_array($this->mCurrent)
		) {

			$this->mCurrent = [$this->mCurrent];

		} elseif($this->mCurrent === null) {

			$this->mCurrent = [];

		}

		if (!$this->bMultiple) {
			
			// Wenn kein neuer Upload -> alten Wert nehmen
			if(empty($mResult)) {
				$mResult = $this->mCurrent;
			}
			
			$mResult = reset($mResult);
		} else {
			$mResult = array_merge($mResult, $this->mCurrent);
		}

		if(
			$this->oEntity !== null &&
			$this->bEntityAttachFiles === true
		) {
			if($this->sColumn === null) {
				throw new \Exception("Entity must have a column, null is given!");
			}

			$this->oEntity->{$this->sColumn} = $mResult;

			/*
			 * @todo Validierung ergänzen
			 */
			$oPersister = \WDBasic_Persister::getInstance();
			$oPersister->attach($this->oEntity);
		}

		if(!empty($this->aDelete)) {

			$sPath = \Util::getDocumentRoot(false).$this->aOptions['upload_path'];
			foreach($this->aDelete as $sFileName) {
				if(is_file($sPath.$sFileName)) {
					unlink($sPath.$sFileName);
				}
			}

		}

		return $mResult;
	}

	/**
	 * @param \Illuminate\Http\UploadedFile $oFile
	 * @param $aOptions
	 * @return string
	 */
	protected function getFileName(\Illuminate\Http\UploadedFile $oFile, $aOptions) {

		$sFileName = '';

		if(
			Arr::get($aOptions, 'add_column_data_filename') == 1 &&
			$this->sColumn !== null
		) {
			$sFileName .= $this->sColumn;
		}

		if(
			Arr::get($aOptions, 'add_column_data_filename') == 1 &&
			!empty(Arr::get($aOptions, 'db_alias'))

		) {
			$sFileName .= '_'.$aOptions['db_alias'];
		}

		if(!empty($sFileName)) {
			$sFileName .= '_';
		}

		if
		(
			Arr::get($aOptions, 'add_id_filename') == 1 &&
			$this->oEntity !== null
		) {
			$sFileName .= $this->oEntity->id.'_';
		}

		$sFileName .= \Util::getCleanFilename($oFile->getClientOriginalName());

		return $sFileName;
	}

	public function __sleep() {
		// TODO: Implement __sleep() method.
	}

}
