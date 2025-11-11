<?php

/**
 * v3
 */
class Ext_Internal_Update {

	protected $_oDb;
	protected $_rFtp;
	protected $_rSSH;
	protected $_sFtpPath;
	protected $_aErrors = array();

	protected static $_oInstance;

	protected $aDescriptionUpdate = array();
	
	protected $sRepo;
	
	protected $sExtension = null;

	/**
	 * @var \Log
	 */
	protected $log;

	/**
	 * @var Access_Backend
	 */
	protected $oAccess;
	
	/**
	 *
	 * @return Ext_Internal_Update
	 */
	public static function getInstance() {

		if(!self::$_oInstance instanceof self) {
			self::$_oInstance = new self();
		}

		return self::$_oInstance;

	}

	public function setRepo($sRepo) {
		$this->sRepo = $sRepo;
	}
	
	public static function getFtp() {

		include \Util::getDocumentRoot().'config/internal.php';

		$aReturn = array();

		$aReturn['path'] = $aUpdateFtp['path'];

		$aReturn['connection'] = ssh2_connect($aUpdateFtp['host'], 22);
		$bLogin = ssh2_auth_password($aReturn['connection'], $aUpdateFtp['user'], $aUpdateFtp['pass']);

		if($bLogin) {
			$aReturn['resource'] = ssh2_sftp($aReturn['connection']);
			return $aReturn;
		}

	}
	
	/**
	 *
	 * @return DB 
	 */
	public static function getUpdateDb() {
 
		include \Util::getDocumentRoot().'config/internal.php';

		$oDb = DB::createConnection($aUpdateDb['name'], $aUpdateDb['host'], $aUpdateDb['user'], $aUpdateDb['pass'], $aUpdateDb['name']);

		return $oDb;

	}

	public function __construct() {

		$this->_oDb = self::getUpdateDb();

		$aFtp = self::getFtp();
		$this->_sFtpPath = $aFtp['path'];
		$this->_rFtp = $aFtp['resource'];
		$this->_rSSH = $aFtp['connection'];

		$this->oAccess = Access::getInstance();
		
		$this->log = \Log::getLogger('internal', 'update');
		
	}

	protected function getUser() {

		if(!$this->oAccess instanceof Access_Backend) {
			return;
		}

		return $this->oAccess->getAccessUser();
		
	}


	/**
	 *
	 * @return DB
	 */
	public function getDb() {
		return $this->_oDb;
	}

	public function checkUpdate($iUpdateId) {
		$aData = array();
		$aData['checked'] = 1;
		$this->_oDb->update('updates_versions', $aData, "`id` = ".(int)$iUpdateId);
		$this->log('Update checked', $aData);
	}

	public function getUpdates($sRelease='test', $bSkipChecked=false, $sExtension=null) {
		
		$sPreLiveRelease = "prelive";
		$sLiveRelease = "live";
		
		if(
			$sRelease == "test_agency" ||
			$sRelease == "prelive_agency" ||
			$sRelease == "live_agency"
		){
			$sPreLiveRelease = "prelive_agency";
			$sLiveRelease = "live_agency";
		}
		
		if(
			$sRelease == "test_school_legacy" ||
			$sRelease == "prelive_school_legacy" ||
			$sRelease == "live_school_legacy"
		){
			$sPreLiveRelease = "prelive_school_legacy";
			$sLiveRelease = "live_school_legacy";
		}
		
		$sWhere = "";

		if($bSkipChecked) {
			$sWhere .= " AND `checked` = 0 ";
		}

		$sSql = "
				SELECT
					*,
					(
						SELECT
							`version`
						FROM
							`updates_versions` `uv1`
						WHERE
							`uv1`.`id` <= `uv`.`id` AND
							`uv1`.`release` = :prelive
						ORDER BY
							`uv1`.`id` DESC
						LIMIT 1
					) `prelive_version`,
					(
						SELECT
							`version`
						FROM
							`updates_versions` `uv2`
						WHERE
							`uv2`.`id` <= `uv`.`id` AND
							`uv2`.`release` = :live
						ORDER BY
							`uv2`.`id` DESC
						LIMIT 1
					) `live_version`
				FROM
					`updates_versions` `uv`
				WHERE
					`release` = :release AND
					`active` = 1
					".$sWhere."
				ORDER BY
					comparable_version(`version`) DESC
				";

		$aSql = array('release'=>$sRelease, 'prelive' => $sPreLiveRelease, 'live' => $sLiveRelease);
		$aUpdates = $this->_oDb->queryRows($sSql, $aSql);
		
		return $aUpdates;
	}

