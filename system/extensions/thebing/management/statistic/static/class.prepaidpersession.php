<?php

use Core\Helper\DateTime;

/**
 * Statische Statistik – Vorauszahlung pro Leistungszeitraum (ELC)
 * https://redmine.thebing.com/redmine/issues/8457
 *
 * Selbe Statistik wie Ertrag pro Leistungszeitraum, nur mit Beträgen der Zahlungen
 */
class Ext_Thebing_Management_Statistic_Static_PrepaidPerSession extends Ext_Thebing_Management_Statistic_Static_PerSessionRevenue {

	protected $bUsePayments = true;

	public static function getTitle() {
		return self::t('Vorauszahlung pro Zeitraum');
	}

	/**
	 * Payment-Beträge benutzen
	 *
	 * @param array $aItem
	 * @param array $aOptions
	 * @return float|int
	 */
	protected function getItemAmount(array $aItem, array $aOptions = array()) {

		$dFrom = new DateTime($aItem['item_from']);
		$dUntil = new DateTime($aItem['item_until']);
		$fAmount = (float)$aItem['payment_amount'];

		if(
			$aItem['item_costs_charge'] == 0 &&
			in_array($aItem['item_type'], ['additional_general', 'additional_course', 'additional_accommodation'])
		) {
			// Einmalige Kosten müssen mit ihrem Starttag in den Zeitraum fallen
			if(!$dFrom->isBetween($this->dFrom, $this->dUntil)) {
				$fAmount = 0;
			}

		} else {
			// Alles immer aufteilen
			$fAmount = Ext_TC_Util::getSplittedAmountByDates($fAmount, $this->dFrom, $this->dUntil, $dFrom, $dUntil);
		}

		return $fAmount;

	}

}