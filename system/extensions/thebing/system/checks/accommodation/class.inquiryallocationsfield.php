<?php

/**
 * Dieser Check aktualisiert alle Buchungen im Index, welche laut Index eine Zuweisung zu einem Unterkunftsanbieter haben,
 * aber laut Datenbank keine aktive Zuweisung mehr haben sollten. Durch das Vorhandensein der ID im Index wird die Buchung
 * fälschlicherweise in der Simple View des entsprechenden Anbieters angezeigt.
 *
 * https://redmine.thebing.com/redmine/issues/6732
 */
class Ext_Thebing_System_Checks_Accommodation_InquiryAllocationsField extends GlobalChecks {

	public function getTitle() {
		return 'Check allocations to accommodation providers (simple view)';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		$iFoundInquiries = 0;

		// Alle Buchungen suchen, welche laut Index-Spalte eine Zuweisung zu einer Unterkunft haben
		$oSearch = new \ElasticaAdapter\Facade\Elastica(Ext_Gui2_Index_Generator::createIndexName('ts_inquiry'));
		$oSearch->setFields(array('id', 'allocated_accommodations'));
		$oQuery = new \Elastica\Query\QueryString('_exists_:allocated_accommodations');
		$oSearch->addQuery($oQuery);
		$oSearch->setLimit(999999);

		$aResult = $oSearch->search();

		$this->logInfo(sprintf('Found %d rows with set allocated_accommodations', count($aResult['hits'])));

		foreach($aResult['hits'] as $aRow) {

			$iInquiryId = $aRow['fields']['id'];

			// Spalte ist Array mit Ints oder Int direkt
			$aProviderIds = $aRow['fields']['allocated_accommodations'];
			if(is_int($aProviderIds)) {
				$aProviderIds = array($aProviderIds);
			}

			// Query zum Prüfen, ob Buchung eine aktive Zuweisung hat
			$sSql = "
				SELECT
					`kaa`.*
				FROM
					`ts_inquiries` `ts_i` INNER JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` INNER JOIN
					`ts_inquiries_journeys_accommodations` `ts_ija` ON
						`ts_ija`.`journey_id` = `ts_ij`.`id`  AND
						`ts_ija`.`active` = 1 INNER JOIN
					`kolumbus_accommodations_allocations` `kaa` ON
						`kaa`.`inquiry_accommodation_id` = `ts_ija`.`id` INNER JOIN
					`kolumbus_rooms` `kr` ON
						`kr`.`id` = kaa.`room_id` INNER JOIN
					`customer_db_4` `cdb4` ON
						`cdb4`.`id` = `kr`.`accommodation_id`
				WHERE
					`ts_i`.`id` = :inquiry_id AND
					`ts_i`.`active` = 1 AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 AND
					`cdb4`.`id` IN ( :provider )
			";

			$aQueryResult = (array)DB::getQueryRows($sSql, array(
				'inquiry_id' => $iInquiryId,
				'provider' => $aProviderIds
			));

			// Keine aktive Zuweisung gefunden, aber laut Index gibt es eine: Buchung nicht aktuell
			if(empty($aQueryResult)) {

				Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 0);

				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				$oSchool = $oInquiry->getSchool();
				$oCustomer = $oInquiry->getCustomer();

				$this->logInfo(vsprintf('Found inquiry %d (%s, %s), provider ids: %s', array(
					$oInquiry->id,
					$oSchool->getName(),
					$oCustomer->getCustomerNumber(),
					join(', ', $aProviderIds)
				)));

				$iFoundInquiries++;
			}

		}

		$this->logInfo(sprintf('Added %d inquiries to stack', $iFoundInquiries));

		Ext_Gui2_Index_Stack::executeCache();

		$this->logInfo('Finished executeCache()');

		return true;

	}

}