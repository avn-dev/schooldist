<?php

class Ext_Thebing_System_Checks_Accommodation_CheckAllocations extends GlobalChecks {

	public function getTitle() {
		return 'Check allocations of accommodation bookings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		$aRows = $this->_checkAccommodations();
		
		__out($aRows);
		
		return true;

	}

	protected function _checkAccommodations() {

		$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation();
		$aAccommodations = $oAccommodation->getObjectList();
		$aInquiries = array();

		// Problematische Unterkünfte finden und gruppieren nach Buchung
		foreach($aAccommodations as $oJourneyAccommodation) {

			if(!$oJourneyAccommodation->checkAllocationContext()) {
				$oInquiry = $oJourneyAccommodation->getInquiry();
				$aInquiries[$oInquiry->id][] = $oJourneyAccommodation->id;
			}
		}

		$aEntries = array();
		foreach($aInquiries as $iInquiryId => $aJourneyAccommodationIds) {

			$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
			$oCustomer = $oInquiry->getCustomer();
			$oSchool = $oInquiry->getSchool();

			$aRows = array();
			$aRows[] = 'School: '.$oSchool->getName().' ('.$oSchool->id.')';
			$aRows[] = 'Customer number: '.$oCustomer->getCustomerNumber().' (Inquiry-ID: '.$oInquiry->id.')';
			$aRows[] = 'Problematic Accommodations: '.join(', ', $aJourneyAccommodationIds);
			$aRows[] = '';
			$aRows[] = 'Allocations:';

			$aAllocations = $this->_getAllocations($oInquiry);
			foreach($aAllocations as $aAllocation) {

				if(
					$aAllocation['allocation_active'] == 0 ||
					$aAllocation['allocation_status'] != 0
				) {
					$sStatus = 'Deleted';
				} else {
					$sStatus = 'Active';
				}

				$aAllocationRow = array();
				$aAllocationRow[] = 'ID: '.$aAllocation['allocation_id'];
				$aAllocationRow[] = 'From: '.$aAllocation['allocation_from'];
				$aAllocationRow[] = 'Until: '.$aAllocation['allocation_until'];
				$aAllocationRow[] = 'Accommodation: '.$aAllocation['accommodation_label'].' ('.$aAllocation['accommodation_from'].' - '.$aAllocation['accommodation_until'].') ('.$aAllocation['accommodation_id'].')';
				$aAllocationRow[] = 'Provider: '.$aAllocation['provider_name'].' ('.$aAllocation['provider_id'].')';
				$aAllocationRow[] = 'Room: '.$aAllocation['room_name'].' ('.$aAllocation['room_id'].')';
				$aAllocationRow[] = 'Status: '.$sStatus.' ('.$aAllocation['allocation_active'].'/'.$aAllocation['allocation_status'].')';

				$aRows[] = join('; ', $aAllocationRow);
			}

			$aEntries[] = $aRows;
		}

		return $aEntries;
	}

	protected function _getAllocations(Ext_TS_Inquiry $oInquiry) {

		$sSql = "
			SELECT
				`kaa`.`id` `allocation_id`,
				`kaa`.`from` `allocation_from`,
				`kaa`.`until` `allocation_until`,
				`kaa`.`active` `allocation_active`,
				`kaa`.`status` `allocation_status`,
				`kaa`.`room_id` `room_id`,
				`ts_ija`.`id` `accommodation_id`,
				CONCAT(`kac`.`short_en`, '/', `kar`.`short_en`, '/', `kam`.`short_en`) `accommodation_label`,
				`ts_ija`.`from` `accommodation_from`,
				`ts_ija`.`until` `accommodation_until`,
				`kr`.`name` `room_name`,
				`cdb4`.`id` `provider_id`,
				`cdb4`.`ext_33` `provider_name`
			FROM
				 `kolumbus_accommodations_allocations` `kaa` INNER JOIN
				 `ts_inquiries_journeys_accommodations` `ts_ija` ON
				 	`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` INNER JOIN
				 `ts_inquiries_journeys` `ts_ij` ON
				 	`ts_ij`.`id` = `ts_ija`.`journey_id` LEFT JOIN
				 `kolumbus_accommodations_categories` `kac` ON
				 	`kac`.`id` = `ts_ija`.`accommodation_id` LEFT JOIN
				 `kolumbus_accommodations_roomtypes` `kar` ON
				 	`kar`.`id` = `ts_ija`.`roomtype_id` LEFT JOIN
				 `kolumbus_accommodations_meals` `kam` ON
				 	`kam`.`id` = `ts_ija`.`meal_id` LEFT JOIN
				 `kolumbus_rooms` `kr` ON
				 	`kr`.`id` = `kaa`.`room_id` LEFT JOIN
				 `customer_db_4` `cdb4` ON
				 	`cdb4`.`id` = `kr`.`accommodation_id`
			WHERE
				`ts_ij`.`inquiry_id` = :inquiry_id
			GROUP BY
				`kaa`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql, array(
			'inquiry_id' => $oInquiry->id
		));

		return $aResult;
	}

}