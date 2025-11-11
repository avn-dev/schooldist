<?php

class Ext_TC_Frontend_Combination_Helper_Caching {
	/**
	 * @var string
	 */
	protected $sDir = 'storage/tc/frontend/combination/cache';
	/**
	 * @var Ext_TC_Frontend_Combination
	 */
	protected $oCombination;
	
	/**
	 * Konstruktor
	 * 
	 * @param Ext_TC_Frontend_Combination $oCombination
	 */
	public function __construct(Ext_TC_Frontend_Combination $oCombination) {
		$this->oCombination = $oCombination;
	}
	
	/**
	 * Liest die Cachedaten für die Kombination
	 * - prüft, ob die Datei existiert
	 * - prüft, ob die Datei befüllt ist
	 * 
	 * @param string $sCacheKey
	 * @param bool $bEncodeData
	 * @return mixed|null
	 */
	public function getFromCache($sCacheKey, $bEncodeData = true) {
		$sFile = $this->buildFilePath($sCacheKey);

//		if(file_exists($sFile)) {
		if($this->checkFile($sFile)) {
			$mData = file_get_contents($sFile);
			
			if($bEncodeData === true) {
				// Daten werden serialisiert in die Session-Dateien geschrieben
				$mData = json_decode($mData, true);
			}
				
			if(!empty($mData)) {
				return $mData;
			}
		}		
		
		if($this->oCombination->overwritable == 0) {
            /*
            * Wenn keine Daten da sind, muss die Kombination in den Stack
            * Die Daten sollten nicht on-the-fly generiert werden, sondern im Template sollte ein
            * Fehler angezeigt werden.
            */
            $this->writeToStack();
        }

		return null;
	}

	/**
	 * Fallback für alte Dateierweiterung, damit beim Update nicht BOA down ist
	 *
	 * @param $sFile
	 * @return bool
	 */
	private function checkFile(&$sFile) {

		if(file_exists($sFile)) {
			return true;
		}

		$sFile = str_replace('.json', '.txt', $sFile);
		if(file_exists($sFile)) {
			return true;
		}

		return false;

	}
	
	/**
	 * Schreibt die Cachedaten der Kombination in eine Datei
	 * 
	 * @param string $sCacheKey
	 * @param mixed $mData
	 * @throws RuntimeException
	 */
	public function writeToCache($sCacheKey, $mData) {
		$sFile = $this->buildFilePath($sCacheKey);
		// Daten werden serialisiert abgespeichert
		$sContent = json_encode($mData);
		// Datei ggf. anlegen überschreiben 
		$mWrite = file_put_contents($sFile, $sContent);
		Util::changeFileMode($sFile);

		if($mWrite === false) {
			throw new RuntimeException('Unable to set cache data');
		}		
	}
	
	/**
	 * Generiert eine Stack-Eintrag für das Parallel Processing
	 */
	protected function writeToStack() {
		$oCombinationProcessing = new Ext_TC_Frontend_Combination_Helper_ParallelProcessing();
		// prüfen, ob die Kombination bereits in dem Stack steht
		if($this->oCombination->status !== 'pending') {
			$oCombinationProcessing->addToStack($this->oCombination);
		}		
	}
	
	/**
	 * Prüft, ob der Cache der Kombination abgelaufen ist
	 * 
	 * @param string $sFile
	 * @return boolean
	 */
	protected function checkFileExpiration($sFile) {
		return false;
	}

	/**
	 * Liefert den Pfad zu der Cache-Datei der Kombination
	 * 
	 * @param string $sCacheKey
	 * @return string
	 */
	protected function buildFilePath($sCacheKey) {		
		$sFile = $this->oCombination->id . '_' . $sCacheKey . '.json';
		return $this->getCachingDirectory() . '/' .$sFile;
	}
	
	/**
	 * Liefert das Cache-Verzeichnis der Kombinationen
	 * - prüft ebenfalls auf Existenz und setzt ggf. Rechte
	 * 
	 * @return string
	 */
	protected function getCachingDirectory() {		
		$sDir = Util::getDocumentRoot() . $this->sDir . '/' . $this->oCombination->usage;	

		if(!is_dir($sDir)) {
			Util::checkDir($sDir);
		}
		
		return $sDir;
	}
		
	public function clearCache() {
		
		$files = glob($this->buildFilePath('*'));
		
		$deletedFiles = [];
		foreach($files as $file) {
			if (is_file($file)) {
				$deletedFiles[] = $file;
				unlink($file);
			}
		}

		return $deletedFiles;
	}
	
}

