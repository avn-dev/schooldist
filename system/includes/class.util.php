<?php

use Illuminate\Support\Str;

/**
 * Util
 */
class Util {
	
	/**
	 * Ob PHP 5.3 cachen
	 */
	public static $_bIsPhp53 = null;
	
    public static $ifoutCount = 1;

	/**
	 * Ist die aktuelle IP eine KOM-IP, sodass beispielsweise __pout() ausgegeben wird?
	 * @return bool
	 */
	public static function isDebugIP() {

		$aDebugIPs = WDCache::get('system_debug_ips', true);
		if($aDebugIPs === null) {
			$aDebugIPs = [];
		}

		$aDebugIPs[] = '87.79.75.171'; // TODO: Was ist das für eine IP?
		$aDebugIPs[] = '87.79.65.41';

		if(
			in_array($_SERVER['REMOTE_ADDR'], $aDebugIPs) ||
			isset($_SERVER['HTTP_HOST']) && ( // HTTP_HOST existiert in CLI nicht
				strpos($_SERVER['HTTP_HOST'], '.dev.box') !== false ||
				strpos($_SERVER['HTTP_HOST'], 'agency.local') !== false ||
				strpos($_SERVER['HTTP_HOST'], 'school.local') !== false ||
				strpos($_SERVER['HTTP_HOST'], 'agency.box') !== false ||
				strpos($_SERVER['HTTP_HOST'], 'school.box') !== false
			)
		) {
			return true;
		}

		return false;

	}
	
	public static function isDeveloperLicense() {
		
		$aLicenses = [];
		$aLicenses[] = 'C5D5-RGWL-GG3C-R5LZ'; // agency
		$aLicenses[] = 'ccccccccc'; // school
		
		return in_array(\System::d('license'), $aLicenses);
	}
	
	static protected $_aDebugTimes = array();

    public static function debugTime($sString){
        global $system_data;
        $system_data['debugmode'] = 0;
        
        if(!isset(self::$_aDebugTimes[$sString])){
            self::$_aDebugTimes[$sString] = microtime(true);
        } else {
            __out($sString.': '.(microtime(true) - self::$_aDebugTimes[$sString]));
            unset(self::$_aDebugTimes[$sString]);
        }
        
    }
    
	/**
	 * Checks if directory exists and creates it if not
	 * 
	 * @param string $sDir Absolute directory path with document root
	 * @return bool
	 */
	
	public static function checkDir($sDir) {

		$sOriginalDir = $sDir;
		
		// strip double slashes
		while(strpos($sDir, "//") !== false) {
			$sDir = str_replace("//", "/", $sDir);
		}

		$sRoot = self::getDocumentRoot(false);

		$sDir = str_replace($sRoot, '', $sDir);

		$aDir = explode("/", $sDir);

		$sPath='/';
		for($i=0; $i < count($aDir); $i++) {
			if(!empty($aDir[$i])) {
				$sPath .= $aDir[$i].'/';
				if(!@chdir($sRoot.$sPath)) {
					mkdir($sRoot.$sPath);
					self::changeDirMode($sRoot.$sPath);
				}
			}
		}

		if(is_dir($sOriginalDir)) {
			return true;
		} else {
			return false;
		}

	}

	public static function getBacktrace(bool $asArray = false) {

		$aBacktrace = debug_backtrace();
		array_shift($aBacktrace);
		$aBacktrace = array_reverse($aBacktrace);

		$aTrace = [];

		foreach((array)$aBacktrace as $aBacktraceData){
			$sCurrentTrace = '';
			if(isset($aBacktraceData['class'])){
				$sCurrentTrace .= $aBacktraceData['class'].'::';
			} 
			$sCurrentTrace .= $aBacktraceData['function'];
			if(isset($aBacktraceData['line'])) {
				$sCurrentTrace .= '('.$aBacktraceData['line'].')';
			}
			$aTrace[] = $sCurrentTrace;
		}

		if (!$asArray) {
			return implode(' > ', $aTrace);
		}

		return $aTrace;

	}

	public static function getPublicRoot($bTrailingSlash=true) {

		// Fallback für Console
		if(php_sapi_name() === 'cli') {
			$sDocumentRoot = str_replace('/system/includes', '/public', __DIR__);
		} else {
			$sDocumentRoot = $_SERVER['DOCUMENT_ROOT'];
		}

		if(
			$bTrailingSlash === true &&
			substr($sDocumentRoot, -1) != '/'
		) {
			$sDocumentRoot .= '/';
		} elseif(
			$bTrailingSlash === false &&
			substr($sDocumentRoot, -1) == '/'
		) {
			$sDocumentRoot = substr($sDocumentRoot, 0, -1);
		}

		return $sDocumentRoot;
	}
	
	public static function getDocumentRoot($bTrailingSlash=true) {

		$sDocumentRoot = self::getPublicRoot($bTrailingSlash);

		/*
		 * @todo /public nur am Ende suchen!
		 */
		$sDocumentRoot = str_replace('/public', '', $sDocumentRoot);
		
		return $sDocumentRoot;
	}

	public static function getPathWithRoot($sFilePath){

		$sDocumentRoot = self::getDocumentRoot();

		if(substr($sFilePath, 0, 1) == '/') {
			$sFilePath = substr($sFilePath, 1);
		}

		return $sDocumentRoot.$sFilePath;

	}
	
    public static function generateBackupTableName(string $table): string
    {
        $timestamp = new \DateTime();
        $base = '__' . $timestamp->format('YmdHis') . '_' . $table;

        if (strlen($base) > 64) {
            $parts = explode('_', $table);
            $short = implode('_', array_map(fn($p) => substr($p, 0, 5), $parts));
            $base = '__' . $timestamp->format('YmdHis') . '_' . $short;
        }

        return $base;
    }

    public static function backupTable(string $table, bool $checkBackupTable = false, ?string $backupTable = null)
    {
        $success = false;

        if (!self::checkTableExists($table)) {
            return $success;
        }

        if (!$backupTable) {
            $backupTable = self::generateBackupTableName($table);
        }

        if (
			$checkBackupTable && 
			self::checkTableExists($backupTable)
		) {
            return true;
        }

        $sql = "SHOW CREATE TABLE " . $table;
        $result = \DB::getQueryData($sql);
        $createSql = $result[0]['Create Table'];
        $createSql = str_replace("`{$table}`", "`{$backupTable}`", $createSql);

        $success = \DB::executeQuery($createSql);

        if ($success) {
            $sql = "INSERT INTO #backuptable SELECT * FROM #table";
            $params = ['table' => $table, 'backuptable' => $backupTable];
            $success = (bool)\DB::executePreparedQuery($sql, $params);

            if ($success) {
                return $backupTable;
            }
        }

        return $success;
    }

