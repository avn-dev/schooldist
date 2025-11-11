<?php

/**
 * 
 */
class Checks_MediaSecurity extends GlobalChecks {

	/**
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);

		$this->deleteInsecureScripts();
		
		$bReturn = $this->checkPHP();

		// Wenn der Check fehlschlägt, HTACCESS neu generieren und nochmal checken
		if($bReturn !== true) {

			$this->logError('checkPHP failed');
			
			$oHtaccessGenerator = new \Core\Generator\Htaccess();
			$mWrite = $oHtaccessGenerator->run();
			
			if($mWrite === true) {
				$this->logInfo('Generating htaccess successfull');
				$bReturn = $this->checkPHP();
			} else {
				$this->logError('Generating htaccess failed');
			}
		}

		return $bReturn;
	}
	
	public function getTitle() {
		return 'Security check';
	}
	
	public function getDescription() {
		return 'Removes insecure scripts and checks if it is possible to execute PHP scripts in media directory.';
	}
	
	private function checkPHP() {

		$bReturn = false;
		
		$sTestScriptFile = 'media/'.Util::generateRandomString(16).'.php';
		
		$sTestScript = '<?php echo "ABC123"; ?>';

		if(is_file(Util::getDocumentRoot().$sTestScriptFile)) {
			throw new Exception('Test script "'.$sTestScriptFile.'" exists!');
		}

		file_put_contents(Util::getDocumentRoot().$sTestScriptFile, $sTestScript);
		
		if(empty($_SERVER['HTTPS'])) {
			$sUrl = 'http://';
		} else {
			$sUrl = 'https://';
		}
		$sUrl .= $_SERVER['HTTP_HOST'].'/'.$sTestScriptFile;

		$bCheckUrl = Util::checkUrl($sUrl);

		$this->logInfo('checkPHP', array('check_url'=>$bCheckUrl));
		
		// Skript darf nicht erreichbar sein
		if($bCheckUrl === false) {
			$bReturn = true;
		} else {
			$sTestResult = file_get_contents($sUrl);

			$this->logInfo('checkPHP', array('test_result'=>$sTestResult));

			// Falls doch, darf nicht der Teststring kommen
			if($sTestResult != 'ABC123') {
				$bReturn = true;
			}
			
		}

		unlink(Util::getDocumentRoot().$sTestScriptFile);

		return $bReturn;
	}
	
	private function deleteInsecureScripts() {
		
		$aDelete = array(
			'admin/editor/',
			'admin/extensions/manual/editor/',
			'admin/includes/codepress/',
			'admin/includes/tiny_mce/',
			'system/extensions/office/fckeditor/'
		);
		
		Util::$iDeletedFiles = 0;
		
		foreach($aDelete as $sDelete) {
			Util::recursiveDelete(Util::getDocumentRoot().$sDelete);
		}

		$this->logInfo('deleteInsecureScripts', array('deleted_files'=>Util::$iDeletedFiles));

	}

}
