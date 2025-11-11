<?php

namespace Codemirror\Controller;

class ResourceController extends \Core\Controller\Vendor\ResourceAbstractController {
	
	protected $sPath = "vendor/thebingservices/codemirror/CodeMirror/";

	public function printFile($sFile) {

		$aResource = explode("_", $sFile);
		$sFile = implode("/", $aResource);

		parent::printFile($sFile);

	}

}