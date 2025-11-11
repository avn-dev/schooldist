<?php

use Core\Service\ComposerService;

/**
 * 
 */
class Update {

	protected $_sUpdateServer = null;

	protected $_bLastFileChanged = null;
	
	protected $sCurrentVersion;

	protected static $sL10NDescription = 'System » Update';
	
	public function __construct($sServerUrl=null) {

		if($sServerUrl === null) {
			$this->_sUpdateServer = System::d('update_server');
		} else {
			$this->_sUpdateServer = $sServerUrl;
		}

		$this->sCurrentVersion = System::d('version');
		
	}

	public function setCurrentVersion($fCurrentVersion) {
		$this->sCurrentVersion = $fCurrentVersion;
	}

	public function requestUpdateServer($sRequest) {

		$sRequest .= "&version=".$this->sCurrentVersion."&key=".System::d('license')."&host=".\Util::getHost();

		$sUrl = 'https://'.$this->_sUpdateServer.$sRequest;

		$rCurl = curl_init();
		curl_setopt($rCurl, CURLOPT_URL, $sUrl);
		// Ist sicherheitsrelevant, daher muss geprüft werden
		curl_setopt($rCurl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($rCurl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rCurl, CURLOPT_USERAGENT, 'Fidelo Update Service');
		curl_setopt($rCurl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($rCurl, CURLOPT_TIMEOUT	, 30);

		$sContent = curl_exec($rCurl);

		$bSuccess = true;
		if($sContent === false) {
			$bSuccess = false;
		}

		return $sContent;

	}

	function getFile($sFile, $sExtension=null, $sCurrentVersion=null) {

		if($sFile) {

			if($sCurrentVersion === null) {
				$sFileVersion = $this->sCurrentVersion;
			} else {
				$sFileVersion = $sCurrentVersion;
			}
			
			$sContent = "";
			$sParameter = "action=getfile&file=".$sFile."&key=".System::d('license')."&host=".\Util::getHost();

			if($sExtension !== null) {
				if($sCurrentVersion === null) {
					throw new InvalidArgumentException('Version parameter is mandatory for bundle files!');
				}
				$sParameter .= "&extension=".$sExtension;
			}

			$sParameter .= "&version=".$this->sCurrentVersion;
			$sParameter .= "&file_version=".$sFileVersion;
			$sParameter .= "&php_version=".Util::getPHPVersion();
			$errno = 0;
			$errstr = "";

			$fp = fsockopen('ssl://'.$this->_sUpdateServer, 443, $errno, $errstr, 10);

			if (!$fp) {
				echo "Der Updateserver konnte nicht erreicht werden: $errstr ($errno)<br />\n";
				return 'no_server';
			} else {

				$sPut = "POST /update.php?".$sParameter." HTTP/1.0\r\nUser-Agent: Fidelo Update Service\r\nHost: ".$this->_sUpdateServer."\r\n\r\n";

				fputs ($fp, $sPut);

				while (!feof($fp)) {
					$sContent .= fgets($fp, 4096);
				}

				fclose($fp);

			}

			$sStart	= "#"."##START"."###";
			$sEnd	= "#"."##END"."###";
			if(
				strpos($sContent,$sStart) && 
				strpos($sContent,$sEnd)
			) {

				$pos1 = strpos($sContent,$sStart);
				$pos2 = strpos($sContent,$sEnd);
				$len1 = strlen($sStart);
				$len2 = strlen($sEnd);
				$sContent = substr($sContent,($pos1+$len1),($pos2-$pos1-$len1));

				// Backup der alten Datei + Fehler abfangen
				if(!chdir(\Util::getDocumentRoot()."backup/")) {
					Util::changeDirMode(\Util::getDocumentRoot()."backup/");
					copy(\Util::getDocumentRoot()."storage/.htaccess", \Util::getDocumentRoot()."backup/.htaccess");
				}
				$sBackUpFile = \Util::getDocumentRoot()."backup/".str_replace("/","_",$sFile);
				if(
					file_exists(\Util::getDocumentRoot().$sFile) &&
					!copy(\Util::getDocumentRoot().$sFile, $sBackUpFile)
				) {
					return 'no_backup';
				}

				$sFile = \Util::getDocumentRoot().$sFile;

				// Verzeichnisse anlegen
				$aPath = pathinfo($sFile);
				Util::checkDir($aPath['dirname']);

				// Fehler abfangen
				$bError = false;

				$fh = fopen($sFile.'__', 'w');

				if($fh) {
					if(fwrite($fh,$sContent) !== false) {
						if(!fclose($fh)) {
							$bError = 'not_closeable';
						}
					} else {
						$bError = 'not_writeable';
					}
				} else {
					$bError = 'not_openable';
				}
				
				if($bError) {
					if(file_exists($sBackUpFile)) {
						copy($sBackUpFile, $sFile);
					}
					return $bError;
				}

				// Prüfen, ob sich die Datei verändert hat
				if(is_file($sFile)) {

					$sMd5Test = md5_file($sFile.'__');
					$sMd5File = md5_file($sFile);
					$iSizeTest = filesize($sFile.'__');
					$iSizeFile = filesize($sFile);

					if(
						$sMd5Test != $sMd5File ||
						$iSizeTest != $iSizeFile
					) {
						$bCreateFile = true;
					} else {
						$bCreateFile = false;
					}

				} else {
					$bCreateFile = true;
				}

				if($bCreateFile) {
					// Kopie der alten Datei herstellen
					$bOldFile = false;
					if(is_file($sFile)) {
						rename($sFile, $sFile.'__TEMP__');
						$bOldFile = true;
					}
					// Original mit der neuen Datei überschreiben
					$bRename = rename($sFile.'__', $sFile);
					if($bRename) {
						Util::changeFileMode($sFile);
						if($bOldFile) {
							unlink($sFile.'__TEMP__');
						}
					} else {
						if($bOldFile) {
							rename($sFile.'__TEMP__', $sFile);
						}
						return 'not_renameable';
					}
					
					$this->_bLastFileChanged = true;
					
				} else {

					unlink($sFile.'__');
					
					$this->_bLastFileChanged = false;
					
				}

				return true;

			} else {
				return 'no_valid_content';
			}
		} else {
			return 'no_file';
		}

	}

	public function getLastFileChanged() {
		return $this->_bLastFileChanged;
	}
	
	// Schreibt eine Datenbank Tabelle komplett neu
	public function updateTable($sTable) {

		$sFullUrl = '/database.php?task=getCreateTable&table='.$sTable;
		$sTableCreateQuery = $this->requestUpdateServer($sFullUrl);

		$sFullUrl = '/database.php?task=getInsert&table='.$sTable;
		$sSerialize = $this->requestUpdateServer($sFullUrl);

		$aData = json_decode($sSerialize, true);

		if(
			!empty($sTableCreateQuery) &&
			is_array($aData) &&
			!empty($aData)
		) {

			$aSqlTable = array('table'=>$sTable);

			$sTempName = \Util::generateRandomString(8);
			$sTableCreateQuery = str_replace($sTable, $sTempName, $sTableCreateQuery);
			DB::executeQuery($sTableCreateQuery);

			foreach((array)$aData as $aRow) {
				DB::insertData($sTempName, $aRow);
			}

			try {
				$sSql = "DROP TABLE #table";
				DB::executePreparedQuery($sSql, $aSqlTable);
			} catch(Exception $e) {

			}

			$sSql = "RENAME TABLE #table1 TO #table2";
			$aSql = array('table1'=>$sTempName, 'table2'=>$sTable);
			DB::executePreparedQuery($sSql, $aSql);

			return true;

		} else {
			__pout($sTable);
			__pout($sTableCreateQuery);
			__pout($aData);
		}

		return false;

	}

	public static function searchChangedFiles(&$aFiles, &$aUsers, $sSearch=null, $sUser=null, $iDays=3) {
		global $root,$files, $iMaxLength;

		$sRoot = Util::getDocumentRoot();

		$aReturn = array();
		if(empty($iDays)) {
			$sCmd = 'find '.$sRoot.' ! -path "*/backup/*" ! -path "*/.svn*" -type f -printf "%p %T@ \n"';
		} else {
			$sCmd = 'find '.$sRoot.' ! -path "*/backup/*" ! -path "*/.svn*" -type f -mtime -'.(int)$iDays.' -printf "%p %T@ \n"';
		}

		exec($sCmd, $aReturn);

		foreach($aReturn as $sLine) {
			$aLine = explode(" ", $sLine);
			$sFile = str_replace($sRoot, "", $aLine[0]);

			if(
				strpos($sFile, "/temp/") === false && 
				(
					strpos($sFile, "/templates_c/") === false ||
					strpos($sFile, "/index.html") !== false
				) &&
				strpos($sFile, "update/") !== 0 && 
				strpos($sFile, "german/") === false && 
				strpos($sFile, "backup/") === false && 
				strpos($sFile, "english/") === false && 
				strpos($sFile, "phpMyAdmin/") === false && 
				strpos($sFile, ".svn") === false && 
				strpos($sFile, ".htaccess") !== 0 && 
				strpos($sFile, ".gitignore") !== 0 && 
				strpos($sFile, "/.htaccess") === false && 
				strpos($sFile, "config/config.php") !== 0 && 
				strpos($sFile, "system/bundles/Pdf/Resources/fonts") !== 0 && 
				strpos($sFile, "storage/gui2/sessions/") === false && 
				strpos($sFile, "storage/calendarsheet/sessions/") === false && 
				strpos($sFile, "update_queries/") === false && 
				strpos($sFile, "vendor/") === false && 
				strpos($sFile, "composer.lock") !== 0 && 
				strpos($sFile, "composer.json") !== 0 && 
				strpos($sFile, "composer.phar") !== 0 && 
				strpos($sFile, "storage/logs/") !== 0 && 
				strpos($sFile, "TODO.txt") !== 0 && 
				(
					strpos($sFile, "storage/public") === 0 ||
					strpos($sFile, "storage/gui2") === 0 ||
					strpos($sFile, "storage") !== 0
				) &&
				(
					!$sSearch ||
					stripos($sFile, $sSearch) !== false
				)
			) {

				$iDate = $aLine[1];

				//$aFileReturn = array();
				//$sCmd = 'svn info '.\Util::getDocumentRoot().$sFile.'';
				//exec($sCmd, $aFileReturn);

				//$sAuthor = self::_getSvnInfo($aFileReturn);

				if($sAuthor) {
					$aUsers[$sAuthor] = $sAuthor;
				}

				if(
					empty($sUser) ||
					$sUser == $sAuthor
				) {
					$aFiles[] = array($iDate, $sFile, $sAuthor);
				}

			}

		}

	}

	protected static function _getSvnInfo($aInfo, $sSearch='Last Changed Author') {

		foreach((array)$aInfo as $sInfo) {
			if(strpos($sInfo, $sSearch) !== false) {
				$sReturn = substr($sInfo, strpos($sInfo, ':')+2);
				return $sReturn;
			}
		}

	}
	
	/**
	 * Gibt den Cache-Key zurück
	 * @return string 
	 */
	protected function _getUpdatesCacheKey() {
		
		$sCacheKey = 'admin_getUpdates';
		
		return $sCacheKey;
		
	}
	
	/**
	 * Löscht den Update-Cache 
	 */
	public function flushUpdatesCache() {
		
		$sCacheKey = $this->_getUpdatesCacheKey();
		
		WDCache::delete($sCacheKey);
		
	}
	
	public function getExtensions() {
		
		$sRequest = '/update.php?action=extensions';
		
		$sResponse = $this->requestUpdateServer($sRequest);
		
		$aResponse = json_decode($sResponse, true);
		
		return $aResponse['extensions'];
	}
	
	/**
	 * Holt alle aktuellen Updates
	 * @return array
	 */
	public function getUpdates($sExtension=null) {

		$sCacheKey = $this->_getUpdatesCacheKey();

		// deaktiviert da der cache beim ausführen nicht "reduziert" wird
		$aUpdate = WDCache::get($sCacheKey);

		if($aUpdate === null) {

			// Einstellungen
			$sConfigserver = $this->_sUpdateServer;

			$sContent = "";
			$sParameter = "action=check&version=".$this->sCurrentVersion."&key=".System::d('license')."&host=".\Util::getHost();

			if(!empty($sExtension)) {
				$sParameter .= "&extension=".$sExtension;
			}

			$errno = 0;
			$errstr = "";
			$fp = fsockopen('ssl://'.$sConfigserver, 443, $errno, $errstr, 2);
			if (!$fp) {
				echo "Der Updateserver konnte nicht erreicht werden: $errstr ($errno)<br />\n";
			} else {
				fputs ($fp, "POST /update.php?".$sParameter." HTTP/1.0\r\nUser-Agent: Fidelo Update Service\r\nHost: ".$sConfigserver."\r\n\r\n");
				while (!feof($fp)) {
					$sContent .= fgets($fp, 4096);
				}
				fclose($fp);
			}

			$sContent = strstr($sContent,"<?xml");

			$sContent = str_replace('<date>', '<date><date1>', $sContent);
			$sContent = str_replace('</date>', '</date1></date>', $sContent);

			$oXml = new Update_Xml($this->sCurrentVersion);

			$aUpdate = $oXml->parse($sContent);
			
			// 10min cachen
			WDCache::set($sCacheKey, 10*60, $aUpdate);

		}

		return $aUpdate;

	}

	public function getComposerBin() {
		
		$oLog = Log::getLogger('composer');
		
		$sPhpBin = System::d('php_executable', 'php');
		$sComposerBin = trim(self::executeShellCommand('which composer'));

		putenv('COMPOSER_HOME='.Util::getDocumentRoot().'vendor/');

		if(
			$sPhpBin === 'php' &&
			is_executable($sComposerBin)
		) {
			$sComposerBin = 'composer';
		} else {
			$sComposerBin = $sPhpBin.' composer.phar';

			if(!$this->checkComposerPhar()) {
				$oLog->error('COMPOSER SETUP FAILED');
				return false;
			}

			$sCmd = sprintf('cd %s; %s self-update', Util::getDocumentRoot(), $sComposerBin);
			$sOutput = self::executeShellCommand($sCmd);
			$oLog->info('COMPOSER SELF-UPDATE', [$sOutput]);
		}
		
		return $sComposerBin;
	}
	
	/**
	 * @return bool
	 */
	public function executeComposerUpdate() {
		
		$oLog = Log::getLogger('composer');

		try {

			$oOutput = new Symfony\Component\Console\Output\BufferedOutput();
			$oCommand = new Core\Command\Composer\Build();
            $oCommand->setLaravel(app());
			$iReturnCode = $oCommand->run(new Symfony\Component\Console\Input\ArrayInput([]), $oOutput);

			if($iReturnCode !== 0) {
				throw new \RuntimeException('Composer\Build return code !== 0 '.$iReturnCode);
			}

		} catch (\RuntimeException $e) {
			
			$oLog->error('COMPOSER FILE GENERATION FAILED', [$e->getMessage()]);

			// Gib die Exception nur aus, wenn ein Debugmodus an ist
			if(System::d('debugmode')) {
				__out($e);
			}

			return false;
		}

		$sComposerBin = $this->getComposerBin();

		$sCmd = sprintf('cd %s; %s update --no-dev --no-interaction --optimize-autoloader', Util::getDocumentRoot(), $sComposerBin);
		$sOutput = self::executeShellCommand($sCmd);
		$oLog->info('COMPOSER UPDATE', ['cmd' => $sCmd, 'output' => $sOutput]);

		// TODO Auf Exit-Code umstellen
		if(
			!strpos($sOutput, "Generating autoload files") &&
			!strpos($sOutput, "Generating optimized autoload files")
		) {
			$oLog->error('COMPOSER UPDATE FAILED');
			return false;
		}

		return true;

	}

	/**
	 * @return bool
	 */
	private function checkComposerPhar() {

		if(is_file(Util::getDocumentRoot().'composer.phar')) {
			return true;
		}

		$sHashExpected = file_get_contents('https://composer.github.io/installer.sig');
		copy('https://getcomposer.org/installer', Util::getDocumentRoot().'composer-setup.php');
		$sHashActual = hash_file('sha384', Util::getDocumentRoot().'composer-setup.php');

		if($sHashExpected !== $sHashActual) {
			return false;
		}

		$sPhpBin = System::d('php_executable', 'php');
		$sCmd = sprintf('cd %s; %s composer-setup.php', Util::getDocumentRoot(), $sPhpBin);
		$sOutput = self::executeShellCommand($sCmd);

		unlink(Util::getDocumentRoot().'composer-setup.php');

		return strpos($sOutput, 'successfully installed') !== false;

	}
	
	/**
	 * @param string $sCmd
	 * @return string
	 */
	static public function executeShellCommand($sCmd) {

		if(function_exists('shell_exec')) {
			$sOutputUpdate = shell_exec($sCmd.' 2>&1');
		} elseif(function_exists('exec')) {
			$aOutputUpdate = array();
			exec($sCmd.' 2>&1', $aOutputUpdate);
			$sOutputUpdate = implode("\n", $aOutputUpdate);
		} else {
			throw new RuntimeException('Unable to execute shell command "'.$sCmd.'". PHP function "shell_exec" or "exec" are not enabled.');
		}

		return $sOutputUpdate;
	}

	public function getModules() {
		
		$sRequest = '/modules.php?';
		
		$sResponse = $this->requestUpdateServer($sRequest);
		
		if(empty($sResponse)) {
			return [];
		}
		
		$oService = new Sabre\Xml\Service();
		$oService->elementMap = [
			'{}updates' => function(Sabre\Xml\Reader $reader) {
				
				$aUpdates = [];
				foreach ($reader->parseInnerTree() as $aUpdate) {
					$aUpdate['value']['key'] = $aUpdate['attributes']['modul'];
					$aUpdates[$aUpdate['attributes']['modul']] = $aUpdate['value'];
				}
							
				return $aUpdates;
			},
			'{}update' => function(Sabre\Xml\Reader $reader) {

				
				return Sabre\Xml\Deserializer\keyValue($reader, '');
			},
			'queries' => function(Sabre\Xml\Reader $reader) {
				return Sabre\Xml\Deserializer\repeatingElements($reader, '{}sql');
			},
			'files' => function(Sabre\Xml\Reader $reader) {
				return Sabre\Xml\Deserializer\repeatingElements($reader, '{}file');
			},
			'require' => function(Sabre\Xml\Reader $reader) {

				$aReturn =  [
					'configsql' => [],
					'extension' => []
				];
				
				$aElements = $reader->parseInnerTree();
				
				if(empty($aElements)) {
					return;
				}

				foreach($aElements as $aUpdate) {
					$aReturn[str_replace('{}', '', $aUpdate['name'])][] = $aUpdate['value'];
				}

				return $aReturn;
			},
		];
		$aModules = $oService->parse($sResponse);
		
		return $aModules;
	}
	
	public function getModule($sModule) {
		
		$aModules = $this->getModules();
		
		if(isset($aModules[$sModule])) {
			return $aModules[$sModule];
		}
		
	}

	public static function beginUpdate() {

		$oCurrentUser = \System::getCurrentUser();

		$aUsers = \Access_Backend::getActiveUser();

		if(count($aUsers) > 1) {

			foreach($aUsers as $aUser) {

				if($aUser['userid'] != $oCurrentUser->id) {

					// Nötige Info in den Cache setzen bevor der Nutzer ausgeloggt wird, sodass er die Meldung sehen kann.
					\Access_Backend::setLogoutInfo($aUser['name'], 'update', ['current_user' => $oCurrentUser->id]);

					\WDCache::delete(Access_Backend::getCacheKey($aUser['name']), true);

				}

			}

			$aUsers = [
				$oCurrentUser->username => time()
			];
			\WDCache::set('access_backend_users', 60*60, $aUsers);

		}

		// Benutzer in system_config speichern, der das Update durchführt, um nicht mitgesperrt zu werden.
		\System::s('system_update_locked_by', $oCurrentUser->id);

	}

	public static function endUpdate() {
		\System::deleteConfig('system_update_locked_by');
	}
	
}
