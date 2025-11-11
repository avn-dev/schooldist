<?php

class Ext_TC_Update {

	protected $_sServer = '';
	protected $_sFileServer = '';
	protected $_sLicence = '';
	protected $_bAllowed = true;
	public $aEmailLog = array();
	public $aErrors = array();
	protected $_sPort = false;

	public function __construct($sServer, $sLicence = null) {
		global $user_data, $system_data;

		ini_set("memory_limit", "1024M");
		set_time_limit(3600);

		if(empty($sLicence)){
			$sLicence = System::d('license');
		}
		
		$this->_sServer = $sServer;
		$this->_sLicence = $sLicence;

		switch($sServer) {
			case 'update':
				$this->_sFileServer = 'https://update.fidelo.com';
				break;
			case 'fidelo':
			case 'license':
			case 'core':
				$this->_sFileServer = 'https://fidelo.com';
				break;
			/*case 'dev_core': // test/debug
				$this->_sFileServer = 'https://dev.core.thebing.com';
				break;*/
			case 'test': // school
				$this->_sFileServer = 'https://test.school.fidelo.com';
				break;
			case 'test_agency':
				$this->_sFileServer = 'https://test.agency.fidelo.com';
				break;
			case 'prelive_agency':
				$this->_sFileServer = 'https://prelive.agency.fidelo.com';
				break;
			case 'prelive': // school
			default:
				$this->_sFileServer = 'https://test.school.fidelo.com';
				break;
		}

		// Wenn keine Licence gefunden wurde darf auch nichts geschehen
		if(empty($this->_sLicence)){
			$this->_bAllowed = false;
		}

	}

	public function sendEmailLog() {

		$log = \Log::getLogger('update');
		$log->info('Update debug', [$_SERVER['HTTP_HOST'], $this->aEmailLog]);

	}

	protected function _updateSystemConfig($sField, $mValue) {

		System::s($sField, $mValue);

	}

	// Holt die beschreibung einer Tabelle von thebing.com
	protected function describeTable($sTable,$sUrl = '/system/extensions/thebing/install/database.php') {
		$sFullUrl = $this->_sFileServer.$sUrl.'?task=describe&table='.$sTable.'&licence='.$this->_sLicence;
		$json = $this->_getFileContents($sFullUrl);
		$aBack = json_decode($json, true);
		return $sHtml;
	}

	// holt die complete Datenbank Structure von Thebing.com
	protected function getNewDatabaseStructure($sUrl = '/system/extensions/thebing/install/database.php') {

		$sFullUrl = $this->_sFileServer.$sUrl.'?task=getStructure&licence='.$this->_sLicence;

		$sHtml = $this->_getFileContents($sFullUrl);

		return $sHtml;

	}

	/**
	 * Schreibt eine Datenbank Tabelle komplett neu
	 *
	 * @param string $sTable
	 * @param bool $bForce
	 * @param bool $bUsePreparedStatement
	 * @return bool
	 */
	public function writeDBTableNew($sTable, $bForce = false, $bUsePreparedStatement=true) {
		global $system_data;

		if($bForce){
			$this->_bAllowed = true;
		}

		if($this->_bAllowed == false){
			return false;
		}

		$sFullUrl = $this->_sFileServer.'/system/extensions/tc/system/update/database.php?task=getCreateTable&table='.$sTable;
		$sTableCreateQuery = $this->_getFileContents($sFullUrl);

		$sFullUrl = $this->_sFileServer.'/system/extensions/tc/system/update/database.php?task=getInsert&table='.$sTable;
		$sSerialize = $this->_getFileContents($sFullUrl);

		$aData = json_decode($sSerialize, true);

		if(
			!empty($sTableCreateQuery) &&
			is_array($aData) &&
			!empty($aData)
		) {

			$aSqlTable = array('table'=>$sTable);

			$sTempName = Ext_TC_Util::generateRandomString(8);
			$sTableCreateQuery = str_replace($sTable, $sTempName, $sTableCreateQuery);
			DB::executeQuery($sTableCreateQuery);

			// Einträge anlegen
			if($bUsePreparedStatement) {
				foreach($aData as $aRow) {
					DB::insertData($sTempName, $aRow);
				}
			} else {
				// Es gibt Tabellen, die bei PreparedStatements total langsam sind
				DB::insertMany($sTempName, $aData);
			}

			$sSql = "DROP TABLE #table";
			DB::executePreparedQuery($sSql, $aSqlTable);
			$sSql = "RENAME TABLE #table1 TO #table2";
			$aSql = array('table1'=>$sTempName, 'table2'=>$sTable);
			DB::executePreparedQuery($sSql, $aSql);

			$this->aEmailLog[] = 'TRUNCATE Table: '.$sTable.', Allowed: '.(int)$this->_bAllowed.' (New: '.count($aData).')';

			return true;

		}

		return false;

	}

