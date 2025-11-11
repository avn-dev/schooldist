<?php

/**
 * Ticket #11031 – ELC - Per session revenue - Income Spalten oftmals leer
 * https://redmine.thebing.com/redmine/issues/11031
 */
class Ext_Thebing_System_Checks_Enquiry_FixConvertedDocumentItems extends GlobalChecks {

	private $aOfferServiceIds = [];

	public function getDescription() {
		return 'Allocate correct service ids to invoices created from converted offers.';
	}

	public function getTitle() {
		return 'Fix for converted enquiries\' document items';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		if(!Util::backupTable('kolumbus_inquiries_documents_versions_items')) {
			throw new RuntimeException('Backup failed!');
		}

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`kidvi`.`id` `item_id`,
				`kidvi`.`type` `item_type`,
				`kidvi`.`type_id` `item_type_id`,
				`kidvi`.`parent_booking_id` `item_parent_booking_id`,
				`kidvi`.`additional_info` `item_additional_info`,
				`kidvi`.`contact_id` `contact_id`,
				`kidvi`.`created` `item_created`,
				`ts_ij`.`id` `journey_id`,
				`ts_eoti`.`enquiry_offer_id`,
				GROUP_CONCAT(DISTINCT `ts_ijc`.`id`) `course_ids`,
				GROUP_CONCAT(DISTINCT `ts_ija`.`id`) `accommodations_ids`,
				GROUP_CONCAT(DISTINCT `ts_ijt`.`id`) `tranfers_ids`,
				GROUP_CONCAT(DISTINCT `ts_iji`.`id`) `insurances_ids`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`ts_enquiries_offers_to_inquiries` `ts_eoti` ON
					`ts_eoti`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`inquiry_id` = `ts_i`.`id` AND
					`kid`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`document_id` = `kid`.`id` AND 
					`kidv`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 LEFT JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` LEFT JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`journey_id` = `ts_ij`.`id` LEFT JOIN
				`ts_inquiries_journeys_transfers` `ts_ijt` ON
					`ts_ijt`.`journey_id` = `ts_ij`.`id` LEFT JOIN
				`ts_inquiries_journeys_insurances` `ts_iji` ON
					`ts_iji`.`journey_id` = `ts_ij`.`id`
			WHERE
				/* Relevant dürften erst Einträge ab Mitte 2016 sein */
				`kidvi`.`created` >= '2015-01-01'
			GROUP BY
				`kidvi`.`id`
		";

		$aItems = DB::getQueryRows($sSql);

		foreach($aItems as $aItem) {

			$sItemType = $aItem['item_type'];
			$sItemIdKey = 'item_type_id';

			switch($aItem['item_type']) {
				case 'course':
				case 'additional_course':
					$sItemType = 'course';
					$aJourneyServiceIds = explode(',', $aItem['course_ids']);

					if($aItem['item_type'] === 'additional_course') {
						$sItemIdKey = 'item_parent_booking_id';
					}
					break;
				case 'accommodation':
				case 'additional_accommodation':
				case 'extra_nights':
				case 'extra_weeks':
					$sItemType = 'accommodation';
					$aJourneyServiceIds = explode(',', $aItem['accommodation_ids']);

					if($aItem['item_type'] === 'additional_accommodation') {
						$sItemIdKey = 'item_parent_booking_id';
					}
					break;
				case 'transfer':
					$aJourneyServiceIds = explode(',', $aItem['tranfers_ids']);
					break;
				case 'insurance':
					$aJourneyServiceIds = explode(',', $aItem['insurances_ids']);
					break;
				default:
					continue 2;
			}

			// Type-ID existiert nicht in den eigenen Services der Buchung
			if(!in_array($aItem[$sItemIdKey], $aJourneyServiceIds)) {

				// Type-ID existiert aber als Kombinations-Service im Angebot, aus dem die Buchung erzeugt wurde
				$aOfferCourseCombationIds = $this->getOfferServiceIds($sItemType, $aItem['enquiry_offer_id'], $aItem['contact_id']);
				if(in_array($aItem[$sItemIdKey], $aOfferCourseCombationIds)) {

					$oInquiry = Ext_TS_Inquiry::getInstance($aItem['inquiry_id']);
					$iMatchingId = null;

					switch($aItem['item_type']) {
						case 'course':
						case 'additional_course':

							$oCombinationCourse = Ext_TS_Enquiry_Combination_Course::getInstance($aItem[$sItemIdKey]);

							foreach($oInquiry->getCourses() as $oJourneyCourse) {
								if(
									$oJourneyCourse->course_id == $oCombinationCourse->course_id &&
									$oJourneyCourse->level_id == $oCombinationCourse->level_id &&
									$oJourneyCourse->weeks == $oCombinationCourse->weeks &&
									$oJourneyCourse->from == $oCombinationCourse->from &&
									$oJourneyCourse->until == $oCombinationCourse->until &&
									$oJourneyCourse->units == $oCombinationCourse->units
								) {
									$iMatchingId = $oJourneyCourse->id;
									break;
								}
							}

							if($iMatchingId === null) {
								foreach($oInquiry->getCourses() as $oJourneyCourse) {
									if($oJourneyCourse->course_id == $oCombinationCourse->course_id) {
										$iMatchingId = $oJourneyCourse->id;
										break;
									}
								}
							}

							break;

						case 'accommodation':
						case 'additional_accommodation':
						case 'extra_nights':
						case 'extra_weeks':

							$oCombinationAccommodation = Ext_TS_Enquiry_Combination_Accommodation::getInstance($aItem[$sItemIdKey]);

							foreach($oInquiry->getAccommodations() as $oJourneyAccommodation) {
								if(
									$oJourneyAccommodation->accommodation_id == $oCombinationAccommodation->accommodation_id &&
									$oJourneyAccommodation->roomtype_id == $oCombinationAccommodation->roomtype_id &&
									$oJourneyAccommodation->meal_id == $oCombinationAccommodation->meal_id &&
									$oJourneyAccommodation->from == $oCombinationAccommodation->from &&
									$oJourneyAccommodation->until == $oCombinationAccommodation->until &&
									$oJourneyAccommodation->weeks == $oCombinationAccommodation->weeks
								) {
									$iMatchingId = $oJourneyAccommodation->id;
									break;
								}
							}

							if($iMatchingId === null) {
								foreach($oInquiry->getAccommodations() as $oJourneyAccommodation) {
									if($oJourneyAccommodation->accommodation_id == $oCombinationAccommodation->accommodation_id) {
										$iMatchingId = $oJourneyAccommodation->id;
										break;
									}
								}
							}

							break;

						case 'transfer':

							$oCombinationTransfer = Ext_TS_Enquiry_Combination_Transfer::getInstance($aItem[$sItemIdKey]);

							foreach($oInquiry->getTransfers() as $oJourneyTransfer) {
								if(
									$oJourneyTransfer->transfer_type == $oCombinationTransfer->transfer_type
									//$oJourneyTransfer->start == $oCombinationTransfer->start &&
									//$oJourneyTransfer->end == $oCombinationTransfer->end &&
									//$oJourneyTransfer->start_type == $oCombinationTransfer->start_type &&
									//$oJourneyTransfer->end_type == $oCombinationTransfer->end_type //&&
									//$oJourneyTransfer->transfer_date == $oCombinationTransfer->transfer_date &&
									//$oJourneyTransfer->transfer_time == $oCombinationTransfer->transfer_time
								) {
									$iMatchingId = $oJourneyTransfer->id;
									break;
								}
							}

							break;

						case 'insurance':

							$oCombinationInsurance = Ext_TS_Enquiry_Combination_Insurance::getInstance($aItem[$sItemIdKey]);

							foreach($oInquiry->getInsurances() as $oJourneyInsurance) {
								if(
									$oJourneyInsurance->insurance_id == $oCombinationInsurance->insurance_id &&
									$oJourneyInsurance->from == $oCombinationInsurance->from &&
									$oJourneyInsurance->until == $oCombinationInsurance->until
								) {
									$iMatchingId = $oJourneyInsurance->id;
									break;
								}
							}

							if($iMatchingId === null) {
								foreach($oInquiry->getInsurances() as $oJourneyInsurance) {
									if($oJourneyInsurance->insurance_id == $oCombinationInsurance->insurance_id) {
										$iMatchingId = $oJourneyInsurance->id;
										break;
									}
								}
							}

							break;

						default:
							throw new RuntimeException('Type '.$aItem['item_type'].' missing but defined in first switch!');
					}

					if($iMatchingId === null) {
						$this->logError('Item '.$aItem['item_id'].': Could not find a matching journey service! (item created: '.$aItem['item_created'].')', $aItem);
						continue;
					}

					$sKey = str_replace('item_', '', $sItemIdKey);
					DB::updateData('kolumbus_inquiries_documents_versions_items', [$sKey => $iMatchingId], " `id` = ".$aItem['item_id']);

					$this->logInfo('Item '.$aItem['item_id'].': Allocated journey service '.$iMatchingId.' (was '.$aItem[$sItemIdKey].')', $aItem);

				}

			}
		}

		DB::commit(__CLASS__);

		return true;

	}

	/**
	 * Alle Ids der Kombinationen, gruppiert nach Service-Typ
	 *
	 * @param $sType
	 * @param $iOfferId
	 * @param $iContactId
	 * @return mixed
	 */
	private function getOfferServiceIds($sType, $iOfferId, $iContactId) {

		if(!isset($this->aOfferServiceIds[$iOfferId])) {
			$sSql = "
				SELECT
					GROUP_CONCAT(DISTINCT `ts_eotcc`.`combination_course_id`) `course_ids`,
					GROUP_CONCAT(DISTINCT `ts_eotca`.`combination_accommodation_id`) `accommodation_ids`,
					GROUP_CONCAT(DISTINCT `ts_eotct`.`combination_transfer_id`) `transfer_ids`,
					GROUP_CONCAT(DISTINCT `ts_eotci`.`combination_insurance_id`) `insurance_ids`
				FROM
					`ts_enquiries_offers` `ts_eo` LEFT JOIN
					`ts_enquiries_offers_to_combinations_courses` `ts_eotcc` ON
						`ts_eotcc`.`offer_id` = `ts_eo`.`id` AND
						`ts_eotcc`.`contact_id` = :contact_id LEFT JOIN
					`ts_enquiries_offers_to_combinations_accommodations` `ts_eotca` ON
						`ts_eotca`.`offer_id` = `ts_eo`.`id` AND
						`ts_eotca`.`contact_id` = :contact_id LEFT JOIN
					`ts_enquiries_offers_to_combinations_transfers` `ts_eotct` ON
						`ts_eotct`.`offer_id` = `ts_eo`.`id` AND
						`ts_eotct`.`contact_id` = :contact_id LEFT JOIN
					`ts_enquiries_offers_to_combinations_insurances` `ts_eotci` ON
						`ts_eotci`.`offer_id` = `ts_eo`.`id` AND
						`ts_eotci`.`contact_id` = :contact_id
				WHERE
					`ts_eo`.`id` = :offer_id
				GROUP BY
					`ts_eo`.`id`
			";

			$aResult = DB::getQueryRow($sSql, ['offer_id' => $iOfferId, 'contact_id' => $iContactId]);

			$aResult = array_map(function($sIds) {
				return explode(',', $sIds);
			}, $aResult);

			$this->aOfferServiceIds[$iOfferId] = $aResult;
		}

		return $this->aOfferServiceIds[$iOfferId][$sType.'_ids'];

	}
}