<?php

abstract class Ext_Gui2_View_Format_Date_Abstract extends Ext_Gui2_View_Format_Abstract {

	protected $aOption = array('format'=>'%x %X');
	protected $sWDDatePart = WDDate::DB_DATETIME;

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Kompatibilität
		if($mValue instanceof \DateTimeInterface) {
			$mValue = $mValue->getTimestamp();
		} else if(preg_match('/(([0-9]){4}\-([0-9]){2}\-([0-9]){2})T(([0-9]){2}\:([0-9]){2}\:([0-9]){2})/', $mValue) === 1) {
			$mValue = substr($mValue, 0, 10).' '.substr($mValue, 11, 8);
		}

		if(
			empty($mValue) ||
			$mValue == '0000-00-00' ||
			$mValue == '0000-00-00 00:00:00'
		) {
			return '';
		}

		$bIsUnixTimestamp = false;

		// Wenn Nummeric => unix timestamp
		if(
			is_numeric($mValue)
		){
			$bIsUnixTimestamp = true;
		}

		// Wenn (mysql) Timestamp dann rechne es in Unix Timestamp um!
		if(!$bIsUnixTimestamp){

			try {
				$oDate = new WDDate($mValue, $this->sWDDatePart);
				$mValue = $oDate->get(WDDate::STRFTIME, $this->format);
			} catch(Exception $e) {
				$mValue = $mValue;
			}

		} else {
			$mValue = strftime($this->format, $mValue);
	
		}

		return $mValue;

	}

	/**
	 * convert Date
	 * @success return converted date
	 * @failed return false
	 * @return type
	 */
	public function executeConvert($mValue, &$oColumn = null, &$aResultData = null){

		// Leere Werte können nicht convertet werden
		if(empty($mValue)) {
			return false;
		}

		try {

			// Timestamp/Format prüfen
			if(is_numeric($mValue)) {
				$sPart = WDDate::TIMESTAMP;
				$sFormat = null;
			} else {
				$sPart = WDDate::STRFTIME;
				$sFormat = $this->format;
			}

			// prüfen auf gültiges datum
			$bCheck = WDDate::isDate($mValue, $sPart, $sFormat);

			// wenn ungültig return false
			if(!$bCheck) {
				return false;
			}
			
			// convertieren
			$oDate = new WDDate($mValue, $sPart, $sFormat);
			$mValue = $oDate->get($this->sWDDatePart);

			return $mValue;
		} catch(Exception $e) {
			return false;
		}

	}

	/**
	 * try convert date
	 * @success return converted value
	 * @failed return input value
	 * @param string $mValue
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return mixed
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		$mConverted = $this->executeConvert($mValue, $oColumn, $aResultData);

		if($mConverted === false) {
			return $mValue;
		}

		return $mConverted;

	}

}