	// Schreibt einen Log mit dem Zeitpunkt
	protected function writeUpdateLog($iSuccess = 1){

		$sSql = " INSERT INTO
						`kolumbus_update_log`
					SET
						`created` = NOW(),
						`success` = :success";
		$aSql = array('success' => $iSuccess);
		DB::executePreparedQuery($sSql,$aSql);

	}

	// Holt den Dateiinhalt von einer Datei von Thebing.com ( Zend verschlüsselt!)
	protected function getFileContent($sFile, $sUrl = '/system/extensions/thebing/install/file.php'){

		if(!empty($this->_sLicence)){
			$sFullUrl = $this->_sFileServer.$sUrl.'?file='.$sFile.'&licence='.$this->_sLicence;
			$sHtml = $this->_getFileContents($sFullUrl);
			return $sHtml;
		} else {
			echo 'File Server Error<br/>';
		}

	}

	// Schreibt eine Datei mit entsprechendem inhalt
	protected function writeFile($sFile, $sFileContent){

		return self::writeNewFile($sFile, $sFileContent);
	}


	public static function writeNewFile($sFile, $sFileContent){

		try {

			$dirs=explode("/", $sFile);
			$path='';
			for($i=0;$i<count($dirs)-1;$i++) {
				$path.=$dirs[$i].'/';
				if(!is_dir(\Util::getDocumentRoot().$path)) {
					mkdir (\Util::getDocumentRoot().$path, 0777);
					chmod (\Util::getDocumentRoot().$path, 0777);
				}
			}

			$sStart	= "#"."##START"."###";
			$sEnd	= "#"."##END"."###";
			if(
				mb_strpos($sFileContent, $sStart) !== false &&
				mb_strpos($sFileContent, $sEnd) !== false
			) {
				$pos1 = mb_strpos($sFileContent, $sStart);
				$pos2 = mb_strpos($sFileContent, $sEnd);
				$len1 = mb_strlen($sStart);
				$len2 = mb_strlen($sEnd);
				$sFileContent = mb_substr($sFileContent,($pos1+$len1),($pos2-$pos1-$len1));
				$handle = fopen(\Util::getDocumentRoot().$sFile, 'wb');

				$iBytesWritten = fwrite($handle, $sFileContent);
				fclose($handle);
				$bChmod = @chmod(\Util::getDocumentRoot().$sFile, 0777);

				if(
					$iBytesWritten < 1 &&
					!$bChmod
				) {
					// reportMessage funktioniert u.U. nicht
					@mail(
						'thebingupdate@p32.de',
						'Thebing Update Server: Access File konnte nicht geschrieben werden! ('.$_REQUEST['cms_licence'].')',
						print_r($_SERVER, 1).print_r(debug_backtrace(), 1)
					);

				}

			}

		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Tabellenstruktur zurückliefern
	 *
	 * @param $sTable
	 * @return array
	 */
	public static function getDescribeTable($sTable){
		$sSql = "DESCRIBE #table";
		return DB::getQueryData($sSql, ['table' => $sTable]);
	}

	/**
	 * SQL-Query zum Erzeugen der Tabelle
	 *
	 * @param string $sTable
	 * @return string
	 */
	public static function getCreateTable($sTable) {
		$sSql = "SHOW CREATE TABLE #table";
		$aCreate = DB::getQueryRow($sSql, ['table' => $sTable]);
		return $aCreate['Create Table'];
	}

	/**
	 * Daten der Tabelle zurückliefern
	 *
	 * @param string $sTable
	 * @return array
	 */
	public static function getInsertTableData($sTable) {
		$sSql = " SELECT * FROM #table ";
		$aResult = (array)DB::getQueryRows($sSql, ['table' => $sTable]);
		return $aResult;
	}

	protected function _getFileContents($sFile, $aPost=array()) {

		if($this->_sServer == 'update') {
			$sUrl = $sFile;
			$aPost['key'] = $this->_sLicence;
			$aPost['host'] = \Util::getHost();
			$aPost['php_version'] = Ext_TC_Util::getPHPVersion();
			$aPost['version'] = System::d('version');
		} else {
			$sUrl = $sFile.'&licence='.$this->_sLicence.'&version='.System::d('version');
		}

		$rCurl = curl_init();
		curl_setopt($rCurl, CURLOPT_USERAGENT, 'Fidelo Update Service');
		curl_setopt($rCurl, CURLOPT_URL, $sUrl);
		// Kann sicherheitsrelevant sein, daher muss geprüft werden
		curl_setopt($rCurl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($rCurl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rCurl, CURLOPT_CONNECTTIMEOUT, 2);
		
		if($this->_sPort !== false){
			curl_setopt($rCurl, CURLOPT_PORT, $this->_sPort);
		}
		
		if(
			!empty($aPost) && 
			is_array($aPost)
		) {
			curl_setopt ($rCurl, CURLOPT_POST, 1);
			curl_setopt ($rCurl, CURLOPT_POSTFIELDS, $aPost);
		}

		$sContent = curl_exec($rCurl);

		$bSuccess = true;
		if($sContent === false) {
			$bSuccess = false;
		}

		$this->aEmailLog[$sFile] = $bSuccess;

		return $sContent;

	}

	public function getFileContents($sFile, $aPost=array()){

		$sFile = $this->_sFileServer.$sFile;

		return $this->_getFileContents($sFile, $aPost);

	}

	/**
	 * Rechte vom Office laden und neu schreiben
	 * @return bool
	 */
	public static function updateAccessDatabase() {

		$api = new Licence\Service\Office\Api();

		$response = $api->getAccessRights();

		if(!$response->isSuccessful()) {
			return false;
		}

		$mappings = [
			'access' => 'tc_access',
			'access_sections' => 'tc_access_sections',
			'access_sections_categories' => 'tc_access_sections_categories',
		];

		$backupKey = 'fix_access_update_structure';

		foreach($mappings as $key => $localTable) {

			$table = $response->get($key, []);

			if(empty($table) || !isset($table['data'])) {
				continue;
			}

			//if(isset($table['structure']) && is_array($table['structure'])) {
			//	// Server hat eine Struktur mitgeschickt
			//	$structure = $table['structure'];
			//} else {
				// Struktur der lokalen Tabelle nehmen
				$structure = \DB::getQueryRow("SHOW CREATE TABLE #table", ['table' => $localTable]);
			//}

			if(isset($structure['Table']) && isset($structure['Create Table'])) {

				// temporäre Tabelle anlegen und mit den neuen Rechten füllen
				$tmpTable = \Util::generateRandomString(8).'_'.$localTable;
				$query = str_replace($structure['Table'], $tmpTable, $structure['Create Table']);

				\DB::executeQuery($query);

				foreach($table['data'] as $row) {
					\DB::insertData($tmpTable, $row);
				}

				if(!\System::d($backupKey)) {
					\Util::backupTable($localTable);
				}

				// Alte Tabelle löschen und die temporäre Tabelle in richtige Tabelle umbennen
				DB::executePreparedQuery("DROP TABLE #table", ['table' => $localTable]);
				DB::executePreparedQuery("RENAME TABLE #table1 TO #table2", ['table1' => $tmpTable, 'table2' => $localTable]);

			}

		}

		\System::s($backupKey, 1);

		//$oUpdate = new \Ext_TC_Update('core');
		//$oUpdate->writeDBTableNew('tc_access');
		//$oUpdate->writeDBTableNew('tc_access_sections');
		//$oUpdate->writeDBTableNew('tc_access_sections_categories');

		return true;
	}

	/**
	 * Frontend-Übersetzungen aktualisieren (abgleichen)
	 */
	public function updateFrontendTranslations() {

		DB::begin(__METHOD__);

		// Da die Felder in der system_translations keinen Präfix haben, werden alle möglichen Präfixe benötigt
		$oLocaleService = new Core\Service\LocaleService;
		$aLocales = $oLocaleService->getInstalledLocales();

		// system_translations vom Update-System holen
		$sFullUrl = $this->_sFileServer.'/system/extensions/tc/system/update/database.php?task=getInsert&table=system_translations';
		$sSerialize = $this->_getFileContents($sFullUrl);
		$aRemoteTranslationsTmp = json_decode($sSerialize, true);
		$aRemoteTranslations = [];
		foreach($aRemoteTranslationsTmp as $aRemoteTranslation) {
			$aRemoteTranslations[crc32($aRemoteTranslation['code'])] = $aRemoteTranslation;
		}

		if(empty($aRemoteTranslations)) {
			throw new RuntimeException('No frontend translations received from remote system!');
		}

		/**
		 * Sprach-Spalten aus der system_translations extrahieren (diese haben keinen Präfix)
		 *
		 * @param $aFields
		 * @return array
		 */
		$oGetLanguageFields = function($aFields) use($aLocales) {
			$aFields = array_filter($aFields, function($sField) use($aLocales) {
				return isset($aLocales[$sField]);
			});

			// Indonesisch ist so eben nicht möglich
			unset($aFields[array_search('id', $aFields)]);

			return $aFields;
		};

		// Sprachen ermitteln, welche auf beiden Systemen in der Tabelle vorhanden sind
		$aLocalTableDescribe = DB::describeTable('system_translations', true);
		$aLocalTableLanguages = $oGetLanguageFields(array_keys($aLocalTableDescribe));
		$aRemoteTableLanguages = $oGetLanguageFields(array_keys(reset($aRemoteTranslations)));
		$aCommonLanguages = array_intersect($aLocalTableLanguages, $aRemoteTableLanguages);

		$sSql = "
			SELECT
				CRC32(`code`),
				`system_translations`.*
			FROM
				`system_translations`
		";
		$aLocalTranslations = DB::getQueryRowsAssoc($sSql);

		// Abgleich
		foreach($aRemoteTranslations as $iRemoteTranslationCrc => $aRemoteTranslation) {

			if($aRemoteTranslation['active'] == 0) {
				continue;
			}

			// Wenn Übersetzung existiert: Gegebenfalls aktualisieren
			if(isset($aLocalTranslations[$iRemoteTranslationCrc])) {

				$aLocalTranslation = $aLocalTranslations[$iRemoteTranslationCrc];
				$aUpdateData = [];

				// Veränderte Sprachfelder aktualisieren
				foreach($aCommonLanguages as $sLanguage) {
					if($aRemoteTranslation[$sLanguage] != $aLocalTranslation[$sLanguage]) {
						$aUpdateData[$sLanguage] = $aRemoteTranslation[$sLanguage];
					}
				}

				// Wenn Übersetzung lokal verändert wurde: Bereits gefüllte Sprachen nicht mehr verändern
				if($aLocalTranslation['update_lock']) {
					foreach($aLocalTableLanguages as $sLanguage) {
						if(!empty($aLocalTranslation[$sLanguage])) {
							unset($aUpdateData[$sLanguage]);
						}
					}
				}

				if(!empty($aUpdateData)) {
					DB::updateData('system_translations', $aUpdateData, ['code' => $aRemoteTranslation['code']]);
				}

			} else {

				// Übersetzung existiert nicht, daher komplett neu anlegen
				$aInsertData = [
					'changed' => $aRemoteTranslation['changed'],
					'created' => $aRemoteTranslation['created'],
					'used' => $aRemoteTranslation['used'],
					'active' => 1,
					'update_lock' => 0,
					'trace' => $aRemoteTranslation['trace'],
					'created_language' => $aRemoteTranslation['created_language'],
					'html' => $aRemoteTranslation['html'],
					'key' => $aRemoteTranslation['key'],
					'code' => $aRemoteTranslation['code']
				];

				// Alle Sprachfelder aller gemeinsam vorhandener Sprachen setzen
				foreach($aCommonLanguages as $sLanguage) {
					$aInsertData[$sLanguage] = $aRemoteTranslation[$sLanguage];
				}

				DB::insertData('system_translations', $aInsertData);

			}

			unset($aLocalTranslations[$iRemoteTranslationCrc]);

		}

		// Wenn die Übersetzung nicht auf dem System verändert wurde: Übersetzung löschen
		foreach($aLocalTranslations as $aLocalTranslation) {
			if(!$aLocalTranslation['update_lock']) {
				$sSql = " DELETE FROM `system_translations` WHERE `code` = :code ";
				DB::executePreparedQuery($sSql, ['code' => $aLocalTranslation['code']]);
			}
		}

		DB::commit(__METHOD__);

	}
	
}