    public static function getLatestBackupTable(string $originalTable, ?\DateTimeInterface $minDate = null): ?string
    {
        $tables  = \DB::listTables();
        $pattern = '/^__\d{14}_.+$/';
        $backups = [];

        // Erwarte denselben Namenssuffix wie generateBackupTableName
        $dummyName = self::generateBackupTableName($originalTable);
        $expectedSuffix = substr($dummyName, 17);

        foreach ($tables as $t) {
			
            if (!preg_match($pattern, $t)) {
                continue;
            }

            $timestamp = substr($t, 2, 14);
            $baseName  = substr($t, 17);

            if ($baseName !== $expectedSuffix) {
                continue;
            }

            $dt = \DateTime::createFromFormat('YmdHis', $timestamp);
            if (!$dt) {
                continue;
            }

            if (
				$minDate && 
				$dt < $minDate
			) {
                continue;
            }

            $backups[$t] = $dt;
        }

        if (empty($backups)) {
            return null;
        }

        uasort($backups, fn($a, $b) => $b <=> $a);

        return array_key_first($backups);
    }

	/**
	 * Checks whether a table exists or not
	 * @param string $sTable The table to check
	 */
	public static function checkTableExists($sTable = ''){
		$sSql = "SHOW TABLES;";
    	$aResult = DB::getQueryData($sSql);
    	$aTables = array();

    	foreach((array)$aResult as $sInfo => $aTable){
    		foreach((array)$aTable as $sKey => $sValue){
    			$aTables[] = $sValue;
    		}
    	}

   		if (in_array($sTable, $aTables)) {
    		return true;
   		}

    	return false;

	}

	/**
	 * Ermitteln rekursiv den Unterschied von zwei Arrays
	 * @param array $aArray1
	 * @param array $aArray2
	 * @return array 
	 */
	public static function getArrayRecursiveDiff(array $aArray1, array $aArray2, $bCheckOrder=false) { 

		$bNoKeys = false;
		$aReturn = array();
		$aFound = array();
		
		reset($aArray1);
		$mFirstKey = key($aArray1);
		end($aArray1);
		$mLastKey = key($aArray1);
		
		$iCount = count($aArray1);
		
		// Wenn das Array durchnummeriert ist, die Keys also keinen Wert haben
		if(
			$mFirstKey === 0 &&
			$mLastKey === ($iCount-1)
		) {
			$bNoKeys = true;
		}
		
		foreach($aArray1 as $mKey => $mValue) { 

			// Wenn das Array nicht durchnummeriert ist, muss die Reihenfolge nicht geprüft werden
			if(
				$bNoKeys === false ||
				$bCheckOrder === true
			) {
				if(array_key_exists($mKey, $aArray2)) { 
					if (
						is_array($mValue) &&
						is_array($aArray2[$mKey])
					) { 
						$aRecursiveDiff = self::getArrayRecursiveDiff($mValue, $aArray2[$mKey]); 
						if (count($aRecursiveDiff)) { 
							$aReturn[$mKey] = $aRecursiveDiff; 
						} 
					} else { 
						if ($mValue != $aArray2[$mKey]) { 
							$aReturn[$mKey] = $mValue; 
						} 
					} 
				} else { 
					$aReturn[$mKey] = $mValue; 
				} 
			} else {

				$bItemFound = false;
				foreach($aArray2 as $mKey2=>$mValue2) {

					if(isset($aFound[$mKey2])) {
						continue;
					}

					if (
						is_array($mValue) &&
						is_array($mValue2)
					) { 
						$aRecursiveDiff = self::getArrayRecursiveDiff($mValue, $mValue2);
						if(empty($aRecursiveDiff)) {
							$bItemFound = true;
						}
					} else {
						if($mValue == $mValue2) {
							$bItemFound = true;
						}
					}						

					if($bItemFound === true) {
						$aFound[$mKey2] = true;
						break;
					}

				}

				// Wenn Eintrag nicht gefunden, dann zurückgeben
				if($bItemFound === false) {
					$aReturn[$mKey] = $mValue;
				}

			}
		}

		return $aReturn; 

	} 
	
	/**
	 * Get the Symetric Difference of 2 Arrays
	 * ( gegenteil der Schnittmenge, alles was nicht in beiden Arrays vorkommt )
	 * @param array $aArray1
	 * @param array $aArray2
	 * @return array
	 * @author C.Wielath
	 */
	public static function arraySymmetricDifference($aArray1, $aArray2){

		$aDiff1 = (array)array_diff($aArray1, $aArray2);
		$aDiff2 = (array)array_diff($aArray2, $aArray1);

		$aSymmetricDifference = array_merge($aDiff1, $aDiff2);

		return $aSymmetricDifference;
	}

	/**
	 * alle Unterschiede bei Array1 finden mit key,value & Reihenfolge Vergleich
	 * @param array $aArray1
	 * @param array $aArray2
	 * @return array
	 * @author Mehmet Durmaz
	 */
	public static function arrayDiffAssocWithSortCompare(array $aArray1, array $aArray2){

		$aDiff			= array();
		$aArray2Sort	= array();
		$iPosCounter	= 1;

		foreach($aArray2 as $mKey => $mValue){
			$aArray2Sort[$mKey] = array(
				'position'	=> $iPosCounter,
				'value'		=> $mValue
			);

			$iPosCounter++;
		}

		$iPosCounter	= 1;
		foreach($aArray1 as $mKey => $mValue){

			if(
				isset($aArray2Sort[$mKey])
			){
				if(
					$iPosCounter != $aArray2Sort[$mKey]['position'] ||
					$mValue	!= $aArray2Sort[$mKey]['value']
				){
					$aDiff[$mKey] = $mValue;
				}
			}else{
				$aDiff[$mKey] = $mValue;
			}

			$iPosCounter++;
		}

		return $aDiff;
	}

	/**
	 * Gibt die entsprechende Flagge zur Sprache zurück
	 * @param string $sIso
	 * @return string 
	 */
	public static function getFlagIcon($sIso, $sDefault=null) {

		if(strpos($sIso, '_') !== false) {
			list($sIso, $sLocal) = explode('_', $sIso, 2);
		} else {
			$sLocal = null;
		}
		

		$sFlag = '/admin/media/flag_'.strtolower($sIso).'.gif';

		if(
			!is_file(\Util::getDocumentRoot().'system/legacy'.$sFlag) &&
			!empty($sLocal)
		) {
			$sFlag = '/admin/media/flag_'. strtolower($sLocal).'.gif';
		}

		if(!is_file(\Util::getDocumentRoot().'system/legacy'.$sFlag)) {
			if($sDefault !== null) {
				return $sDefault;
			} else {
				return false;
			}
		}
		
		return $sFlag;

	}
	
