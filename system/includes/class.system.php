<?php
 
/**
 * 
 */
class System {

	static $_sInterfaceLanguage = null;

	static $aConfig = [];
	
	private static $sInterface = 'backend';

	static $sLocale;
	
	static public function setInterface($sInterface) {
		self::$sInterface = $sInterface;
	}

	static public function getInterface() {
		return self::$sInterface;
	}

	/**
	 * @param string $sInterface
	 */
	public static function boot(string $sInterface = null) {

		// system_elements vorbereiten

		if($sInterface === null) {
			webdynamics::resetInstances();
			// trigger interface boot
			webdynamics::getInstance('frontend');
			webdynamics::getInstance('backend');
		} else {
			webdynamics::resetInstance($sInterface);
			// trigger interface boot
			webdynamics::getInstance($sInterface);
		}

		// Laravel

		$app = new \Illuminate\Foundation\Application(Util::getDocumentRoot());

		$bootstrappers = [
			#\Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
			\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, // TODO Caching einbauen (siehe \Illuminate\Foundation\Console\ConfigCacheCommand)
			#\Illuminate\Foundation\Bootstrap\HandleExceptions::class,
			\Illuminate\Foundation\Bootstrap\RegisterFacades::class,
			\Core\App\Bootstrap\RegisterProviders::class,
			\Illuminate\Foundation\Bootstrap\BootProviders::class, // $app->boot()
		];

		$app->bootstrapWith($bootstrappers);

		return $app;
	}

	/**
	 * Gibt einen Wert aus der system_config zurück
	 * 
	 * @global array $system_data
	 * @param string $sOption
	 * @return mixed
	 */
	public static function d($sOption, $mDefault=null) {
		global $system_data;

		if($sOption == 'allowed_languages') {
			return array(
				'de' => 'german',
				'en' => 'english',
				'fr' => 'french',
				'es' => 'spanish',
				'it' => 'italian',
				'tr' => 'turkish'
			);
		} elseif(isset($system_data[$sOption])) {
			if($sOption == 'backend_languages') {
				return json_decode($system_data[$sOption], 1);
			} else {
				return $system_data[$sOption];
			}			
		} elseif($mDefault !== null) {
			return $mDefault;
		}

		return false;

	}
	
	/**
	 * Speichert einen Wert in der system_config
	 * 
	 * @param string $sOption
	 * @param mixed $mValue
	 */
	public static function s($sOption, $mValue, $bSave=true) {
		global $system_data;

		if($bSave === true) {
			$aSql[] = [
				'c_key' => $sOption,
				'c_value' => $mValue
			];

			DB::insertMany('system_config', $aSql, true);
			$aLog = $aSql;
			$aLog['backtrace'] = Util::getBacktrace();

			Log::getLogger()->info('system_config update', $aLog);
		}

		// Wert ins Array schreiben
		$system_data[$sOption] = $mValue;

	}
	
	/**
	 * Gibt eine Instanz von der Klasse webdynamics zurück
	 *
	 * @return \webdynamics
	 */
	public static function wd() {

		if(self::$sInterface === 'backend') {
			$objWebDynamics = webdynamics::getInstance('backend');
		} else {
			$objWebDynamics = webdynamics::getInstance('frontend');
		}

		return $objWebDynamics;

	}

	/**
	 * CSS für Systemfarbe
	 * @return string 
	 */
	public static function getSystemColorStyles() {
		return '';
	}

