<?php

namespace Notices\Http\Controller;

class ResourceController extends \Core\Controller\Vendor\ResourceAbstractController {
	
	protected $sPath = null;//

	protected $_sAccessRight = null;//'control';

	public function outputNoticesResource($sType, $sFile) {
		
		$this->sPath = "system/bundles/Notices/Resources/public/";
		
		$sTypePath = null;
				 
		switch($sType) {
			case 'css':
				$sTypePath = 'css/';
				break;
			case 'js':
				$sTypePath = 'js/';
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