	/*
	 * Liefert die übersetzten Systemsprachen zurück
	 * $sType = 1 == frontend
	 * $sType = 2 == backend
	 */
	public static function getLanguages($sType = 'frontend') {

		$aBack = array();
	
		if($sType == 'backend') {

			$sInterfaceLang = Factory::executeStatic('Util', 'getInterfaceLanguage');

			$aLanguages = (array)Data_Languages::getList($sInterfaceLang);

			$aAllowedLanguages = (array)System::getBackendLanguages(true);

			$aBack = array_intersect_key($aLanguages, $aAllowedLanguages);

		} elseif($sType == 'frontend') {

			$aData = (new Cms\Helper\Data())->getWebSiteLanguages(0, 0);
			foreach((array)$aData as $aLangData){
				$aBack[$aLangData['code']] = $aLangData['name'];
			}
		}
		
		return $aBack;

	}

	public static function checkPHP53(){
		
		if(self::$_bIsPhp53 === null){
			
			$mVersion = self::getPHPVersion();

			if(version_compare($mVersion , "5.3.0", '>=')){
				self::$_bIsPhp53 = true;
			}else{
				self::$_bIsPhp53 = false;
			}	
			
		}
		
		return self::$_bIsPhp53;

	}

	public static function getPHPVersion() {

		$sVersion = phpversion();

		preg_match("/[0-9]+\.[0-9]+\.[0-9]+/", $sVersion, $aMatch);

		return $aMatch[0];

	}
	
	public static function stripQuotes($mString) {
		
		if(is_array($mString)) {
			foreach((array)$mString as $mKey=>$sString) {
				$mString[$mKey] = self::stripQuotes($sString);
			}
		} else {		
			$mString = preg_replace('/^(["\']?)(.*)\\1$/', '\\2', $mString);
		}

		return $mString;

	}

	/**
	 * Prüft, ob eine URL existiert
	 * 
	 * @param string $sUrl
	 * @param int $iTimeout
	 * @return boolean 
	 */
	public static function checkUrl($sUrl, $iTimeout=2) {

		$sUrl = trim($sUrl);
		
		$sCacheKey = 'check_url_'.$sUrl;

		$bResult = WDCache::get($sCacheKey);

		if($bResult === null) {

			$bResult = true;
			
			$aUrl = self::parseUrl($sUrl);
			$sHost = $aUrl['host'];
			$bIP = filter_var($sHost, FILTER_VALIDATE_IP);

			// Default-Port
			$iPort = 80;
			if($aUrl['scheme'] === 'https') {
				$iPort = 443;
			}

			// Port setzen, wenn vorhanden
			if(!empty($aUrl['port'])) {
				$iPort = $aUrl['port'];
			}

			// DNS Check
			if(!$bIP && $_SERVER["REMOTE_ADDR"] !== '127.0.0.1') {
				$bCheckDns = self::checkDns($sHost, ['A', 'CNAME']);
				if($bCheckDns === false) {
					$bResult = false;
				}
			}

			if($bResult === true) {
				$iErrno = 0;
				$sErrstr = '';

				if($aUrl['scheme'] === 'https') {
					$rFid = @fsockopen("tls://".$sHost, $iPort, $iErrno, $sErrstr, $iTimeout);
				} else {
					$rFid = @fsockopen($sHost, $iPort, $iErrno, $sErrstr, $iTimeout);
				}

				if($rFid !== false) {
					
					if(empty($aUrl['path'])) {
						$aUrl['path'] = '/';
					}

					fputs($rFid, "GET ".$aUrl['path']." HTTP/1.1\r\nHost: ".$sHost."\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n\r\n");
					$sGets = fgets($rFid, 1024);

					fclose($rFid);
					$aRegs = array();
					$mMatch = preg_match("/HTTP\/1.. ([0-9]{3})/", $sGets, $aRegs);

					if (
						$mMatch !== false &&
						$aRegs[1] < 400
					) {
						$bResult = true;
					} else {
						$bResult = false;
					}
				} else {
					$bResult = false;
				}
				
			}

			// Nur positive Prüfung speichern, da negatives Ergebnis temporär sein kann
			if($bResult === true) {
				WDCache::set($sCacheKey, 24*60*60, $bResult);
			}

		}

		return $bResult;
	}

	/**
	 * Besserer URL-Parser
	 *
	 * Dieser Parser bietet im Gegensatz zu PHP's parse_url auch die Fähigkeiten,
	 * 	IPs zu parsen inklusive IPv6.
	 * @param string $sUrl
	 * @return array
	 */
	public static function parseUrl($sUrl) {

		$sRegex  = "(?:([a-z0-9+-._]+)://)?";
		$sRegex .= "(?:";
		$sRegex .= "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
		$sRegex .= "(?:\[((?:[a-z0-9:])*)\])?";
		$sRegex .= "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
		$sRegex .= "(?::(\d*))?";
		$sRegex .= "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
		$sRegex .= "|";
		$sRegex .= "(/?";
		$sRegex .= "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
		$sRegex .= "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
		$sRegex .= ")?";
		$sRegex .= ")";
		$sRegex .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
		$sRegex .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";

		preg_match("`$sRegex`i", $sUrl, $aMatch);

		$aParts = array(
			"scheme" => '',
			"userinfo" => '',
			"authority" => '',
			"host" => '',
			"port" => '',
			"path" => '',
			"query" => '',
			"fragment" => ''
		);

		switch (count ($aMatch)) {
			case 10: $aParts['fragment'] = $aMatch[9];
			case 9: $aParts['query'] = $aMatch[8];
			case 8: $aParts['path'] =  $aMatch[7];
			case 7: $aParts['path'] =  $aMatch[6] . $aParts['path'];
			case 6: $aParts['port'] =  $aMatch[5];
			case 5: $aParts['host'] =  $aMatch[3] ? '['.$aMatch[3].']' : $aMatch[4];
			case 4: $aParts['userinfo'] =  $aMatch[2];
			case 3: $aParts['scheme'] =  $aMatch[1];
		}

		$aParts['authority'] = ($aParts['userinfo'] ? $aParts['userinfo'].'@' : '').$aParts['host'].($aParts['port'] ? ':'.$aParts['port'] : '');

		return $aParts;
	}

