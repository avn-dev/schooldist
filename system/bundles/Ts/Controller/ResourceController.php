<?php

namespace Ts\Controller;

/**
 * Class ResourceController
 * @package Ts\Controller
 */
class ResourceController extends \Core\Controller\Vendor\ResourceAbstractController {

	protected $sPath = 'system/bundles/Ts/Resources/assets/';

	public function outputBackendResource($sFile) {

		$this->_sInterface = 'backend';
		$this->sPath = 'system/bundles/Ts/Resources/assets/';

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

}