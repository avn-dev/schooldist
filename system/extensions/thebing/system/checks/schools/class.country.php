<?php
/**
 * Schulen müssen Länder haben
 * Sobald Länder zugewiesen sind, werden die Umsatzsteuern in die neue Struktur 
 * gebracht und den Ländern zugeordnet
 * 
 * #3373
 */
class Ext_Thebing_System_Checks_Schools_Country extends GlobalChecks {
	
	public $aDebug = array();
	
	protected $_aSchoolIsoCache = array();
	
	public function getTitle() {
		$sTitle = 'Add country information to all schools';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Each school must be assigned to a country! Existing sales taxes are then assigned to the countries of the schools.';
		return $sDescription;
	}

	/**
	 * Gibt pro Schule ohne Land eine Auswahl aus
	 */
	public function printFormContent() {

		// Länder holen
		$aCountries = Ext_Thebing_Data::getCountryList(true, true);
		
		// Alle aktiven Schulen ohne Land
		$aSchools = $this->_getSchools();

		if(!empty($aSchools)) {

			printTableStart();
			foreach($aSchools as $aSchool) {
				printFormSelect($aSchool['ext_1'], 'country['.$aSchool['id'].']', $aCountries);
			}

			printTableEnd();

		}

?>

		<div style="width:100%; text-align:right;">
			<input type="submit" value="<?=L10N::t('Save')?>" class="btn" />
		</div>
<?
	}
	
	/**
	 * Speichert die Länder
	 * 
	 * @global array $_VARS
	 * @return boolean
	 */
	public function executeCheck(){
		global $_VARS;

		$this->aDebug = array();

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		Ext_Thebing_Util::backupTable('customer_db_2');

		// Alle aktiven Schulen ohne Land
		$aSchools = $this->_getSchools();

		$this->_aFormErrors = array();

		if(!empty($aSchools)) {

			foreach($aSchools as $aSchool) {

				// Wenn kein Land für die Schule gewählt wurde
				if(empty($_VARS['country'][$aSchool['id']])) {
					$this->_aFormErrors[] = 'Please choose a country for "'.$aSchool['ext_1'].'"!';
				}

			}

		}

		// Falls Fehler aufgetreten ist
		if(!empty($this->_aFormErrors)) {
			return false;
		}

		if(!empty($aSchools)) {

			foreach($aSchools as $aSchool) {
				
				// Land speichern
				$sSql = "
					UPDATE
						`customer_db_2`
					SET 
						`changed` = `changed`,
						`country_id` = :country_id
					WHERE
						`id` = :id
					";
				$aSql = array(
					'country_id' => $_VARS['country'][$aSchool['id']],
					'id' => (int)$aSchool['id']
				);
				DB::executePreparedQuery($sSql, $aSql);
				
			}

		}		

		$this->_moveVatSettings();
		
		if(!empty($this->aDebug['errors']))
		{
			__pout($this->aDebug['errors']); 
			
			return false;
		}

		// Debugdaten an TS versenden
		#wdmail('ts@thebing.com', 'Ext_Thebing_System_Checks_Schools_Country', print_r($this->aDebug, 1));

		return true;

	}

