<?

class Ext_Sync_Util {
	
	protected $_aSyncOptions = array();
	protected $_rConnection;
	protected $_rFTP;
	
	public function __construct() {
		$this->_aSyncOptions = self::getSyncOptions();
	}
	
	public function connect() {
		
		if($this->_aSyncOptions['use_sftp']) {
			
			if($this->_aSyncOptions['use_sftp_pubkey_auth']) {

				$this->_rConnection = ssh2_connect($this->_aSyncOptions['live_ftp_hostname'], (int)$this->_aSyncOptions['ssh_port'], array('hostkey'=>$this->_aSyncOptions['ssh_hostkey']));
				if($this->_rConnection == false) {
					return false;
				}

				if(empty($this->_aSyncOptions['live_ftp_password'])) {
					$bLogin = ssh2_auth_pubkey_file($this->_rConnection, $this->_aSyncOptions['live_ftp_username'], $this->_aSyncOptions['sftp_pubkey_path'], $this->_aSyncOptions['sftp_privkey_path']);
				} else {
					$bLogin = ssh2_auth_pubkey_file($this->_rConnection, $this->_aSyncOptions['live_ftp_username'], $this->_aSyncOptions['sftp_pubkey_path'], $this->_aSyncOptions['sftp_privkey_path'], $this->_aSyncOptions['live_ftp_password']);
				}

			} else {

				$this->_rConnection = ssh2_connect($this->_aSyncOptions['live_ftp_hostname'], $this->_aSyncOptions['ssh_port']);
				if($this->_rConnection == false) {
					return false;
				}

				$bLogin = ssh2_auth_password($this->_rConnection, $this->_aSyncOptions['live_ftp_username'], $this->_aSyncOptions['live_ftp_password']); 

			}

			if($bLogin) {
				$this->_rFTP = ssh2_sftp($this->_rConnection);
			}

		} else {

			$this->_rFTP = ftp_connect($this->_aSyncOptions['live_ftp_hostname']);
			if($this->_rFTP == false) {
				return false;
			}
			
			$bLogin = ftp_login($this->_rFTP, $this->_aSyncOptions['live_ftp_username'], $this->_aSyncOptions['live_ftp_password']);

		}

		if($bLogin === false) {
			return false;
		} else {
			return true;
		}

	}

	public function checkDir($sPath) {
		global $system_data;
				
		if($this->_aSyncOptions['use_sftp']) {
			
			$path = substr($sPath, 0, strrpos($sPath, '/'));
			$path = $this->_aSyncOptions['live_ftp_dirtocms'].$path;
			
			$bSuccess = ssh2_sftp_mkdir($this->_rFTP, $path, $system_data['chmod_mode_dir'], true);

		} else {
		
			$aPath = explode('/', $sPath);

			// change into cms root directory
			ftp_chdir($this->_rFTP, $this->_aSyncOptions['live_ftp_dirtocms']);

			// write paths
			$iCountPath = count($aPath);
			$i = 0;
			$path = '';
			while($i != $iCountPath-1) {
				$path .= $aPath[$i].'/';
				ftp_mkdir($this->_rFTP, $path);
				ftp_chmod($this->_rFTP, $system_data['chmod_mode_dir'], $path);
				$i++;
			}

		}

	}

	public function sendFile($sLocalFile, $sTargetFile) {
		global $system_data;
		
		if($this->_aSyncOptions['use_sftp']) {

			/*
			$bSend = ssh2_scp_send($this->_rConnection, $sLocalFile, $sTargetFile, $system_data['chmod_mode_file']);

			if(!$bSend) {
				__pout($sLocalFile);
				__pout($sTargetFile);				
			}
			*/
			
			$bSend = false;
			$rFtpStream = fopen('ssh2.sftp://'.$this->_rFTP.$sTargetFile, 'w');

		    if($rFtpStream) {

			    $sData = file_get_contents($sLocalFile);

			    if ($sData !== false) {
				    $bWrite = fwrite($rFtpStream, $sData);
			    	if ($bWrite !== false) {
				    	$bSend = true;
				    }
			    }

			    fclose($rFtpStream);

			}

			if(!$bSend) {
				__pout($sLocalFile);
				__pout($sTargetFile);				
			}

		} else {
		
			// open file for handle
			$rLocalFile = fopen($sLocalFile, 'r');

			// write file into other system
			$bSend = ftp_fput($this->_rFTP, $sTargetFile, $rLocalFile, FTP_BINARY);
			if($bSend) {
				ftp_chmod($this->_rFTP, $system_data['chmod_mode_file'], $sTargetFile);
			}

			// close local file
			fclose($rLocalFile);

		}

		return $bSend;

	}

