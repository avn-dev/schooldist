<?php

class Ext_Office_Employee_Contract extends WDBasic {

	protected $_sTable = 'office_employee_contract_data';

	public function getDuration($sFrom = null, $sUntil = null) {

		$sObjectFrom = $this->from;
		$sObjectFrom = substr($sObjectFrom, 0, 10);
		$sObjectUntil = $this->until;
		$sObjectUntil = substr($sObjectUntil, 0, 10);
		if($sObjectFrom == '0000-00-00') {
			$sObjectFrom = $sFrom;
		}
		if($sObjectUntil == '0000-00-00') {
			$sObjectUntil = $sUntil;
		}

		$oObjectFrom = new WDDate($sObjectFrom, WDDate::DB_DATE);
		$oObjectUntil = new WDDate($sObjectUntil, WDDate::DB_DATE);

		$oFrom = new WDDate($sFrom, WDDate::DB_DATE);
		$oUntil = new WDDate($sUntil, WDDate::DB_DATE);

		$iCompareFrom = $oObjectFrom->compare($oFrom);
		if($iCompareFrom > 0) {
			$oFrom = $oObjectFrom;
		}

		$iCompareUntil = $oObjectUntil->compare($oUntil);
		if($iCompareUntil < 0) {
			$oUntil = $oObjectUntil;
		}

		$oUntil->set(23, WDDate::HOUR);
		$oUntil->set(59, WDDate::MINUTE);
		$oUntil->set(59, WDDate::SECOND);

		// Checken ob der Monat voll ist
		if(
			$oFrom->get(WDDate::DAY) == 1 &&
			$oFrom->get(WDDate::MONTH) == 1 &&
			$oUntil->get(WDDate::DAY) == $oUntil->get(WDDate::MONTH_DAYS)
		) {
			$iMonth = $oUntil->getDiff(WDDate::MONTH, $oFrom)+1;
			$fFactor = $iMonth / 12;
		} else {
			$iDays = $oUntil->getDiff(WDDate::DAY, $oFrom)+1;
			$fFactor = $iDays / $oUntil->get(WDDate::YEAR_DAYS);
		}

		return $fFactor;

	}

	public function getHolidays($fFactor) {

		$fDays = round($this->holiday * $fFactor, 2);
		return $fDays;

	}

	/**
	 * Return the years between max and min of start date
	 * 
	 * @return array : The list of years
	 */
	static public function getAvailableYears() {

		$sSQL = "
			SELECT 
				MAX(YEAR(`from`)) AS `max`,
				MIN(YEAR(`from`)) AS `min`
			FROM
				`office_employee_contract_data`
			WHERE
				`active` = 1
		";
		$aBorderYears = DB::getQueryRow($sSQL);

		$iMaxYear = max($aBorderYears['max'], date('Y')+2);

		$aYears = array();
		for($i = $iMaxYear; $i >= $aBorderYears['min']; $i--) {
			$aYears[(int)$i] = (int)$i;
		}

		if(empty($aYears)) {
			$aYears[date('Y')] = date('Y');
		}

		return $aYears;
	}

