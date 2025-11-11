<?php

class L10N {
	
	/**
	 * @staticvar array 	Cache Array from the Translation Database
	 */
 	static $aCache = array();
 	
 	/**
	 * @staticvar string 	Default Language
	 */
 	static protected $sDefaultLanguage = "code";
	static protected $sCodeField = "code";
 	
 	/**
	 * @staticvar string 	System language
	 */
 	protected $sLanguage;
 	
 	/**
	 * @staticvar int		id of the current file 
	 */
 	static $iFileId = 0;
 	static $aFileId = array();
 	
 	static $mUseFileId = 0;
 	
 	 /**
	 * @staticvar string		Databasetable with translation Data 
	 */
 	protected $_sDatabaseTable = 'language_data';
 	
 	static protected $aInstance = array();

	// Schon abgefragte Files werden hier zwischen gespeichert
	protected $_aFileCache = array();

	private $sInsertLockKey = 'l10n_insert_translation_lock';

	public $aUsedTranslations = [];
	
	/**
	 *
	 * @return L10N
	 */
 	static public function getInstance($sLang = null) {
	
		if($sLang === null){
			$sLang	= System::getInterfaceLanguage();
		}
		
		$sInterface = System::wd()->getInterface();

 		if(
			!isset(self::$aInstance[$sLang]) ||
			!isset(self::$aInstance[$sLang][$sInterface])
		) {
			self::$aInstance[$sLang][$sInterface] = new self($sLang);
		}
		
		return self::$aInstance[$sLang][$sInterface];

	}

	/**
	 * Set static Variables
	 * - System Language
	 * - File Id
	 * - Cache Array
	 */
 	public function __construct($sLang = null, $sInterface=null) {

 		DB::setResultType(MYSQL_ASSOC);

		// Kann wieder verwendet werden, da $session_data['backend'] in der wd()-Methode überprüft wird
		if($sInterface === null) {
			$sInterface = System::wd()->getInterface();
		}

		if($sLang == null){
			$sLang	= System::getInterfaceLanguage();
		}
		
 		$this->sLanguage = $sLang;
		
 		if($sInterface == 'backend') {
 			$this->_sDatabaseTable = 'language_data';
 		} else {
 			$this->_sDatabaseTable = 'system_translations';
 		}
 		
 	}
 	
	/**
	 * Erzeugt einen eindeutigen Cache Key
	 * 
	 * @param int $iFileId
	 * @return string 
	 */
	protected function _getCacheKey($iFileId, $sLanguage = '') {
		
		$aCacheKey = array();
		$aCacheKey[] = $this->_sDatabaseTable;
        if($sLanguage != ""){
            $aCacheKey[] = $sLanguage;
        } else {
           $aCacheKey[] = $this->sLanguage; 
        }
		$aCacheKey[] = $iFileId;
 		$aCacheKey[] = self::$sDefaultLanguage;
 		$aCacheKey[] = self::$sCodeField;
		
		$sCacheKey = implode('_', $aCacheKey);

		return $sCacheKey;
		
	}
	
	public static function getSelectAddon($sLanguage, $sDefaultLanguage, $sCodeField) {

		// If SystemLanguage the same as the Default select only on Column
		if($sDefaultLanguage == $sLanguage) {
			$sSelectAddon = "
							`t`.#language,
							`t`.#default_language
							";
		} else {
			$sSelectAddon = "IF(`t`.#language != '',
								`t`.#language,
								`t`.#default_language
							) #language,
							`t`.#default_language";
		}
		
		// Wenn Default != Code
		if($sDefaultLanguage != $sCodeField) {
			$sSelectAddon .= ",
							`t`.#code_field
				";
		}