	public static function handleErrorMessage($mText, $iSendmail=1, $iShowtxt=0, $iPrio=2) {
		global $page_data, $system_data, $_VARS, $browser_data;

		if(isset($browser_data['agent']) && $browser_data['agent'] != "Bot") {

			if(is_array($mText)) {
				$sSubject = (string)$mText[0];
				$sText = (string)$mText[1];
			} else {
				$sSubject = null;
				$sText = $mText;
			}

			$sMailText = "";

			if(!empty($sSubject)) {
				$sMailText .= "Subject: ".$sSubject."\n\n";
			}

			$sMailText .= "Message:\n".$sText."\n\n\n";

			if($page_data['id'] > 0) {
				$oPage = Cms\Entity\Page::getInstance($page_data['id']);
				$sMailText .= "Page: ".$oPage->getLink()."\n\n";
			}

			$sMailText .= "Version: ".System::d('version')."\n\n";

			if(!empty($_SERVER['QUERY_STRING'])) {
				$sMailText .= "Parameter: ".$_SERVER['QUERY_STRING']."\n\n";
			}
			if(!empty($_SERVER['HTTP_REFERER'])) {
				$sMailText .= "Referer: ".$_SERVER['HTTP_REFERER']."\n\n";
			}
			if(!empty($_SERVER['REDIRECT_URL'])) {
				$sMailText .= "Redirect-URL: ".$_SERVER['REDIRECT_URL']."\n\n";
			}
			if(!empty($_SERVER['REDIRECT_QUERY_STRING'])) {
				$sMailText .= "Redirect-Parameter: ".$_SERVER['REDIRECT_QUERY_STRING']."\n\n";
			}
			if(!empty($_SERVER['REMOTE_ADDR'])) {
				$sMailText .= "Rechner: ".$_SERVER['REMOTE_ADDR']."\n\n";
			}
			if(!empty($browser_data)) {
				$sMailText .= "Browser: ".$browser_data['agent']."\n\n";
				$sMailText .= "OS: ".$browser_data['os']."\n\n";
			}
			if(!empty($_SERVER['HTTP_USER_AGENT'])) {
				$sMailText .= "User agent: ".$_SERVER['HTTP_USER_AGENT']."\n\n";
			}
			if(!empty($_VARS)) {
				$sMailText .= "VARS: \n".print_r($_VARS, 1)."";
			}
			$sMailText .= "SERVER: \n".print_r($_SERVER, 1)."";
			$sMailText .= "Trace: ".Util::getBacktrace()."\n\n";

			if(
				$iSendmail == 1 &&
				array_key_exists('report_error', (array)$system_data) &&
				(
					\System::d('report_error') == 1 ||
					(
						\System::d('report_error') == 2 &&
						$iPrio == 1
					)
				)
			) {

				$sMailSubject = "Error";

				if(!empty($sSubject)) {
					$sMailSubject .= ' - '.$sSubject;
				}
				self::reportError($sMailSubject, $sMailText, false, $iPrio);
		
			}

			if($iShowtxt == 1) {

				echo nl2br($sText);

				if(System::d('debugmode')) {
					$aTrace = debug_backtrace();
					array_shift($aTrace);
					echo "<br/><br/><b>Trace:</b><br/>";
					echo "<ul>";
					foreach ($aTrace as $aTraceEntry) {
						echo "<li>".$aTraceEntry['file']." (Line: ".$aTraceEntry['line'].", Function: ".$aTraceEntry['function'].")</li>";
					}
					echo "</ul>";
				}		

				if(
					$iSendmail == 1 && 
					System::d('report_error') > 0
				) {
					echo "Der <a href=\"mailto:".System::getErrorEmail()."?subject=".$system_data['site_name']."\">System Administrator</a> wird automatisch informiert.<p>";
				}

			}
		}

	}

	/**
	 * Sends error message e-mail
	 *
	 * @param string $sSubject
	 * @param string|mixed $sMessage
	 * @param bool $bFullBacktrace
	 * @return bool
	 */
	public static function reportError($sSubject, $sMessage = "", $bFullBacktrace = false, $iPrio=null) {

		if($sMessage instanceof \Throwable) {
			$sMessage = $sMessage->getMessage();
		}
		
		if(!is_scalar($sMessage)) {
			$sMessage = print_r($sMessage, 1);
		}

		// Sehr lange Nachrichten abschneiden
		if(strlen($sMessage) > 65000) {
			$sMessage = substr($sMessage, 0, 65000);
			$sMessage .= "\n\nOutput cut off!";
		}

		$sDebug = \Util::getBacktrace();

		// Nach 5 Mails mit gleichem Betreff oder bei fehlende E-Mail-Adresse keine Mails mehr schicken, sondern loggen
		$sCacheKey = __METHOD__.'_'.md5($sSubject);
		$iMailCount = (int)\WDCache::get($sCacheKey);
		if(
			\System::d('report_error') === '0' ||
			$iMailCount >= 5
		) {
			$oLogger = \Log::getLogger();
			$oLogger->addError(__METHOD__.': '.$sSubject, [$sMessage, $sDebug]);
			return true;
		}

		// Betreff für 15 Minuten im Cache halten
		\WDCache::set($sCacheKey, 60*15, ++$iMailCount);

		$sSubject = $sSubject.' - '.self::getHost();
		$sMessage = $sMessage."\n\n".$sDebug."\n\n".print_r($_SERVER, 1)."\n\n".print_r($_REQUEST, 1);

		if($iMailCount == 5) {
			// Bei 5. E-Mail deutlich machen, dass danach (erst einmal) keine mehr folgen wird
			$sSubject .= ' - E-MAIL SENDING STOPS FOR 15 MINUTES';
		}

		if(System::d('error_logging') === 'apache') {

			$sErrorLog = preg_replace('/(\s+|)(\n)(\s+|)/', '|', $sMessage);
			if(strlen($sErrorLog) > ini_get('log_errors_max_len')) {
				$sErrorLog = substr($sErrorLog, 0, ini_get('log_errors_max_len'));
			}
			error_log($sErrorLog);

		} elseif(
			System::d('error_logging') === 'logging_server' &&
			class_exists('\Licence\Service\Office\Api\Object\Log')
		) {
			
			$bSuccess = \Log::addErrorMessage($sSubject, $sMessage);
			
		} else {
			
			$sTo = System::getErrorEmail();

			$oMail = new WDMail();
			$oMail->subject = $sSubject;
			$oMail->text = $sMessage;

			if($iPrio !== null) {
				$oMail->priority = $iPrio;
			}

			$bSuccess = $oMail->send($sTo);
		}
		
		return $bSuccess;
	}
    
    static public function getHost() {

        $sHost = $_SERVER['HTTP_HOST'] ?? '';
        $sHost = explode(':', $sHost);
        $sHost = reset($sHost);

		// Bei CLI gibt es kein HTTP_HOST
		if(empty($sHost)) {
			$sHost = self::getSystemHost();
		}

        return $sHost;
    }
	
	static public function getSystemHost() {

		$sHost = System::d('domain');
		$sHost = preg_replace('#^https?://#', '', $sHost);

		return $sHost;
	}
	
