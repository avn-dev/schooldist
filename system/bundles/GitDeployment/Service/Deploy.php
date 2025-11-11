<?php

namespace GitDeployment\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * Git-Deployment: Dateien aktualisieren
 *
 * Achtung, die Klasse existiert nochmal auf dem Update-Server in abgespeckter Version!
 */
class Deploy {

	/**
	 * @var \Monolog\Logger
	 */
	protected $oLogger;

	/**
	 * @var string
	 */
	protected $sOriginalWorkingDirector;

	/**
	 * @var string
	 */
	protected $sRepositoryDir;

	/**
	 * @var string
	 */
	private $sCommitHashBefore = '';

	/**
	 * @var string
	 */
	private $sCommitHashAfter = '';

	/**
	 * Environment und Logger konfigurieren (E-Mails an P32)
	 */
	public function __construct() {

		// Verzeichnisse: Service geht davon aus, dass die Ordner git und html im selben Verzeichnis liegen
		$sDocumentRoot = \Util::getDocumentRoot();
		$this->sRepositoryDir = $sDocumentRoot.'../git/master/';
		$sGitSSH = str_replace('Service/Deploy.php', 'App/git-ssh-wrapper.sh', __FILE__);
		$sGitSSHConfig = $sDocumentRoot.'../git/.ssh/config';

		// Wenn nicht ausführbar: Versuchen, ausführbar zu machen
		if(!is_executable($sGitSSH)) {
			chmod($sGitSSH, 0777);
		}

		// Git-Dir setzen, um chdir() für Git zu vermeiden (muss .git-Verzeichnis sein)
		//putenv('GIT_DIR='.$this->sRepositoryDir.'.git/');
		//putenv('GIT_WORK_TREE='.$this->sRepositoryDir);

		// Git mitteilen, dass als SSH-Script der Wrapper benutzt werden soll
		putenv('GIT_SSH='.$sGitSSH);

		// Variable wird im Git-Wrapper-Script benutzt
		putenv('GIT_SSH_CONFIG='.$sGitSSHConfig);

		$this->oLogger = \Log::getLogger('git-deployment');
		$this->oLogger->pushProcessor(function($aRecord) {
			if($aRecord['level'] > \Monolog\Logger::ERROR) {
				$sHost = $_SERVER['HTTP_HOST'];
				$oMail = new \WDMail();
				$oMail->subject = 'GitDeployment '.$sHost.': '.$aRecord['message'];
				$oMail->text = print_r($aRecord, 1);
				$oMail->send(array('deployment@p32.de'));
			}

			return $aRecord;
		});

		$this->sOriginalWorkingDirector = getcwd();

	}

	public function handleExecute(string $hashBefore=null) 
	{
		
		$this->oLogger->addInfo('Check lock');
		
		$lockKey = __METHOD__;
		$ttl     = 600;  // Sekunden: > Worst-Case-Laufzeit + Puffer

		try {
			// wartet bis zu 300 Sekunden (5 Min) auf freien Lock
			Cache::lock($lockKey, $ttl)->block(300, function () use($hashBefore) {
				$oDeploy = new \GitDeployment\Service\Deploy();
				$oDeploy->execute($hashBefore);
			});
		} catch (LockTimeoutException $e) {
			$this->oLogger->addInfo('Check lock failed!');
			// nach 300 Sekunden immer noch kein Lock bekommen
			// → hier kannst du loggen oder eine Exception werfen
			throw new \RuntimeException("Deployment-Lock nicht verfügbar nach 5 Minuten");
		}

	}
	
	/**
	 * Service ausführen
	 */
	public function execute(string $hashBefore=null) {

		$this->oLogger->addInfo('Deploy-Service execution started');

		if($hashBefore !== null) {

			$this->fetch();
			$this->merge();

			$this->sCommitHashBefore = $hashBefore;
			$this->sCommitHashAfter = $this->getCurrentCommitHash();

		} else {

			// TODO Da nun die Hashs verglichen werden, könnte man auch direkt git pull ausführen
			$maxTries = 3;
			$waitTime = 20;

			for ($i = 1; $i <= $maxTries; $i++) {
				try {
					$this->fetch();
					$this->merge();
				} catch (\Exception $e) {
					$this->oLogger->addError('Git-Deployment failed: '.$e->getMessage());
					continue;
				}

				if ($this->sCommitHashBefore !== $this->sCommitHashAfter) {
					// Erfolg -> raus aus der Schleife
					break;
				}

				if ($i < $maxTries) {
					$this->oLogger->warning("Same hash (try {$i}/{$maxTries}), wait {$waitTime}s and try again...");
					sleep($waitTime);
				} else {
					$this->oLogger->error('Same hash after max retries, abort!');
					return;
				}
			}
		}
		
		
		$aFiles = $this->getChangedFiles();

		foreach($aFiles as $aFile) {

			// https://git-scm.com/docs/git-status#_output
			if(
				$aFile['state'] === 'A' ||
				$aFile['state'] === 'M' ||
				$aFile['state'] === 'C'
			) {
				$this->putFile($aFile['path']);
			} elseif($aFile['state'] === 'R') {
				$this->oLogger->info('Renamed file: '.$aFile['path_before'].' => '.$aFile['path']);
				$this->deleteFile($aFile['path_before']);
				$this->putFile($aFile['path']);
			} elseif($aFile['state'] === 'D') {
				$this->deleteFile($aFile['path']);
			} else {
				$sError = 'Unknown file state: '.$aFile['state'];
				$this->oLogger->addError($sError);
				//throw new \RuntimeException($sError);
			}

		}

		$this->switchWorkingDirectory(true);

		$this->oLogger->addInfo('Deploy-Service execution finished');
		
		\System::wd()->executeHook('git_deployment_post_hook', $aFiles);
		
	}