	public function getUpdate($iUpdateId) {

		$sSql = "
				SELECT
					*
				FROM
					`updates_versions`
				WHERE
					`id` = :id
				";
		$aSql = array('id'=>(int)$iUpdateId);
		$aUpdate = $this->_oDb->queryRow($sSql, $aSql);

		return $aUpdate;
	}

	/**
	 * @param string $sRelease
	 * @param string $sExtension
	 * @return array
	 */
	public function getCurrentUpdate($sRelease, $sExtension=null) {

		$sSql = "
				SELECT
					*
				FROM
					`updates_versions`
				WHERE
					`active` = 0 AND
					`release` = :release
				";
		
		$aSql = array('release'=>$sRelease);
		
		if($sExtension !== null) {
			$sSql .= " AND `extension` = :extension";
			$aSql['extension'] = $sExtension;
		} else {
			$sSql .= " AND `extension` IS NULL";
		}
		
		$aUpdate = $this->_oDb->queryRow($sSql, $aSql);

		if(empty($aUpdate)) {
			$aUpdate = $this->addVersion($sRelease, '', $sExtension);
		}

		return $aUpdate;
	}

	public function addVersion($sRelease, $sDescription='', $sExtension=null) {

		$sSql = "
			SELECT
				*
			FROM
				`updates_versions`
			WHERE
				`release` = :release
		";
		$aSql = array('release'=>$sRelease);
		
		if($sExtension !== null) {
			$sSql .= " AND `extension` = :extension";
			$aSql['extension'] = $sExtension;
		} else {
			$sSql .= " AND `extension` IS NULL";
		}

		$sSql .= "
			ORDER BY
				`id` DESC";
		
		$aUpdate = $this->_oDb->queryRow($sSql, $aSql);

		if(
			!empty($aUpdate) &&
			$aUpdate['active'] == 0
		) {

			if($sDescription != '') {
				$aData = array();
				if(!empty($aUpdate['description'])) {
					$aData['description'] = $aUpdate['description'].", ";
				}
				$aData['description'] .= $sDescription;
				$this->_oDb->update('updates_versions', $aData, "`id` = ".(int)$aUpdate['id']);
			}

			return $aUpdate;
		}

		if(empty($aUpdate)) {
			$aUpdate = array();
			$aUpdate['version'] = 1.000;
		}

		$aData = array();
		$aData['active'] = 0;
		$aData['description'] = $sDescription;
		$aData['version'] = null;#$aUpdate['version'] + 0.001;
		$aData['release'] = $sRelease;
		$aData['extension'] = $sExtension;
		$this->_oDb->insert('updates_versions', $aData);

		$aUpdate = $this->getCurrentUpdate($sRelease, $sExtension);

		return $aUpdate;
	}