	/**
	 * Entfernt MagicQuotes wenn gesetzt
	 * 
	 * @param type $aArray
	 * @return boolean 
	 */
	static public function removeMagicQuotes(&$aArray) {

		$bIsMagicQuotes = get_magic_quotes_gpc();

		if(
			!is_array($aArray) ||
			!$bIsMagicQuotes
		) {
			return false;
		}

		foreach($aArray AS $sKey => $mValue) {
			if (is_array($mValue)) {
				WD_gpc_extract($aArray[$sKey]);
			} else {
				if (
					$bIsMagicQuotes &&
					$sKey != 'tmp_name'
				) {
					$aArray[$sKey] = stripslashes($mValue);
				} else {
					$aArray[$sKey] = $mValue;
				}

			}
		}

		return true;

	}

	// string mit microsekunden
	static public function getMicrotime(){
		$floMicroSeconds = microtime(1);
		return $floMicroSeconds;
	}

	/**
	 * Calculates and formats the H:i:s by seconds
	 * 
	 * @param int : The number of seconds
	 * @return array : Formated times
	 */
	public static function getFormatedTimes($iSeconds) {

		$iOverflowH		= $iSeconds % 3600;
		$aTimes['H']	= $iSeconds - $iOverflowH;
		$aTimes['H']	= round($aTimes['H'] / 3600);
		$iOverflowM		= $iOverflowH % 60;
		$iOverflowH		= $iOverflowH - $iOverflowM;
		$aTimes['M']	= $iOverflowH / 60;
		$aTimes['S']	= $iOverflowM;

		$aTimes['H'] = str_pad($aTimes['H'], 2, '0', STR_PAD_LEFT);
		$aTimes['M'] = str_pad($aTimes['M'], 2, '0', STR_PAD_LEFT);
		$aTimes['S'] = str_pad($aTimes['S'], 2, '0', STR_PAD_LEFT);
		$aTimes['T'] = $aTimes['H'].':'.$aTimes['M'].':'.$aTimes['S'];
		$aTimes['O'] = $iSeconds;

		return $aTimes;

	}

	static public function generateRandomString($iDigits, $arrOptions=array()) {

		$aSpecials	= array('#', '=', '~', '$', '%', '&', '§', '*', '?', '!');
		$aSigns		= array();

		for($i = 0; $i < $iDigits; $i++) {
			// USE NUMBERS EXCEPT 0 AND 1 AND CAPITAL LETTERS EXCEPT I AND O
			if(
				isset($arrOptions['no_numbers']) &&
				$arrOptions['no_numbers']
            ) {
				$c = mt_rand(0,23);
			} else {
				$c = mt_rand(0,31);
			}
			if($c<8) $c+=65;		// A-H
			elseif($c<13) $c+=66;	// J-N
			elseif($c<24) $c+=67;	// P-Z
			else $c+=26;			// 2-9
			$aSigns[] = chr($c);
		}

		if(
			isset($arrOptions['with_specials']) &&
			$arrOptions['with_specials']
		)
		{
			$aSigns = array_merge($aSigns, $aSpecials);
		}

		shuffle($aSigns);

		$aString = array_slice($aSigns, 0, $iDigits);

		if(
			isset($arrOptions['as_array']) &&
			$arrOptions['as_array']
		)
		{
			return $aString;
		}

		return implode('', $aString);

	}

	/**
	 * @return array
	 */
	public static function getNameservers() {

		return array(
			'8.8.8.8',
			#'8.8.4.4',
			'208.67.222.222',
			#'208.67.220.220'
		);

	}

	/**
	 * @param string $sEmail
	 * @return bool
	 */
	public static function checkEmailMx($sEmail) {
        
		$sEmail = trim($sEmail);
		
		if(empty($sEmail)) {
			return false;
		}
		
		$sCacheKey = 'check_email_mx_'.$sEmail;

		$bResult = WDCache::get($sCacheKey);

		if($bResult === null) {

			$bResult = false;

			// pre check (Vereinfachter REGEX nach RFC 5322
			$strRegex = "/^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i";
			$iMatch = preg_match($strRegex, $sEmail);
			
			if($iMatch === 1) {

				// Sub-/Domains -^ TLD-^
				$aHost = explode('@', $sEmail);

				$bCheckDns = self::checkDns($aHost[1]);

				if($bCheckDns === true) {
					$bResult = true;
				}

			}

			// Nur positive Prüfung speichern, da negatives Ergebnis temporär sein kann
			if($bResult === true) {
				WDCache::set($sCacheKey, 24*60*60, $bResult);
			}

		}

		return $bResult;
	}
	
	public static function checkDns($sHost, $aDnsTypes = ['MX', 'A', 'CNAME']) {

		$oDnsResolver = new \NetDNS2\Resolver(array(
			'timeout' => 2,
			'nameservers' => self::getNameservers()
		));

		foreach($aDnsTypes as $sDnsType) {

			try {

				$oDnsResolver->query($sHost, $sDnsType);
				return true;

			} catch(\NetDNS2\Exception $e) {

			}

		}

		return false;
	}
	
	public static function getBackendLanguages(){
		
		$aLanguages = System::getBackendLanguages(true);
		
		$aReturn = array();
		
		foreach($aLanguages as $sIso => $sValue){
			
			$aReturn[] = array(
				'iso' => $sIso,
				'name' => $sValue
			);
			
		}

		return $aReturn;
	}

	public static function getInterfaceLanguage() {
		return System::getInterfaceLanguage();
	}
	
	/**
	 * Fügt am Anfang eines Array ein leeres Element ein
	 */
	public static function addEmptyItem($aArray, $sText='', $sValue=0) {

        if ($aArray instanceof \Illuminate\Support\Collection) {
            $aArray = $aArray->prepend($sText, $sValue);
        } else {
            $aArray = (array)$aArray;

            $aFirst = array($sValue=>$sText);
            $aArray = $aFirst + $aArray;
        }

		return $aArray;
	}

	/**
	 * Timezone determinieren und setzen
	 * @return bool
	 */
	public static function getAndSetTimezone() {

		$sTimezone = System::d('timezone');

		$bReturn = false;
		if(!empty($sTimezone)) {
			$bReturn = self::setTimezone($sTimezone);
		}

		return $bReturn;

	}
	
