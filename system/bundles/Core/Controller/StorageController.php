<?php
 
namespace Core\Controller;

class StorageController extends \Core\Controller\Vendor\ResourceAbstractController {

	protected $_sAccessRight = 'control';

	protected $sPath = 'storage/';

	public function downloadStorageFile($sFile) {

		// Der String zur resource im Vendor Verzeichnis
		$sResource = \Util::getDocumentRoot() . $this->sPath . $sFile;

		// Wenn die Datei existiert, dann wird sie ausgegeben, sonst wird ein
		// 404 ausgegeben
		if (file_exists($sResource)) {
			$this->downloadFile($sResource);
		} else {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
		}
		
		exit();
	}


	public function openStorageFile($sFile) {

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
		
		exit();
	}
	
}