	/**
	 * 
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getBackendLanguages($bForSelect=false, $sLanguage=null) {

		if($sLanguage === null) {
			$sLanguage = System::getInterfaceLanguage();
		}
		
		$sCacheKey = 'System::getBackendLanguages_'.$sLanguage;
		
		$aCache = WDCache::get($sCacheKey);

		if(!isset($aCache[(int)$bForSelect])) {

			$aBackendLanguages = System::d('backend_languages');
			if(empty($aBackendLanguages)) {
				$aBackendLanguages = array_keys(System::d('allowed_languages'));
			}
			
			$sSql = "
				SELECT
					`iso_639_1`,
					#field
				FROM
					`data_languages`
				ORDER BY
					#field
					";
			$aSql = array('field'=>'name_'.$sLanguage);
			$aLanguages = DB::getQueryRows($sSql, $aSql);

			// Auf verfügbare Sprachen reduzieren
			foreach($aLanguages as $iKey=>&$aLanguage) {
				$aLanguage = array_values($aLanguage);
				if(!in_array($aLanguage[0], $aBackendLanguages)) {
					unset($aLanguages[$iKey]);
				}
			}

			if($bForSelect === true) {
				$aTemp = $aLanguages;
				$aLanguages = array();
				foreach($aTemp as $aLanguage) {
					$aLanguages[$aLanguage[0]] = $aLanguage[1];
				}
			}
			
			$aCache[(int)$bForSelect] = $aLanguages;
			WDCache::set($sCacheKey, 86400, $aCache, false, 'System::getBackendLanguages');
		}

		return $aCache[(int)$bForSelect];
	}

	public static function setDebugmode() {
		global $system_data;

		if(!isset($_SERVER['REMOTE_ADDR'])) {
			$_SERVER['REMOTE_ADDR'] = 'console';
		}
		
		if(!empty($_REQUEST['X-Originating-IP'])) {
			$sKey = 'system_debugmode_'.$_REQUEST['X-Originating-IP'];
		} else {
			$sKey = 'system_debugmode_'.$_SERVER['REMOTE_ADDR'];
		}
		
		$iDebugmode = WDCache::get($sKey, true);

		if($iDebugmode !== null) {
			$system_data['debugmode'] = (int)$iDebugmode;
		} else {
			$system_data['debugmode'] = 0;

			// Debug-Modus kann in config.php gesetzt werden, falls das über die Software nicht geht (z.B. 500 beim Login)
			if(
				defined('APP_DEBUG') &&
				APP_DEBUG
			) {
				$system_data['debugmode'] = 2;
			}
		}

		// Wenn nicht im Debugmode dann keine Fehler anzeigen
		if($system_data['debugmode'] == 0) {
			error_reporting(0);
			ini_set('display_errors', '0');
		} else {
 
			if(
				$system_data['debugmode'] == 3 &&
				class_exists('\Whoops\Run')
			) {
				$oWhoops = new \Whoops\Run;

				if (\Whoops\Util\Misc::isAjaxRequest()) {

					$oJsonHandler = new Whoops\Handler\JsonResponseHandler();
					$oJsonHandler->setJsonApi(true);
					$oWhoops->pushHandler($oJsonHandler);

				} else {
					$oWhoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
				}

				$oWhoops->register();
			}
			
			if($system_data['debugmode'] == 4) {
				error_reporting(E_ALL);
			} else {
				error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);
			}
			ini_set('display_errors', '1');
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		}

		if(!defined('APP_DEBUG')) {
			// Konstante für Laravel setzen
			define('APP_DEBUG', (bool)$system_data['debugmode']);
		}

		return $system_data['debugmode'];
	}
	
	public static function readConfig() {

		$sSql = "
			SELECT 
				`c_key`,
				`c_value`
			FROM 
				`system_config`
			";

		$aSystemData = DB::getQueryPairs($sSql);

		if(!isset($aSystemData['debugmode'])) {
			$aSystemData['debugmode'] = 0;
		}
		if(!isset($aSystemData['db_query_report_limit'])) {
			$aSystemData['db_query_report_limit'] = 0.2;
		}
		if(!isset($aSystemData['software_name'])) {
			$aSystemData['software_name'] = 'Fidelo Framework';
		}
		if(!isset($aSystemData['software_producer'])) {
			$aSystemData['software_producer'] = 'Fidelo Software GmbH';
		}
		if(!isset($aSystemData['software_icon_prefix'])) {
			$aSystemData['software_icon_prefix'] = '';
		}
		if(!isset($aSystemData['update_server'])) {
			$aSystemData['update_server'] = 'update.webdynamics.de';
		}
		if(!isset($aSystemData['session_time'])) {
			$aSystemData['session_time'] = '240';
		}
		if(!isset($aSystemData['timezone'])) {
			$aSystemData['timezone'] = 'Europe/Berlin';
		}
		if(!isset($aSystemData['ignore_copyright'])) {
			$aSystemData['ignore_copyright'] = 0;
		}
		if(!isset($aSystemData['ignore_login_support'])) {
			$aSystemData['ignore_login_support'] = 0;
		}

		$mModeDir = $mModeFile = '';
		if(isset($aSystemData['chmod_mode_dir'])) {
			$mModeDir = $aSystemData['chmod_mode_dir'];
		}
		if(isset($aSystemData['chmod_mode_file'])) {
			$mModeFile = $aSystemData['chmod_mode_file'];
		}

		$aSystemData['chmod_mode_dir'] = Util::getModeOctalValue($mModeDir, 0755);
		$aSystemData['chmod_mode_file'] = Util::getModeOctalValue($mModeFile, 0755);

		self::$aConfig = $aSystemData;
		
		return $aSystemData;
	}

	static public function getConfig() {

		return self::$aConfig;
	}
	
	static public function getDefaultInterfaceLanguage(array $aLanguages=null) {

		if(
			$aLanguages === null &&
			self::$sInterface === 'backend'
		) {
			$aLanguages = \System::d('backend_languages');
			if(empty($aLanguages)) {
				$aLanguages = array_keys(\System::d('allowed_languages'));
			}
		}

		$sSystemLanguage = \Core\Helper\Agent::getBrowserLanguage($aLanguages, reset($aLanguages));
		
		if(self::$sInterface === 'frontend') {
			Core\Handler\CookieHandler::set("frontendlanguage", $sSystemLanguage);
		} else {
			Core\Handler\CookieHandler::set("systemlanguage", $sSystemLanguage);
		}

		return $sSystemLanguage;
	}
	
	/**
	 * Gibt die aktuelle Interface-Sprache zurück
	 * 
	 * @global array $session_data
	 * @global array $page_data
	 * @throws Exception 
	 * @return string
	 */
	public static function getInterfaceLanguage() {
		global $page_data;

		if(self::$_sInterfaceLanguage !== null) {
			$sInterfaceLanguage = self::$_sInterfaceLanguage;
		} else {

			// Im Backend
			if(self::$sInterface === 'backend') {
				$sInterfaceLanguage = self::d('systemlanguage');
			// Im Frontend
			} else {
				// Wenn Sprache gesetzt
				if(isset($page_data['language'])) {
					$sInterfaceLanguage = $page_data['language'];
				}
			}

		}

		if(empty($sInterfaceLanguage)) {
			throw new Exception('No interface language found!');
		}
		
		return $sInterfaceLanguage;

	}

