<?php

/**
 * @property id
 * @property changed
 * @property created
 * @property active
 * @property name
 * @property update_time
 */
class Ext_TC_Exchangerate_Table extends Ext_TC_Basic {

	protected $_sTable = 'tc_exchangerates_tables';
	protected $_sTableAlias = 'tc_et';

	protected $_aRateCache = array();
	
	protected $_aJoinedObjects = array(
		'sources' => array(
			'class' => 'Ext_TC_Exchangerate_Table_Source',
			'key' => 'table_id',
			'type' => 'child',
			'check_active' => true,
			'orderby' => 'position',
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Holt einen Umrechnungskurs von einem bestimmten Datum, bzw. den zuletzt vorhandenen Kurs, wenn $bGetLastRate auf true steht
	 * @param <string> $sCurrencyFrom
	 * @param <string> $sCurrencyTo
	 * @param <string> $sDate
	 * @return <float>
	 */
	public function getRate($sCurrencyFrom, $sCurrencyTo, $sDate=null) {

		// bei gleicher währung ein Faktor 1 Object generieren
		if($sCurrencyFrom == $sCurrencyTo) {
			$oRate = Ext_TC_Exchangerate_Table_Source_Rate::getInstance(0);
			$oRate->currency_iso_from = $sCurrencyFrom;
			$oRate->currency_iso_to = $sCurrencyTo;
			$oRate->price = 1;
			$oRate->table_id = $this->id;
			if($sDate){
				$oRate->date = $sDate;
			}
			return $oRate;
		}

		if($this->id == 0) {
			throw new Ext_TC_Exchangerate_Exception('No exchange rate table given');
		}

		if($sDate === null) {
			$sDate = date('Y-m-d');
		}

		if(!isset($this->_aRateCache[$sCurrencyFrom][$sCurrencyTo][$sDate])) {

			$sSql = "
				SELECT
					`tc_etr`.`id`
				FROM
					`tc_exchangerates_tables_rates` `tc_etr`
				WHERE
					`tc_etr`.`table_id` = :table_id AND
					`tc_etr`.`currency_iso_from` = :currency_iso_from AND
					`tc_etr`.`currency_iso_to` = :currency_iso_to AND
					`tc_etr`.`date` <= :date
				ORDER BY
					`date` DESC
				LIMIT
					1
			";
			$aSql = array(
				'table_id' => (int) $this->id,
				'currency_iso_from' => $sCurrencyFrom,
				'currency_iso_to' => $sCurrencyTo,
				'date' => $sDate
			);

			$iRate = (int)DB::getQueryOne($sSql, $aSql);

			// Wenn kein direkter Kurs gefunden wurde, dann indirekten Kurs suchen
			if(empty($iRate)) {

				$sSql = "
					SELECT
						`tc_etr1`.`id`
					FROM
						`tc_exchangerates_tables_rates` `tc_etr1` JOIN
						`tc_exchangerates_tables_rates` `tc_etr2` ON
							`tc_etr1`.`table_id` = `tc_etr2`.`table_id` AND
							`tc_etr1`.`date` = `tc_etr2`.`date` AND
							`tc_etr1`.`currency_iso_to` = `tc_etr2`.`currency_iso_from`
					WHERE
						`tc_etr1`.`table_id` = :table_id AND
						`tc_etr1`.`currency_iso_from` = :currency_iso_from AND
						`tc_etr2`.`currency_iso_to` = :currency_iso_to AND
						`tc_etr1`.`date` <= :date
					ORDER BY
						`tc_etr1`.`date` DESC
					LIMIT
						1
				";
				$aSql = array(
					'table_id'=>$this->id,
					'currency_iso_from'=>$sCurrencyFrom,
					'currency_iso_to'=>$sCurrencyTo,
					'date' => $sDate
				);

				$iRate = (int)DB::getQueryOne($sSql, $aSql);
			}
			
			if(empty($iRate)) {
				throw new Ext_TC_Exchangerate_Exception('Unable to find rate for this combination ("'.$sCurrencyFrom.'", "'.$sCurrencyTo.'", "'. $sDate.'") in table "'.$this->name.'"');
			}
			
			$oRate = Ext_TC_Exchangerate_Table_Source_Rate::getInstance($iRate);
			
			$this->_aRateCache[$sCurrencyFrom][$sCurrencyTo][$sDate] = $oRate;

		}

		return $this->_aRateCache[$sCurrencyFrom][$sCurrencyTo][$sDate];

	}

	/**
	 * Gibt ein Array mit Zeitpunkt (Beschreibung) und optional Gültigkeit zurück
	 * @param <int> $iObjectId
	 * @param <string> $sApplication
	 * @return <array>
	 */
	public static function getRateDate($iObjectId, $sApplication) {

		$sSql = "
			SELECT
				`date`,
				`validity`
			FROM
				`tc_exchangerates_allocations`
			WHERE
				`object_id` = :object_id AND
				`application` = :application
			";
		$aSql = array(
			'object_id'=>(int)$iObjectId,
			'application'=>$sApplication
		);

		$aRateDate = DB::getQueryRow($sSql, $aSql);

		return $aRateDate;

	}

	/**
	 * holt alle Wechselkurs-Daten aus den angegebenen Quellen
	 * @return array
	 */
	public function readData($aSources){
		
		$aData = array();
		
		foreach($aSources as $oSource) {
			/* @var $oSource Ext_TC_Exchangerate_Table_Source */
			$aSourceData = $oSource->readData();
			if($aSourceData !== false) {
				$aData = $aData + $aSourceData;
			}
		}
		
		return $aData;
	}
	
	/**
	 * Speichert die übergebenen Wechselkursdaten in die Datenbank
	 * @param array $aData
	 * @throws Exception 
	 */
	public static function saveRateData($aData){

		$aArray = (array) $aData;    

		$bSuccess = false;
		
		foreach($aArray as $aRate){

			try {
				// Helfer-Klasse, um die Daten abzuspeichern
				$oHelper = new Ext_TC_Exchangerate_Table_Helper_SaveRates($aRate);
				$bSuccess = $oHelper->saveRate();

			} catch(DB_QueryFailedException $e) {
				__pout($e);
				throw new Exception('Error by updating exchange rate data (Query)');
			} catch (Exception $e) {
				__pout($e);
				throw new Exception('Error by updating exchange rate data');
			}

		}

		return $bSuccess;
	}

	/**
	 * calculate Amount with given Currencies and Date
	 * @param float $fAmount
	 * @param string $sFromCurrency
	 * @param string $sToCurrency
	 * @param string $sCurrencyDate
	 * @return float
	 */	
	public function calculateAmount($fAmount, $sFromCurrency, $sToCurrency, $sCurrencyDate){

		if($fAmount != 0) {
			$oRate = $this->getRate($sFromCurrency, $sToCurrency, $sCurrencyDate);

			$fFaktor = 1;

			if($oRate){
				$fFaktor = (float)$oRate->price;
			}

			$fAmount = $fAmount * $fFaktor;
		}
		
		return $fAmount;
		
	}

	
	/**
	 * Get current exchange rates
	 * 
	 * @return array 
	 */
	public function getCurrentRates()
	{
		// Wenn Tabelle nicht gespeichert, dann keine Daten
		if($this->id === 0) {
			return null;
		}	
		
		$sSql = "
			SELECT
				`tc_etr`.`currency_iso_from` AS `currency_from`,
				`tc_etr`.`currency_iso_to` AS `currency_to`,
				`tc_etr`.`price` AS `rate`
			FROM
				`tc_exchangerates_tables_rates` AS `tc_etr`
			WHERE
				`table_id` = :iTableID AND
				`date` = 
				(
					SELECT
						MAX(`date`)
					FROM
						`tc_exchangerates_tables_rates`
					WHERE
						`table_id` = :iTableID AND
						`date` <= DATE(NOW())
				)
		";
		$aSql = array(
			'iTableID' => (int) $this->id
		);
		$aData = (array)DB::getQueryRows($sSql, $aSql);

		if(empty($aData)) {
			throw new Ext_TC_Exchangerate_Exception('No current rates found for table "'.$this->name.'"!');
		}
		
		return $aData;
	}
	
	/**
	 * gibt alle angelegten Quellen zurück
	 * @return array Ext_TC_Exchangerate_Table_Source 
	 */
	public function getSources() {
		$aSources = (array) $this->getJoinedObjectChilds('sources', true);
		return $aSources;
	}
	
	/**
	 * liest für alle angegebenen Quellen die Wechselkurse aus und speichert sie
	 * @return int 
	 */
	public function update() {
		
		$aSources = $this->getSources();

		// Ohne diese Prüfung würde der Cronjob der Installation für das Objekt
		// 	als fehlgeschlagen markiert, was aber falsch ist.
		if(empty($aSources)) {
			return 0;
		}

		// Falls das Xml nicht ausgelesen werden kann
		try {
			$aData = $this->readData($aSources);
		} catch (Exception $e) {
			__pout($e);
			return false;
		}

		$iData = count($aData);
		
		// Wenn nichts aktualisiert wurde
		if($iData == 0) {
			return false;
		}		
		
		// Daten speichern
		try {
			$this->saveRateData($aData);
		} catch (Exception $e) {
			__pout($e);
			return false;
		}

		return $iData;
	}

	/**
	 * Liefert ein Array mit Daten, für die Wechselkurse vorhanden sind
	 * 
	 * Array
	 *	(
	 *		[0] => 2012-09-13
	 *		[1] => 2012-09-14
	 *		[2] => 2012-09-17
	 *	)
	 * 
	 * @return array 
	 */
	public function getAllRateDates() {
		
		$aReturn = array();
		
		$sSql = "
			SELECT DISTINCT 
				`tc_etr`.`date`
			FROM
				`tc_exchangerates_tables_rates` `tc_etr` LEFT JOIN
				`tc_exchangerates_tables_sources` `tc_ets` ON
					`tc_ets`.`id` = `tc_etr`.`source_id` AND
					`tc_ets`.`table_id` = :table_id AND
					`tc_ets`.`active` = 1
			ORDER BY
				`tc_etr`.`date` ASC
		";
		
		$aSql = array(
			'table_id' => (int) $this->id
		);
		
		$aData = (array)DB::getQueryRows($sSql, $aSql);
		
		// Array umformatieren
		foreach($aData as $sKey => $aRateData) {
			$aReturn[] = $aRateData['date'];
		}
		
		return $aReturn;
	}

	public static function getSelectOptions() {

		$aTables = self::getRepository()->findAll();
		$aOptions = [];

		foreach($aTables as $oTable) {
			$aOptions[$oTable->getId()] = $oTable->getName();
		}

		return $aOptions;
	}

}