	public function copyUpdateFile($sSource, $sTarget) {

		$sFtpPath = $this->_sFtpPath;
		$sSource = $this->_sFtpPath.$sSource;
		$sTarget = $this->_sFtpPath.$sTarget;

		$aFile = pathinfo($sTarget);
		$sDir = $aFile['dirname'];

		// Verzeichnisse anlegen
		$aDir = explode("/", $sDir);
		$sPath = '';
		array_shift($aDir);

		foreach((array)$aDir as $sPart) {
			$sPath .= '/'.$sPart;
			ssh2_sftp_mkdir($this->_rFtp, $sPath, 0777);
			ssh2_sftp_chmod($this->_rFtp, $sPath, 0777);
		}

		$sLocal = tempnam(\Util::getDocumentRoot().'media/temp', 'THEBING');

		$bFile = false;

		/*
		 * https://bugs.php.net/bug.php?id=73597
		 * $sFileContent = file_get_contents("ssh2.sftp://".$this->_rFtp.$sSource);
		 */
		$sFileContent = file_get_contents("ssh2.sftp://".intval($this->_rFtp).$sSource);
		if($sFileContent !== false) {
			$bPut = file_put_contents($sLocal, $sFileContent);
			
			if(
				$bPut !== false &&
				is_file($sLocal)
			) {
				$bFile = $this->sendFile($sLocal, $sTarget);
			}
		}

		unlink($sLocal);

		return $bFile;

	}

	public function checkChecklist($sRelease = "test") {
        return true;
        
		$sSql = "
				SELECT
					*
				FROM
					`updates_versions` `uv`
				WHERE
					`release` = :release AND
					`active` = 1 AND
					`checked` = 0
				LIMIT 1
				";
		$aSql = array('release' => $sRelease);
		$aUpdate = $this->_oDb->queryRow($sSql, $aSql);

		if(empty($aUpdate)) {
			return true;
		}

		return false;

	}

	public function getUpdatePath($iUpdate) {

		$aUpdate = $this->getUpdate($iUpdate);

		$sUpdatePath = $aUpdate['release'].'/';
		
		if(!empty($aUpdate['extension'])) {
			$sUpdatePath .= $aUpdate['extension'].'/';
		}

		$sUpdatePath .= $aUpdate['id'];

		return $sUpdatePath;
	}
	
	public function checkFiles($iUpdate) {

		$aFiles = $this->_oDb->queryRows("SELECT * FROM `updates_files` WHERE `version_id` = ".(int)$iUpdate." ORDER BY `file`");

		$sPath = $this->_sFtpPath.'/'.$this->getUpdatePath($iUpdate);

		$aErrors = array();
		foreach((array)$aFiles as $aFile) {

			$aFileStat = ssh2_sftp_stat($this->_rFtp, $sPath.$aFile['file']);

			if($aFileStat === false) {
				$aErrors[] = $aFile['file'];
			}

		}

		return $aErrors;

	}

	/**
	 * @param int $iSourceVersionId
	 * @param int $iTargetVersionId
	 * @return bool
	 */
	public function copyItems($iSourceVersionId, $iTargetVersionId) {

		$bSuccess = true;

		$aFiles = $this->_oDb->queryRows("SELECT * FROM `updates_files` WHERE `version_id` = ".(int)$iSourceVersionId." ORDER BY `file`");
		$aQueries = $this->_oDb->queryRows("SELECT * FROM `updates_sql` WHERE `version_id` = ".(int)$iSourceVersionId." ORDER BY `id`");
		$aChecks = $this->_oDb->queryRows("SELECT * FROM `updates_checks` WHERE `version_id` = ".(int)$iSourceVersionId." ORDER BY `id`");
		$aRequirements = $this->_oDb->queryRows("SELECT * FROM `updates_requirements` WHERE `version_id` = ".(int)$iSourceVersionId." ORDER BY `id`");

		foreach((array)$aFiles as $aFile) {

			$aData = array();
			$aData['version_id'] = (int)$iTargetVersionId;
			$aData['user'] = $this->getUser();
			$aData['file'] = $aFile['file'];

			$sSourceFile = '/'.$this->getUpdatePath($iSourceVersionId).$aFile['file'];
			$sTargetFile = '/'.$this->getUpdatePath($iTargetVersionId).$aFile['file'];

			try {
				$this->_oDb->insert('updates_files', $aData);
			} catch(DB_QueryFailedException $e) {
				$this->_aErrors[] = 'Query error for File '.$sSourceFile.' detected!';
			}

			// Dateien auch kopieren
			$bCopy = $this->copyUpdateFile($sSourceFile, $sTargetFile);

			if(!$bCopy) {
				$this->_aErrors[] = 'Copy error by File '.$sSourceFile.' detected!';
				$bSuccess = false;	
			}

		}

		foreach((array)$aQueries as $aQuery) {

			$aData = array();
			$aData['version_id'] = (int)$iTargetVersionId;
			$aData['user'] = $this->getUser();
			$aData['query'] = $aQuery['query'];
			try {
				$this->_oDb->insert('updates_sql', $aData);
			} catch(DB_QueryFailedException $e) {

			}

		}

		foreach((array)$aChecks as $aCheck) {

			$aData = array();
			$aData['version_id'] = (int)$iTargetVersionId;
			$aData['user'] = $this->getUser();
			$aData['check'] = $aCheck['check'];
			try {
				$this->_oDb->insert('updates_checks', $aData);
			} catch(DB_QueryFailedException $e) {

			}

		}

		foreach((array)$aRequirements as $aRequirement) {

			$aData = array();
			$aData['version_id'] = (int)$iTargetVersionId;
			$aData['user'] = $this->getUser();
			$aData['requirement'] = $aRequirement['requirement'];
			try {
				$this->_oDb->insert('updates_requirements', $aData);
			} catch(DB_QueryFailedException $e) {

			}

		}

		return $bSuccess;
	}