	/**
	 * Git-Fetch ausführen
	 */
	protected function fetch() {

		// Mit chdir() arbeiten, da Environment-Variablen nicht richtig funktionieren (nicht im Konstruktor, da Software irgendwas macht)
		$this->switchWorkingDirectory();

		// Aktueller Commit-Hash von HEAD, für Vergleich später
		$this->sCommitHashBefore = $this->getCurrentCommitHash();

		$sGitFetch = \Update::executeShellCommand('git fetch');

		if($sGitFetch === null) {

			$this->oLogger->addInfo('git fetch empty: no changes');

		} elseif(strpos($sGitFetch , 'fatal:') !== false) {

			$this->oLogger->addError('git fetch: fatal error', array($sGitFetch));
			throw new \RuntimeException('git fetch: fatal error');

		} else {
			$this->oLogger->addInfo('git fetch: ', [$sGitFetch]);
		}

	}

	/**
	 * Differenz bilden, welche Dateien im Git-Fetch seit dem letzten Merge verändert wurden
	 *
	 * @return array
	 */
	protected function getChangedFiles() {

		$this->switchWorkingDirectory();

		$aFiles = array();

		// Commit-Hashs vorher und nachher vergleichen, um veränderte Dateien zu ermitteln
		$cmd = 'git diff --name-status '.$this->sCommitHashBefore.' '.$this->sCommitHashAfter;
		$sGitLog = \Update::executeShellCommand($cmd);
		
		if(strpos($sGitLog, 'error:') !==false) {
			$this->oLogger->error($cmd, [$sGitLog]);
			return [];
		} else {		
			$this->oLogger->addInfo($cmd, [$sGitLog]);
		}

		$aRawFiles = explode("\n", $sGitLog);

		// Letzten Eintrag (Leerzeile) entfernen
		array_pop($aRawFiles);

		if(empty($aRawFiles)) {
			$this->oLogger->addInfo('git diff: No changes, compared '.$this->sCommitHashBefore.' with '.$this->sCommitHashAfter);
		} else {
			$this->oLogger->addInfo('git diff: Changes detected, compared '.$this->sCommitHashBefore.' with '.$this->sCommitHashAfter, $aRawFiles);
		}

		// Array umdrehen, da neueste Dateien zuerst kommen (Überschreiben und letztes States)
		$aRawFiles = array_reverse($aRawFiles);

		foreach($aRawFiles as $sRawFile) {
			// Zeilen sind im Format: M Dateiname
			[$sState, $sFile] = explode("\t", $sRawFile, 2);

			$sState = mb_substr($sState, 0, 1); // z.B. R100 statt A/M/D https://stackoverflow.com/a/53057010
			$sOldFile = null;
			if(strpos($sFile, "\t") !== false) {
				[$sOldFile, $sFile] = explode("\t", $sFile, 2);
			}

			$aCommit = $this->getLastCommitForFile($sFile);
			$aFiles[$sFile] = $aCommit;
			$aFiles[$sFile]['path'] = $sFile;
			$aFiles[$sFile]['path_before'] = $sOldFile;
			$aFiles[$sFile]['state'] = $sState;

		}

		$this->oLogger->info('Changed files array', $aFiles);

		return $aFiles;

	}

	/**
	 * Git-Merge ausführen (vom Fetch)
	 */
	protected function merge() {

		// Mit chdir() arbeiten, da Environment-Variablen nicht richtig funktionieren (zur Sicherheit)
		$this->switchWorkingDirectory();

		$sMerge = shell_exec('git merge FETCH_HEAD 2>&1');
		$this->oLogger->info('git merge FETCH_HEAD 2>&1', [$sMerge]);
		if(strpos($sMerge, 'fatal:') !== false) {
			$this->oLogger->addError('git merge: fatal error:'. $sMerge);
			throw new \RuntimeException('git merge: fatal error');
		}

		// Aktueller Commit-Hash von HEAD, für Vergleich später
		$this->sCommitHashAfter = $this->getCurrentCommitHash();

	}

