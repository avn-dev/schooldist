<?php

/**
 * Merkwürdige Probleme haben in der Preisliste dafür gesorgt, dass Preise mehrfach gespeichert werden konnten.
 * Zusätzlich gibt es keinen UNIQUE auf den entsprechenden Spalten. Der Query hat auch keinen GROUP BY, sodass
 * die Preise in der Versicherungspreisliste dann einfach mehrfach angezeigt werden. Wie immer bei den Versicherungen
 * alles wundervoll programmiert. Der Check löscht mehrfach vorhandene Preise und setzt endlich diesen dämlichen Index.
 * Der höchste Preis wird berücksichtigt.
 *
 * https://redmine.fidelo.com/issues/11876
 * https://redmine.fidelo.com/issues/14343
 */
class Ext_Thebing_System_Checks_Insurance_UniquePrices extends GlobalChecks {

	public function getTitle() {
		return 'Repair insurance prices';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_insurance_prices');

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_insurance_prices`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$aPrices = [];

		foreach($aResult as $aRow) {
			$sKey = $aRow['school_id'].'_'.$aRow['insurance_id'].'_'.$aRow['week_id'].'_'.$aRow['period_id'].'_'.$aRow['currency_id'];
			$aPrices[$sKey][] = $aRow;
		}

		foreach($aPrices as $aPriceGroup) {

			if(count($aPriceGroup) === 1) {
				continue;
			}

			// Inaktive Preise direkt löschen (falls es so etwas überhaupt gibt in dieser Tabelle)
			foreach($aPriceGroup as $iKey => $aPrice) {
				if(!$aPrice['active']) {
					$this->deletePrice($aPrice);
					unset($aPriceGroup[$iKey]);
				}
			}

			// Höchster Preis nach unten
			uasort($aPriceGroup, function($aPrice1, $aPrice2) {
				return (float)$aPrice1['price'] > (float)$aPrice2['price'];
			});

			array_pop($aPriceGroup);

			foreach($aPriceGroup as $aPrice) {
				$this->deletePrice($aPrice);
			}

		}

		DB::executeQuery("ALTER TABLE `kolumbus_insurance_prices` ADD UNIQUE(`school_id`, `insurance_id`, `week_id`, `period_id`, `currency_id`);");

		return true;

	}

	/**
	 * @param array $aPrice
	 */
	private function deletePrice(array $aPrice) {

		$sSql = "
					DELETE FROM
						`kolumbus_insurance_prices`
					WHERE
						`id` = :id
				";

		DB::executePreparedQuery($sSql, $aPrice);

		$this->logInfo('Deleted doubled insurance price', $aPrice);

	}

}