	/**
	 * Setzt die übergebene Zeitzone falls möglich
	 * Falls nicht, wird CET gesetzt
	 *
	 * Achtung! Methode ist (fast) redundant überschrieben in TC!
	 *
	 * @see Ext_TC_Util::setTimezone()
	 * @param string $sTimezone
	 * @return boolean
	 */
	public static function setTimezone($sTimezone) {
		
		$mReturnMySQL = false;
		$mReturnPHP = false;
		
		try{

			// MySQL
			
			// Hier stand mal "SET SESSION time_zone = :time_zone", das hat aber leider nicht 
			// funktioniert, obwohl @@session.time_zone richtig gesetzt war ergab NOW() nicht die
			// richtige Uhrzeit, darum habe ich das in SET time_zone umgeändert (#4591)
			$sSql = "SET time_zone = :time_zone;";
			$aSql = array();
			$aSql['time_zone'] = (string)$sTimezone;
			$mReturnMySQL = DB::executePreparedQuery($sSql, $aSql);

			// PHP
			$mReturnPHP = date_default_timezone_set((string)$sTimezone);

		} catch(Exception $e){
		}

		if(
			$mReturnMySQL === false ||
			$mReturnPHP === false
		) {
			// Sicherheitshalber Defaultzeitzone setzen
			if($sTimezone != 'CET') {
				self::setTimezone('CET');
			}

			return false;
		} else {
			return true;
		}
		
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @since 2.0.0
	 *
	 * @param string $original Maybe unserialized original, if is needed.
	 * @return mixed Unserialized data can be any type.
	 */
	public static function decodeSerializeOrJson($sOriginal, $bJsonArray=true) {

		// Diese Datentypen nicht behandeln
		if(
			is_numeric($sOriginal) ||
			is_null($sOriginal) ||
			is_bool($sOriginal)
		) {
			return $sOriginal;
		}

		if ( self::isSerialized( $sOriginal ) ) {
			$mReturn = unserialize($sOriginal);
		} else {
			$mReturn = json_decode($sOriginal, $bJsonArray);
			if(
				$sOriginal != 'null' &&
				$mReturn === null
			) {
				$mReturn = $sOriginal;
			}
		}

		return $mReturn;
	}

	/**
	 * Check value to find if it was serialized.
	 *
	 * If $data is not an string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @since 2.0.5
	 *
	 * @param mixed $sData Value to check to see if was serialized.
	 * @return bool False if not serialized and true if it was.
	 */
	public static function isSerialized( $sData ) {

		// if it isn't a string, it isn't serialized
		if ( ! is_string( $sData ) )
			return false;
		$sData = trim( $sData );
		if ( 'N;' == $sData )
			return true;
		$length = strlen( $sData );
		if ( $length < 4 )
			return false;
		if ( ':' !== $sData[1] )
			return false;
		$lastc = $sData[$length-1];
		if ( ';' !== $lastc && '}' !== $lastc )
			return false;
		$token = $sData[0];
		switch ( $token ) {
			case 's' :
				if ( '"' !== $sData[$length-2] )
					return false;
			case 'a' :
			case 'O' :
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $sData );
			case 'b' :
			case 'i' :
			case 'd' :
				return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $sData );
		}