	// get last Sync Date
	public static function getLastSyncDate()
	{

		$sSql = "
			SELECT
				UNIX_TIMESTAMP(`created`)
			FROM
				`sync_logs`
			WHERE
				`active` = 1
			ORDER BY
				`id`
			DESC
			LIMIT 1
		";
		$iLastLog = DB::getQueryOne($sSql);
		
		$aSyncOptions = self::getSyncOptions();
		
		if($iLastLog == false || ($iLastLog != false && $aSyncOptions['first_sync_date'] > $iLastLog))
		{
			return $aSyncOptions['first_sync_date'];
		}
		else if
		($iLastLog != false && $aSyncOptions != false && $iLastLog >= $aSyncOptions['first_sync_date'])
		{
			return $iLastLog;
		}
		else
		{
			return 0;
		}
	}
	
	
	
	// get db entrys / options of sync
	public static function getSyncOptions()
	{

		$sSql = "
			SELECT
				*,
				UNIX_TIMESTAMP(`first_sync_date`) AS `first_sync_date`
			FROM
				`sync_options`
			WHERE
				`active` = 1
			LIMIT 1
		";
		$aSyncOptions = DB::getQueryRow($sSql);
		if(empty($aSyncOptions))
		{
			return false;
		}
		return $aSyncOptions;
	}
	
	
	
	// TODO->Description
	public static function sendFileFtp($sContent, $sName) {
		$hStream = ftp_connect($this->_aReseller['cdr_ftp_host']);
		if(!$hStream) {
			return false;
		}
		$bLogin = ftp_login($hStream, $this->_aReseller['cdr_ftp_user'], $this->_aReseller['cdr_ftp_pass']); 
		if(!$bLogin) {
			return false;
		}
		ftp_pasv($hStream, false);
		if(substr($this->_aReseller['cdr_ftp_dir'], -1) != "/") {
			$this->_aReseller['cdr_ftp_dir'] .= "/";
		}
		$hFile = tmpfile();
		fwrite($hFile, $sContent);
		rewind($hFile);
		ftp_fput($hStream, $this->_aReseller['cdr_ftp_dir'].$sName, $hFile, FTP_ASCII);
		fclose($hFile);
		ftp_close($hStream);
		return true;
	}

	public static function prepareString($sString)
	{
		$sString = str_ireplace("AUTO_INCREMENT", "AUTO_INCREMENT", $sString);
		$sString = str_ireplace("default", "default", $sString);
		$sString = str_ireplace("ON UPDATE", "ON UPDATE", $sString);
		$sString = str_ireplace("CHARACTER SET", "CHARACTER SET", $sString);
		$sString = str_ireplace("COLLATE", "COLLATE", $sString);
		$sString = str_ireplace("  ", " ", $sString);
		
		$sString = preg_replace("/ AUTO_INCREMENT=[0-9]+/", "", $sString);
		$sString = preg_replace("/ default '([0\-\.]*)'/", "", $sString);
		$aArray = preg_split("/[\s,]*\n[\s,]*/", $sString);

		return $aArray;
	}
	
	
	// TODO->Description
	public static function readDir($iDays, $iExactTimestamp, $aSkipExt, $aSkipDir, $sFindPath="find") {
		
		// set variables
		global $root,$files, $iMaxLength;
		$aReturn = array();

		if(substr($root, -1) == "/") {
			$root = substr($root, 0, -1);
		}

		if(empty($sFindPath)) {
			$sFindPath = "find";
		}
		
		$sCmd = $sFindPath.' '.$root.' -type f -mtime -'.(int)$iDays.' -printf "%p %T@ \n"';

		exec($sCmd, $aReturn);

		$sReturn = implode("", $aReturn);
		$aReturn = preg_split("=".$root."=", $sReturn);

		foreach($aReturn as $sLine) {
			$aLine = explode(" ", $sLine);
			$sFile = trim($aLine[0]);

			if(empty($sFile)) {
				continue;
			}
			
			if(
				strpos($sFile, "/temp/") === false && 
				strpos($sFile, "/templates_c/") === false && 
				strpos($sFile, "/update/") === false && 
				strpos($sFile, "/backup/") === false && 
				strpos($sFile, "/phpMyAdmin/") === false && 
				strpos($sFile, "/.svn") === false && 
				strpos($sFile, "/system/includes/config.inc.php") === false
			) {

				foreach((array)$aSkipDir as $sDir) {
					if(strpos($sFile, $sDir) !== false) {
						continue 2;
					}
				}

				$sExt = substr($sFile, strrpos($sFile, '.')+1);

				if(in_array($sExt, $aSkipExt)) {
					continue;
				}

				$iDate = $aLine[1];

				$files[] = array($iDate, $sFile);

			}
		}

		// check auf $iExactTimestamp
		foreach($files as $mKey => $mValue)
		{
			if($mValue['0'] < $iExactTimestamp)
			{
				unset($files[$mKey]);
			}
		}

	}
}
