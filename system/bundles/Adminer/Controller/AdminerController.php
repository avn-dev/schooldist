<?php

namespace Adminer\Controller;

class AdminerController extends \Core\Controller\Vendor\ResourceAbstractController {

	protected $_sAccessRight = 'adminer';

	/**
	 * Die Methode ist flexibel gehalten, weil angedacht war diese allgemein für verschiedene Admin-Views zu verwenden
	 * @throws \RuntimeException
	 */
	public function view() {

		// Damit die Header nicht direkt gesendet werden -> wg. Sessions
		ob_start();
		
		$sResource = \Util::getDocumentRoot().'system/bundles/Adminer/Resources/php/adminer-4.7.8-mysql-en.php';

		$this->bExecutePHP = true;
		
		$this->_printFile($sResource);
		die();
	}
	
}