		return false;
	}

	/**
	 * Wenn es ein JSON-String ist, wird er dekodiert
	 * @param string $sInput
	 */
	public static function reconvertMixed($sInput) {
		
		if(is_scalar($sInput)) {

			$mDecode = json_decode($sInput, true);

			if($mDecode !== null) {
				$sInput = $mDecode;
			}

		}

		return $sInput;
	}
	
	/**
	 * Falls der Wert nicht skalar ist, wird er per JSON kodiert.
	 * @param mixed $mInput
	 * @return scalar
	 */
	public static function convertMixed($mInput) {
		
		if(!is_scalar($mInput)) {
			$mInput = json_encode($mInput);
		}
		
		return $mInput;		
	}
	
    /**
     * Gibt alle bisher getätigten Queries aus
     * @return mixed Alle bisher getätigten Queries
     */
    public static function getQueryHistory() {

        $aQueryHistory = \DB::getQueryHistory();

        return $aQueryHistory;
    }

	/**
	 * @param string $sFile
	 * @param string $sSeparator
	 * @param boolean $bIncludePoint
	 * @return string
	 */
	public static function getCleanFilename(string $sFile, string $sSeparator='_', bool $bIncludePoint=true, bool $lowerCase=true): string {

		// Alle Sonderzeichen durch entsprechende Zeichen ersetzen
		$sFile = Util::replaceSpecialChars($sFile);
		
		if($bIncludePoint === true) {
			$sRegexp = "/[^a-z0-9\.-]/i";
		} else {
			$sRegexp = "/[^a-z0-9-]/i";
		}
		
		// Alle restlichen Zeichen durch "_" ersetzen
		$sFile = preg_replace($sRegexp, $sSeparator, $sFile);

		if($lowerCase === true) {
			$sFile = strtolower($sFile);
		}

		while(strpos($sFile, $sSeparator.$sSeparator) !== false) {
			$sFile = str_replace($sSeparator.$sSeparator, $sSeparator, $sFile);
		}
		$sFile = trim($sFile, $sSeparator);

		return $sFile;
	}

	public static function getCleanPath($sFile) {
		// Alle Sonderzeichen durch entsprechende Zeichen ersetzen
		$sFile = Util::replaceSpecialChars($sFile);
		// Alle restlichen Zeichen durch "_" ersetzen
		$sFile = strtolower(preg_replace("/[^a-z0-9_\/\.\-]/i","_",$sFile));
		while(strpos($sFile,"__") !== false) {
			$sFile = str_replace("__","_",$sFile);
		}
		$sFile = trim($sFile, '_');
		return $sFile;
	}

	public static function replaceSpecialChars($strInput) {
		$arrPairs = array(
					"à"=>"a",
					"á"=>"a",
					"á"=>"a",
					"á"=>"a",
					"â"=>"a",
					"ã"=>"a",
					"ä"=>"ae",
					"å"=>"a",
					"æ"=>"a",
					"ç"=>"c",
					"è"=>"e",
					"é"=>"e",
					"ê"=>"e",
					"ë"=>"e",
					"ì"=>"i",
					"í"=>"i",
					"î"=>"i",
					"ï"=>"i",
					"ð"=>"o",
					"ñ"=>"n",
					"ò"=>"o",
					"ó"=>"o",
					"ô"=>"o",
					"õ"=>"o",
					"ö"=>"oe",
					"÷"=>"_",
					"ø"=>"o",
					"ù"=>"u",
					"ü"=>"ue",
					"ú"=>"u",
					"þ"=>"p",
					"ÿ"=>"y",
					"À"=>"A",
					"Á"=>"A",
					"Â"=>"A",
					"Ã"=>"A",
					"Ä"=>"Ae",
					"Å"=>"A",
					"Æ"=>"A",
					"Ç"=>"C",
					"È"=>"E",
					"É"=>"E",
					"Ê"=>"E",
					"Ë"=>"E",
					"Ì"=>"I",
					"Í"=>"I",
					"Î"=>"I",
					"Ï"=>"I",
					"Ð"=>"D",
					"Ñ"=>"N",
					"Ò"=>"O",
					"Ó"=>"O",
					"Ô"=>"O",
					"Õ"=>"O",
					"Ö"=>"Oe",
					"×"=>"x",
					"Ø"=>"o",
					"Ù"=>"U",
					"Ü"=>"Ue",
					"Ú"=>"U",
					"Þ"=>"P",
					"ß"=>"ss"
					);
		$strOutput = strtr($strInput, $arrPairs);
		return $strOutput;
	}
	
	/**
	 * Der Mode für chmod() muss als Dezimalzahl formatiert werden, damit es klappt
	 * 
	 * @param string|int $mValue
	 * @param int $iDefault
	 * @return int
	 */
	static public function getModeOctalValue($mValue, $iDefault=null) {

		if(
			empty($mValue) ||
			!is_numeric($mValue)
		) {
			return $iDefault;
		}

		// Oktalwert als Zahl
		if(is_int($mValue)) {
			$mValue = (int)base_convert($mValue, 10, 8);
		// Oktalwert als String
		} else {
			$mValue = intval($mValue, 10);
		}

		return octdec($mValue);
	}

	static public function changeFileMode($sFile) {
		$iMode = System::d('chmod_mode_file');
		if(is_string($iMode)) {
			$iMode = Util::getModeOctalValue($iMode);
		}
		chmod($sFile, $iMode);
	}


	static public function changeFileModeReadonly($sFile) {
		$iMode = System::d('chmod_mode_file_readonly');
		if(is_string($iMode)) {
			$iMode = Util::getModeOctalValue($iMode);
		}
		chmod($sFile, $iMode);
	}

	static public function changeDirMode($sFile) {

		$iMode = System::d('chmod_mode_dir');
		
		if(is_string($iMode)) {
			$iMode = Util::getModeOctalValue($iMode);
		}

		chmod($sFile, $iMode);

	}

	/**
	 * Calculate the column code by column number.
	 * A - Z, AA - AZ, BA - BZ, ...
	 *
	 * @param int $iColumnNumber
	 * @return string
	 */
	public static function getColumnCodeForExcel($iColumnNumber) {

		for($sCode = ""; $iColumnNumber >= 0; $iColumnNumber = intval($iColumnNumber / 26) - 1) {
			$sCode = chr($iColumnNumber%26 + 0x41) . $sCode;
		}

		return $sCode;

	}

	static public $iDeletedFiles = 0;
	
	/**
	 * Löscht Dateien und Verzeichnisse 
	 * @param string $str
	 * @return boolean
	 */
	public static function recursiveDelete($str){

        if(is_file($str)) {
			self::$iDeletedFiles++;
            return @unlink($str);
        } elseif(is_dir($str)) {
            $aDeleteItems = array();
			// Normale Dateien
			$scan = glob(rtrim($str,'/').'/*');
			if(is_array($scan)) {
				$aDeleteItems += $scan;
			}
			// Versteckte Dateien auch löschen
			$scan = glob(rtrim($str,'/').'/.[a-z]*');
			if(is_array($scan)) {
				$aDeleteItems += $scan;
			}
            foreach($aDeleteItems as $sPath) {
				self::recursiveDelete($sPath);
            }
            return @rmdir($str);
        }

    }

	/**
	 * PHP-Shorthand-Notation in Bytes konvertieren
	 */
	public static function convertPHPShorthandNotationToBytes($sValue) {

		$sValue = trim($sValue);
		$sLastChar = strtolower($sValue[strlen($sValue)-1]);
		switch($sLastChar) {
			case 'g':
				$sValue *= 1024;
			case 'm':
				$sValue *= 1024;
			case 'k':
				$sValue *= 1024;
		}

		return (int)$sValue;

	}
	
	public static function compressCssOutput($sCss) {

		$sCss = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sCss);
		$sCss = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $sCss);

		return $sCss;

	}

	/**
	 * Konvertiert einen String von lowercase_with_hypens zu PascalCase:
	 * * CamelCase (core -> Core)
	 * * Bindestriche (camel-case -> CamelCase)
	 *
	 * @param string $sString
	 * @return string
	 */
	public static function convertHyphenLowerCaseToPascalCase($sString) {
		$sCamelCaps = ucfirst($sString);

		while(($iPos = strpos($sCamelCaps, '-')) !== false) {
			// Bindestriche in CamelCase-Schreibweise konvertieren
			$sCamelCaps = substr($sCamelCaps, 0, $iPos).ucfirst(substr($sCamelCaps, $iPos+1));
		}

		return $sCamelCaps;
	}

	public static function truncateText($sText, $iWidth, $iXPos, $iFont, $sFont, $iRows=0, $sSuffix="...") {
		$oImgBuilder = new imgBuilder;
		$aTemp = $oImgBuilder->determineWordWrap($sText, $iWidth, $iXPos, $iFont, $sFont, $iRows, $sSuffix);
		$sText = $aTemp['wrap'];
		if(empty($sText)) {
			$sText = $aTemp['text'];
		}
		return $sText;
	}

	/**
	 * Konvertiert einen String von PascalCase zu lowercase_with_hypens
	 * CamelCase -> camel-case
	 *
	 * @param string $sString
	 * @return string
	 */
	public static function convertPascalCaseToHyphenLowerCase($sString) {
		return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $sString));
	}

	/**
	 * Wrapper für json_encode() der im Fehlerfall eine Exception wirft
	 *
	 * @param mixed $mValue
	 * @param int $iOptions
	 * @param int $iDepth
	 * @return string
	 * @throws RuntimeException
	 */
	public static function encodeJson($mValue, $iOptions = 0, $iDepth = 512) {

		$sEncoded = json_encode($mValue, $iOptions, $iDepth);

		if($sEncoded !== false) {
			return $sEncoded;
		}

		$iJsonErrorCode = json_last_error();
		$sJsonErrorMessage = 'Unknown error';

		if(function_exists('json_last_error_msg')) { // gibt es erst ab PHP 5.5

			$sJsonErrorMessage = json_last_error_msg();

		} else {

			switch($iJsonErrorCode) {
				case 1:
					$sJsonErrorMessage = 'Maximum stack depth exceeded';
					break;
				case 2:
					$sJsonErrorMessage = 'Underflow or the modes mismatch';
					break;
				case 3:
					$sJsonErrorMessage = 'Unexpected control character found';
					break;
				case 4:
					$sJsonErrorMessage = 'Syntax error, malformed JSON';
					break;
				case 5:
					$sJsonErrorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
			}

		}

		$sMsg = 'json_encode() failed: '.$sJsonErrorMessage.' ('.$iJsonErrorCode.')';
		throw new RuntimeException($sMsg);

	}

	public static function formatFilesize($bytes): string {
		$units = [' B', ' KB', ' MB', ' GB', ' TB'];
		$i = 0;
		while ($bytes >= 1000 && $i < count($units) - 1) {
			$bytes /= 1000;
			$i++;
		}
		return round($bytes, 2).$units[$i];
	}
	
	/**
	 * @param array $aArray1
	 * @param array $aArray2
	 * @return array
	 */
	public static function mergeArrayRecursiveDistinct(array &$aArray1, array &$aArray2) {

		if(empty($aArray1)) {
			$aMerged = $aArray2;
			return $aMerged;
		}

		$aMerged = $aArray1;

		foreach($aArray2 as $mKey => &$mValue) {

			if(
				is_array($mValue) && 
				isset($aMerged[$mKey]) && 
				is_array($aMerged[$mKey]) 
			) {
				$aMerged[$mKey] = self::mergeArrayRecursiveDistinct($aMerged[$mKey], $mValue);
			} else {
				$aMerged[$mKey] = $mValue;
			}

		}

		return $aMerged;
	}

	public static function convertHtmlEntities($strText, $strCharset = 'UTF-8') {
		$strText = htmlentities((string)$strText, ENT_QUOTES, $strCharset);
		return $strText;
	}

	public static function getEscapedString($strInput, $strType = 'html', $strCharset = 'UTF-8') {
		if(!$strType) {
			$strType = "html";
		}
		switch ($strType) {
			case 'html':
				return htmlspecialchars($strInput, ENT_QUOTES, $strCharset);

			case 'htmlall':
				return htmlentities($strInput, ENT_QUOTES, $strCharset);

			case 'url':
				return rawurlencode($strInput);

			case 'urlpathinfo':
				return str_replace('%2F','/',rawurlencode($strInput));

			case 'quotes':
				// escape unescaped single quotes
				return preg_replace("%(?<!\\\\)'%", "\\'", $strInput);

			case 'hex':
				// escape every character into hex
				$return = '';
				for ($x=0; $x < strlen($strInput); $x++) {
					$return .= '%' . bin2hex($strInput[$x]);
				}
				return $return;

			case 'hexentity':
				$return = '';
				for ($x=0; $x < strlen($strInput); $x++) {
					$return .= '&#x' . bin2hex($strInput[$x]) . ';';
				}
				return $return;

			case 'decentity':
				$return = '';
				for ($x=0; $x < strlen($strInput); $x++) {
					$return .= '&#' . ord($strInput[$x]) . ';';
				}
				return $return;

			case 'javascript':
				// escape quotes and backslashes, newlines, etc.
				return strtr($strInput, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));

			case 'mail':
				// safe way to display e-mail address on a web page
				return str_replace(array('@', '.'),array(' [AT] ', ' [DOT] '), $strInput);

			case 'nonstd':
			   // escape non-standard chars, such as ms document quotes
			   $_res = '';
			   for($_i = 0, $_len = strlen($strInput); $_i < $_len; $_i++) {
				   $_ord = ord(substr($strInput, $_i, 1));
				   // non-standard char, escape it
				   if($_ord >= 126){
					   $_res .= '&#' . $_ord . ';';
				   }
				   else {
					   $_res .= substr($strInput, $_i, 1);
				   }
			   }
			   return $_res;

			default:
				return $strInput;
		}

	}

	static public function setRecursiveArrayValue(array &$aArray, array $aKeys, $mValue) {
		
		$aTmp =& $aArray;
		
		foreach($aKeys as $sKey) {
			$aTmp =& $aTmp[$sKey];
		}
		
		$aTmp = $mValue;
		
	}

	public static function getProxyHost($protocol = true, $trailingSlash = true): string {
		$proxy = \System::d('proxy_host', 'proxy.fidelo.com');

		if ($protocol) {
			if (!str_starts_with($proxy, 'http://')) {
				// Lokale Umgebung
				$proxy = Str::start($proxy, 'https://');
			}
		} else {
			$proxy = str_replace(['https://', 'http://'], '', $proxy);
		}

		if ($trailingSlash) {
			$proxy = Str::finish($proxy, '/');
		} else {
			$proxy = rtrim($proxy, '/');
		}

		return $proxy;
	}

	public static function getInstallationKey(): string {
		$systemHost = \Util::getSystemHost();

		if (str_contains($systemHost, '.fidelo.com')) {
			$systemHost = \Illuminate\Support\Str::before($systemHost, '.fidelo.com');
		}

		return $systemHost;
	}

	public static function getProxyCustomer(): string {
		$customer = \System::d(
			'proxy_customer',
			\Illuminate\Support\Str::before(\Util::getHost(), '.')
		);
		return $customer;
	}

	public static function isInternEmail(string $email): bool
	{
		$intern = config('app.intern.emails.domains');

		$found = \Illuminate\Support\Arr::first($intern, fn ($domain) => str_ends_with($email, $domain));

		return $found !== null;
	}

	public static function convertDateFormat(&$dateFormat, $dateFormatConvertion, $throwException = false) {

		switch ($dateFormatConvertion) {
			case 'backend_datepicker_format':
			case 'backend_datepicker_format_short':
				$aMapping = [
					'%d' => 'dd',
					'%m' => 'mm',
					'%Y' => 'yyyy',
					'%y' => 'yy'
				];
				break;
			case 'backend_moment_format_long':
			case 'backend_date':
				$aMapping = [
					'%d' => 'DD',
					'%m' => 'MM',
					'%Y' => 'YYYY',
					'%y' => 'YY',
					'%b' => 'MMM',
					'%O' => 'o'
				];
				break;
			case 'date_time':
				$aMapping = [
					'%d' => 'd',
					'%m' => 'm',
					'%Y' => 'Y',
					'%y' => 'y',
					'%b' => 'M',
					'%B' => 'F',
					'%O' => '' # Dafür gibt es nichts wirkliches bei dateTime
				];
				break;
			case 'jquery':
				// http://api.jqueryui.com/datepicker/#utility-formatDate
				$aMapping = [
					'%d' => 'dd',
					'%m' => 'mm',
					'%Y' => 'yy',
					'%y' => 'y',
					'%b' => 'M',
					'%B' => 'MM',
					'%O' => 'O' // Gibt es im Datepicker von Haus aus nicht
				];
				break;
			case 'moment': // TODO Deprecated
				// https://momentjs.com/docs/#/parsing/string-format/ + Fecha (V-Calendar)
				$aMapping = [
					//'%d%O' => 'Do', // %d%O => DDo funktioniert nicht
					'%d' => 'DD',
					'%m' => 'MM',
					'%Y' => 'YYYY',
					'%y' => 'YY',
					'%b' => 'MMM',
					'%B' => 'MMMM',
					'%O' => ''
				];
				break;
			case 'date-fns':
				$aMapping = [
					'%d%O' => 'do',
					'%d' => 'dd',
					'%m' => 'MM',
					'%Y' => 'yyyy',
					'%y' => 'yy',
					'%b' => 'MMM',
					'%B' => 'MMMM',
					'%O' => ''
				];
				break;
			default:
				if ($throwException) {
					throw new InvalidArgumentException('Unknown datepicker format!');
				}

		}

		if(!empty($aMapping)) {
			$dateFormat = str_replace(array_keys($aMapping), $aMapping, $dateFormat);
		}
	}

}
