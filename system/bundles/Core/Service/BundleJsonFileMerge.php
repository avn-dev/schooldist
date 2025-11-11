<?php

namespace Core\Service;

use Core\Helper\Bundle;
use Illuminate\Validation\Validator;

/**
 * Generate composer.json/package.json including active bundles
 */
class BundleJsonFileMerge {

	/**
	 * @var string
	 */
	private $sFile;

	/**
	 * @var \Closure
	 */
	private $cMergeCallback;

	/**
	 * @var Validator
	 */
	private $oFileValidator;

	/**
	 * @var array
	 */
	private $aFileStructure = [];

	/**
	 * @var array
	 */
	private $aLog = [];

	/**
	 * @param string $sFile Filename with full path
	 * @param \Closure $cMergeCallback function(array $aFile, array &$aResult, string $sBundle) {}
	 * @param Validator $oFileValidator Laravel-Validator
	 */
	public function __construct(string $sFile, \Closure $cMergeCallback, Validator $oFileValidator) {

		$this->sFile = $sFile;
		$this->cMergeCallback = $cMergeCallback;
		$this->oFileValidator = $oFileValidator;

	}

	/**
	 * Parse *-base.json for default structure
	 */
	private function parseBaseFile() {

		$sBaseFile = str_replace('.json', '-base.json', $this->sFile);

		if(!is_readable($sBaseFile)) {
			throw new \RuntimeException('File does not exist: '.$sBaseFile);
		}

		$aJson = json_decode(file_get_contents($sBaseFile), true);
		if(empty($aJson)) {
			throw new \RuntimeException('Could not parse '.$sBaseFile);
		}

		$this->aFileStructure = $aJson;
		$this->aFileStructure['//'] = 'This file has been generated automatically.';

	}

	/**
	 * Merge all files from active bundles and pass merge logic in callback function
	 */
	private function mergeBundleFiles() {

		$aBundles = (new BundleService())->getActiveBundleNames();

		foreach($aBundles as $sBundle) {

			$sDir = (new Bundle())->getBundleDirectory($sBundle);
			$sFile = $sDir.'/'.basename($this->sFile);

			if(!is_readable($sFile)) {
				continue;
			}

			$aJson = json_decode(file_get_contents($sFile), true);
			$this->oFileValidator->setData($aJson);

			if($this->oFileValidator->fails()) {
				$sFailed = join(', ', array_keys($this->oFileValidator->failed()));
				$this->aLog[\Monolog\Logger::ERROR][] = 'File "'.$sFile.'" from bundle "'.$sBundle.'" has wrong format: '.$sFailed;
				continue;
			}

			$this->aLog[\Monolog\Logger::INFO][] = 'Include file "'.$sFile.'" from bundle "'.$sBundle.'"';

			($this->cMergeCallback)($aJson, $this->aFileStructure, $sBundle);

		}

	}

	/**
	 * Write file
	 */
	public function write() {

		$this->parseBaseFile();
		$this->mergeBundleFiles();

		$iOptions = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
		if(version_compare(PHP_VERSION, '7.3', '>=')) {
			$iOptions |= JSON_THROW_ON_ERROR;
		}

		$sJson = json_encode($this->aFileStructure, $iOptions);

		if(!file_put_contents($this->sFile, $sJson)) {
			throw new \RuntimeException('Could not write file '.$this->sFile);
		}

		$this->removeLockFile();

	}

	/**
	 * Remove lock file as an install (not update) command would install from lock rather than new json file
	 */
	private function removeLockFile() {

		foreach(['-lock.json', '.lock'] as $sReplace) {
			$sLockFile = str_replace('.json', $sReplace, $this->sFile);
			if(file_exists($sLockFile)) {
				if(!unlink($sLockFile)) {
					throw new \RuntimeException('Could not remove lock file: '.$sLockFile);
				}
				$this->aLog[\Monolog\Logger::INFO][] = 'Removed lock file: '.$sLockFile;
			}
		}

	}

	/**
	 * @return array
	 */
	public function getResult() {
		return $this->aFileStructure;
	}

	/**
	 * @return array
	 */
	public function getLog() {
		return $this->aLog;
	}

}
