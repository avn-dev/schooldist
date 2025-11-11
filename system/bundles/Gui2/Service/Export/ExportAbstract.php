<?php

namespace Gui2\Service\Export;

abstract class ExportAbstract {
		
	protected $sFilename = '';
	protected $sTitle = '';

	protected $sExtension;
	
	protected $aColumnList = [];

	/**
	 * 
	 * @param string $sFilename
	 */
	public function __construct(string $sFilename) {
		
		// Unter anderem &raquo; ersetzen
		$sFilename = html_entity_decode($sFilename, ENT_QUOTES, 'UTF-8');
		
		if(empty($sFilename)) {
			$sFilename = 'file';
		}
		
		$this->sTitle = $sFilename;
		$this->sFilename = \Util::getCleanFilename($sFilename).'.'.$this->sExtension;

		// Muss bereits hier geschehen,
		// 	ansonsten kann der Speicher bei >1000 ElasticSearch-Ergebnissen voll laufen!
		ini_set('memory_limit','8G');
		ini_set('max_execution_time','1800');

	}
	
	abstract public function sendHeader();
	
	/**
	 * Sendet eine Zeile des CSVs an den Browser
	 * @param array $aLine 
	 */
    public function sendLine($aLine, $aResultData=null) {
        echo $this->getLine($aLine, $aResultData);
    }
	
	public function setTitle(string $sTitle) {
		$this->sTitle = $sTitle;
	}
	
	public function setColumnList(array $aColumnList) {
		$this->aColumnList = $aColumnList;
	}
	
	public function save($sTarget) {
		
		$bSuccess = $this->oWriter->save($sTarget);
		
		return $bSuccess;
	}
	
	/**
	 * gibt den Titel für das CSV zurück
	 * @return string 
	 */
	public function getTitle() {
		return $this->sTitle;
	}
	
	protected function prepareValue($mValue) {
		
		// Vereinheitliche alle Breaks zu <br />
		$mValue = str_replace(array('<br>', '<br/>'), '<br />', $mValue);

		// Entferne alle <br /> am Ende des Strings
		while(strpos($mValue, '<br />') === strlen($mValue) - 6) {
			$mValue = substr($mValue, 0, strlen($mValue) - 6);
		}

		// Ersetze alle <br /> mitten im String mit einem KOMMA und einem Leerzeichen
		$mValue = str_replace('<br />', ', ', $mValue);
		
		$sValue = strip_tags($mValue);
		
		// Wenn nur HTML vorkam, nach alt-Attribut schauen
		if(
			!empty($mValue) &&
			empty(trim($sValue))
		) {
			$aMatch = [];
			if(preg_match('/alt="(.*?)"/', $mValue, $aMatch) === 1) {
				$sValue = $aMatch[1];
			}
		}
		
		return $sValue;
	}

	/**
	 * In TMP speichern
	 */
	public function end() {
		
		$sPath = \Util::getDocumentRoot().'storage/tmp/'.$this->sFilename;
		
		// Ausgabe in Datei, damit Zugriff geschützt ist. Ausgabe in Output wird in allgemeinem tmp Ordner zwischengespeichert
		$this->oWriter->save($sPath);

		echo file_get_contents($sPath);
		
	}

	public function setFilename($filename) {
		$this->sFilename = $filename;
	}
	
}
