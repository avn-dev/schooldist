<?php

/**
 * Wenn keine Kursabkürzung gesetzt ist, wird versucht, den Namen des Kurses der Standardsprache zu setzen
 */

class Ext_Thebing_System_Checks_CourseAbbreviations extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Course abbreviations update';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Set default course abbreviation if none set.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		
		global $system_data;
		
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('customer_db_3');
		
		$sSql = "
		SELECT
			`id`, `ext_291`
		FROM
			`customer_db_2`";
		
		$aSchools = DB::getQueryPairs($sSql);
		
		$sSql = "
		SELECT
			`id`, `ext_8`
		FROM
			`customer_db_3`
		WHERE
			`active` = 1 AND
			`ext_36` = ''";
		
		$aResult = DB::getQueryPairs($sSql);
		
		foreach((array)$aResult as $iId => $iSchoolId) {
			
			if(!empty($aSchools[$iSchoolId])) {
				$sLanguage = $aSchools[$iSchoolId];
			} else {
				$sLanguage = 'en';
			}

			$aSql = array(
				'name_field'=>'name_'.$sLanguage,
				'id'=>(int)$iId
			);

			$sSql = "
			UPDATE
				`customer_db_3`
			SET
				`ext_36` = #name_field,
				`changed` = `changed`
			WHERE
				`id` = :id
			LIMIT 1";
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		return true;

	}
	
}
?>
