<?php

namespace AdminLte\Controller;

class ResourceController extends \Core\Controller\Vendor\ResourceAbstractController {
	
	protected $sPath = null;//

	protected $_sAccessRight = null;//'control';

	public function outputAdminLTEResource($sType, $sFile) {

		$this->sPath = "vendor/almasaeed2010/adminlte/";
		
		$sTypePath = null;
				 
		switch($sType) {
			case 'bootstrap':
				$sTypePath = 'bower_components/bootstrap/dist/';
				break;
			case 'css':
				$sTypePath = 'dist/css/';
				break;
			case 'img':
				$sTypePath = 'dist/img/';
				break;
			case 'js':
				$sTypePath = 'dist/js/';
				break;
			case 'components':
				$sTypePath = 'bower_components/';
				break;
			case 'plugins':
				$sTypePath = 'plugins/';
				break;
		}
		
		// Der String zur resource im Vendor Verzeichnis
		$sResource = \Util::getDocumentRoot() . $this->sPath . $sTypePath . $sFile;

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