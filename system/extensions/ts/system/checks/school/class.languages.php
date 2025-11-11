<?php

class Ext_TS_System_Checks_School_Languages extends GlobalChecks {

	public function getTitle() {
		return 'Update languages to new structure';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$this->updateSchoolLanguages();
		$this->updateLanguageIsoDBFields();
		
		return true;
	}
	
	public function updateLanguageIsoDBFields() {

		/**
		 * Clean tables
		 */
		$aTables = DB::listTables();

		foreach((array)$aTables as $sTable) {

			if(strpos($sTable, '__') === 0) {
				continue;
			}

			$aDescribe = DB::describeTable($sTable);

			foreach((array)$aDescribe as $aField) {
				if(
					$aField['COLUMN_NAME'] == 'language_iso' &&
					(
						strtolower($aField['DATA_TYPE']) != 'varchar' ||
						$aField['LENGTH'] < 50
					)
				) {

					$sSql = "ALTER TABLE #table CHANGE `language_iso` `language_iso` VARCHAR(50) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL";
					$aSql = array(
						'table' => $sTable
					);
					DB::executePreparedQuery($sSql, $aSql);

					$sCacheKey = 'db_table_description_'.$sTable;
					WDCache::delete($sCacheKey);

					break;
				}
			}

		}
		
		return true;
	}
	
	/**
	 * Wirft Fehler, wenn die Locales nicht gefunden wurden
	 * 
	 * @return boolean
	 * @throws RuntimeException
	 */
	public function updateSchoolLanguages() {

		$bBackup = Util::backupTable('customer_db_2');
		
		if(!$bBackup) {
			return false;
		}
		
		$oConfig = Ext_TS_Config::getInstance();
		
		$aFrontendLanguages = $oConfig->frontend_languages;

		if($aFrontendLanguages === null) {
			
			$sSql = "
				SELECT
					`id`,
					`ext_1`,
					`languages`
				FROM
					`customer_db_2`
				WHERE 
					`active` = 1
			";
			$aSchools = DB::getQueryRows($sSql);
			
			$aLanguages = [];
			
			if(is_array($aSchools)) {
				foreach($aSchools as $aSchool) {
					$aSchoolLanguages = json_decode($aSchool['languages'], true);
					if(!empty($aSchoolLanguages)) {
						
						$bUpdateLanguage = false;
						foreach($aSchoolLanguages as &$sSchoolLanguage) {
							
							try {
								$sResult = \Zend_Locale::findLocale($sSchoolLanguage);
								
								// Sprache muss unveändert vorkommen!
								if($sResult != $sSchoolLanguage) {
									$sError = 'School language "'.$sSchoolLanguage.'" could not be found in locales ("'.$sResult.'" found).';
									throw new RuntimeException($sError);
									$this->logError($sError);
									return false;
								}
								
							} catch (Exception $e) {
								$this->logError($e->getMessage());
								return false;
							}

							$aLanguages[] = $sSchoolLanguage;
						}

					}
				}
				
				$aLanguages = array_unique($aLanguages);
			}

			if(!empty($aLanguages)) {				
				$oConfig->frontend_languages = array_values($aLanguages);
			}
			
		}
		
		return true;

	}

}
