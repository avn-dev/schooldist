<?php

/**
 * Die Visum-Objekte sind neben der Journey auch einer traveller_id zugewiesen. Das wurde wohl mal zur Vorbereitung
 * auf die Gruppenumstellung eingebaut. Leider wurde die traveller_id aber nicht überall erzwungen und so gibt es
 * auch Einträge mit 0, die nicht existieren dürfen. Das wurde vor allem durch das Formular (alt und neu) ausgelöst.
 *
 * Wenn nun ein Eintrag mit 0 existierte und man speicherte den Visum-Dialog mit neuen Datum, wurdem immer nur die
 * alten Daten angezeigt, da der neue Eintrag eben mit einer traveller_id gespeichert wurde. Zusätzlich existieren
 * auch einfach mal mehrere Einträge zu einer Journey, was eigentlich nicht möglich ist.
 *
 * Dieser Check korrigiert die Einträge mit traveller_id = 0 und löscht zudem alle doppelten Einträge (älteste zuerst).
 * Daten, die im neuen nicht vorkommen, aber noch im alten, werden übernommen. An sich sollte das aber keine großen
 * Auswirkungen haben.
 */
class Ext_TS_System_Checks_Inquiry_Journey_VisaTravellerId extends GlobalChecks {

	/**
	 * @see \Ext_TS_Inquiry_Journey_Visa::isAllEmpty()
	 * @var array
	 */
	private $aJourneyVisaFields = [
		'servis_id' => 'empty',
		'tracking_number' => 'empty',
		'status' => 'empty',
		'required' => 'empty',
		'passport_number' => 'empty',
		'passport_date_of_issue' => 'date',
		'passport_due_date' => 'date',
		'date_from' => 'date',
		'date_until' => 'date'
	];

	public function getTitle() {
		return 'Fix journey visa data';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		Util::backupTable('ts_journeys_travellers_visa_data');

		$this->addTimestampColumns();

		$this->fixVisaEntries();

		// Nach der Bereinigung muss das jetzt eigentlich funktionieren
		DB::executeQuery("ALTER TABLE ts_journeys_travellers_visa_data DROP INDEX journey_id");
		DB::executeQuery("ALTER TABLE `ts_journeys_travellers_visa_data` ADD UNIQUE( `journey_id`)");

		return true;

	}

	/**
	 * created und changed hinzufügen, da diese Spalten bisher komplett fehlten
	 */
	private function addTimestampColumns() {

		$aFields = DB::describeTable('ts_journeys_travellers_visa_data', true);
		if(
			!isset($aFields['created']) ||
			!isset($aFields['changed'])
		) {
			DB::executeQuery("ALTER TABLE `ts_journeys_travellers_visa_data`
				ADD `created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `id`,
				ADD `changed` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created`
			;");

			DB::executeQuery("
				UPDATE
					`ts_journeys_travellers_visa_data` `ts_jtvd` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`id` = `ts_jtvd`.`journey_id`
				SET
					`ts_jtvd`.`created` = `ts_ij`.`created`,
					`ts_jtvd`.`changed` = `ts_ij`.`changed`
				WHERE
					`ts_jtvd`.`created` = 0 OR
					`ts_jtvd`.`changed` = 0
			");
		}

	}

	/**
	 * Siehe Klassen-Kommentar
	 */
	private function fixVisaEntries() {

		DB::begin(__METHOD__);

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_itc`.`contact_id` `itc_contact_id`,
				`ts_jtvd`.*
			FROM
				`ts_journeys_travellers_visa_data` `ts_jtvd` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_jtvd`.`journey_id` LEFT JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller'
			GROUP BY
				`ts_jtvd`.`id`
			ORDER BY
				`ts_jtvd`.`id` DESC
		";

		$aResult = (array)DB::getQueryRows($sSql);
		$aJourneyVisa = [];

		foreach($aResult as $aRow) {
			if(
				empty($aRow['inquiry_id']) ||
				empty($aRow['itc_contact_id'])
			) {
				// Mit diesen Einträgen kann man nichts mehr anfangen
				DB::executePreparedQuery("DELETE FROM `ts_journeys_travellers_visa_data` WHERE `id` = :id", ['id' => $aRow['id']]);
				$this->logInfo('Visa delete: No inquiry or traveller', $aRow);
			} else {
				$aJourneyVisa[$aRow['journey_id']][] = $aRow;
			}
		}

		foreach($aJourneyVisa as $aVisas) {

			$aLatestVisa = array_shift($aVisas);

			// traveller_id updaten, falls nicht Traveller der Buchung
			// traveller_id kann hier wegen einem Fehler im Anmeldeformular auch 0 sein
			if($aLatestVisa['traveller_id'] != $aLatestVisa['itc_contact_id']) {
				DB::updateData('ts_journeys_travellers_visa_data', ['traveller_id' => $aLatestVisa['itc_contact_id']], "`id` = ".$aLatestVisa['id']);
				$this->logInfo('Visa update: Set traveller_id = '.$aLatestVisa['itc_contact_id'].' ('.$aLatestVisa['traveller_id'].' before)', $aLatestVisa);
			}

			// Alle Visa durchlaufen (von neu nach alt) und Felder füllen, die leer sind
			$aUpdateData = [];
			foreach($aVisas as $aVisa) {
				foreach($this->aJourneyVisaFields as $sField => $sCheck) {
					if(
						!$this->checkVisaField($aLatestVisa, $sField) &&
						$this->checkVisaField($aVisa, $sField)
					) {
						$aUpdateData[$sField] = $aVisa[$sField];
						$aLatestVisa[$sField] = $aVisa[$sField]; // Damit neuester Wert nicht überschrieben wird
					}
				}

				DB::executePreparedQuery("DELETE FROM `ts_journeys_travellers_visa_data` WHERE `id` = :id", ['id' => $aVisa['id']]);
				$this->logInfo('Visa delete: Deleted multiple visa entry of journey', $aVisa);
			}

			if(!empty($aUpdateData)) {
				DB::updateData('ts_journeys_travellers_visa_data', $aUpdateData, "`id` = ".$aLatestVisa['id']);
				$this->logInfo('Visa update: Filled empty fields of previous visas ('.$aLatestVisa['id'].')', [$aUpdateData, $aLatestVisa]);
			}
		}

		DB::commit(__METHOD__);

	}

	/**
	 * @param array $aVisa
	 * @param string $sField
	 * @return bool
	 */
	private function checkVisaField($aVisa, $sField) {
		switch($this->aJourneyVisaFields[$sField]) {
			case 'empty':
				return !empty($aVisa[$sField]);
			case 'date':
				return $aVisa[$sField] !== '0000-00-00';
			default:
				throw new RuntimeException('Unknown check for field '.$sField);
		}
	}

}