	/**
	 * - Steuersätze in die neue Tabelle übertragen
	 * - IDs austauschen
	 * - Zuordnungen Leistungen / Steuersätze
	 * 
	 * ID austauschen:
	 * kolumbus_cancellation_fees_dynamic.tax_category_id
	 * kolumbus_inquiries_documents_versions_items.tax_category
	 * 
	 */
	protected function _moveVatSettings() {

		// Prüfen, ob die Methode schon ausgeführt wurde
		$aTables = DB::listTables();
		if(
			!in_array('kolumbus_accounting_taxes_categories', $aTables) &&
			!in_array('kolumbus_accounting_taxes', $aTables)
		) {
			$this->aDebug['errors'][] = 'Alte Tabellen fehlen!';
			return false;
		}

		// Tabellen erstellen falls nicht vorhanden
		$sSql = "CREATE TABLE IF NOT EXISTS `tc_vat_rates` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`creator_id` int(11) NOT NULL,
			`editor_id` int(11) NOT NULL,
			`country` varchar(2) NOT NULL,
			`name` varchar(150) NOT NULL,
			`short` varchar(255) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `active` (`active`,`creator_id`,`editor_id`),
			KEY `country` (`country`)
		  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		DB::executeQuery($sSql);
		$sSql = "CREATE TABLE IF NOT EXISTS `tc_vat_rates_values` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`creator_id` int(11) NOT NULL,
			`editor_id` int(11) NOT NULL,
			`rate_id` int(11) NOT NULL,
			`valid_from` date NOT NULL,
			`valid_until` date NOT NULL,
			`rate` decimal(5,2) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `active` (`active`,`creator_id`,`editor_id`,`rate_id`,`valid_from`,`valid_until`)
		  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
		DB::executeQuery($sSql);
		
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_vat_rates_combinations` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`changed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
			`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`valid_until` date NOT NULL DEFAULT '0000-00-00',
			`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
			`editor_id` int(10) unsigned NOT NULL,
			`creator_id` int(10) unsigned NOT NULL,
			`vat_rate_id` mediumint(8) NOT NULL,
			`country_iso` char(2) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `active` (`active`),
			KEY `valid_until` (`valid_until`),
			KEY `valid_until_active` (`valid_until`,`active`),
			KEY `name` (`country_iso`),
			KEY `vat_rate_id` (`vat_rate_id`)
		  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
		DB::executeQuery($sSql);

		$sSql = "CREATE TABLE IF NOT EXISTS `ts_vat_rates_combinations_to_objects` (
			`combination_id` int(11) NOT NULL,
			`class` varchar(100) NOT NULL,
			`class_id` int(11) NOT NULL,
			PRIMARY KEY (`combination_id`,`class`,`class_id`)
		  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		DB::executeQuery($sSql);
		
		// Tabellen sichern und leeren
		Ext_Thebing_Util::backupTable('tc_vat_rates');
		Ext_Thebing_Util::backupTable('tc_vat_rates_values');
		Ext_Thebing_Util::backupTable('ts_vat_rates_combinations');
		Ext_Thebing_Util::backupTable('ts_vat_rates_combinations_to_objects');

		$sSql = "TRUNCATE TABLE `tc_vat_rates`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `tc_vat_rates_values`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_vat_rates_combinations`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_vat_rates_combinations_to_objects`";
		DB::executeQuery($sSql);

		// Schul-Länder, gruppiert nach Land
		$sSql = "
			SELECT
				`country_id` `iso`,
				GROUP_CONCAT(`id`) `schools`
			FROM
				`customer_db_2`
			WHERE
				`active` = 1
			GROUP BY
				`country_id`
			";
		$aCountries = (array)DB::getQueryRows($sSql);

		foreach($aCountries as $aCountry) {

			// Report
			$this->aDebug['countries'][$aCountry['iso']]['country'] = $aCountry;

			$aCountry['schools'] = explode(',', $aCountry['schools']);

			// Alle Steuersätze der Schulen dieses Landes rausfinden, gruppiert nach aktuellem Prozentsatz
			$sSql = "
				SELECT
					`katc`.`name`,
					GROUP_CONCAT(`katc`.`id`) `tax_categories`,
					`kat`.`tax_rate`
				FROM
					`kolumbus_accounting_taxes_categories` `katc` JOIN
					`kolumbus_accounting_taxes` `kat` ON
						`katc`.`id` = `kat`.`type_id` AND
						`kat`.`active` = 1 AND
						(
							NOW() BETWEEN `kat`.`valid_from` AND `kat`.`valid_until` OR
							(
								`kat`.`valid_until` = '0000-00-00' AND
								NOW() > `kat`.`valid_from`
							) OR
							(
								`kat`.`valid_from` = '0000-00-00' AND
								`kat`.`valid_until` = '0000-00-00'
							)
						)
				WHERE
					`katc`.`school_id` IN (:schools) AND
					`katc`.`active` = 1
				GROUP BY
					`kat`.`tax_rate`
				";
			$aSql = array(
				'schools' => (array)$aCountry['schools']
			);
			$aVats = (array)DB::getQueryRows($sSql, $aSql);

			foreach($aVats as $aVat) {

				$aTaxCategories = explode(",", $aVat['tax_categories']);
				$iTaxCategoryId = reset($aTaxCategories);

				// Rates holen
				$sSql = "
					SELECT
						*
					FROM
						`kolumbus_accounting_taxes`
					WHERE
						`type_id` = :tax_category_id
					";
				$aSql = array(
					'tax_category_id' => (int)$iTaxCategoryId
				);
				$aRates = DB::getQueryRows($sSql, $aSql);

				$aData = array(
					'created' => date('Y-m-d H:i:s'),
					'active' => 1,
					'country' => $aCountry['iso'],
					'name' => (string)$aVat['name'],
					'short' => (string)$aVat['name']
				);

				$iNewVatId = DB::insertData('tc_vat_rates', $aData);

				foreach($aRates as $aRate) {

					$aData = array(
						'created' => date('Y-m-d H:i:s'),
						'active' => 1,
						'rate_id' => $iNewVatId,
						'valid_from' => $aRate['valid_from'],
						'valid_until' => $aRate['valid_until'],
						'rate' => $aRate['tax_rate']
					);

					DB::insertData('tc_vat_rates_values', $aData);

				}

				$aVat['new_id'] = $iNewVatId;

				// Report
				$this->aDebug['countries'][$aCountry['iso']]['vats'][] = $aVat;

				// IDs ersetzen
				$this->_replaceVatIds($iNewVatId, $aTaxCategories);
				// Zuweisungen in die neue Struktur kopieren
				$this->_moveAllocations($iNewVatId, $aTaxCategories);

			}

		}

		// Alte Tabellen umbenennen (Backup)
		
		$this->makeBackupAndDropTable('kolumbus_accounting_taxes_categories');
		
		$this->makeBackupAndDropTable('kolumbus_accounting_taxes');

		// Cache leeren
		WDCache::flush();
	}
	