		return $sSelectAddon;
	}
	
 	/**
 	 * Select all Translation Entries
 	 * @return array	Array  [file_id][CRC Key][column]
 	 */
 	protected function _getData($iFileId=0) {

 		$aSql = array();

 		if(isset(self::$aCache[$this->_sDatabaseTable][$this->sLanguage][(int)$iFileId])) {
 			return;
 		}
	
		// Sicherstellen, dass eine FileID nicht mehrmals abgefragt wird
		self::$aCache[$this->_sDatabaseTable][$this->sLanguage][(int)$iFileId]['XYZ'] = true;

		// Wenn Backend
		if($this->_sDatabaseTable == 'language_data') {

			// Alle 0 Einträge holen
			if($iFileId != 0) {
				$this->_getData(0);
			}

		}
		
		$sCacheKey = $this->_getCacheKey($iFileId);
		
		$aTranslations = WDCache::get($sCacheKey);

		// Wenn kein Cache-Eintrag gefunden wurde
		if($aTranslations === null) {

			$aTranslations = array();
			
			$aSql['language'] = $this->sLanguage;
			$aSql['default_language'] = self::$sDefaultLanguage;
			$aSql['code_field'] = self::$sCodeField;

			$sSelectAddon = self::getSelectAddon($this->sLanguage, self::$sDefaultLanguage, self::$sCodeField);

			// Select all Entries
			if($this->_sDatabaseTable == 'language_data') {

				$sSQL = "
					SELECT
						`file_id`, `use`, ".$sSelectAddon."
					FROM
						#table `t`
					WHERE
						`file_id` = :file_id AND
						`active` = 1
						";

				$aSql['file_id'] = (int)$iFileId;

			} else {
				$sSQL = "
					SELECT 
						".$sSelectAddon." ,
						0 as `file_id`,
						1 as `use`
					FROM 
						#table `t`
					WHERE
						`active` = 1
					";
			}
			$aSql['table'] = $this->_sDatabaseTable;

			$aData = DB::getPreparedQueryData($sSQL, $aSql);

			// Make an Array with CRC Key
			$aDataArray = array();
			foreach((array)$aData as $iKey => $aValue){

				$iNewKey = crc32($aValue[self::$sCodeField]);

				// Wenn Übersetzung nicht genutzt werden soll und es eine globale Übersetzung gibt, wird diese genutzt
				if(
					$this->_sDatabaseTable == 'language_data' &&
					$iFileId != 0 &&
					$aValue['use'] == 0 &&
					isset(self::$aCache[$this->_sDatabaseTable][$this->sLanguage][0][$iNewKey])
				) {
					$aTranslations[$iNewKey] = self::$aCache[$this->_sDatabaseTable][$this->sLanguage][0][$iNewKey];
				} else {
					$aTranslations[$iNewKey] = $aValue[$this->sLanguage];
				}

			}

			$aHook = array(
				'translations' => &$aTranslations,
				'l10n' => $this,
				'file_id' => $iFileId,
				'default_language' => self::$sDefaultLanguage,
				'code_field' => self::$sCodeField
			);

			System::wd()->executeHook('l10n_getdata', $aHook);
			unset($aHook);

			// 30 Minuten speichern
			WDCache::set($sCacheKey, (30*60), $aTranslations, false, 'L10N::getData');

		}

		self::$aCache[$this->_sDatabaseTable][$this->sLanguage][(int)$iFileId] = $aTranslations;

 	}
 	
 	
 	/**
 	 * If "true" the function return the ID of The current File
 	 * If not they return 0
 	 * 
 	 * @return int	id of file
 	 */
 	protected function _getFileId($mOptionalDescription = false){

		$sFile = $_SERVER['PHP_SELF'];
	
		if(
			!is_numeric($mOptionalDescription) &&
			!is_bool($mOptionalDescription) &&
			$mOptionalDescription != ""
		) {
			$sFile = $mOptionalDescription;
		} else if(
			$mOptionalDescription === 0 ||
			$mOptionalDescription === false
		) {
			return 0;
		}

		if(empty($this->_aFileCache[$sFile])) {

			$sSQL = "SELECT
						`id`
					FROM
						`language_files`
					WHERE
						`file` = :file";
			$aSQL = array('file'=>$sFile);
			$aData = DB::getPreparedQueryData($sSQL,$aSQL);

			if(count($aData) <= 0){
				$iBack = $this->_insertFileEntry($sFile);
			} else {
				$iBack = $aData[0]['id'];
			}

			$this->_aFileCache[$sFile] = $iBack;

		}

		$iBack = $this->_aFileCache[$sFile];
 
		return (int)$iBack;

 	}
 	
 	/**
 	 * Insert the Data of the current File into Database as new Entry
 	 * @return int 	ID of the new Entry File
 	 */
 	protected function _insertFileEntry($sFile){

 		$sSQL = "INSERT INTO 
					`language_files` 
				SET 
					`file` = :file
					";
 		$aSQL = array('file'=>$sFile);
 		$aData = DB::executePreparedQuery($sSQL,$aSQL);
 		$iId = DB::fetchInsertID();
 		return (int)$iId;

 	}

	/**
	 * Insert a new Translationstring
	 *
	 * @param $sTranslate
	 * @param $iCrcKey
	 * @param int $iFileId
	 */
 	protected function _insertData($sTranslate, $iCrcKey, $iFileId = 0) {

		// Doppelte Übersetzungen (Race Condition durch parallele Prozesse) vermeiden
		$iLock = WDCache::set($this->sInsertLockKey, 5, $iCrcKey);
		if($iLock === WDCache::REPLACED) {

			$oLogger = Log::getLogger();
			$oLogger->error('L10N insert race condition for translation "'.$sTranslate.'"', array(Util::getBacktrace()));

			return;
		}

		$aSearchResult = array();
 		$aSql = array(
			'table' => $this->_sDatabaseTable,
			'language' => self::$sCodeField,
			'text' => $sTranslate
		);

		$aSqlSearch = $aSql;

		$sSqlAddon = "";
		if($this->_sDatabaseTable == 'language_data') {
			$sSqlAddon = " `file_id` = :file AND ";
			$aSqlSearch['file'] = $iFileId;
		}

		// Suchen, ob es diese Übersetzung schon gibt
		$sSqlSearch = "
			SELECT
				*
			FROM
				#table
			WHERE
				`active` = 1  AND
				".$sSqlAddon."
				#language COLLATE utf8mb4_bin = :text
		";
		try {
			$aResult = DB::getQueryRow($sSqlSearch, $aSqlSearch);
		} catch (Exception $e) {
			// Probably because collation of table or column not yet changed to utf8mb4. See Collation_Check.
			$oLogger = Log::getLogger('l10n');
			$oLogger->error($e->getMessage(), array(Util::getBacktrace()));
			return;
		}
		if(!empty($aResult)) {
			// Race Condition verhindern, wenn das Array mit den Übersetzungen noch nicht geladen ist
			// Wenn man beispielsweise zwei Mal je zwei L10N ausführt, das andere aber schläft, werden Übersetzungen verdoppelt
			return;
		}

		// Prüfen, ob der Text mit einer anderen file_id verfügbar ist
 		if(
			$iFileId > 0 &&
			$this->_sDatabaseTable == 'language_data'
		) {

 			$sSqlSearch = "
				SELECT
					*
				FROM
					#table
				WHERE
					`active` = 1  AND
					`file_id` != :file AND
					#language COLLATE utf8mb4_bin = :text
				ORDER BY
					`file_id`
				";
			try {
				$aSearchResult = DB::getPreparedQueryData($sSqlSearch, $aSqlSearch);
			} catch (Exception $e) {
				// Probably because collation of table or column not yet changed to utf8mb4. See Collation_Check.
				$oLogger = Log::getLogger('l10n');
				$oLogger->error($e->getMessage(), array(Util::getBacktrace()));
				return;
			}
 		}

 		if(!empty($aSearchResult)) {

			// Wenn nur einer gefunden wurde und eine FileID hat
			if(
				$iFileId > 0 &&
				count($aSearchResult) == 1 &&
				$aSearchResult[0]['file_id'] > 0
			) {

				$aFileIds = array(
					array(
						'file_id'=>0,
						'use'=>1
					),
					array(
						'file_id'=>$iFileId,
						'use'=>0
					)
				);

				DB::updateData($this->_sDatabaseTable, array('use'=>0), "`id` = ".(int)$aSearchResult[0]['id']);

			} elseif(
				$iFileId > 0 &&
				$aSearchResult[0]['file_id'] == 0
			) {
				$aFileIds = array(
					array(
						'file_id'=>$iFileId,
						'use'=>0
					)
				);
			} else {
				$aFileIds = array(
					array(
						'file_id'=>$iFileId,
						'use'=>1
					)
				);
			}

			foreach((array)$aFileIds as $aFileId) {

				$aSql['file'] = $aFileId['file_id'];

				$sSQL = "INSERT INTO
							#table
						SET
							";

				foreach((array)$aSearchResult[0] as $sColumn =>$mValue) {

					if(
						!is_numeric($sColumn) &&
						$sColumn != 'id' &&
						$sColumn != 'file_id' &&
						$sColumn != self::$sCodeField &&
						$sColumn != 'created' &&
						$sColumn != 'used' &&
						$sColumn != 'use' &&
						$sColumn != 'trace' &&
						$sColumn != 'created_language'
					) {
						$sSQL .= " #name_".$sColumn." =  :value_".$sColumn.", ";
						$aSql["name_".$sColumn] = $sColumn;
						$aSql["value_".$sColumn] = $mValue;
					}

				}

				if($this->_sDatabaseTable == 'language_data') {
					$sSQL .= "
								`file_id` = :file,
								`use` = :use,";
					$aSql['use'] = $aFileId['use'];
				}
				
				$sSQL .= "`trace` = :backtrace, `created_language` = :created_language, `used` = NOW(), #language = :text, created = NOW()";
				
				$aSql['backtrace'] = Util::getBacktrace();
				$aSql['created_language'] = $this->sLanguage;

				DB::executePreparedQuery($sSQL, $aSql);

			}

 		} else {

	 		$sSQL = "INSERT INTO 
						#table 
					SET 
						";
			if($this->_sDatabaseTable == 'language_data'){
				$sSQL .= "
							`file_id` = :file,
							";
				$aSql['file'] = $iFileId;
				
			}
			$sSQL .= "`trace` = :backtrace, `created_language` = :created_language, `used` = NOW(), #language = :text, created = NOW()";
					
			$aSql['backtrace'] = Util::getBacktrace();
			$aSql['created_language'] = $this->sLanguage;

			DB::executePreparedQuery($sSQL, $aSql);

 		}
		
		if(!isset(self::$aCache[$this->_sDatabaseTable])) {
			self::$aCache[$this->_sDatabaseTable] = array();
		}

		// Wert in jeder Sprache ergänzen
		foreach(self::$aCache[$this->_sDatabaseTable] as $sLanguage=>$aFiles) {
			self::$aCache[$this->_sDatabaseTable][$sLanguage][(int)$iFileId][$iCrcKey] = $sTranslate;
            // Cache leeren
            $sCacheKey = $this->_getCacheKey($iFileId, $sLanguage);
            WDCache::delete($sCacheKey);
        }

		WDCache::delete($this->sInsertLockKey);

 	}

 	/**
 	 * This Function return the Translation text
 	 * @param String 	The Translation text 
 	 * @param boolean 	True for File Translation Data, False for global Data
 	 *  
 	 */
 	public function translate($sTranslation, $mUseFileId = false, $bAddslashes = false) {

		// Fehler abfangen
		if(
			is_null($mUseFileId) &&
			System::d('debugmode')
		) {
			throw new Exception('Wrong context identifier in L10N (Phrase "'.$sTranslation.'")!');
		} elseif(is_null($mUseFileId)) {
			$mUseFileId = 0;
		}

		// Im Frontend gibt es keine File Id!
 		if($this->_sDatabaseTable != 'language_data') {
 			$mUseFileId = 0;
 		}

 		// Create CRC32 from Translation String
		$sKey = $sTranslation;
 		$iCrcKey = crc32($sTranslation);
		
 		$iFileId = $this->_getFileId($mUseFileId);
		
 		$this->_getData($iFileId);

		// Wenn globale Übersetzung gefragt ist
		if(
			!$mUseFileId ||
			$mUseFileId == "" ||
			$mUseFileId === false ||
			$mUseFileId === 0
		) {

			$iFileId = 0;

		}

		// Wenn eine Übersetzung für die angegebene File ID da ist
		if(
			array_key_exists($iCrcKey, (array)self::$aCache[$this->_sDatabaseTable][$this->sLanguage][$iFileId]) &&
			!empty(self::$aCache[$this->_sDatabaseTable][$this->sLanguage][$iFileId][$iCrcKey])
		) {
			$sTranslation = self::$aCache[$this->_sDatabaseTable][$this->sLanguage][$iFileId][$iCrcKey];
		}

 		// If the Translation Text doesn't exit, return the requestet String
		if(!array_key_exists($iCrcKey, (array)self::$aCache[$this->_sDatabaseTable][$this->sLanguage][$iFileId])) {
			$this->_insertData($sTranslation, $iCrcKey, $iFileId);
		}

 		if($bAddslashes == true){
			$sTranslation = addslashes($sTranslation);
		}

		// Im Translation-Mode: used aktualisieren
		if(
			\Core\Handler\SessionHandler::getInstance()->get('system_translation_mode') === true &&
			$this->_sDatabaseTable === 'language_data'
		) {
 			$this->updateTranslationUsage($sKey, $iFileId);
		}

		if(
			System::d('debugmode') == 2 &&
			\Core\Handler\SessionHandler::getInstance()->get('system_translation_mode') === true
		) {
			if($this->_sDatabaseTable == 'language_data') {
				$sTranslation = '[BE:'.$this->sLanguage.']'.$sTranslation;
			} else {
				$sTranslation = '[FE:'.$this->sLanguage.']'.$sTranslation;
			}
		}

		return $sTranslation;
 	}

	/**
	 * This Funktion print the Translation text with function echo
	 * @param String 	The Translation text 
 	 * @param boolean 	True for File Translation Data, False for global Data
 	 * @param boolean 	True for Files who needs Addslashes ( .js for Italien Language ,... )
	 */
	public function printTranslation($sTranslation, $bUseFileId = false, $bAddslashes = false) {
		echo $this->translate($sTranslation, $bUseFileId, $bAddslashes);
	}

	static public function t($sTranslation, $mUseFileId = false, $bAddslashes = false) {
		
		if(empty($sTranslation))
		{
			return $sTranslation;
		}
		
		$oL10N = self::getInstance();

		$sTranslation = $oL10N->translate($sTranslation, $mUseFileId, $bAddslashes);
		// Im Übersetzungsmodus werden weitere Infos angezeigt
//		if($_SESSION['system']['translation_mode']) {
//			$sTranslation = '['.\Util::convertHtmlEntities($mUseFileId).'] '.$sTranslation.'';
//		}
		return $sTranslation;
	}

	/**
	 * Setzt die Defaultsprache
	 */
	static public function setDefaultLanguage($sLanguage) {

		self::$sDefaultLanguage = $sLanguage;

	}

	/**
	 * used aktualisieren
	 *
	 * Eigentlich wäre es wesentlich besser, wenn die verwendeten Translations zuerst
	 * gesammelt werden würden und dann alle zusammen ins PP eingetragen werden. Das
	 * würde theoretisch auch mit SequentialProcessing + ParallelProcessing funktionieren,
	 * allerdings wird das SequentialProcessing nicht überall aufgerufen, da es keine
	 * Shutdown-Funktion in V5 gibt, wo man das einbauen könnte.
	 *
	 * @param string $sTranslation
	 * @param int $iFileId
	 */
	public function updateTranslationUsage($sTranslation, $iFileId) {

		if(!isset($this->aUsedTranslations[$iFileId][$sTranslation])) {

			// Zumindest sammeln, damit die Querys bei wieder verwendeten Translations reduziert werden
			$this->aUsedTranslations[$iFileId][$sTranslation] = true;

			$aSql = array(
				'code' => $sTranslation,
				'file_id' => $iFileId
			);

			// Bei globalen Übersetzungen muss used auch aktualisiert werden. wenn diese Übersetzung stattdessen benutzt wird
			$sSql = "
				UPDATE
					`language_data` `ld1` LEFT JOIN
					`language_data` `ld2` ON
						`ld1`.`use` = 0 AND
						`ld2`.`code` = :code AND
						`ld2`.`file_id` = 0 AND
						`ld2`.`use` = 1
				SET
					`ld1`.`used` = NOW(),
					`ld2`.`used` = NOW()
				WHERE
					`ld1`.`code` = :code AND
					`ld1`.`file_id` = :file_id
			";

			DB::executePreparedQuery($sSql, $aSql);

		}

	}

}
