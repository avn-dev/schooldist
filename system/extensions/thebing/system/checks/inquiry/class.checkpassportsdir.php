<?php
/**
 * In der Ext_TS_Inquiry_Contact_Abstract::savePhoto() wurde der Ordner nicht mit Util::checkdir() erstellt.
 * Das Problem war aber, dass bei mkdir und chmod die eigentliche Oktalzahl als String übergeben wurde,
 * 	und somit von PHP als Integer angesehen wurde. Das ergab dann ein Recht von 1411.
 *
 * @since 13.06.2013
 * @author DG <dg@thebing.com>
 */
class Ext_Thebing_System_Checks_Inquiry_CheckPassportsDir extends GlobalChecks {

	protected $_aLog = array();
	protected $_sMediaPath;

	public function getTitle() {
		$sTitle = 'Check student photos integrity';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check student photos integrity';
		return $sDescription;
	}

	public function executeCheck() {
		$oSchool = new Ext_Thebing_School();
		$aSchools = $oSchool->getObjectList();

		foreach($aSchools as $oSchool) {
			$sDir = $oSchool->getSchoolFileDir().'/studentcards';
			if(is_dir($sDir)) {
				$iChmod = substr(decoct(fileperms($sDir)), 2);
				if($iChmod != 777) {
					$this->logInfo('Directory "'.$sDir.'" has mod "'.$iChmod.'"!');
					$bChmod = chmod($sDir, 0777);
					if(!$bChmod) {
						$sError = 'Failed to set chmod of directory "'.$sDir.'"!';
						$this->logError($sError);
						throw new RuntimeException($sError);
					}
					else {
						$this->logInfo('Mode of directory "'.$sDir.'" changed.');
					}
				}
			}
		}

		return true;
	}

}
