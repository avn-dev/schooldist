<?php

/**
 * Missbraucht Update-Requirements zum Aktualisieren von Dateien vor dem Ausführen des Updates
 */
class Updates_Requirements_UpdateFilePreUpdate extends Requirement {

	protected $aFiles = array(
		'/system/includes/class.update.php',
		'/system/bundles/Core/Helper/Composer/File.php',
		'/system/bundles/Core/Service/ComposerService.php',
		'/system/bundles/Core/Helper/Composer/File/Scripts.php',
		'/system/bundles/Core/Controller/ComposerController.php',
		'/system/bundles/Core/Helper/Composer/FileCollector.php',
		'/system/bundles/Core/Helper/Composer/File/Requirements/Requirement.php',
		'/system/bundles/Core/Helper/Composer/File/Requirements.php',
		'/system/bundles/Core/Exception/ComposerException.php',
		'/system/bundles/Core/Helper/Composer/Requirements.php'
	);

	/**
	 * 
	 * @return boolean
	 */
	public function checkSystemRequirements() {

		$oUpdate = new Update;

		foreach($this->aFiles as $sFile) {
			$mSuccess = $oUpdate->getFile($sFile);
			if($mSuccess !== true) {
				$this->_aErrors[] = 'File "'.$sFile.'" could not be updated. ('.$mSuccess.')';
				return false;
			}
		}

		return true;
	}
}
	