	/**
	 * Setzt die Interface-Sprache (Wird nicht gespeichert!)
	 *
	 * @global $system_data
	 * @param string $sIso
	 * @throws Exception
	 */
	public static function setInterfaceLanguage($sIso) {
		global $session_data, $system_data, $page_data;

		if(
			!is_string($sIso)
		) {
			throw new Exception('No valid iso language string!'.Util::getBacktrace());
		}

		self::$_sInterfaceLanguage = $sIso;
		$session_data['language'] = $sIso;

		$sInterface = self::wd()->getInterface();

		// System-Variable auch setzen
		if($sInterface === 'backend') {
			$system_data['systemlanguage'] = $sIso;
		} else {
			$page_data['language'] = $sIso;
		}

	}

	public static function getAvailableLocales($sSearch=false, $sCharset=false) {

		$sCmd = "locale -a";

		$aReturn = array();
		$iReturn = 0;

		$sReturn = Update::executeShellCommand($sCmd);

		$aReturn = explode("\n", $sReturn);

		if($sCharset) {
			$sCharset = str_replace('-', '', $sCharset);
			$sCharset = str_replace(' ', '', $sCharset);
		}

		$aLocales = array();
		foreach((array)$aReturn as $sLine) {
			if(
				(
					empty($sSearch) ||
					stripos($sLine, $sSearch) !== false
				) &&
				(
					empty($sCharset) ||
					stripos($sLine, $sCharset) !== false
				) &&
				strpos($sLine, '_') !== false
			) {
				$aLocales[$sLine] = $sLine; 
			}
		}

		return $aLocales;

	}

