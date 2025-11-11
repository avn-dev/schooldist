<?php

use Carbon\Carbon;

/**
 * @see Ext_Thebing_System_Checks_Accommodation_CheckAllocations
 */
class Ext_TS_System_Checks_Accommodation_RepairAllocations extends GlobalChecks {

	/**
	 * @var WDBasic_Persister
	 */
	private $persister;
	
	public function getTitle() {
		return 'Repairs allocations of accommodation bookings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$backup = Util::backupTable('kolumbus_accommodations_allocations');
		
		if($backup === false) {
			return false;
		}
		
		$this->persister = WDBasic_Persister::getInstance();
		
		set_time_limit(3600);
		ini_set('memory_limit', '4G');

		DB::begin(__CLASS__);
		
		$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation();
		$accommodations = $oAccommodation->getObjectList();
		
		$brokenAllocationCount = 0;

		// Problematische Unterkünfte finden und gruppieren nach Buchung
		foreach($accommodations as $journeyAccommodation) {
			
			$accommodationFrom = new Carbon($journeyAccommodation->from);
			
			if(
				$accommodationFrom->lt('- 10 years') ||
				$journeyAccommodation->checkAllocationContext(false) === true
			) {
				continue;
			}
			
			if(\System::d('debugmode')) {
				__pout($this->printOverview($journeyAccommodation));
			}
			
			$this->repairAllocations($journeyAccommodation);
			
			if(\System::d('debugmode')) {
				__pout($this->printOverview($journeyAccommodation));
			}
			
			$brokenAllocationCount++;
			
		}

		__pout($brokenAllocationCount);
		
		DB::commit(__CLASS__);
		
		$this->logInfo('Repair end', ['count'=>$brokenAllocationCount]);
		
		return true;
	}

	private function repairAllocations(Ext_TS_Inquiry_Journey_Accommodation $journeyAccommodation) {

		// Auch inaktive Zuweisungen holen, da ganzer Zeitraum geprüft wird
		$allocations = $journeyAccommodation->getAllocations(true, false, true);

		// Wenn keine Zuweisungen vorhanden sind, muss auch nichts geprüft werden
		if(empty($allocations)) {
			return true;
		}

		$this->logInfo('Repair start', ['journey_accommodation_id'=>$journeyAccommodation->id]);
		
		// Sortierung ist wichtig, da von der frühsten bis zur spätesten Zuweisung iteriert wird
		$sortedAllocations = Ext_Thebing_Allocation::sortAllocationsByDate($allocations);

		$accommodationFrom = new Carbon($journeyAccommodation->from);
		$accommodationUntil = new Carbon($journeyAccommodation->until);

		// Zuweisungen, die nicht im Zeitraum sind und nicht zugewiesen entfernen
		foreach($sortedAllocations as $sortedAllocationKey=>$allocation) {
			
			$allocationFrom = new Carbon($allocation->from);
			$allocationUntil = new Carbon($allocation->until);
			
			if(
				$allocationFrom < $accommodationFrom ||
				$allocationUntil > $accommodationUntil
			) {
				
				// Wenn die Zuweisung schon einem Zimmer/Bett zugewiesen ist, kann sie nicht gelöscht werden
				if($allocation->hasRoom()) {
					return false;
				}
				
				$this->logInfo('Delete allocation not in booking period', ['journey_accommodation_id'=>$journeyAccommodation->id, 'allocation_id'=>$allocation->id]);
				
				$allocation->bPaymentGenerationDeleteCheck = false;
				$allocation->status = 2;
				$allocation->matching_canceled = date('Y-m-d H:i:s');
				
				$this->persister->attach($allocation);
				
				unset($sortedAllocations[$sortedAllocationKey]);

			} elseif(!$allocation->hasRoom()) {
				
				// Alle Zuweisungen ohne Raum (noch nicht zugewiesen) werden entfernt
				
				$this->logInfo('Delete allocation without assignment', ['journey_accommodation_id'=>$journeyAccommodation->id, 'allocation_id'=>$allocation->id]);
				
				$allocation->bPaymentGenerationDeleteCheck = false;
				$allocation->status = 2;
				$allocation->matching_canceled = date('Y-m-d H:i:s');
				
				$this->persister->attach($allocation);

				unset($sortedAllocations[$sortedAllocationKey]);
				
			}
			
		}

		$currentFrom = $accommodationFrom->copy();
		
		// Eventuelle Lücken schliessen
		foreach($sortedAllocations as $allocation) {

			$allocationFrom = new Carbon($allocation->from);
			$allocationUntil = new Carbon($allocation->until);

			if($currentFrom != $allocationFrom) {

				$this->logInfo('Add allocation', [$currentFrom->toDateTimeString(), $allocationFrom->toDateTimeString()]);
				
				$newAllocation = new Ext_Thebing_Accommodation_Allocation();
				$newAllocation->inquiry_accommodation_id = $journeyAccommodation->id;
				$newAllocation->from = $currentFrom->toDateTimeString();
				$newAllocation->until = $allocationFrom->toDateTimeString();

				$this->persister->attach($newAllocation);
				
			}

			$currentFrom = $allocationUntil->copy();
			
		}
		
		if($allocationUntil != $accommodationUntil) {

			$this->logInfo('Add allocation at end', [$allocationUntil->toDateTimeString(), $accommodationUntil->toDateTimeString()]);

			$newAllocation = new Ext_Thebing_Accommodation_Allocation();
			$newAllocation->inquiry_accommodation_id = $journeyAccommodation->id;
			$newAllocation->from = $allocationUntil->toDateTimeString();
			$newAllocation->until = $accommodationUntil->toDateTimeString();

			$this->persister->attach($newAllocation);

		}

		// TODO $journeyAccommodation->checkAllocationContext() ?

		try {
			$this->persister->save();
		} catch(Throwable $e) {
			
			$this->logError($e->getMessage(), ['journey_accommodation_id'=>$journeyAccommodation->id]);
			
			__pout($this->persister);
			throw $e;
		}
		
		return true;
	}

