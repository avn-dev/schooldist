<?php

/**
 * Importklasse für WDLocale-XML-Dateien
 * 
 * Es handelt sich um die XML-Dateien aus dem Zend-Framework.
 * Die entsprechenden Name-Spalten müssen zuvor angelegt werden!
 *
 * @author Dennis G. <dg@plan-i.de>
 * @since 24.06.2011 
 */
class WDLocale_Import {
	
	private static $_aTableCache = null;
	private static $_bCheckLanguageData = false;
	private static $_bCheckCountryData = false;
	
	private $_sDataLanguageTable = 'data_languages';
	private $_sDataCountriesTable = 'data_countries';
	
	public function __construct($bTestMode = false)
	{
		if($bTestMode) {
			
			$this->_sDataLanguageTable = '_'.$this->_sDataLanguageTable.'_test';
			$this->_sDataCountriesTable = '_'.$this->_sDataCountriesTable.'_test';
			
			DB::executeQuery('DROP TABLE IF EXISTS `'.$this->_sDataLanguageTable.'`');
			$aResult = DB::getQueryData('SHOW CREATE TABLE `data_languages`');
			$sCreate = $aResult[0]['Create Table'];
			$sCreate = str_replace('data_languages', $this->_sDataLanguageTable, $sCreate);
			DB::executeQuery($sCreate);

			DB::executeQuery("INSERT INTO `{$this->_sDataLanguageTable}` SELECT * FROM data_languages");

			DB::executeQuery('DROP TABLE IF EXISTS `'.$this->_sDataCountriesTable.'`');
			$aResult = DB::getQueryData('SHOW CREATE TABLE `data_countries`');
			$sCreate = $aResult[0]['Create Table'];
			$sCreate = str_replace('data_countries', $this->_sDataCountriesTable, $sCreate);
			DB::executeQuery($sCreate);
			DB::executeQuery("INSERT INTO `{$this->_sDataCountriesTable}` SELECT * FROM data_countries");
			
		} else {
			
			Util::backupTable($this->_sDataLanguageTable);
			Util::backupTable($this->_sDataCountriesTable);
			
		}
		

	}
	
	public function handleXML($sFile)
	{
		$sLanguageCode = basename($sFile, '.xml');
		
		$this->checkTables($sLanguageCode);
		
		$oXML = simplexml_load_file($sFile);
		
		$this->insertLanguageData($sLanguageCode, $oXML);
		$this->insertCountryData($sLanguageCode, $oXML);
		
	}
	
	private function insertLanguageData($sLanguageCode, $oXML)
	{
		$aData = array();
		
		foreach($oXML->localeDisplayNames->languages->language as $oNode) {
			$sISO = (string)$oNode->attributes()->type;
			$sValue = (string)$oNode;
			
			// Alle Sprachen mit ISO-639-3 ausschließen
			if(mb_strlen($sISO) !== 2) continue;
			
			$aData[$sISO] = $sValue;
			
			// Prüfen, welche Sprachen noch nicht existieren und ansonsten: Füge sie ein
			if(!self::$_bCheckLanguageData) {
				if(!array_key_exists($sISO, self::$_aTableCache['data']['data_languages'])) {
					
					$aSql = array(
						'table' => $this->_sDataLanguageTable,
						'iso' => $sISO
					);
					
					$sSql = "
					INSERT INTO 
						#table
					SET
						`iso_639_1` = :iso,
						`created` = NOW()
					";
					
					DB::executePreparedQuery($sSql, $aSql);
					
				}
			}
			
		}
		
		// Fügt die Sprachen dieser XML in ihre entsprechende Spalte
		if(array_key_exists($sLanguageCode, self::$_aTableCache['structure']['data_languages'])) {

			$aSql = array(
				'code' => 'name_'.$sLanguageCode,
				'table' => $this->_sDataLanguageTable
			);
			$sSql = "
			SELECT
				`iso_639_1` AS `iso`,
				`id`
			FROM
				#table
			WHERE
				#code = ''
			";

			$aResult = DB::getQueryPairs($sSql, $aSql);
			
			// Hier stehen jetzt demnach alle Einträge per ID drin, deren Felder für diese Sprache leer sind
			if(is_array($aResult)) {
				
				foreach($aResult as $sKey => $iId) {
					if(isset($aData[$sKey])) {
						 
						$aSql = array(
							'table' => $this->_sDataLanguageTable,
							'namefield' => 'name_'.$sLanguageCode,
							'translation' => $aData[$sKey],
							'key' => $sKey,
						);
						$sSql = "
						UPDATE
							#table
						SET
							#namefield = :translation
						WHERE 
							`iso_639_1` = :key
						";
						
						DB::executePreparedQuery($sSql, $aSql);
						
					}
				}
				
			}
			
		}
		
		self::$_bCheckLanguageData = true;
		
	}
	