	/**
	 * Setzt lokale Zeiteinstellung und Sprache.
	 */
	public static function setLocale() {
		global $system_data;

		$oWebDynamics = self::wd();
		$aLocale = array();

		$sInterface = System::wd()->getInterface();

		if($sInterface == 'backend') {

			// Darf nie geändert werden, weil sonst Kommazahlen überall falsch sind
			$aLocale['category'] = LC_TIME;
			
			$system_data['language_locales'] = self::getLanguageLocales();

			if(isset($system_data['language_locales'][self::getInterfaceLanguage()])) {
				$system_data['locale'] = $system_data['language_locales'][self::getInterfaceLanguage()];
			}
			
			$aLocale['system'] = $system_data['locale'];

		} else {

			$aLocale['category'] = LC_TIME;

			// Fallback
			if(empty($system_data['site_id'])) {
				$system_data['site_id'] = 1;
			}

			$oSite = \Cms\Entity\Site::getInstance($system_data['site_id']);
			$aLanguage = $oSite->getLanguage(self::getInterfaceLanguage());
			
			$aLocale['system'] = $aLanguage['locale'];

		}

		$aLocale['fallback'] = str_replace('utf8', 'UTF-8', $aLocale['system']);

		// Locale hook
		$oWebDynamics->executeHook('set_locale', $aLocale);
		$sSettedLocale = setlocale($aLocale['category'], $aLocale['system'], $aLocale['fallback']);

		self::$sLocale = $sSettedLocale;

		return $sSettedLocale;
	}

	public static function getLocaleConv() {
		
		$sCacheKey = __METHOD__.self::$sLocale;
		
		$aLocaleConv = WDCache::get($sCacheKey);
		
		if($aLocaleConv === null) {
			
			$sOriginalLocale = setlocale(LC_ALL, 0);

			// Temporär Locale für ALL setzen, damit man die Einstellungen holen kann
			setlocale(LC_ALL, self::$sLocale);

			$aLocaleConv = localeconv();

			setlocale(LC_ALL, $sOriginalLocale);
			
			WDCache::set($sCacheKey, (60*60*24*28), $aLocaleConv);
		}
		
		return $aLocaleConv;
	}
			
	
	/**
	 * Mapping: Language ISO => Locale-Code
	 * @return array
	 */
	public static function getLanguageLocales() {
		return array(
			'de' => 'de_DE.utf8',
			'en' => 'en_GB.utf8',
			'fr' => 'fr_FR.utf8',
			'es' => 'es_ES.utf8',
			'it' => 'it_IT.utf8'
		);
	}

	/**
	 * get the current System User
	 */
	public static function getCurrentUser(): ?User {

		$oAccess = Access::getInstance();

		if (
			$oAccess instanceof Access_Backend &&
			$oAccess->checkValidAccess() === true
		) {
			return Factory::executeStatic('User', 'getInstance', array($oAccess->id));
		}

		return null;

	}
	
