<?php

class MVC_View_File extends MVC_View {

	/**
	 * Wenn auf true, dann eine leere Seite ausgeben
	 * Ansonsten würden die Exceptions in render() greifen
	 * @var bool
	 */
	protected $_bSkipRender = false;

	/**
	 * Gibt an, ob die Datei gedownloaded werden soll
	 * @var bool 
	 */
	public $bForceDownload = true;
	
	public function set($sName, $mValue) {

		$aAllowed = array(
			'mime_type',
			'file_name',
			'file_path',
		);

		if(!in_array($sName, $aAllowed)) {
			throw new BadMethodCallException('Not allowed configuration: "'.$sName.'"');
		}

		parent::set($sName, $mValue);

	}

	/**
	 * Datei mit übergebenen Daten anzeigen
	 * @TODO Download erzwingen
	 *
	 * @throws UnexpectedValueException
	 */
	public function render() {

		// Nur den Response-Code setzen
		http_response_code($this->getHTTPCode());

		$sMimeType = $this->_aTransfer['mime_type'];
		$sFilePath = $this->_aTransfer['file_path'];

		if(empty($sMimeType)) {
			throw new UnexpectedValueException('MIME type missing!');
		}

		if(
			empty($sFilePath) ||
			!is_file($sFilePath)
		) {
			throw new UnexpectedValueException('No file!');
		}

		$sFilename = $this->_getFilename();

		header('Content-Type: '.$sMimeType, true, $this->getHTTPCode());
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($sFilePath)).' GMT');
		//header('ETag: '.md5(filemtime($sFilePath).$sFilename));
		header('X-File-Name: '.$sFilename);

		if($this->bForceDownload) {
			header('Content-disposition: attachment; filename="'.$sFilename.'"');
		}

		$sFile = file_get_contents($sFilePath);

		echo $sFile;

		die();
	}

	/**
	 * Dateiname ggf. aus Pfad extrahieren
	 *
	 * @return string
	 */
	protected function _getFilename() {

		if(empty($this->_aTransfer['file_name'])) {
			return basename($this->_aTransfer['file_path']);
		}

		return $this->_aTransfer['file_name'];

	}

}