	public function makeBackupAndDropTable($sTable)
	{
		$bSuccess = Util::backupTable($sTable);
		
		if($bSuccess)
		{
			$sSql = "
				DROP TABLE #table
			";
			
			$aSql = array(
				'table' => $sTable,
			);
			
			$rRes = DB::executePreparedQuery($sSql, $aSql);
			
			if(!$rRes)
			{
				$this->aDebug['errors'][] = 'Couldnt delete old table "'.$sTable.'"!';
			}
		}
		else
		{
			$this->aDebug['errors'][] = 'Couldnt backup old table "'.$sTable.'"!';
		}
	}
	
	/**
	 * Ersetzt die VatRate ID
	 * 
	 * @param int $iNewId
	 * @param array $aOldIds
	 */
	protected function _replaceVatIds($iNewId, array $aOldIds) {

		$aTables = array(
			array(
				'table' => 'kolumbus_cancellation_fees_dynamic',
				'field' => 'tax_category_id',
				'changed' => false
			),
			array(
				'table' => 'kolumbus_inquiries_documents_versions_items',
				'field' => 'tax_category',
				'changed' => true
			)
		);

		foreach($aTables as $aTable) {

			$sSetAddon = "";
			if($aTable['changed'] === true) {
				$sSetAddon .= "`changed` = `changed`, ";
			}

			// Temporäre Spalte ergänzen
			$bCheck = DB::getDefaultConnection()->checkField($aTable['table'], '__tmp_vat_rate', true);
			if($bCheck === false) {
				$bSuccess = DB::addField($aTable['table'], '__tmp_vat_rate', 'INT(11) NOT NULL');
			} else {
				$bSuccess = true;
			}
			
			// Nur wenn es die Spalte noch nicht gab, dann Werte übertragen!
			if($bSuccess === true) {
				$sSql = "
					UPDATE
						#table
					SET
						".$sSetAddon."
						`__tmp_vat_rate` = #field
				";
				$aSql = array(
					'table' => $aTable['table'],
					'field' => $aTable['field']
				);
			}

			// Alte IDs gegen neue tauschen
			$sSql = "
				UPDATE 
					#table
				SET 
					#field = :new_id
				WHERE
					`__tmp_vat_rate` IN (:old_ids)
				";
			$aSql = array(
				'table' => $aTable['table'],
				'field' => $aTable['field'],
				'new_id' => (int)$iNewId,
				'old_ids' => (array)$aOldIds
			);
			DB::executePreparedQuery($sSql, $aSql);

		}

	}

