<?php
/**
 * https://redmine.thebing.com/redmine/issues/5047
 *
 * @since 09.07.2013
 * @author DG <dg@thebing.com>
 */
class Ext_Thebing_System_Checks_Inquiry_FixGroupFlags extends GlobalChecks {

	protected $_aLog = array();

	public function getTitle() {
		$sTitle = 'Check group flags of students';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check group flags of students';
		return $sDescription;
	}

	public function executeCheck() {
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$oDB = DB::getDefaultConnection();

		// Dieser Query sucht alle Kunden, die über die Kundensuche aus ihrer Gruppe gelöscht worden sind.
		// Dazu holt der Query aus der entsprechenden Buchung den neu-zugewiesenen Kontakt.
		$sSql = "
			SELECT
				`ts_jtd`.`traveller_id` `deleted_contact_id`,
				`ts_itc2`.`contact_id` `new_contact_id`,
				`ts_i`.`id` `inquiry_id`,
				`ts_ij`.`id` `journey_id`
			FROM
				`ts_journeys_travellers_detail` `ts_jtd`
			LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`contact_id` = `ts_jtd`.`traveller_id` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` =  `ts_jtd`.`journey_id` LEFT JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` =  `ts_ij`.`inquiry_id` LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` = `ts_i`.`group_id` LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc2` ON
					`ts_itc2`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc2`.`type` = 'traveller'
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_itc`.`inquiry_id` IS NULL AND
				`kg`.`id` IS NOT NULL -- Sollte eine Buchung aus einer Gruppe manuell gelöscht worden sein
			GROUP BY
				`ts_jtd`.`traveller_id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		DB::begin('Ext_Thebing_System_Checks_Inquiry_FixGroupFlags');

		// Alle Details mit der alten ID auf die neue ID updaten.
		// Ansonsten funktioniert das Speichern der beteiligten Gruppe nicht mehr!
		// Mit Objekten würde dies eine Exception auslösen wegen $_aFormat.
		foreach($aResult as $aData) {
			$sSql = "
				UPDATE
					`ts_journeys_travellers_detail` `ts_jtd`
				SET
					`traveller_id` = :new_contact_id
				WHERE
					`journey_id` = :journey_id AND
					`traveller_id` = :deleted_contact_id
			";

			DB::executePreparedQuery($sSql, $aData);
			$iAffectedRows = $oDB->_getAffectedRows();

			$this->logInfo(sprintf('Changed traveller_id from %d to %d for journey_id %d. Affected Records: %d',
				$aData['deleted_contact_id'],
				$aData['new_contact_id'],
				$aData['journey_id'],
				$iAffectedRows
			), $aData);
		}

		DB::commit('Ext_Thebing_System_Checks_Inquiry_FixGroupFlags');

		return true;
	}

}