	private function printOverview(Ext_TS_Inquiry_Journey_Accommodation $journeyAccommodation) {

		$oInquiry = $journeyAccommodation->getInquiry();
		$oCustomer = $oInquiry->getCustomer();
		$oSchool = $oInquiry->getSchool();

		$aRows = array();
		$aRows[] = 'School: '.$oSchool->getName().' ('.$oSchool->id.')';
		$aRows[] = 'Customer number: '.$oCustomer->getCustomerNumber().' (Inquiry-ID: '.$oInquiry->id.')';

		$aRows[] = 'Problematic Accommodation: '.$journeyAccommodation->id;
		$aRows[] = '';

		$aAllocations = $this->_getAllocations($oInquiry, $journeyAccommodation->id);

		$aAllocation = reset($aAllocations);
		$aRows[] = 'Accommodation: '.$aAllocation['accommodation_label'].' ('.$aAllocation['accommodation_from'].' - '.$aAllocation['accommodation_until'].') ('.$aAllocation['accommodation_id'].')';

		$aRows[] = 'Allocations:';

		foreach($aAllocations as $aAllocation) {

			if(
				$aAllocation['allocation_active'] == 0 ||
				$aAllocation['allocation_status'] != 0
			) {
				$sStatus = 'Deleted';
				continue;
			} else {
				$sStatus = 'Active';
			}

			$aAllocationRow = array();
			$aAllocationRow[] = 'ID: '.$aAllocation['allocation_id'];
			$aAllocationRow[] = 'From: '.$aAllocation['allocation_from'];
			$aAllocationRow[] = 'Until: '.$aAllocation['allocation_until'];
			$aAllocationRow[] = 'Provider: '.$aAllocation['provider_name'].' ('.$aAllocation['provider_id'].')';
			$aAllocationRow[] = 'Room: '.$aAllocation['room_name'].' ('.$aAllocation['room_id'].')';
			$aAllocationRow[] = 'Status: '.$sStatus.' ('.$aAllocation['allocation_active'].'/'.$aAllocation['allocation_status'].')';

			$aRows[] = join('; ', $aAllocationRow);
		}

		return $aRows;
	}
	
	protected function _getAllocations(Ext_TS_Inquiry $oInquiry, $iJourneyAccommodationId) {

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
				`ts_ij`.`inquiry_id` = :inquiry_id AND
				`ts_ija`.`id` = :journey_accommodation_id
			GROUP BY
				`kaa`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql, array(
			'inquiry_id' => (int)$oInquiry->id,
			'journey_accommodation_id' => (int)$iJourneyAccommodationId
		));

		return $aResult;
	}
	
}