	public static function checkValidLicense($aUser) {
		global $system_data;

		$sLicenseKey = $system_data['license'];//(strtoupper(dechex($system_data['license']*3)))."-".(strtoupper(dechex($system_data['license']*2)));

		$oLog = Log::getLogger();

		$errno = 0;
		$errstr = "";
		try {
			$fp = @fsockopen('ssl://'.$system_data['update_server'], 443, $errno, $errstr, 5);
		} catch(Exception $e) {
			$oLog->error('checkValidLicense - Connection exception', array('exception'=>$e->getMessage(), 'error'=>$errstr));
			return true;
		}
		// Wenn Verbindung nicht erfolgreich, dann trotzdem License OK.
		if (!$fp) {
			$oLog->error('checkValidLicense - Connection failed', array('error'=>$errstr));
			return true;
		} else {

			$sRequestUrl = "/license.php?version=".$system_data['version']."&key=".$sLicenseKey."&host=".\Util::getHost();

			if(isset($_SESSION['system']['sh'])) {
				$sRequestUrl .= '&sh='.(int)$_SESSION['system']['sh'];
			}
			if(isset($_SESSION['system']['sw'])) {
				$sRequestUrl .= '&sw='.(int)$_SESSION['system']['sw'];
			}
			if(isset($_SERVER["HTTP_USER_AGENT"])) {
				$sRequestUrl .= '&browser='.rawurlencode($_SERVER["HTTP_USER_AGENT"]);
			}

			$oUser = Factory::getInstance(User::class, $aUser['id']);
			$sRights = '';

			if ($oUser !== null) {
				$aRights = $oUser->getUserRights();
				if(
					!empty($aRights) &&
					$aRights !== null &&
					is_array($aRights)
				)
				$aRights = reset($aRights);
				$aRights = explode(',', $aRights['rights']);
				$sRights = implode('_', $aRights);
			}

			// E-Mail-Adresse und ID mitsenden
			$sRequestUrl .= '&user_email='.rawurlencode($aUser['email']).'&user_id='.rawurlencode($aUser['id']).'&user_name='.rawurlencode($aUser['username']).'&user_rights='.rawurlencode($sRights);

			// PHP-Version mitschicken
			$sRequestUrl .= '&php_version='.Util::getPHPVersion();

			fputs($fp, "GET ".$sRequestUrl." HTTP/1.0\r\nUser-Agent: Fidelo Update Service\r\nHost: ".$system_data['update_server']."\r\n\r\n");
			$sAnswer = "";
			while (!feof($fp)) {
				$sAnswer .= fgets($fp, 4096);
			}

			fclose($fp);

			// HTTP-Header abschneiden
			$sAnswer = substr($sAnswer, strpos($sAnswer,"\r\n\r\n")+4);

			$aResponse = array();
			parse_str($sAnswer, $aResponse);

			System::wd()->executeHook('check_valid_license', $aResponse);

			// Wenn Datei falsche Ausgabe hat, dann trotzdem License OK.
			if(
				!isset($aResponse['valid']) ||
				$aResponse['valid'] == 1
			) {
				$oLog->info('checkValidLicense - Check successful', (array)$aResponse);
				return true;
			} else {
				$oLog->error('checkValidLicense - Check failed', (array)$aResponse);
				return false;
			}

		}

	}
	
	/**
	 * @deprecated
	 * @param type $iPageId
	 * @param type $sEntry
	 * @param type $element_id
	 * @param type $element_name
	 * @param type $tbl_name
	 * @param type $field_name
	 * @param type $content
	 */
	public static function enterLog($iPageId, $sEntry, $element_id="", $element_name="", $tbl_name="", $field_name="", $content="") {
		\Log::enterLog($iPageId, $sEntry, $element_id, $element_name, $tbl_name, $field_name, $content);
	}

	public static function getErrorEmail() {
		return self::d('error_email');
	}
	
	static function getMinPasswordStrength() {

		$iStrength = (int)\System::d('password_strength', 2);

		return $iStrength;
	}

	/**
	 * @param string $sKey
	 */
	static public function deleteConfig(string $sKey) {

		$sSql = "
			DELETE FROM 
				`system_config`
			WHERE
				`c_key` = :c_key
			";

		$aSql = [
			'c_key' => $sKey
		];

		DB::executePreparedQuery($sSql, $aSql);

	}

}
