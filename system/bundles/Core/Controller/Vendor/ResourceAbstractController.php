<?php

namespace Core\Controller\Vendor;

abstract class ResourceAbstractController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'control';

	protected $sPath = '';
	
	protected $bExecutePHP = false;

	protected $aGlobalVars = [];

	protected function initFrontend() {

		// Für Hooks
		$oWebDynamics = \webdynamics::getInstance('frontend');

		$oWebDynamics->getIncludes();
		
		// Sprache usw. sollte hier nicht benötigt werden
		\System::setInterfaceLanguage('en');
	}

	public function __call($sMethod, $arguments) {

		// Schneide den String "Action" am ende ab
		$iCut = strlen("Action");
		$sRequiredResource = substr($sMethod, 0, -$iCut);
		$sRequiredResource = strtolower($sRequiredResource);

		// Die Resource mit ihren verzeichnissen (Verzeichnisse sind durch unterstriche getrennt)
		$aResource = explode("_", $sRequiredResource);
		$sFile = implode("/", $aResource);
		
		$this->printFile($sFile);
		
	}

	public function printFile($sFile) {

		// Der String zur resource im Vendor Verzeichnis
		$sResource = \Util::getDocumentRoot() . $this->sPath . $sFile;

		// Wenn die Datei existiert, dann wird sie ausgegeben, sonst wird ein
		// 404 ausgegeben
		if (file_exists($sResource)) {
			$this->_printFile($sResource);
		} else {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
		}
		// Beenden, damit nichts weiteres ausgegeben wird (MVC_Abstract_Controller gibbt sonst einen leeren JSON-String zurück)
		exit();
	}

	final protected function downloadFile($sFile) {

		$sFile = $this->sanitizePath($sFile);

		if (!file_exists($sFile)) {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
			die();
		}

		$aPathInfo = pathinfo($sFile);
		
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=".$aPathInfo['basename']."");
		
		$this->sendFileHeader($sFile);

		// Öffne die Datei im binär Modus
		$fp = fopen($sFile, 'rb');
		// Gib die Datei aus
		fpassthru($fp);

	}
	
	/**
	 * Gibt die Datei im Browser aus
	 * @param string $sFile <p>
	 * Die Datei, die im Browser ausgegeben wird.
	 * </p>
	 */
	final protected function _printFile($sFile) {

		$sFile = $this->sanitizePath($sFile);

		foreach($this->aGlobalVars as $sGlobalVar) {
			global ${$sGlobalVar};
		}
		
		if(!file_exists($sFile)) {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
			die();
		}

		// Die Endung der Datei
		$aFileParts = explode('.', $sFile);
		$sFileExtension = strtolower(array_pop($aFileParts));

		$sMimeType = $this->getMimeType($sFileExtension);
		header("Content-Type: " . $sMimeType);

		if(
			$this->bExecutePHP === true &&
			(
				$sFileExtension == 'php' ||
				$sFileExtension == 'html'
			)
		) {

			// Wird in den Includes benötigt (Abwärtskompatibilität)
			global $_VARS, $user_data;
			$_VARS = $this->_oRequest->getAll();
			
			if($this->_oAccess instanceof \Access_Backend) {
				$user_data = $this->_oAccess->getUserData();
			}

			// Direkten Aufruf simulieren
			$_SERVER['SCRIPT_FILENAME'] = $sFile;
			$_SERVER['SCRIPT_NAME'] = str_replace(\Util::getDocumentRoot(false), '', $sFile);

			$aPathInfo = pathinfo($sFile);
			chdir($aPathInfo['dirname']);
			require $sFile;
			die();

		} else {

			if ($sFileExtension === 'php') {
				// return abort(400); // Funktioniert nicht im alten Code
				die('400');
			}

			$this->sendFileHeader($sFile);

			// Öffne die Datei im binär Modus
			$fp = fopen($sFile, 'rb');
			// Gib die Datei aus
			fpassthru($fp);

		}

	}

	protected function sendFileHeader($sFile) {

		header("Content-Length: ".filesize($sFile));

		if($this->_oRequest->has('no_cache')) {

			header("Expires: 0");
			header("Cache-Control: no-cache, no-store, must-revalidate");
			header("Pragma: no-cache");

		} else {

			// Zuletzt modifiziert
			$iLastModified = filemtime($sFile);
			// Cachzeit - Eine Stunde
			$iLifetime = 60 * 60 * 1;

			$sExpireDate = gmdate("D, d M Y H:i:s", time() + $iLifetime) . " GMT";
			$sLastModifiedDate = gmdate("D, d M Y H:i:s", $iLastModified) . " GMT";
			//$sEtag = md5($sLastModifiedDate);

			// Header setzen
			header("Expires: " . $sExpireDate);
			header("Last-Modified: " . $sLastModifiedDate);
			header("Cache-Control: private, must-revalidate, max-age=" . $iLifetime);
			header("Pragma: private");
			//header("ETag: " . $sEtag); // TODO: Nicht implementiert

		}

	}
	
	/**
	 * @TODO Kann man mit finfo oder eine Symfony-Klasse ersetzen
	 *
	 * @param string $sFileExtension
	 * @return mixed
	 */
	private function getMimeType($sFileExtension) {

		// Alle "möglichen" (Dieser Methode) MIME-Typen
		$aMimeTypes = array(
			'css' => 'text/css',
			'js' => 'text/javascript',
			'mjs' => 'text/javascript',
			'php' => 'text/html',
			'svg' => 'image/svg+xml',
			'jpg' => "image/jpeg",
			'gif' => "image/gif",
			'png' => "image/png",
			'pdf' => "application/pdf",
			'csv' => "application/csv"
		);

		// MIME-Typ zurück geben, wenn er existiert
		if (array_key_exists($sFileExtension, $aMimeTypes)) {
			return $aMimeTypes[$sFileExtension];
		}
	}

	/**
	 * Sollte am besten RFC3986/5.2.4 folgen, aber das sollte ausreichend sein.
	 *
	 * Beispiel: https://gist.github.com/rdlowrey/5f56cc540099de9d5006
	 *
	 * @param string $path
	 * @return string
	 */
	protected function sanitizePath(string $path) {

		return str_replace('..', '', $path);

	}

}