	/**
	 * Letzten Commit der Datei holen (direkt von origin/master)
	 *
	 * @param string $sFile
	 * @return array
	 */
	public function getLastCommitForFile($sFile) {

		// ASCII-Unit-Separator als Trennzeichen benutzen
		$cmd = "git log --pretty=format:\"%h\x1f%an\x1f%B\x1f%p%x00\" origin/master -- ".$sFile;
		$sOutput = shell_exec($cmd);
		if (empty($sOutput)) {
			$this->oLogger->info($cmd." is null, trying fetch head", [$sFile, $cmd, $sOutput]);
			$cmd = "git log --pretty=format:\"%h\x1f%an\x1f%B\x1f%p%x00\" FETCH_HEAD -- ".$sFile;
			$sOutput = shell_exec($cmd);
		}
		$aLines = explode("\0", $sOutput);

		$this->oLogger->info('GIT log', [$sFile, $cmd, $sOutput, $aLines]);
		
		foreach($aLines as $sLine) {
			
			if (trim($sLine) === '') {
				$this->oLogger->info('Empty line', [$sLine]);
				continue; // leere Einträge überspringen
			}
			
			$aRecord = explode("\x1f", $sLine);

			// Sicherheit: unvollständige Datensätze überspringen
			if (count($aRecord) < 4) {
				$this->oLogger->info('Incomplete line', [$sLine]);
				continue;
			}
			
			// Wenn mehr als 1 Parent-Hash: Das ist ein Merge, daher Commit ignorieren und nächsten nehmen
			$aParentHashs = explode(' ', $aRecord[3]);
			if(count($aParentHashs) > 1) {
				$this->oLogger->info('Merge line', [$sLine]);
				continue;
			}

			// Commit-Message in eine Zeile bringen
			$message = preg_replace('/\s+/', ' ', trim($aRecord[2]));

			$aCommit = array(
				'hash' => $aRecord[0],
				'author' => $aRecord[1],
				'message' => $message
			);

			\System::wd()->executeHook('git_deployment_commit_info', $aCommit);

			return $aCommit;

		}

		$this->oLogger->addError('git log: log empty for '.$sFile);
		throw new \RuntimeException('git log: log empty');

	}

	/**
	 * Datei vom Repository in die Installation kopieren
	 *
	 * @param string $sFile
	 * @return bool
	 */
	protected function putFile($sFile) {

		$sDocumentRoot = \Util::getDocumentRoot();
		$sOriginalFile = $this->sRepositoryDir.$sFile;
		$sTargetFile = $sDocumentRoot.$sFile;

		if(!is_readable($sOriginalFile)) {
			$this->oLogger->addError('File is not readable: '.$sOriginalFile);
			return false;
		}

		$bSuccess = \Util::checkDir(dirname($sTargetFile));
		if(!$bSuccess) {
			$this->oLogger->addError('Util::checkDir() failed: '.$sTargetFile);
			return false;
		}

		if(
			(
				// Datei existiert nicht: Verzeichnis nicht beschreibbar?
				!is_file($sTargetFile) &&
				!is_writeable(dirname($sTargetFile))
			) || (
				// Datei existiert: Datei nicht beschreibbar?
				is_file($sTargetFile) &&
				!is_writeable($sTargetFile)
			)
		) {
			$this->oLogger->addError('File is not writable: '.$sTargetFile);
			return false;
		}

		$bSuccess = copy($sOriginalFile, $sTargetFile);
		if(!$bSuccess) {
			$this->oLogger->addError('File copying failed: '.$sOriginalFile.' -> '.$sTargetFile);
			return false;
		}

		$this->oLogger->addInfo('File created/updated: '.$sTargetFile);

		@touch($sTargetFile);
		@chmod($sTargetFile, 0777);

		return true;

	}

	/**
	 * Datei auf der Installation löschen
	 *
	 * @param string $sFile
	 * @return bool
	 */
	protected function deleteFile($sFile) {

		$sDocumentRoot = \Util::getDocumentRoot();
		$sTargetFile = $sDocumentRoot.$sFile;

		$bSuccess = @unlink($sTargetFile);
		if(!$bSuccess) {
			$this->oLogger->addError('File deletion failed: '.$sFile);
		} else {
			$this->oLogger->addInfo('File deleted: '.$sFile);
		}

		return $bSuccess;

	}

	/**
	 * Arbeitsverzeichnis wechseln für GIT-Kommandos, da Environment-Variablen nicht richtig funktionieren
	 *
	 * @param bool $bReverse
	 */
	public function switchWorkingDirectory($bReverse=false) {
		if(!$bReverse) {
			chdir($this->sRepositoryDir);
		} else {
			chdir($this->sOriginalWorkingDirector);
		}
	}

	/**
	 * @return string
	 */
	public function getRepositoryDirectory() {
		return $this->sRepositoryDir;
	}

	/**
	 * @return string
	 */
	public function getCurrentCommitHash() {
		$hash = trim(shell_exec('git log -1 --format="%H"'));
		$this->oLogger->info('Current commit hash: '.$hash);
		return $hash;
	}

}
