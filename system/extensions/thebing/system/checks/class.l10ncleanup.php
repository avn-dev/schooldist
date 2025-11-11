<?php

/**
	* Konvertiert language_files und updatet entsprechende Einträge oder entfernt sie, wenn nicht mehr vorhanden
	* Passt language_data.file_id an geänderte language_files.id an
	* Entfernt alle language_data, dessen Benutzung älter als 1.1.11 oder nicht gesetzt ist
 *
 */
class Ext_Thebing_System_Checks_L10NCleanUp extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Language Module Convert and Clean-up';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Remove or update deprecated language_data file entries with notability to language_data and remove ununused translate strings.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		
		global $system_data;
		
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		Ext_Thebing_Util::backupTable('language_files');
		Ext_Thebing_Util::backupTable('language_data');
		
		$aLangFiles = array();
		$sSql = "
			SELECT 
				* 
			FROM
				`language_files` 
			WHERE 
				`file` LIKE '% >> %'
				";
		$aLangFiles = DB::getQueryPairs($sSql);
		
		foreach($aLangFiles as $iLangFileId => $sLangFileEntry) {
			
			$sLangFileEntryNew = str_replace(" >> ", " » ", $sLangFileEntry);
			
			$aSql = array(
				"index" => $sLangFileEntryNew
			);
			
			$sSql = "
				SELECT 
					* 
				FROM 
					`language_files` 
				WHERE 
					`file` = :index 
				LIMIT 1";
			$aResult = DB::getQueryRow($sSql,$aSql);
			
			// Löscht den ursprünglichen Eintrag und updatet language_data auf die neue ID
			if(!empty($aResult)) {
				$aSql = array(
					"id" => $iLangFileId
				);
				$sSql = "DELETE FROM `language_files` WHERE `id` = :id";
				DB::executePreparedQuery($sSql, $aSql);
				
				$aSql = array(
					"new_file_id" => (int)$aResult['id'],
					"old_file_id" => $iLangFileId
				);
				$sSql = "UPDATE `language_data` SET `file_id` = :new_file_id WHERE `file_id` = :old_file_id";
				DB::executePreparedQuery($sSql, $aSql);
				
			} else {
				$aSql = array(
					"file" => $sLangFileEntryNew,
					"id" => $iLangFileId
				);
				$sSql = "UPDATE `language_files` SET `file` = :file WHERE `id` = :id LIMIT 1";
				DB::executePreparedQuery($sSql, $aSql);
			}
			
		}
		
		$sQuery = "
			DELETE FROM
				`language_data`
			WHERE
				`used` < '2011-01-01 00:00:00'
				";
		DB::executeQuery($sQuery);
		
		$sQuery = "
			DELETE 
				`lf` 
			FROM
				`language_files` `lf` LEFT JOIN
				`language_data` `ld` ON
					`lf`.`id` = `ld`.`file_id`
			WHERE
				`ld`.`id` IS NULL
				";
		
		return true;

	}

}