	public function log($sAction, $mLog=array()) {

		$aData = array();
		$aData['user'] = $this->getUser();
		$aData['action'] = $sAction;
		$aData['data'] = json_encode($mLog);
		$this->_oDb->insert('updates_log', $aData);

	}

	public function getErrors(){
		return $this->_aErrors;
	}

	public function putFile($aUpdate, $sFile) {
		
		$aFile = pathinfo($sFile);
		$sDir = $aFile['dirname'];

		$sFtpPath = $this->_sFtpPath.'/'.$this->getUpdatePath($aUpdate['id']);

		// Verzeichnisse anlegen
		$aDir = explode("/", $sFtpPath.$sDir);

		$sPath = '';
		foreach((array)$aDir as $sPart) {
			$sPath .= $sPart.'/';
			ssh2_sftp_mkdir($this->_rFtp, $sPath, 0777);
			ssh2_sftp_chmod($this->_rFtp, $sPath, 0777);
		}

		$sLocalFile = Util::getDocumentRoot(false).$sFile;
		$sTargetFile = $sPath.$aFile['basename'];

		// Encoding prüfen bei .html, .php und .mod
		if(
			$aFile['extension'] == 'php' ||
			$aFile['extension'] == 'html' ||
			$aFile['extension'] == 'mod'
		) {
			$sFileContent = file_get_contents($sLocalFile);
			$bUtf8 = mb_detect_encoding($sFileContent, 'UTF-8', true);
			if(!$bUtf8){
				$this->_aErrors[] = 'Encoding error in File '.$sFile.' detected!';
				#return false;
			}
		}

		$bFile = $this->sendFile($sLocalFile, $sTargetFile);

		if($bFile) {

			// Exception kann vorkommen, wenn Datei schon im Update ist -> ist Ok
			try {
				$aData = array();
				$aData['version_id'] = $aUpdate['id'];
				$aData['file'] = $sFile;
				$aData['user'] = $this->getUser();
				$this->_oDb->insert('updates_files', $aData);				
			} catch(Exception $e) {
			} catch(DB_QueryFailedException $e) {
			}
		
			// SVN-Description holen
			$sMessage = $this->getCommitFileMessage($sFile);
			if(!empty($sMessage)) {
				$this->aDescriptionUpdate[md5($sMessage)] = $sMessage;
			}

			return true;
		} else {
			$this->_aErrors[] = 'Upload error by File '.$sFile.' detected!';
			return false;
		}

	}

