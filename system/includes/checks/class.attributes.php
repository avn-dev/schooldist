<?php

class Checks_Attributes extends GlobalChecks {
	
	public function executeCheck() {
		global $system_data;

		Util::backupTable('wdbasic_attributes');

		// Prüfen, ob neue Spalte angelegt werden konnte
		$bCreated = DB::addField('wdbasic_attributes', 'table', 'VARCHAR(255) NOT NULL');

		// Wenn Feld noch nicht da war
		if($bCreated) {
			
			// Alle Klassen auslesen
			$sSql = "
				SELECT 
					DISTINCT `class`
				FROM
					`wdbasic_attributes`
				";
			$aClasses = DB::getQueryCol($sSql);
			
			foreach($aClasses as $sClass) {
			
				$oInstance = new $sClass();
				
				// Dazugehörige Tabelle ermitteln
				$sTable = $oInstance->getTableName();
			
				// Werte setzen
				$aUpdate = array(
					'table' => $sTable
				);
				$aWhere = array(
					'class' => $sClass
				);
				DB::updateData('wdbasic_attributes', $aUpdate, $aWhere);

			}
			
			// Alte Spalte löschen
			$sSql = "ALTER TABLE `wdbasic_attributes` DROP `class`";
			DB::executeQuery($sSql);

			$sKey = 'db_table_description_wdbasic_attributes';	
			WDCache::delete($sKey);

			$sKey = 'wdbasic_table_description_wdbasic_attributes';
			WDCache::delete($sKey);

		}
		
		return true;

	}
	
	public function getTitle() {
		return 'Checking the structure of entity attributes';
	}
	
	public function getDescription() {
		return '...';
	}

}