	/**
	 * kopiert die alte Zuweisung zu den neuen Steuersätzen
	 * @param int $iNewId
	 * @param array $aOldIds
	 */
	protected function _moveAllocations($iNewId, $aOldIds) {

		// alte Zuweisungen holen
		$aOldAllocations = (array) $this->_getOldAllocations($aOldIds);

		// Temporärer Cache für statische Elemente
		$aTransfer		= array();
		$aExtraposition = array();
		$aInsurances	= array();
	
		// Alte Zuweisungen durchlaufen
		foreach ($aOldAllocations as $aEntry) {

			$aTransfer[$iNewId]			= false;
			$aExtraposition[$iNewId]	= false;
			$aInsurances[$iNewId]		= false;

			// ISO-Code der Schule holen
			$sSchoolIso = $this->_getSchoolIso($aEntry['idSchool']);

			// Schauen, ob es bereits eine Kombination für den ISO-Code gibt, ansonsten
			// neue Kombination erstellen
			$iCombinationId = $this->_getCombinationByIso($sSchoolIso, $iNewId);

			if($iCombinationId === null) {
				
				$sDate = date('Y-m-d H:i:s');
				
				$aData = array(
					'changed'		=> $sDate,
					'created'		=> $sDate,
					'vat_rate_id'	=> $iNewId,
					'country_iso'	=> $sSchoolIso
				);

				$iCombinationId = DB::insertData('ts_vat_rates_combinations', $aData);
			}

			// Mapping für die Klassennamen
			$sClass = $this->_mapAllocation($aEntry['allocation_db']);

			// Mapping gefunden
			if($sClass != '') {

				$iClassId	= (int) $aEntry['allocation_id'];
				$bInsert	= true;				

				// Statische Elemente einzeln behandeln
				if($sClass == 'STATIC') {

					// Versicherungen - Vorher wurde immer nur der Fleck zu Versicherungen gesetzt, in der neuen Übersicht
					// werden alle Versicherungen aufgeführt. Wenn der Fleck für diesen Steuersatz gesetzt wurde, werden alle Versicherungen
					// zugeordnet
					if($aEntry['allocation_id'] == 6) {

						if(!$aInsurances[$iNewId]) {
							$sClass = 'Ext_Thebing_Insurances';

							// Alle Versicherungen zuweisen
							$oInsurance = new Ext_Thebing_Insurance();
							$aTempInsurances = $oInsurance->getArrayList();
							foreach($aTempInsurances as $aInsuranceData) {
								$aObject = array(
									'combination_id'	=> (int)$iCombinationId,
									'class'				=> $sClass,
									'class_id'			=> (int)$aInsuranceData['id']
								);
								$this->_insertCombinationToObject($aObject);
							}
							$aInsurances[$iNewId] = true;
						}
						$bInsert = false;						
					}

					// nicht zugeordnete Extrapositionen
					elseif($aEntry['allocation_id'] == 7) {
						if(!$aExtraposition[$iNewId]) {
							$sClass = 'NOT_ALLOCATED_EXTRAPOSITIONS';
							$iClassId = -2;
							$aExtraposition[$iNewId] = true;
						} else {
							$bInsert = false;
						}
					} 

					// Transfer
					elseif($aEntry['allocation_id'] == 2) {
						if(!$aTransfer[$iNewId]) {
							$sClass = 'TRANSFER';
							$iClassId = -1;
							$aTransfer[$iNewId] = true;
						} else {
							$bInsert = false;
						}
						
					// andere Statische Elemente ignorieren
					} else {
						$bInsert = false;
					}

				}

				// Objekt der Kombination zuweisen
				if($bInsert) {
					$aObject = array(
						'combination_id'	=> $iCombinationId,
						'class'				=> $sClass,
						'class_id'			=> $iClassId
					);
					
					$this->_insertCombinationToObject($aObject);
				}

			}

		}

	}
	