	public function sendFile($sLocalFile, $sTargetFile) {
	
		/*
		 * https://bugs.php.net/bug.php?id=73597
		 * $sftpStream = fopen('ssh2.sftp://'.$this->_rFtp.$sTargetFile, 'w');
		 */
		$sftpStream = fopen('ssh2.sftp://'.intval($this->_rFtp).$sTargetFile, 'w');

		try {

			if (!$sftpStream) {
				throw new Exception("Could not open remote file: ".$sTargetFile);
			}
			
			$data_to_send = file_get_contents($sLocalFile);
			
			if ($data_to_send === false) {
				throw new Exception("Could not open local file: ".$sLocalFile.".");
			}
			
			if (@fwrite($sftpStream, $data_to_send) === false) {
				throw new Exception("Could not send data from file: ".$sLocalFile.".");
			}
			
			fclose($sftpStream);
							
			ssh2_sftp_chmod($this->_rFtp, $sTargetFile, 0777);
			
			return true;
			
		} catch (Exception $e) {
			error_log('Exception: ' . $e->getMessage());
			fclose($sftpStream);
		}

		return false;
	}
	
	public static function transferUpdate($mixInput, $sRelease, $sExtension=null) {
		
		$oUpdate = self::getInstance();
		$oDb = $oUpdate->getDb();
		$aUpdate = $oUpdate->getCurrentUpdate($sRelease, $sExtension);

		if(!empty($mixInput['MESSAGE'])) {
			$aData = array();
			if(!empty($aUpdate['description'])) {
				$aData['description'] = $aUpdate['description'].",\n".$mixInput['MESSAGE'];
			} else {
				$aData['description'] = $mixInput['MESSAGE'];
			}
			$oDb->update('updates_versions', $aData, "`id` = ".(int)$aUpdate['id']);
		}

		foreach((array)$mixInput['QUERIES'] as $sSql) {
			$sSql = trim($sSql);
			if(!preg_match("/^(PROCESS|SHUTDOWN|DROP DATABASE)/i", $sSql) && preg_match("/(SELECT|INSERT|CREATE|UPDATE|ALTER|RENAME|DROP|TRUNCATE)/i", $sSql)) {
				$arrQueueQueries[] = $sSql;

				if(!empty($sSql)) {
					$aData = array();
					$aData['version_id'] = $aUpdate['id'];
					$aData['query'] = $sSql;
					$aData['user'] = $oUpdate->getUser();
					$bSql = $oDb->insert('updates_sql', $aData);
				}

			}
		}

		// Dateien updaten
		foreach((array)$mixInput['FILES'] as $strFile) {
			// Keine internen Dateien ins nächste Update durchreichen
			if(
				strpos($strFile, "/ti/") === false &&
				strpos($strFile, "/Internal/") === false
			) {
				$oUpdate->putFile($aUpdate, $strFile);
			}
		}

		foreach((array)$mixInput['CHECKS'] as $sClass) {
			$aData = array();
			$aData['version_id'] = $aUpdate['id'];
			$aData['check'] = $sClass;
			$aData['user'] = $oUpdate->getUser();
			try {
				$bSql = $oDb->insert('updates_checks', $aData);
			} catch(DB_QueryFailedException $e) {

			}
		}

		foreach((array)$mixInput['REQUIREMENTS'] as $sRequirement) {
			$aData = array();
			$aData['version_id'] = $aUpdate['id'];
			$aData['requirement'] = $sRequirement;
			$aData['user'] = $oUpdate->getUser();
			try {
				$bSql = $oDb->insert('updates_requirements', $aData);
			} catch(DB_QueryFailedException $e) {

			}
		}
		
	}

