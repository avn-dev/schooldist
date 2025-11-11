<?php

class Ext_TS_System_Checks_Hubspot_TravellerHubspotId extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Sets values for a new internal column regarding the hubspot id of the traveller.';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck()
	{

		if (!Util::checkTableExists('ts_hubspot_ids')) {
			// Tabelle gibt es nur, wenn Hubspot installiert ist. Dementsprechend einfach true weil ohne Hubspot installiert
			// muss auch nichts gemacht werden.
			return true;
		}

		set_time_limit(3600);
		ini_set('memory_limit', '4G');

		$success = Util::backupTable('ts_hubspot_ids');
		if(!$success) {
			return false;
		}

		$sql = "
			SELECT
				entity_id
			FROM
				ts_hubspot_ids
			WHERE
				entity = :entity
		";

		$rows = DB::getQueryRows($sql, ['entity' => 'Ext_TS_Inquiry']);

		foreach ($rows as $row) {
			$inquiry = Ext_TS_Inquiry::getInstance($row['entity_id']);
			$traveller = $inquiry->getTraveller();

			$sql = "
				SELECT
					hubspot_id
				FROM
					ts_hubspot_ids
				WHERE
					entity = :entity AND
					entity_id = :entity_id
			";

			$travellerHubspotId = DB::getQueryOne($sql, ['entity' => 'Ext_TS_Inquiry_Contact_Traveller', 'entity_id' => $traveller->id]);

			DB::updateData('ts_hubspot_ids', ['traveller_hubspot_id' => $travellerHubspotId], ['entity_id' => $inquiry->id, 'entity' => 'Ext_TS_Inquiry']);
		}

		return parent::executeCheck();
	}

}