	private function insertCountryData($sLanguageCode, $oXML)
	{
		$aData = array();
		
		foreach($oXML->localeDisplayNames->territories->territory as $oNode) {
			$sISO = (string)$oNode->attributes()->type;
			$sValue = (string)$oNode;
			
			// Alle Länder mit Zahlen (Kontinente et cetera), die EU und das »unbekannte Land« ausschließen
			if(
				!ctype_alpha($sISO) ^
				$sISO === 'ZZ' ^
				$sISO === 'QU'
			) continue;
			
			$aData[$sISO] = $sValue;
			
			// Prüfen, welche Länder noch nicht existieren und ansonsten: Füge sie ein
			if(!self::$_bCheckCountryData) {
				if(!array_key_exists($sISO, self::$_aTableCache['data']['data_countries'])) {
					
					$aSql = array(
						'table' => $this->_sDataCountriesTable,
						'iso' => $sISO
					);
					
					$sSql = "
					INSERT INTO 
						#table
					SET
						`cn_iso_2` = :iso,
						`created` = NOW()
					";
					
					DB::executePreparedQuery($sSql, $aSql);
					
				}
			}
			
		}
		
		// Fügt die Länder dieser XML in ihre entsprechende Spalte
		if(array_key_exists($sLanguageCode, self::$_aTableCache['structure']['data_languages'])) {

			$aSql = array(
				'code' => 'cn_short_'.$sLanguageCode,
				'table' => $this->_sDataCountriesTable
			);
			$sSql = "
			SELECT
				`cn_iso_2` AS `iso`,
				`id`
			FROM
				#table
			WHERE
				#code = ''
			";

			$aResult = DB::getQueryPairs($sSql, $aSql);
			
			// Hier stehen jetzt demnach alle Einträge per ID drin, deren Felder für diese Sprache leer sind
			if(is_array($aResult)) {
				
				foreach($aResult as $sKey => $iId) {
					if(isset($aData[$sKey])) {
						 
						$aSql = array(
							'table' => $this->_sDataCountriesTable,
							'namefield' => 'cn_short_'.$sLanguageCode,
							'translation' => $aData[$sKey],
							'key' => $sKey,
						);
						$sSql = "
						UPDATE
							#table
						SET
							#namefield = :translation
						WHERE 
							`cn_iso_2` = :key
						";
						
						DB::executePreparedQuery($sSql, $aSql);
						
					}
				}
				
			}
			
			if($sLanguageCode === 'en') {
				$this->insertCountryDataEN($aData);
			}
			
		}
		
		self::$_bCheckCountryData = true;
	}
	
	/**
	 * Füllt cn_official_name_en und/oder cn_official_name_local mit EN, wenn es leer ist
	 * @param array $aData
	 */
	private function insertCountryDataEN($aData)
	{
		$aSql = array(
			'table' => $this->_sDataCountriesTable
		);
		
		$sSql = "
		SELECT
			`cn_iso_2`,
			`cn_official_name_local`,
			`cn_official_name_en`
		FROM
			#table
		WHERE
			`cn_official_name_local` = '' OR
			`cn_official_name_en` = ''
		";
		
		$aResults = DB::getQueryRows($sSql, $aSql);
		
		foreach((array)$aResults as $aResult) {
			
			$aSql = array(
				'table' => $this->_sDataCountriesTable,
				'iso' => $aResult['cn_iso_2'],
			);
			
			$aSet = array();
			
			if(empty($aResult['cn_official_name_local'])) {
				$aSet[] = "`cn_official_name_local` = '{$aData[$aResult['cn_iso_2']]}'";
			}
			
			if(empty($aResult['cn_official_name_en'])) {
				$aSet[] = "`cn_official_name_en` = '{$aData[$aResult['cn_iso_2']]}'";
			}
			
			$sSet = implode(', ', $aSet);
			
			$sSql = "
			UPDATE
				#table
			SET
				{$sSet}
			WHERE
				`cn_iso_2` = :iso

			";
			
			DB::executePreparedQuery($sSql, $aSql);
				
		}
		
	}
	
	/**
	 * Durchläuft das komplette Import-Verzeichnis und führt jede XML-Datei aus
	 */
	public function workOffImportDir()
	{
		$sPath = \Util::getDocumentRoot().'system/includes/wdlocale/import';
		
		$oDir = dir($sPath);
		while($sXML = $oDir->read()) {
			
			if(strrchr($sXML, '.') != '.xml') continue;
			$this->handleXML($sPath.'/'.$sXML);
			
		}
		$oDir->close();
		
	}
	
	/**
	 * Prüft die Tabellen
	 * @param string $sLanguageCode ISO-639-1
	 */
	private function checkTables($sLanguageCode)
	{
		
		if(!is_null(self::$_aTableCache)) return;
		
		$aCache = array();
		
		$aRows = DB::getQueryRows('DESCRIBE `'.$this->_sDataLanguageTable.'`');
		foreach($aRows as $aRow) {
			
			$sSubVal = substr($aRow['Field'], 0, 5);
			if($sSubVal !== 'name_') continue;
			
			$sNameField = str_replace($sSubVal, '', $aRow['Field']);
			
			$aCache['structure']['data_languages'][] = $sNameField;
		}
		
		$aCache['structure']['data_languages'] = array_flip($aCache['structure']['data_languages']);
		$aCache['data']['data_languages'] = array_flip(DB::getQueryCol('SELECT `iso_639_1` FROM `data_languages`'));
		
		
		$aRows = DB::getQueryRows('DESCRIBE `'.$this->_sDataCountriesTable.'`');
		foreach($aRows as $aRow) {
			
			$sSubVal = substr($aRow['Field'], 0, 9);
			if(
				$sSubVal !== 'cn_short_' ||
				$aRow['Field'] === 'cn_short_local'
			) continue;
			
			$sNameField = str_replace($sSubVal, '', $aRow['Field']);
			
			$aCache['structure']['data_countries'][] = $sNameField;
		}
		
		$aCache['structure']['data_countries'] = array_flip($aCache['structure']['data_countries']);
		$aCache['data']['data_countries'] = array_flip(DB::getQueryCol('SELECT `cn_iso_2` FROM `data_countries`'));
		
		
		self::$_aTableCache = $aCache;
		
	}
	
	function __destruct() {
		//wdmail('dg@plan-i.de', 'V5 LOCAL IMPORT TABLE CACHE', print_r(self::$_aTableCache, true));
	}
	
}
