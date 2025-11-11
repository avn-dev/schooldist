<?php

/**
 * Interne Update-Klasse für das Update-Tool (Deployment)
 *
 * @author Thomas Schröder
 */
class Ext_Internal_Update_Files {

	/**
	 * Nur Dateien in denen der Suchstring vorkommt
	 */
	public $sSearch = null;
	
	/**
	 * Nur Dateien von diesem User
	 */
	public $sUser = null;
	
	/**
	 * Nur Dateien die in den letzten X Tagen verändert wurden
	 */
	public $iDays = 4;
	
	/**
	 * Je nach Release interne Dateien ausschliessen
	 */
	public $bInternal = null;
	
	public function searchChangedFiles(&$aFiles, &$aUsers, $bDeployment = false, $bLoadCommitMessages = false) {

		$sRoot = Util::getDocumentRoot();
		if($bDeployment) {
			$sRoot .= '../git/master/';
		}

		// Neues exclude, welches die Pfade direkt in find ignoriert
		$aExcludePaths = [
			'/backup',
			'/node_modules',
			'/vendor'
		];

		$sExcludeArgs = join(' ', array_map(function ($sPath) use($sRoot) {
			return '-not \( -path '.rtrim($sRoot, '/').$sPath.' -prune \)';
		}, $aExcludePaths));

		$aReturn = array();
		if($this->sSearch !== null) {
			$sCmd = 'find '.$sRoot.' '.$sExcludeArgs.' -type f -printf "%p %T@ \n"';
		} else {
			$sCmd = 'find '.$sRoot.' '.$sExcludeArgs.' -type f -mtime -'.$this->iDays.' -printf "%p %T@ \n"';
		}

		exec($sCmd, $aReturn);

		foreach($aReturn as $sLine) {

			$aLine = explode(" ", $sLine);
			$sFile = str_replace($sRoot, "/", $aLine[0]);

			if(
				(
					strpos($sFile, "/admin") === 0 ||
					strpos($sFile, "/media") === 0 ||
					strpos($sFile, "/system") === 0 ||
					strpos($sFile, "/tools") === 0 ||
					strpos($sFile, "/favicon.") === 0
				) &&
				strpos($sFile, "/system/extensions/test/") === false &&
				strpos($sFile, "/admin/extensions/test/") === false &&
				// SCSS-Partials beginnen mit _
				// strpos($sFile, "/_") === false &&
				strpos($sFile, "/temp/") === false &&
				strpos($sFile, "/templates_c/") === false &&
				strpos($sFile, "/german/") === false &&
				strpos($sFile, "/backup/") === false &&
				strpos($sFile, "/english/") === false &&
				strpos($sFile, "/phpmyadmin/") === false &&
				strpos($sFile, "/phpMyAdmin/") === false &&
				strpos($sFile, "/.svn") === false &&
				strpos($sFile, ".htaccess") === false &&
				strpos($sFile, "config.inc.php") === false &&
				strpos($sFile, "access/class.client.php") === false &&
				strpos($sFile, "/tc/access/class.data.php") === false &&
				strpos($sFile, "/tmp") === false &&
				strpos($sFile, "/DefinitionCache/") === false &&
				strpos($sFile, "/node_modules") === false &&
				(
					strpos($sFile, "/media") !== 0 ||
					strpos($sFile, "/media/secure/templates") === 0 ||
					strpos($sFile, "/media/secure/tc/templates") === 0 ||
					strpos($sFile, "/media/js") === 0 ||
					strpos($sFile, "/media/frontend") === 0
				) &&
				(
					!$this->sSearch ||
					strpos($sFile, $this->sSearch) !== false
				) &&
				(
					$this->bInternal === true ||
					(
						strpos($sFile, "/ti/") === false &&
						strpos($sFile, "/internal/") === false
					)
				)
			) {

				$iDate = $aLine[1];

				/*$aFileReturn = array();
				$sCmd = 'svn info '.\Util::getDocumentRoot().$sFile.'';
				exec($sCmd, $aFileReturn);

				$sAuthor = $this->_getSvnInfo($aFileReturn);*/

				try {
					if(!$bLoadCommitMessages) {
						throw new RuntimeException();
					}
					$oGitDeployment = new GitDeployment\Service\Deploy();
					$oGitDeployment->switchWorkingDirectory();
					$aLastCommit = $oGitDeployment->getLastCommitForFile(ltrim($sFile, '/'));
					$oGitDeployment->switchWorkingDirectory(true);
					$sAuthor = $aLastCommit['author'];
					$sCommitMessage = $aLastCommit['message'];
				} catch(RuntimeException $e) {
					$sAuthor = null;
					$sCommitMessage = null;
				}

				if($sAuthor) {
					$aUsers[$sAuthor] = $sAuthor;
				}

				if(
					empty($this->sUser) ||
					$this->sUser == $sAuthor
				) {
					$aFiles[] = array($iDate, $sFile, $sAuthor, $sCommitMessage);
				}

			}

		}

	}

	/*protected function _getSvnInfo($aInfo, $sSearch='Last Changed Author') {

		foreach((array)$aInfo as $sInfo) {
			if(strpos($sInfo, $sSearch) !== false) {
				$sReturn = substr($sInfo, strpos($sInfo, ':')+2);
				return $sReturn;
			}
		}

	}*/
	
}
