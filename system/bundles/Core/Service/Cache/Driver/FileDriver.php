<?php

namespace Core\Service\Cache\Driver;

/**
 * @todo Ist noch nicht fertig
 */
class FileDriver extends AbstractDriver {

	public function __construct($sStoragePath) {
		$this->sStoragePath = $sStoragePath;
		\Util::checkDir($this->sStoragePath);
	}
	
	public function exists($sKey) {
		
		$sFile = $this->searchFile($sKey);
		
		if(
			!is_null($sFile) &&
			file_exists($sFile)
		) {
			return true;
		}
		
		return false;
	}


	protected function add($sKey, $iExpiration, $mData) {
		
//		if($this->exists($sKey)) {
//			$this->forget($sKey);
//		}
//
//		if($oEntry->isNotForever()) {
//			$sFilename = $this->buildFileName($sKey, $iExpiration);
//		} else {
//			$sFilename = $this->buildFileName($sKey);
//		}
//		
//		file_put_contents($sFilename, serialize($oEntry));
//		
//		return $oEntry;
	}

	public function forget($sKey) {
		if($this->exists($sKey)) {
			$this->deleteFile($this->searchFile($sKey));
			return true;
		}
		return false;
	}

	public function get($sKey) {
		
		if($this->exists($sKey)) {			
			$sFile = $this->searchFile($sKey);
			touch($sFile);
			
			return unserialize(file_get_contents($sFile));
		}
		
		return null;
	}

	public function getExistingKeys($sPrefix) {	
		return array_map(function($sFile) {
			$sFile = str_replace([$this->sStoragePath, '.txt'], '', $sFile);
			$aFileParts = explode('_', $sFile);
			return implode('_', [$aFileParts[0], $aFileParts[1]]);
		}, $this->getAllFiles());		
	}

	public function garbageCollector() {
		
		$aExpirationFiles = $this->getFilesWithExpiration();
		
		if(!empty($aExpirationFiles)) {
			$iCurrentTimestamp = (new \DateTime())->getTimestamp();
			foreach($aExpirationFiles as $sFile) {
				
				$aFileParts = explode('_', $sFile);
				
				$iExpirationTime = str_replace('.txt', '', $aFileParts[2]);
				
				if($iExpirationTime <= $iCurrentTimestamp) {
					$this->deleteFile($sFile);
				}
			}
		}		
	}
	
	private function deleteFile($sFile) {
		if(file_exists($sFile)) {
			unlink($sFile);
			return true;
		}
		return false;
	}
	
	private function getAllFiles() {
		return $this->search('*');
	}
	
	private function getFilesWithExpiration() {
		return $this->search('*_*_*.txt');
	}
	
	private function search($sPattern) {
		return glob($this->sStoragePath.$sPattern);
	}
	
	private function searchFile($sKey) {
		$aFiles = $this->search($sKey . '_*');
		if(!empty($aFiles)) {
			return reset($aFiles);
		}
		return null;
	}
	
	private function buildFileName($sKey, \DateTime $oExpirationDate = null) {
		
		$aFile = [ $this->sStoragePath, $sKey ];
		
		if($oExpirationDate instanceof \DateTime) {
			$aFile[] = '_'.$oExpirationDate->getTimestamp();
		}
		
		return implode('', $aFile).'.txt';
	}

	public function increment($key, $value, $initialValue = 0, $expiry = 0) {
		throw new \BadMethodCallException('increment method not implemented for database cache driver');
	}

}