	public function saveUpdateQuery($sQuery, $sRelease, $sExtension=null) {

		if(empty($sQuery)) {
			return;
		}

		// Kennung der lokalen Umgebung holen
		$sLocalIdentifier = System::d('ext_localdev_sql_local_identifier');
		if(empty($sLocalIdentifier)) {
			$sLocalIdentifier = 'dev.box';
		}

		// Wenn ONLINE-Server
		if(strpos($_SERVER['HTTP_HOST'], $sLocalIdentifier) === false) {
			
			$oDb = $this->getDb();

			$aUpdate = $this->getCurrentUpdate($sRelease, $sExtension);

			try {

				$aData = array();
				$aData['version_id'] = $aUpdate['id'];
				$aData['query'] = $sQuery;
				$aData['user'] = $this->getUser();
				$bSql = $oDb->insert('updates_sql', $aData);

			} catch(Exception $e) {

				__out($e->getMessage());

			}

		}
			
	}
	
	/**
	 * Gibt ein Array mit allen Usern aus den Commits des Repos zurück
	 * 
	 * @param string $sRepo
	 * @return array
	 */
	public function getCommitUsers($sRepo) {
		
		$sSql = "
			SELECT
				`user` `key`,
				`user` `value`
			FROM
				`updates_commits`
			WHERE
				`repo` = :repo
			GROUP BY
				`user`
			ORDER BY
				`user` ASC
			";
		$aSql = array(
			'repo' => $sRepo
		);
		
		$aUsers = $this->_oDb->queryPairs($sSql, $aSql);

		return $aUsers;
	}

	/**
	 * Gibt ein Array mit Dateien eines Commits des Repos zurück
	 * 
	 * @param string $sRepo
	 * @param string $sUser
	 * @return array
	 */
	public function getCommitFiles($sRepo, $sUser=null) {
		
		$aSql = array(
			'repo' => $sRepo
		);
		
		$sWhere = "";
		
		if(!empty($sUser)) {
			$sWhere = " AND `user` = :user ";
			$aSql['user'] = $sUser;
		}
		
		$sSql = "
			SELECT
				*,
				UNIX_TIMESTAMP(`created`) `created`
			FROM
				`updates_commits`
			WHERE
				`repo` = :repo
				".$sWhere."
			ORDER BY
				`created` DESC,
				`file` ASC
			";

		$aFiles = $this->_oDb->queryRows($sSql, $aSql);

		return $aFiles;

	}

	public function getCommitFileMessage($sFile) {
		
		$aSql = array(
			'repo' => $this->sRepo,
			'file' => $sFile
		);
		
		$sSql = "
			SELECT
				`comment`
			FROM
				`updates_commits`
			WHERE
				`repo` = :repo AND
				`file` = :file
			ORDER BY
				`created` DESC
			";

		$sMessage = $this->_oDb->queryOne($sSql, $aSql);

		return $sMessage;
	}
	
	/**
	 * Löscht eine Dateie eines Commits des Repos
	 * 
	 * @param string $sRepo
	 * @param string $sFile
	 */
	public function deleteCommitFile($sRepo, $sFile) {
		
		$aSql = array(
			'repo' => $sRepo,
			'file' => $sFile
		);
		
		$sSql = "
			DELETE FROM
				`updates_commits`
			WHERE
				`repo` = :repo AND
				`file` = :file
			";

		$this->_oDb->preparedQuery($sSql, $aSql);
		
	}

	public function resetDescriptionUpdate() {
		$this->aDescriptionUpdate = array();
	}
	
	public function cleanDescription($sDescription) {
		
		$aDescriptions = explode("\n", $sDescription);

		foreach($aDescriptions as $iKey=>&$sDescription) {
			$sDescription = trim($sDescription, ", \t\n\r\0\x0B");
			if(empty($sDescription)) {
				unset($aDescriptions[$iKey]);
			}
		}
		unset($sDescription);

		$aDescriptions = array_unique($aDescriptions);

		$sDescription = trim(implode(",\n", $aDescriptions));

		return $sDescription;
	}