	/**
	 * Berechnet die Soll-Stunden pro Arbeitstag im angegebenen Monat.
	 *
	 * Folgende Felder müssen in $aContractData vorhanden sein:
	 *
	 * - hours_type
	 * - hours_per_week | hours_per_month (je nach "hours_type")
	 * - days_per_week
	 *
	 * Die Felder gehören zur Tabelle "office_employee_contract_data", weitere Felder des Vertrags werden
	 * NICHT geprüft (z.B. ob der Vertrag überhaupt für den angegeben Monat gültg ist).
	 *
	 * Wenn Felder fehlen werden diese mit folgenden Standardwerten in die Berechnung einbezogen (es gibt
	 * keine Warnungen oder Fehlermeldungen bei fehlenden Feldern):
	 *
	 * - hours_type = "week"
	 * - hours_per_week = 40
	 * - hours_per_month = 160
	 * - days_per_week = 5
	 *
	 * Die Berechnung erfolgt nur auf Basis der Wochentage und "days_per_week", es wird immer bei Montag
	 * angefangen (sprich bei "days_per_week = 1" muss nur Montags gearbeitet werden, bei "days_per_week = 3"
	 * von Montag bis Mittwoch, usw.).
	 *
	 * Feiertage usw. werden hier nicht beachtet, da diese die Soll-Stunden pro Werktag nicht verringern sondern
	 * nur das Monats-Soll senken (wenn ein Tag ein Feiertag ist muss der Mitarbeiter entsprechend weniger Stunden
	 * in diesem Monat arbeiten).
	 *
	 * @param mixed[] $aContractData Daten des Vertrags
	 * @param integer $iYear
	 * @param integer $iMonth
	 * @return float
	 */
	public static function calculateHoursPerDayInMonth(array $aContractData, $iYear, $iMonth) {

		if(
			!isset($aContractData['hours_type']) ||
			empty($aContractData['hours_type'])
		) {
			$aContractData['hours_type'] = 'week';
		}

		if(
			!isset($aContractData['hours_per_week']) ||
			!is_numeric($aContractData['hours_per_week']) ||
			$aContractData['hours_per_week'] <= 0
		) {
			$aContractData['hours_per_week'] = 40;
		}

		if(
			!isset($aContractData['hours_per_month']) ||
			!is_numeric($aContractData['hours_per_month']) ||
			$aContractData['hours_per_month'] <= 0
		) {
			$aContractData['hours_per_month'] = 160;
		}

		if(
			!isset($aContractData['days_per_week']) ||
			!is_numeric($aContractData['days_per_week']) ||
			$aContractData['days_per_week'] <= 0
		) {
			$aContractData['days_per_week'] = 5;
		}

		$fHoursPerDay = 0.00;

		switch($aContractData['hours_type']) {

			case 'month':
				$oDay = new WDDate(mktime(0, 0, 0, $iMonth, 1, $iYear));
				$iWorkDays = 0;
				while($oDay->get(WDDate::MONTH) == $iMonth) {
					if(self::isWorkday($aContractData, $oDay)) {
						$iWorkDays++;
					}
					$oDay->add(1, WDDate::DAY);
				}
				$fHoursPerDay = (
					$aContractData['hours_per_month'] /
					$iWorkDays
				);
				break;

			case 'week':
			default:
				$fHoursPerDay = (
					$aContractData['hours_per_week'] /
					$aContractData['days_per_week']
				);
				break;

		}

		return $fHoursPerDay;

	}

	/**
	 * Ermittelt ob der angegebene Tag für den angegebenen Vertrag ein Werktag ist.
	 *
	 * Folgende Felder müssen in $aContractData vorhanden sein:
	 *
	 * - days_per_week
	 *
	 * Die Felder gehören zur Tabelle "office_employee_contract_data", weitere Felder des Vertrags werden
	 * NICHT geprüft (z.B. ob der Vertrag überhaupt für den angegeben Tag gültig ist).
	 *
	 * Wenn Felder fehlen werden diese mit folgenden Standardwerten in die Berechnung einbezogen (es gibt
	 * keine Warnungen oder Fehlermeldungen bei fehlenden Feldern):
	 *
	 * - days_per_week = 5
	 *
	 * Die Berechnung erfolgt nur auf Basis der Wochentage und "days_per_week", es wird immer bei Montag
	 * angefangen (sprich bei "days_per_week = 1" muss nur Montags gearbeitet werden, bei "days_per_week = 3"
	 * von Montag bis Mittwoch, usw.).
	 *
	 * Feiertage usw. werden hier nicht beachtet, es wird nur ermittelt ob der angegebene Tag generell ein Werktag
	 * für diesen Vertrag ist.
	 *
	 * @param mixed[] $aContractData Daten des Vertrags
	 * @param WDDate $oDay
	 * @return boolean
	 */
	public static function isWorkday(array $aContractData, WDDate $oDay) {

		if($oDay->get(WDDate::WEEKDAY) <= $aContractData['days_per_week']) {

			return true;

		}

		return false;

	}

}