	protected function _insertCombinationToObject($aObject) {
		
		try {
			
			DB::insertData('ts_vat_rates_combinations_to_objects', $aObject);
			
		} catch(DB_QueryFailedException $e) {
			
		}
		
	}
	
	/**
	 * Holt alle aktiven Schulen ohne Land
	 * @return array
	 */
	protected function _getSchools() {

		$sSql = "
			SELECT
				*
			FROM
				`customer_db_2`
			WHERE
				`active` = 1 AND
				`country_id` = ''
			";
		$aSchools = DB::getQueryRows($sSql);

		return $aSchools;

	}
	
	/**
	 * holt den ISO-Code der übergebenen Schule
	 * @param type $iId
	 * @return type
	 */
	protected function _getSchoolIso($iId) {

		// Cache prüfen, ob Schule schon einmal aufgerufen wurde
		if(!isset($this->_aSchoolIsoCache[$iId])) {

			$sSql = "
				SELECT
					`country_id` 
				FROM
					`customer_db_2`
				WHERE
					`id` = :id
			";

			$aSql = array(
				'id' => (int) $iId
			);

			$sIso = DB::getQueryOne($sSql, $aSql);
			$this->_aSchoolIsoCache[$iId] = $sIso;
		}

		return $this->_aSchoolIsoCache[$iId];
	}
	
	/**
	 * alle Einträge filtern, die als 'tax_kategory_id' eine der alten Ids in $aOldIds haben.
	 * Anschließend wird für jeden der gefundenen Einträge eine Zuweisung in der neuen Struktur für $iNewId gemacht
	 * @param array $aOldIds
	 * @return array
	 */
	protected function _getOldAllocations($aOldIds) {

		$sSql = "
			SELECT 
				*
			FROM
				`kolumbus_accounting_allocation_accounts`
			WHERE
				`tax_kategory_id` IN (
					:old_ids
				)
		";

		$aSql = array(
			'old_ids' => $aOldIds
		);

		$aOldAllocations = DB::getQueryData($sSql, $aSql);

		return $aOldAllocations;
	}
	
	/**
	 * Mapping für die neue Struktur
	 * @param string $sMap
	 * @return string
	 */
	protected function _mapAllocation($sMap) {

		switch($sMap) {
			case 'customer_db_3':
				$sReturn = 'Ext_Thebing_Tuition_Course';
				break;
			case 'customer_db_8':
				$sReturn = 'Ext_Thebing_Accommodation';
				break;
			case 'kolumbus_costs':
				$sReturn = 'Ext_Thebing_School_Cost';
				break;
			case 'kolumbus_accounting_static_returns':
				$sReturn = 'STATIC';
				break;
			default:
				$sReturn = '';
		}

		return $sReturn;
	}
	
	/**
	 * gibt die neu angelegte Kombination der Umsatzsteuer für das entsprechende Land
	 * zurück
	 * @param string $sIso
	 * @param int $iNewId
	 * @return int|null
	 */
	protected function _getCombinationByIso($sIso, $iNewId) {

		$sSql = "
			SELECT 
				`id`
			FROM
				`ts_vat_rates_combinations`
			WHERE
				`country_iso` = :iso AND
				`vat_rate_id` = :new_id
		";

		$aSql = array(
			'iso'		=> $sIso,
			'new_id'	=> $iNewId
		);

		$iId = DB::getQueryOne($sSql, $aSql);		
		return $iId;

	}
	
}
