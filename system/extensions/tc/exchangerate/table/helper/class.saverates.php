<?php

class Ext_TC_Exchangerate_Table_Helper_SaveRates {
	
	/** @var array */
	protected $_aRate = array();
	
	/**
	 * Konstruktor, dem der abzuspeichernde Eintrag übergeben wird
	 * @param array $aRate 
	 */
	public function __construct($aRate) {
		$this->_aRate = $aRate;
	}
	
	/**
	 * prüft ob für den Tag bereits Daten eingetragen wurden
	 * @return boolean 
	 */
	protected function _checkRatesForDay() {
		
		$sSql = "
			SELECT
				`id`
			FROM
				`tc_exchangerates_tables_rates`
			WHERE
				`source_id` = :source_id AND
				`table_id` = :table_id AND
				`currency_iso_from` = :currency_iso_from AND
				`currency_iso_to` = :currency_iso_to AND
				`date` = :date
		";
		
		$aSql = $this->_getSqlArray(false);
		
		$iRateId = DB::getQueryOne($sSql, $aSql);
		
		if(!empty($iRateId)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * speichert die übergebenen Daten ab
	 * @return boolean 
	 */
	public function saveRate() {
		
		// Prüfen ob Daten bereits vorhanden sind
		$bUpdate = $this->_checkRatesForDay();
		
		if($bUpdate === true) {
			$sSql = $this->_updateRate();
		} else {
			$sSql = $this->_insertRate();
		}
	
		$aSql = $this->_getSqlArray();
		
		$bSuccess = (bool)DB::executePreparedQuery($sSql, $aSql);
		
		return $bSuccess;
	}
	
	/**
	 * Gibt den Query zurück, welcher die Daten in der Datenbank updatet
	 * @return string 
	 */
	protected function _updateRate() {
		
		$sSql = "
			UPDATE
				`tc_exchangerates_tables_rates`
			SET
				`price` = :price,
				`factor` = :factor,
				`rate` = :rate
			WHERE
				`source_id` = :source_id AND
				`table_id` = :table_id AND
				`currency_iso_from` = :currency_iso_from AND
				`currency_iso_to` = :currency_iso_to AND
				`date` = :date
		";
		
		return $sSql;
	}
	
	/**
	 * Gibt den Query zurück, welcher die Daten in der Datenbank als neuen Eintrag anlegt
	 * @return string 
	 */
	protected function _insertRate() {
		
		$sSql = "
			INSERT INTO
				`tc_exchangerates_tables_rates`
				(`source_id`, `table_id`, `currency_iso_from`, `currency_iso_to`, `price`, `factor`, `rate`, `date`)
			VALUES (:source_id, :table_id, :currency_iso_from, :currency_iso_to, :price, :factor, :rate, :date)
			";
		
		return $sSql;
	}	
	
	/**
	 * Array für PreparedQuery
	 * @return array 
	 */
	protected function _getSqlArray($bPrice = true) {
		
		$aSql = array(
			'source_id'			=> (int)$this->_aRate['source_id'],
			'table_id'			=> (int)$this->_aRate['table_id'],
			'currency_iso_from' => $this->_aRate['currency_iso_from'],
			'date'				=> $this->_aRate['date'],
			'currency_iso_to'	=> $this->_aRate['currency_iso_to']			
		);
		
		if($bPrice === true) {
			$aSql['price'] = $this->_aRate['price'];
			$aSql['factor'] = $this->_aRate['factor'];
			$aSql['rate'] = $this->_aRate['rate'];
		}

		return $aSql;
	}
}
