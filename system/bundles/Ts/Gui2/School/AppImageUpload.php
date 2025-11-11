<?php

namespace Ts\Gui2\School;

use \Gui2\Interfaces\PostProcess;

class AppImageUpload implements PostProcess {

	/**
	 * 
	 * @param array $aResult
	 * @param array $aOptions
	 * @param \WDBasic $oEntity
	 * @return mixed
	 */
	public function execute(array $aResult, array $aOptions, \WDBasic $oEntity = null) {

		if(!empty($aResult[0])) {

			$sDocumentRoot = \Util::getDocumentRoot(false);
			$sUploadPath = $aOptions['upload_path'].'/';
			$sFileName = $aResult[0];

			$sSchoolDir = $oEntity->getSchoolFileDir(false);
			\Util::checkDir($sDocumentRoot.$sSchoolDir.'/app');

			$sCurrentFile = $sUploadPath.$sFileName;

			$sTarget = $sSchoolDir.'/app/'.$sFileName;

			rename($sDocumentRoot.$sCurrentFile, $sDocumentRoot.$sTarget);
			\Util::changeFileMode($sDocumentRoot.$sTarget);

		}

		return $aResult;
	}

}