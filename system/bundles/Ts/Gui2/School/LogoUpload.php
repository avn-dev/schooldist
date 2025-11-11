<?php

namespace Ts\Gui2\School;

use \Gui2\Interfaces\PostProcess;

class LogoUpload implements PostProcess {

	/**
	 * 
	 * @param array $aResult
	 * @param array $aOptions
	 * @param \WDBasic|null $oEntity
	 * @return array|mixed $aResult
	 */
	public function execute(array $aResult, array $aOptions, \WDBasic $oEntity = null) {

		if(!empty($aResult[0])) {

			$sDocumentRoot = \Util::getDocumentRoot(false);
			$sUploadPath = $aOptions['upload_path'].'/';
			$sFileName = $aResult[0];

			$sSchoolDir = $oEntity->getSchoolFileDir().'/';
			\Util::checkDir($sSchoolDir);

			$sCurrentFile = $sUploadPath.$sFileName;

			if(!is_file($sDocumentRoot.$sCurrentFile)) {
				return array();
			}

			$sLogo = \Ext_Thebing_Util::formatLogo($sFileName, $sUploadPath);

			$sTarget = $sSchoolDir.'logo.png';

			rename($sDocumentRoot.$sLogo, $sTarget);
			\Util::changeFileMode($sTarget);

			unlink($sDocumentRoot.$sCurrentFile);

			$aResult[] = 'logo.png';

		}

		return $aResult;
	}

}