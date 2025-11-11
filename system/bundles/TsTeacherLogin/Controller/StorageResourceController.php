<?php

namespace TsTeacherLogin\Controller;

class StorageResourceController extends \Core\Controller\Vendor\ResourceAbstractController {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	protected $sPath = '';

	public function getLogo() {

		$oDesign = new \Admin\Helper\Design;

		$aImages = $oDesign->getLogos();

		$this->printFile($aImages['system_logo']);
		
	}

	public function getFile($sFile) {

		$oSession = \Core\Handler\SessionHandler::getInstance();

		$aStorageFiles = $oSession->get('ts_teacherlogin_storage_files', []);

		if(in_array($sFile, $aStorageFiles)) {
			$this->printFile($sFile);
		} else {
			header("HTTP/1.1 401 Unauthorized");
			die();
		}

	}

}