	public function saveDescriptionUpdate($aUpdate) {

		if(!empty($this->aDescriptionUpdate)) {

			$aData = array();
			if(empty($aUpdate['description'])) {
				$aData['description'] = implode(",\n", $this->aDescriptionUpdate);
			} else {
				$aData['description'] = trim($aUpdate['description']).",\n".implode(",\n", $this->aDescriptionUpdate);
			}
			
			$aData['description'] = $this->cleanDescription($aData['description']);

			$this->_oDb->update('updates_versions', $aData, "`id` = ".(int)$aUpdate['id']);
	
		}

		$this->aDescriptionUpdate = array();
	}

	protected function checkFileBlacklist($sFile) {

		// Hier können Regex-Patterns definiert werden, damit bestimmte Dateien nicht ins Update kommen
		$aBlacklist = array(
			'/^\/test\//', // Test-Verzeichnis im Root
			'/^\/phpunit\//', // Test-Verzeichnis im Root
			'/^\/update_queries\//', // Test-Verzeichnis im Root
			//'/\/_/', // Dateien mit Unterstrich am Anfang (entfernt wegen SCSS Partials)
			'/\/templates_c\//', // Smarty-Templates
			'/\/tmp\//', // Temp-Dir
			'/\/temp\//', // Temp-Dir
			'/\/system\/extensions\/test\//', // Test-Verzeichnis
			'/\/admin\/extensions\/test\//', // Test-Verzeichnis
		);
		
		// Blacklist prüfen
		foreach($aBlacklist as $sBlacklistPattern) {
			$iBlacklist = preg_match($sBlacklistPattern, $sFile);
			if($iBlacklist > 0) {
				return false;
			}
		}

		return true;
	}
	
	public function saveBitbucketApiCommitFiles($aInfo) {

		$sRepository = $aInfo['repository']['slug'];
		
		foreach((array)$aInfo['commits'] as $aCommit) {

			$sBranch = $aCommit['branch'];

			// Nur Dateien aus dem master-Branch in die Liste packen (analog zum Hook-Controller)
			if($sBranch !== 'master') {
				continue;
			}

			foreach($aCommit['files'] as $aFile) {

				$sFile = $aFile['file'];
				
				// Slash ergänzen für die Einheitlichkeit mit dem bestehenden Select
				$sFile = '/'.$sFile;

				// Blacklist prüfen
				if(!$this->checkFileBlacklist($sFile)) {
					continue;
				}

				// Ist die Datei schon in der Liste?
				$sSql = "
					SELECT
						*
					FROM
						`updates_commits`
					WHERE
						`repo` = :repo AND
						`file` = :file
					";
				$aSql = array(
					'repo' => (string)$sRepository,
					'file' => (string)$sFile
				);
				$aCheck = $this->_oDb->queryRow($sSql, $aSql);

				// Wenn schon da, Daten aktualisieren
				if(!empty($aCheck)) {

					// @TODO Entweder korrigieren (auf mehrere parents prüfen) oder rausnehmen, da das nicht funktioniert
					if(!preg_match('/merge.*branch/i', $aCommit['message'])) {				

						$sSql = "
							UPDATE
								`updates_commits`
							SET
								`user` = :user,
								`comment` = :comment,
								`created` = NOW()
							WHERE
								`repo` = :repo AND
								`file` = :file
							";
						$aSql = array(
							'repo' => (string)$sRepository,
							'file' => (string)$sFile,
							'user' => (string)$aCommit['author'],
							'comment' => (string)$aCommit['message'],
						);
						$this->_oDb->preparedQuery($sSql, $aSql);
						
					}

				// Datei eintragen
				} else {

					$aData = array(
						'repo' => (string)$sRepository,
						'file' => (string)$sFile,
						'user' => (string)$aCommit['author'],
						'comment' => (string)$aCommit['message'],
					);

					$this->_oDb->insert('updates_commits', $aData);

				}

			}

		}

	}

	/**
	 * Für Modul Git-Deployment
	 *
	 * @param string $sRepository
	 * @param array $aFiles
	 */
	public function saveCommittedFiles($aFiles) {

		$this->log->info('Files', (array)$aFiles);
		
		include \Util::getDocumentRoot().'config/internal.php';
		
		foreach($aFiles as $aFile) {

			// Gelöschte Dateien ignorieren
			if($aFile['state'] === 'D') {
				$this->log->info('Deleted', (array)$aFile);
				continue;
			}

			// Slash ergänzen für die Einheitlichkeit mit der bestehenden Struktur
			$sFile = '/'.$aFile['path'];

			// Blacklist überprüfen
			if(!$this->checkFileBlacklist($sFile)) {
				continue;
			}

			// Datei in die Liste einfügen / aktualisieren
			$sSql = "
				REPLACE INTO
					`updates_commits`
				SET
					`repo` = :repository,
					`file` = :file,
					`user` = :user,
					`comment` = :comment,
					`created` = NOW()
			";

			$insert = array(
				'repository' => $sRepo,
				'file' => $sFile,
				'user' => $aFile['author'],
				'comment' => $aFile['message']
			);
			
			$this->_oDb->preparedQuery($sSql, $insert);

			$this->log->info('Insert', $insert);
			
		}

	}

	/**
	 * Git-Autor für Update-Liste ändern (für ein wenig Freude im Deployment)
	 *
	 * @param array $aCommit
	 */
	public static function reformatCommitAuthor(&$aCommit) {

		$aMapping = [
			'koopmann' => 'Dr. Merk',
			'dennis' => 'Den',
			'thomas' => 'Mr. Fantastic',
		];

		foreach($aMapping as $sMatch => $sNewName) {
			if(mb_stripos($aCommit['author'], $sMatch) !== false) {
				$aCommit['author'] = $sNewName;
				break;
			}
		}

	}

	/**
	 * Gibt ein Array mit allen Dateien, die für die Prüfung der Systemvoraussetzungen
	 * zur Verfügung stehen
	 * @return array 
	 */
	public static function getRequirementClasses() {
		
		// Pfad der Dateien
		$sPath = \Util::getDocumentRoot().'system/updates/requirements/';
		$aFiles = glob($sPath . '*');
		
		// Dateien durchlaufen und präperieren
		$aReturn = array();
		foreach($aFiles as $sFile) {
			
			// Klassennamen definieren
			$aFileData = array();
			// Dateiinhalt holen
			$sContent = file_get_contents($sFile);
			// Klassennamen suchen
			preg_match_all('/class (Updates_Requirements_.*?) /', $sContent, $aFileData);

			$sClass = trim($aFileData[1][0]);			
			
			if(!empty($sClass)) {
				$aReturn[$sClass] = $sClass;
			}
		}
		
		return $aReturn;
	}
	
	public function getNextVersionNumber($update, $setVersionNumber) {
			
		$sSql = "
			SELECT
				*
			FROM
				`updates_versions`
			WHERE
				`release` = :release
		";
		$aSql = array('release'=>$update['release']);
		
		if($update['extension'] !== null) {
			$sSql .= " AND `extension` = :extension";
			$aSql['extension'] = $update['extension'];
		} else {
			$sSql .= " AND `extension` IS NULL";
		}

		$sSql .= " AND `version` IS NOT NULL
			ORDER BY
				comparable_version(`version`) DESC";
		
		$latestActiveUpdate = $this->_oDb->queryRow($sSql, $aSql);

		if(empty($latestActiveUpdate)) {
			$latestActiveUpdate = array();
			$latestActiveUpdate['version'] = 1.000;
		}

		list($major, $minor, $patch) = explode('.', $latestActiveUpdate['version'], 3);

		switch($setVersionNumber) {
			case 'major':
				$major++;
				$minor = 0;
				$patch = 0;
				break;
			case 'minor':
				$minor++;
				$patch = 0;
				break;
			case 'patch':
				$patch++;
				break;
		}

		$version = intval($major).'.'.intval($minor).'.'.intval($patch);

		return $version;
	}
	
}
